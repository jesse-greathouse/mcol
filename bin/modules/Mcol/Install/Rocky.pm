package Mcol::Install::Rocky;
use strict;
use warnings;
use Cwd qw(getcwd abs_path);
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk);

# ---- Base/devel you actually need on Rocky (names adjusted) ----
my @base_deps = qw(
  gcc gcc-c++ make curl pkgconf pkgconf-pkg-config
  openssl-devel ncurses-devel pcre2-devel libcurl-devel
  libxml2-devel libxslt-devel libicu-devel glib2-devel
  libwebp-devel libpng-devel libjpeg-turbo-devel bzip2-devel
  libzip-devel oniguruma-devel
  autoconf automake libtool m4
  perl-App-cpanminus
  golang bash
  mariadb-connector-c-devel
);

# These typically come from EPEL on EL systems
my @epel_deps = qw(
  supervisor
  libsodium libsodium-devel
  re2c
);

# Some packages changed names between Fedora and Rocky
# We'll pick the first available from each candidate set below.
my @imagemagick_candidates      = qw(imagemagick-devel ImageMagick-devel);
my @imagemagick_cpp_candidates  = qw(imagemagick-c++-devel ImageMagick-c++-devel);
my @imagemagick_runtime         = qw(imagemagick ImageMagick);

# Message queue: prefer Valkey (base/AppStream on newer EL),
# fall back to Redis (EPEL) if Valkey isn’t available.
my @mq_candidates = qw(valkey valkey-compat-redis redis);

# ---------------- helpers ----------------
sub _has_cmd {
    my ($cmd) = @_;
    for my $d (split /:/, ($ENV{PATH} // '')) {
        return 1 if -x "$d/$cmd";
    }
    return 0;
}

sub _enable_repos_rocky {
    my ($dnf) = @_;
    # Enable CRB (EL9/EL10) – ignore failures if not present
    system('sudo', $dnf, 'config-manager', '--set-enabled', 'crb');
    # Enable EPEL for extra tooling (supervisor, libsodium, re2c, etc.)
    system('sudo', $dnf, 'install', '-y', 'epel-release');
}

sub _pkg_installed {
    my ($name) = @_;
    return system("rpm -q $name >/dev/null 2>&1") == 0;
}

sub _pkg_available {
    my ($dnf, $name) = @_;
    # dnf and dnf5 both accept `list`; quiet the output
    return system($dnf, 'list', $name, '>/dev/null', '2>&1') == 0;
}

sub _first_available {
    my ($dnf, @candidates) = @_;
    # Already installed wins immediately
    for my $p (@candidates) {
        return $p if _pkg_installed($p);
    }
    # Otherwise pick the first resolvable in repos
    for my $p (@candidates) {
        return $p if _pkg_available($dnf, $p);
    }
    return undef;
}

# Prime env for builds (unchanged from your good version)
sub _prepare_build_env {
    my $cflags_in  = $ENV{CFLAGS} // '';
    my $has_opt    = ($cflags_in =~ /(^|\s)-O[0-3]\b/);
    my @cflags     = ('-std=gnu99');
    push @cflags, ('-O2', '-g') unless $has_opt;
    push @cflags, $cflags_in if length $cflags_in;
    $ENV{CFLAGS} = join(' ', @cflags);

    my $cxx_in  = $ENV{CXXFLAGS} // '';
    my $has_optc = ($cxx_in =~ /(^|\s)-O[0-3]\b/);
    my @cxx = ();
    push @cxx, ('-O2', '-g') unless $has_optc;
    push @cxx, $cxx_in if length $cxx_in;
    $ENV{CXXFLAGS} = join(' ', @cxx) if @cxx;

    my @incs = grep { -d $_ } ('/usr/local/include');
    my @libs = grep { -d $_ } ('/usr/local/lib64', '/usr/local/lib');
    my $cpp  = join(' ', map { "-I$_" } @incs);
    my $ld   = join(' ', map { "-L$_" } @libs);
    $ENV{CPPFLAGS} = join(' ', grep { length } ($cpp, $ENV{CPPFLAGS} // ''));
    $ENV{LDFLAGS}  = join(' ', grep { length } ($ld,  $ENV{LDFLAGS}  // ''));

    my @pc = grep { defined && length } (
        (-d '/usr/local/lib64/pkgconfig' ? '/usr/local/lib64/pkgconfig' : ()),
        (-d '/usr/local/lib/pkgconfig'   ? '/usr/local/lib/pkgconfig'   : ()),
        $ENV{PKG_CONFIG_PATH}
    );
    $ENV{PKG_CONFIG_PATH} = join(':', @pc);

    my @path = ('/usr/local/bin','/usr/local/sbin', split(/:/, ($ENV{PATH}//'')));
    my %seen; @path = grep { !$seen{$_}++ } @path;
    $ENV{PATH} = join(':', @path);

    $ENV{PERL_CPANM_OPT} //= '--notest --quiet --no-man-pages --skip-satisfied';

    my $kerl = $ENV{KERL_CONFIGURE_OPTIONS} // '';
    $kerl .= ' --without-wx' if $ENV{MCOL_WITHOUT_WX};
    $kerl = join(' ', grep { length } ($kerl, "CFLAGS=$ENV{CFLAGS}")) if $kerl !~ /\bCFLAGS=/;
    $ENV{KERL_CONFIGURE_OPTIONS} = $kerl;

    $ENV{MAKEFLAGS} //= '-j' . (eval { require POSIX; POSIX::sysconf(POSIX::_SC_NPROCESSORS_ONLN()) } || 2);

    print "Build env primed (CFLAGS='$ENV{CFLAGS}').\n";
}

sub install_system_dependencies {
    my $dnf = _has_cmd('dnf5') ? 'dnf5'
            : _has_cmd('dnf')  ? 'dnf'
            : die "No dnf/dnf5 found in PATH\n";

    my $username = getpwuid($<);
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privileges.\n";

    # Enable CRB + EPEL; ignore failures (already enabled or not present)
    _enable_repos_rocky($dnf);

    # Refresh metadata
    my @updateCmd = ('sudo', $dnf, 'makecache', '--refresh');
    system(@updateCmd);
    command_result($?, $!, "Updated package index...", \@updateCmd);

    # Resolve candidate sets against what Rocky actually offers
    my @resolved_optional;
    if (my $mq = _first_available($dnf, @mq_candidates)) {
        push @resolved_optional, $mq;
    } else {
        warn "No Valkey/Redis provider found (tried: @mq_candidates). Skipping MQ package.\n";
    }

    if (my $im = _first_available($dnf, @imagemagick_runtime)) {
        push @resolved_optional, $im;
    } else {
        warn "No ImageMagick runtime found (tried: @imagemagick_runtime).";
    }
    if (my $imd = _first_available($dnf, @imagemagick_candidates)) {
        push @resolved_optional, $imd;
    } else {
        warn "No ImageMagick devel found (tried: @imagemagick_candidates).";
    }
    if (my $imcpp = _first_available($dnf, @imagemagick_cpp_candidates)) {
        push @resolved_optional, $imcpp;
    } else {
        warn "No ImageMagick C++ devel found (tried: @imagemagick_cpp_candidates).";
    }

    # Build final install list:
    my @wanted = (@base_deps, @epel_deps, @resolved_optional);

    # Filter: remove already-installed, and only keep packages that are in repos
    my @to_install;
    for my $pkg (@wanted) {
        next if _pkg_installed($pkg);
        next unless _pkg_available($dnf, $pkg);
        push @to_install, $pkg;
    }

    if (@to_install) {
        my @installCmd = ('sudo', $dnf, 'install', '-y', @to_install);
        system(@installCmd);
        command_result($?, $!, "Installed missing dependencies...", \@installCmd);
    } else {
        print "All system dependencies already installed or unavailable.\n";
    }

    _prepare_build_env();

    # If Valkey installed, you can enable it. Service name differs by distro,
    # so try both and ignore failures.
    system('sudo','systemctl','enable','--now','valkey');
    system('sudo','systemctl','enable','--now','redis');  # if EPEL Redis exists
}

sub install_php {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();

    my @configurePhp = (
        './configure',
        "--prefix=$dir/opt/php",
        "--sysconfdir=$dir/etc",
        "--with-config-file-path=$dir/etc/php",
        "--with-config-file-scan-dir=$dir/etc/php/conf.d",
        '--enable-opcache', '--enable-fpm', '--enable-dom', '--enable-exif',
        '--enable-fileinfo', '--enable-mbstring', '--enable-bcmath',
        '--enable-intl', '--enable-ftp', '--enable-pcntl', '--enable-gd',
        '--enable-soap', '--enable-sockets', '--without-sqlite3',
        '--without-pdo-sqlite', '--with-libxml', '--with-xsl', '--with-zlib',
        '--with-curl', '--with-webp', '--with-openssl', '--with-zip', '--with-bz2',
        '--with-sodium', '--with-mysqli', '--with-pdo-mysql', '--with-mysql-sock',
        '--with-iconv',
    );

    my $originalDir = getcwd();

    system('bash', '-c', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");
    command_result($?, $!, 'Unpacked PHP Archive...', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");

    chdir glob("$dir/opt/php-*/");

    system(@configurePhp);
    command_result($?, $!, 'Configured PHP...', \@configurePhp);

    print "\n=================================================================\n";
    print " Compiling PHP...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'Made PHP...', 'make');

    system('make install');
    command_result($?, $!, 'Installed PHP...', 'make install');

    chdir $originalDir;
}

sub install_bazelisk {
    my ($dir) = @_;
    my $originalDir = getcwd();
    my $bazeliskDir = "$dir/opt/bazelisk/";

    if (-d $bazeliskDir) {
        print "Bazel dependency already exists, skipping...(`rm -rf $bazeliskDir` to rebuild)\n";
        return;
    }

    system(('bash', '-c', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/"));
    command_result($?, $!, 'Unpack Bazelisk...', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/");

    system(('bash', '-c', "mv $dir/opt/bazelisk-*/ $bazeliskDir"));
    command_result($?, $!, 'Renaming Bazelisk Dir...', "mv $dir/opt/bazelisk-*/ $bazeliskDir");

    chdir glob($bazeliskDir);

    print "\n=================================================================\n";
    print " Installing Bazelisk....\n";
    print "=================================================================\n\n";

    system('bash', '-c', 'go install github.com/bazelbuild/bazelisk@latest');
    command_result($?, $!, 'Install Bazelisk...', 'go install github.com/bazelbuild/bazelisk@latest');

    system('bash', '-c', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");
    command_result($?, $!, 'Build Bazelisk...', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");

    system('bash', '-c', "$dir/bin/bazel version");
    command_result($?, $!, 'Run Bazelisk...', "$dir/bin/bazel version");

    chdir $originalDir;
}

1;
