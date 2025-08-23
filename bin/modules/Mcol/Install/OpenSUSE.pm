#!/usr/bin/env perl
package Mcol::Install::OpenSUSE;

use strict;
use Cwd qw(getcwd abs_path);
use File::Basename qw(dirname);
use File::Path qw(make_path);
use File::Temp qw(tempfile);
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk);

# --- candidate name buckets (resolve to what's actually available) ---
my @pcre_candidates          = qw(pcre2-devel);
my @imagemagick_runtime      = qw(ImageMagick);
my @imagemagick_devel        = qw(ImageMagick-devel);
my @mysql_dev_candidates     = qw(libmariadb-devel mariadb-connector-c-devel libmysqlclient-devel);
my @onig_candidates          = qw(oniguruma-devel libonig-devel onig-devel);
my @sodium_candidates        = qw(libsodium-devel);
my @supervisor_candidates    = qw(python3-supervisor supervisor);
my @redis_candidates         = qw(redis);

# always-try base deps (these names are correct on openSUSE)
my @base_deps = qw(
  gcc gcc-c++ make curl pkg-config
  libopenssl-devel ncurses-devel libcurl-devel
  libxml2-devel libxslt-devel libicu-devel glib2-devel
  libwebp-devel libpng16-devel libjpeg-turbo libjpeg-turbo-devel libjpeg62-devel bzip2-devel
  libzip-devel autoconf automake libtool m4
  perl-App-cpanminus expect go bash
);

# ---------------- helpers ----------------
sub _pkg_installed {
    my ($name) = @_;
    return system("rpm -q $name >/dev/null 2>&1") == 0;
}

sub _pkg_available {
    my ($name) = @_;
    # exact match search
    return system("zypper -q se -x -s --match-exact $name >/dev/null 2>&1") == 0;
}

sub _first_available {
    my (@candidates) = @_;
    for my $p (@candidates) {
        return $p if _pkg_available($p);
    }
    return undef;
}

sub _prepare_build_env {
    my $cflags_in = $ENV{CFLAGS} // '';
    my $has_opt   = ($cflags_in =~ /(^|\s)-O[0-3]\b/);
    my @cflags    = ('-std=gnu99');
    push @cflags, ('-O2','-g') unless $has_opt;
    push @cflags, $cflags_in if length $cflags_in;
    $ENV{CFLAGS} = join(' ', @cflags);

    my $cxx_in = $ENV{CXXFLAGS} // '';
    my $has_o  = ($cxx_in =~ /(^|\s)-O[0-3]\b/);
    my @cxx    = ();
    push @cxx, ('-O2','-g') unless $has_o;
    push @cxx, $cxx_in if length $cxx_in;
    $ENV{CXXFLAGS} = join(' ', @cxx) if @cxx;

    my @incs = grep { -d $_ } ('/usr/local/include');
    my @libs = grep { -d $_ } ('/usr/local/lib64','/usr/local/lib');
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

    # ensure ldconfig is on PATH
    my $check = system('command -v ldconfig >/dev/null 2>&1');
    if ($check != 0) {
        system('sudo', 'ln', '-sf', '/sbin/ldconfig', '/usr/local/bin/ldconfig');
        print "✓ Linked /sbin/ldconfig into /usr/local/bin so it's in PATH.\n";
    }

    print "Build env primed (CFLAGS='$ENV{CFLAGS}').\n";
}

sub _ensure_passthrough_authbind {
    # If a real authbind exists anywhere common, bail out.
    for my $p (split(/:/, $ENV{PATH} // ''), qw(/usr/sbin /sbin)) {
        return if -x "$p/authbind";
    }

    # Decide target: prefer system-wide if /usr/local/bin exists; else ~/.local/bin
    my $system_dir = '/usr/local/bin';
    my $home_dir   = ($ENV{HOME} // '.') . '/.local/bin';

    my ($target_dir, $dest, $use_sudo);
    if (-d $system_dir) {
        ($target_dir, $dest, $use_sudo) = ($system_dir, "$system_dir/authbind", 1);
    } else {
        ($target_dir, $dest, $use_sudo) = ($home_dir,   "$home_dir/authbind",   0);
    }

    # Ensure target dir exists (sudo for system dir; mkpath for user dir)
    if (!-d $target_dir) {
        if ($use_sudo) {
            system('sudo', 'mkdir', '-p', $target_dir) == 0
              or warn "Could not create $target_dir with sudo\n";
        } else {
            make_path($target_dir);
        }
    }

    # Always create the temp file in a writable tmp dir, not in the target dir
    my ($fh, $tmp) = tempfile('authbindXXXX',
                              DIR    => ($ENV{TMPDIR} // '/tmp'),
                              UNLINK => 0);
    print $fh <<"SH";
#!/usr/bin/env bash
# inert authbind shim: ignore --deep and exec the program unchanged
args=()
for a in "\$@"; do
  if [[ "\$a" == "--deep" ]]; then
    continue
  else
    args+=( "\$a" )
  fi
done
exec "\${args[@]}"
SH
    close $fh or warn "Close failed for shim: $!";
    chmod 0755, $tmp;

    # Install into place (sudo for system dir)
    my $ok;
    if ($use_sudo) {
        # 'install' sets mode atomically and avoids partial writes
        $ok = (system('sudo', 'install', '-m', '0755', $tmp, $dest) == 0);
    } else {
        $ok = rename($tmp, $dest);
        if (!$ok) {
            # cross-filesystem or other issue: fall back to copy+chmod
            require File::Copy;
            $ok = File::Copy::copy($tmp, $dest) && chmod 0755, $dest;
            unlink $tmp;
        } else {
            # ensure mode if rename preserved odd perms
            chmod 0755, $dest;
            unlink $tmp;
        }
    }

    if ($ok) {
        print "Installed inert authbind shim at $dest\n";
        # Make it usable immediately if we put it in ~/.local/bin but PATH lacks it
        if (!$use_sudo && (':' . ($ENV{PATH} // '') . ':') !~ /:\Q$target_dir\E:/) {
            $ENV{PATH} = "$target_dir:" . ($ENV{PATH} // '');
            print "Added $target_dir to PATH for this process.\n";
        }
    } else {
        warn "Failed to install authbind shim to $dest\n";
    }
}

# --------------- main installer ---------------
sub install_system_dependencies {
    my $username = getpwuid($<);
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privileges.\n";

    # Refresh repos
    my @refreshCmd = ('sudo','zypper','refresh');
    system(@refreshCmd);
    command_result($?, $!, "Refreshed package repositories...", \@refreshCmd);

    # Resolve candidates
    my @resolved_optional;

    if (my $pcre = _first_available(@pcre_candidates))           { push @resolved_optional, $pcre }
    else { warn "No PCRE2 devel found (tried: @pcre_candidates)\n" }

    if (my $im = _first_available(@imagemagick_runtime))         { push @resolved_optional, $im }
    else { warn "No ImageMagick runtime found (tried: @imagemagick_runtime)\n" }

    if (my $imd = _first_available(@imagemagick_devel))          { push @resolved_optional, $imd }
    else { warn "No ImageMagick devel found (tried: @imagemagick_devel)\n" }

    if (my $sql = _first_available(@mysql_dev_candidates))       { push @resolved_optional, $sql }
    else { warn "No MariaDB/MySQL devel headers found (tried: @mysql_dev_candidates)\n" }

    if (my $onig = _first_available(@onig_candidates))           { push @resolved_optional, $onig }
    else { warn "No Oniguruma/onig devel found (tried: @onig_candidates)\n" }

    if (my $sod = _first_available(@sodium_candidates))          { push @resolved_optional, $sod }
    else { warn "No libsodium-devel found (tried: @sodium_candidates)\n" }

    if (my $sup = _first_available(@supervisor_candidates))      { push @resolved_optional, $sup }
    else { warn "No supervisor package found (tried: @supervisor_candidates)\n" }

    if (my $redis = _first_available(@redis_candidates))         { push @resolved_optional, $redis }
    else { warn "No redis package found (tried: @redis_candidates)\n" }

    my @wanted = (@base_deps, @resolved_optional);

    my @to_install;
    for my $pkg (@wanted) {
        next if _pkg_installed($pkg);
        next unless _pkg_available($pkg);
        push @to_install, $pkg;
    }

    if (@to_install) {
        my @installCmd = ('sudo','zypper','--non-interactive','install',@to_install);
        system(@installCmd);
        command_result($?, $!, "Installed missing dependencies...", \@installCmd);
    } else {
        print "All system dependencies already installed or unavailable.\n";
    }

    _prepare_build_env();
    _ensure_passthrough_authbind();

    # enable redis if present
    my $has_unit = system('bash','-lc', "systemctl list-unit-files | grep -q '^redis\\.service'") == 0;
    if ($has_unit && _pkg_installed('redis')) {
        system('sudo','systemctl','enable','--now','redis');
    }
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

    system('bash','-c',"tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");
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

    system('make','install');
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

    system('bash','-c',"tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/");
    command_result($?, $!, 'Unpack Bazelisk...', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/");

    system('bash','-c',"mv $dir/opt/bazelisk-*/ $bazeliskDir");
    command_result($?, $!, 'Renaming Bazelisk Dir...', "mv $dir/opt/bazelisk-*/ $bazeliskDir");

    chdir glob($bazeliskDir);

    print "\n=================================================================\n";
    print " Installing Bazelisk....\n";
    print "=================================================================\n\n";

    system('bash','-c','go install github.com/bazelbuild/bazelisk@latest');
    command_result($?, $!, 'Install Bazelisk...', 'go install github.com/bazelbuild/bazelisk@latest');

    system('bash','-c',"GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");
    command_result($?, $!, 'Build Bazelisk...', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");

    system('bash','-c',"$dir/bin/bazel version");
    command_result($?, $!, 'Run Bazelisk...', "$dir/bin/bazel version");

    chdir $originalDir;
}

1;
