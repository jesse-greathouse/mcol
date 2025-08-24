#!/usr/bin/env perl

package Mcol::Install::MacOS;
use strict;
use Cwd qw(getcwd abs_path);
use Env;
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';
use POSIX qw(uname);

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk build_erlang_otp_on_macos);

my $bin = abs_path(dirname(__FILE__) . '/../../../');
my $applicationRoot = abs_path(dirname($bin));
my @systemDependencies = qw(
    intltool autoconf automake expect gcc pcre2 curl libiconv pkg-config
    openssl@3 mysql-client oniguruma libxml2 libxslt icu4c imagemagick mysql
    libsodium libzip glib webp go cpanminus redis python@3.12 libmd wxwidgets
    bzip2
);

# ====================================
# Subroutines
# ====================================

sub _brew_prefix {
    my ($formula) = @_;
    my $p = `brew --prefix $formula 2>/dev/null`; chomp $p;
    return $p if $p;
    $p = `brew --prefix 2>/dev/null`; chomp $p;
    return $p || '/usr/local';
}

sub _export_brew_env {
    my @keg = qw(openssl@3 icu4c libxml2 libzip oniguruma libxslt libiconv bzip2);
    my @pc  = map { _brew_prefix($_).'/lib/pkgconfig' } @keg;
    my @inc = map { _brew_prefix($_).'/include'       } @keg;
    my @lib = map { _brew_prefix($_).'/lib'           } @keg;
    $ENV{PKG_CONFIG_PATH} = join(':', (grep {-d} @pc), split(':', $ENV{PKG_CONFIG_PATH} // ''));
    $ENV{CPPFLAGS} = join(' ', (map { "-I$_" } grep {-d} @inc), split(' ', $ENV{CPPFLAGS} // ''));
    $ENV{LDFLAGS}  = join(' ', (map { "-L$_" } grep {-d} @lib), split(' ', $ENV{LDFLAGS}  // ''));
}

sub _prepare_build_env_macos {
    # Toolchain flags
    my $c_in = $ENV{CFLAGS} // '';
    my @cflags = ('-std=gnu99','-O2','-g','-fno-builtin','-Werror=implicit-function-declaration');
    push @cflags, $c_in if length $c_in;
    $ENV{CFLAGS} = join(' ', @cflags);

    my $cxx_in = $ENV{CXXFLAGS} // '';
    my @cxx = ('-O2','-g');
    push @cxx, $cxx_in if length $cxx_in;
    $ENV{CXXFLAGS} = join(' ', @cxx);

    # Homebrew keg paths
    my @keg = qw(openssl@3 icu4c libxml2 libxslt libzip oniguruma libiconv bzip2);
    my @pc  = map { _brew_prefix($_).'/lib/pkgconfig' } @keg;
    my @inc = map { _brew_prefix($_).'/include'       } @keg;
    my @lib = map { _brew_prefix($_).'/lib'           } @keg;

    $ENV{PKG_CONFIG_PATH} = join(':', (grep {-d} @pc), split(':', $ENV{PKG_CONFIG_PATH}//''));
    $ENV{CPPFLAGS}        = join(' ', (map { "-I$_" } grep {-d} @inc), split(' ', $ENV{CPPFLAGS}//''));
    $ENV{LDFLAGS}         = join(' ', (map { "-L$_" } grep {-d} @lib), split(' ', $ENV{LDFLAGS}//''));

    # Autoconf probe workaround
    $ENV{ac_cv_c_undeclared_builtin_options} //= 'none needed';

    # Use clang & align SDK/deployment target
    $ENV{CC} //= 'clang';
    chomp(my $sdk = `xcrun --sdk macosx --show-sdk-path 2>/dev/null`);
    $ENV{SDKROOT} = $sdk if $sdk;
    my $v = `sw_vers -productVersion 2>/dev/null`; chomp $v;
    $ENV{MACOSX_DEPLOYMENT_TARGET} //= (split /\./, $v)[0] || '13';

    # Parallel make
    $ENV{MAKEFLAGS} //= '-j' . (eval { require POSIX; POSIX::sysconf(POSIX::_SC_NPROCESSORS_ONLN()) } || 2);

    # Point kerl at OpenSSL if used elsewhere
    my $ossl = _brew_prefix('openssl@3');
    my $kerl = $ENV{KERL_CONFIGURE_OPTIONS} // '';
    $kerl = join(' ', grep { length } ($kerl, "--with-ssl=$ossl"));
    $ENV{KERL_CONFIGURE_OPTIONS} = $kerl;

    print "macOS build env primed (CC=$ENV{CC}; CFLAGS='$ENV{CFLAGS}').\n";
}

# Helper: make sure a brew formula is installed and return its prefix
# Helper: make sure a brew formula is installed and return its prefix
sub _ensure_brew_formula {
    my ($formula) = @_;

    my $installed = (system("brew list --versions $formula >/dev/null 2>&1") == 0);
    unless ($installed) {
        system('brew', 'install', $formula);
        command_result($?, $!, "Installed $formula...", ['brew', 'install', $formula]);
    } else {
        print "$formula already installed, skipping.\n";
    }

    my $prefix = _brew_prefix($formula);
    die "Could not find Homebrew prefix for $formula\n" unless $prefix && -d $prefix;
    return $prefix;
}

sub build_erlang_otp_on_macos {
    my ($dir) = @_;
    my $originalDir = getcwd();

    # 1) Ensure Homebrew Erlang/OTP 26 is present
    my $erl_prefix = _ensure_brew_formula('erlang@26');
    print "Using Homebrew Erlang/OTP 26 at $erl_prefix\n";

    # 2) Provide a stable path in your tree (keeps existing callers happy)
    my $erlangPrefixDir = "$dir/opt/erlang";
    if (-l $erlangPrefixDir || -e $erlangPrefixDir) {
        my $points_to = readlink($erlangPrefixDir) // '';
        if ($points_to ne $erl_prefix) {
            unlink($erlangPrefixDir) or warn "unlink $erlangPrefixDir failed: $!";
            symlink($erl_prefix, $erlangPrefixDir) or warn "symlink $erl_prefix -> $erlangPrefixDir failed: $!";
        }
    } else {
        require File::Path;
        File::Path::make_path("$dir/opt") unless -d "$dir/opt";
        symlink($erl_prefix, $erlangPrefixDir) or warn "symlink $erl_prefix -> $erlangPrefixDir failed: $!";
    }

    # 3) Put the keg's bin on PATH for subsequent steps
    $ENV{PATH} = join(':', "$erl_prefix/bin", $ENV{PATH} // '');

    # 4) Read OTP version from the Homebrew layout
    my $vf = "$erl_prefix/lib/erlang/OTP_VERSION";
    my $otp_version = '';
    if (-f $vf) {
        if (open my $fh, '<', $vf) {
            local $/ = undef;               # slurp
            my $s = <$fh>;
            close $fh;
            $otp_version = $s if defined $s;
            $otp_version =~ s/\s+\z// if defined $otp_version;
        } else {
            warn "open($vf) failed: $!";
        }
    }
    print "Detected OTP version: " . ($otp_version || 'unknown') . "\n";

    # 5) Create a top-level OTP_VERSION in the keg (handy for Bazel or callers that expect it)
    my $top_otp_file = "$erl_prefix/OTP_VERSION";
    if ($otp_version && !-f $top_otp_file) {
        if (open my $out, '>', $top_otp_file) {
            print {$out} "$otp_version\n";
            close $out;
            print "Wrote top-level OTP_VERSION at $top_otp_file\n";
        } else {
            warn "Could not write $top_otp_file: $!";
        }
    }

    # 6) Create the platform-arch symlink inside the Homebrew keg:
    #    <prefix>/bin/<triplet>/erl  ->  ../erl
    #    Triplet roughly matches GNU config.guess naming.
    my ($sysname, $nodename, $release, $version, $machine) = POSIX::uname();
    my $arch = ($machine eq 'arm64') ? 'aarch64' : $machine;  # map arm64 -> aarch64
    my $triplet = "${arch}-apple-darwin$release";

    my $platform_bin_dir = "$erl_prefix/bin/$triplet";
    my $platform_erl     = "$platform_bin_dir/erl";

    require File::Path;
    File::Path::make_path($platform_bin_dir) unless -d $platform_bin_dir;

    unless (-e $platform_erl) {
        # Relative link keeps things tidy if the keg ever moves
        symlink("../erl", $platform_erl)
            or warn "Failed platform symlink: $platform_erl -> ../erl ($!)\n";
        print "Created platform symlink: $platform_erl -> ../erl\n";
    } else {
        print "Platform symlink already present: $platform_erl\n";
    }

    chdir $originalDir;
}

# Installs OS level system dependencies.
sub install_system_dependencies {
    print "Brew is required for updating and installing system dependencies.\n";

    # Update/Upgrade Homebrew
    system('brew','update');
    command_result($?, $!, "Updated Homebrew...", ['brew','update']);
    system('brew','upgrade');
    command_result($?, $!, "Updated system dependencies...");

    # Filter system dependencies to only install what's missing
    my @to_install;
    foreach my $pkg (@systemDependencies) {
        my $check = system("brew list $pkg > /dev/null 2>&1");
        if ($check != 0) {
            push @to_install, $pkg;
        } else {
            print "$pkg already installed, skipping.\n";
        }
    }

    # Install only what's needed
    if (@to_install) {
        system('brew', 'install', @to_install);
        command_result($?, $!, "Installed missing dependencies...", 'brew install', @to_install);
    } else {
        print "All system dependencies already installed.\n";
    }

    _export_brew_env();
    _prepare_build_env_macos();
    install_pip();
    install_supervisor();
}

# Installs PHP.
sub install_php {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();

    _export_brew_env();

    my $bzip2_prefix = _brew_prefix('bzip2');
    my $iconv_prefix = _brew_prefix('libiconv');

    my @configurePhp = (
        './configure',
        '--prefix=' . $dir . '/opt/php',
        '--sysconfdir=' . $dir . '/etc',
        '--with-config-file-path=' . $dir . '/etc/php',
        '--with-config-file-scan-dir=' . $dir . '/etc/php/conf.d',
        '--enable-opcache', '--enable-fpm', '--enable-dom', '--enable-exif',
        '--enable-fileinfo', '--enable-mbstring', '--enable-bcmath',
        '--enable-intl', '--enable-ftp', '--enable-pcntl', '--enable-gd',
        '--enable-soap', '--enable-sockets', '--without-sqlite3',
        '--without-pdo-sqlite', '--with-libxml', '--with-xsl', '--with-zlib',
        '--with-curl', '--with-webp', '--with-openssl', '--with-zip',
        '--with-bz2=' . $bzip2_prefix,
        '--with-sodium', '--with-mysqli', '--with-pdo-mysql',
        '--with-iconv=' . $iconv_prefix
    );

    my $originalDir = getcwd();

    # Unpack PHP Archive
    system('bash', '-lc', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");
    command_result($?, $!, 'Unpacked PHP Archive...', 'tar -xzf ' . $dir . '/opt/php-*.tar.gz -C ' . $dir . '/opt/');

    my @php_dirs = glob("$dir/opt/php-*/");
    die "PHP source not found under $dir/opt/php-*/\n" unless @php_dirs;
    chdir $php_dirs[0];

    # Configure PHP
    system(@configurePhp);
    command_result($?, $!, 'Configured PHP...', \@configurePhp);

    # Make and Install PHP
    print "\n=================================================================\n";
    print " Compiling PHP...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'Compile PHP...', "make -j$threads");

    system('make install');
    command_result($?, $!, 'Installed PHP...', 'make install');

    chdir $originalDir;
}

# Installs Pip if not already installed.
sub install_pip {
    # Check if pip is already available
    my $pipStatus = `python3 -m pip --version 2>&1`;
    if ($? == 0 && $pipStatus =~ /^pip\s+\d+\./) {
        print "Pip already installed: $pipStatus";
        return;
    }

    # Try ensurepip first (works with Homebrew python too), fall back to get-pip.py
    print "Installing pip via ensurepip\n";
    system('python3','-m','ensurepip','--upgrade');
    if ($? != 0) {
        print "ensurepip failed; falling back to get-pip.py\n";
        my $pipInstallScript = 'get-pip.py';
        system("curl -fsSL https://bootstrap.pypa.io/get-pip.py -o $pipInstallScript");
        command_result($?, $!, "Downloaded pip installer...");
        system("python3 $pipInstallScript");
        command_result($?, $!, "Installed pip...");
        unlink($pipInstallScript);
    }
}

# Installs Supervisor using pip, if not already installed, and ensures shims.
sub install_supervisor {
    my $installed = (system('python3', '-m', 'pip', 'show', 'supervisor') == 0);
    if ($installed) {
        print "Supervisor already installed (via pip); ensuring shims...\n";
    } else {
        print "Installing Supervisor via pip...\n";
        system('python3', '-m', 'pip', 'install', '--user', 'supervisor');
        command_result($?, $!, "Installed Supervisor...", 'python3 -m pip install --user supervisor');
    }

    # Where pip put the scripts for THIS python3
    chomp(my $user_base = `python3 -m site --user-base`);
    my $user_bin = "$user_base/bin";
    print "Supervisor scripts expected under: $user_bin\n";

    # Ensure project-local symlinks (Queue.pm will call these directly)
    require File::Path;
    File::Path::make_path("$applicationRoot/bin") unless -d "$applicationRoot/bin";

    for my $exe (qw(supervisord supervisorctl echo_supervisord_conf pidproxy)) {
        my $src = "$user_bin/$exe";
        my $dst = "$applicationRoot/bin/$exe";

        unless (-x $src) {
            warn "Expected $src not found or not executable; skipping link for $exe\n";
            next;
        }

        if (-l $dst || -e $dst) {
            my $cur = readlink($dst) // '';
            if ($cur ne $src) {
                unlink $dst or warn "unlink $dst failed: $!";
                symlink $src, $dst or warn "symlink $src -> $dst failed: $!";
                print "Updated symlink: $dst -> $src\n";
            }
        } else {
            symlink $src, $dst or warn "symlink $src -> $dst failed: $!";
            print "Created symlink: $dst -> $src\n";
        }
    }

    # Optional: system-wide wrappers if /usr/local/bin is writable
    _ensure_wrapper("/usr/local/bin/supervisord",    "$applicationRoot/bin/supervisord");
    _ensure_wrapper("/usr/local/bin/supervisorctl",  "$applicationRoot/bin/supervisorctl");
}

# Tiny helper to create a passthrough shell wrapper if target doesn’t exist.
sub _ensure_wrapper {
    my ($target, $real) = @_;
    return if -x $target;  # already present

    my $dir;
    if ($target =~ m{^(.*)/[^/]+$}) { $dir = $1 }

    unless ($dir && -d $dir && -w $dir) {
        # Not fatal; just skip if we can’t write there without sudo
        return;
    }

    if (open my $fh, '>', $target) {
        print {$fh} "#!/bin/sh\nexec \"$real\" \"\$@\"\n";
        close $fh;
        chmod 0755, $target or warn "chmod 0755 $target failed: $!\n";
        print "Created wrapper $target -> $real\n";
    } else {
        warn "Failed to write wrapper $target: $!\n";
    }
}

# installs Bazelisk.
sub install_bazelisk {
    my ($dir) = @_;
    print "\n=================================================================\n";
    print " Installing Bazelisk\n";
    print "=================================================================\n\n";
    local $ENV{GOBIN} = "$dir/bin";
    system('go','install','github.com/bazelbuild/bazelisk@latest');
    command_result($?, $!, 'Install Bazelisk...', ['go','install','github.com/bazelbuild/bazelisk@latest']);
    # Provide bazel alias
    if (-x "$dir/bin/bazelisk" && !-e "$dir/bin/bazel") {
        symlink "$dir/bin/bazelisk", "$dir/bin/bazel" or warn "symlink bazelisk->bazel failed: $!";
    }
    system("$dir/bin/bazel","version");
    command_result($?, $!, 'Run Bazelisk...', "$dir/bin/bazel version");
}

1;
