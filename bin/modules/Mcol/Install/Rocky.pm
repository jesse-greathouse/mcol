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
  autoconf automake libtool m4 re2c
  perl-App-cpanminus
  golang bash
  mariadb-connector-c-devel
  supervisor
  libsodium libsodium-devel
  redis valkey
);

# These typically come from EPEL on EL systems
my @epel_deps = qw(
  supervisor
  libsodium libsodium-devel
  re2c
);

my @imagemagick_runtime         = qw(ImageMagick);
my @imagemagick_candidates      = qw(ImageMagick-devel);
my @imagemagick_cpp_candidates  = qw(ImageMagick-c++-devel);

# Message queue: prefer Valkey (base/AppStream on newer EL),
# fall back to Redis (EPEL) if Valkey isn’t available.
my @mq_candidates = qw(valkey redis);

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
    # EPEL provides supervisor, libsodium*, re2c, ImageMagick*
    system('sudo', $dnf, 'install', '-y', 'epel-release');

    # CRB: use helper if present (as the epel-release script suggests)
    if (-x '/usr/bin/crb') {
        system('sudo', '/usr/bin/crb', 'enable');
    } else {
        # fallback if crb helper is missing
        system('sudo', $dnf, 'config-manager', '--set-enabled', 'crb');
    }
}

sub _pkg_installed {
    my ($name) = @_;
    return system("rpm -q $name >/dev/null 2>&1") == 0;
}

sub _pkg_available {
    my ($dnf, $name) = @_;
    my $cmd = "$dnf list --available $name >/dev/null 2>&1";
    return system('bash','-lc',$cmd) == 0;
}

sub _first_available {
    my ($dnf, @candidates) = @_;
    for my $p (@candidates) { return $p if _pkg_installed($p) }
    for my $p (@candidates) { return $p if _pkg_available($dnf, $p) }
    return undef;
}

sub _ensure_authbind_passthrough {
    # If authbind exists, do nothing.
    my $have = system('bash','-lc','command -v authbind >/dev/null 2>&1');
    return if $have == 0;

    # Create an inert shim that swallows --deep and execs the command.
    my $shim = <<'SH';
#!/usr/bin/env bash
# Inert authbind shim for systems without authbind.
# Usage: authbind [--deep] <cmd> [args...]
set -euo pipefail
if [[ "${1-}" == "--deep" ]]; then
  shift
fi
exec "$@"
SH

    my $tmp = "/tmp/authbind.shim.$$";
    open my $fh, '>', $tmp or die "open $tmp: $!";
    print {$fh} $shim;
    close $fh;
    chmod 0755, $tmp or die "chmod $tmp: $!";

    # Install to /usr/local/bin so 'authbind' is found without PATH edits.
    # (coreutils 'install' is present on Rocky.)
    system('sudo','install','-m','0755',$tmp,'/usr/local/bin/authbind');
    unlink $tmp;
    print "Installed inert authbind shim at /usr/local/bin/authbind\n";
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

    _enable_repos_rocky($dnf);

    # refresh metadata
    my @updateCmd = ('sudo', $dnf, 'makecache', '--refresh');
    system(@updateCmd);
    command_result($?, $!, "Updated package index...", \@updateCmd);

    # resolve optional candidates to names Rocky actually provides
    my @resolved_optional;
    if (my $mq = _first_available($dnf, @mq_candidates)) {
        push @resolved_optional, $mq;
    } else {
        warn "No Valkey/Redis provider found (tried: @mq_candidates). Skipping MQ package.\n";
    }
    if (my $im = _first_available($dnf, @imagemagick_runtime)) {
        push @resolved_optional, $im;
    } else {
        warn "No ImageMagick runtime found (tried: @imagemagick_runtime).\n";
    }
    if (my $imd = _first_available($dnf, @imagemagick_candidates)) {
        push @resolved_optional, $imd;
    } else {
        warn "No ImageMagick devel found (tried: @imagemagick_candidates).\n";
    }
    if (my $imcpp = _first_available($dnf, @imagemagick_cpp_candidates)) {
        push @resolved_optional, $imcpp;
    } else {
        warn "No ImageMagick C++ devel found (tried: @imagemagick_cpp_candidates).\n";
    }

    # compute final list: only install if available and not already installed
    my @wanted = (@base_deps, @resolved_optional);
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

    # enable whichever MQ we actually installed (service name-safe)
    my @svc_try;
    if (_pkg_installed('valkey')) { push @svc_try, qw(valkey valkey-server); }
    if (_pkg_installed('redis'))  { push @svc_try, qw(redis redis-server);    }
    for my $svc (@svc_try) {
        my $r = system('bash','-lc', "systemctl list-unit-files | grep -q '^$svc\\.service'");
        next if $r != 0;
        system('sudo','systemctl','enable','--now',$svc);
        last if $? == 0;
    }

    # Ensure an authbind-compatible passthrough exists if the real tool is absent
    _ensure_authbind_passthrough();
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
