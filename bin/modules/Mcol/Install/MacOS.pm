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
    my @keg = qw(openssl@3 icu4c libxml2 libzip oniguruma libxslt libiconv);
    my @pc  = map { _brew_prefix($_).'/lib/pkgconfig' } @keg;
    my @inc = map { _brew_prefix($_).'/include'       } @keg;
    my @lib = map { _brew_prefix($_).'/lib'           } @keg;
    $ENV{PKG_CONFIG_PATH} = join(':', (grep {-d} @pc), split(':', $ENV{PKG_CONFIG_PATH} // ''));
    $ENV{CPPFLAGS} = join(' ', (map { "-I$_" } grep {-d} @inc), split(' ', $ENV{CPPFLAGS} // ''));
    $ENV{LDFLAGS}  = join(' ', (map { "-L$_" } grep {-d} @lib), split(' ', $ENV{LDFLAGS}  // ''));
}

# Prefer a wx build that is compat with OTP-25 (wx 3.2 has compat30 by default)
sub _prefer_wx_config {
    for my $cand (
        _brew_prefix('wxwidgets@3.2').'/bin/wx-config',      # best choice
        _brew_prefix('wxwidgets').'/bin/wx-config',          # may be 3.3.x (often NOT compat)
        $ENV{WX_CONFIG},                                     # user-provided
    ) {
        return $cand if defined $cand && -x $cand;
    }
    return undef;
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
    my @keg = qw(openssl@3 icu4c libxml2 libxslt libzip oniguruma libiconv);
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

sub _prepare_build_env_macos_for_erlang {
    # Toolchain
    $ENV{CC} ||= 'clang';
    $ENV{CFLAGS} = join(' ', grep { length } (
        $ENV{CFLAGS} // '',
        '-std=gnu99','-O2','-g',
        '-fno-builtin',
        '-Werror=implicit-function-declaration',
        '-Qunused-arguments'
    ));

    # Only wire up OpenSSL via Homebrew (do NOT add -lmd / -lei)
    my $ossl = _brew_prefix('openssl@3');
    $ENV{CPPFLAGS} = join(' ', ($ENV{CPPFLAGS}//''), "-I$ossl/include");
    $ENV{LDFLAGS}  = join(' ', ($ENV{LDFLAGS}//''),  "-L$ossl/lib");
    $ENV{PKG_CONFIG_PATH} = join(':', grep {-d} ("$ossl/lib/pkgconfig"), split(':', $ENV{PKG_CONFIG_PATH}//''));

    # Ensure the ei-internal MD5 symbol names are generated as _ei_MD5*
    # (this prevents the undefined _ei_MD5* at link time)
    my @md5_defines = (
        '-DMD5_INIT_FUNCTION_NAME=ei_MD5Init',
        '-DMD5_UPDATE_FUNCTION_NAME=ei_MD5Update',
        '-DMD5_FINAL_FUNCTION_NAME=ei_MD5Final',
        '-DMD5_TRANSFORM_FUNCTION_NAME=ei_MD5Transform',
    );
    $ENV{CPPFLAGS} = join(' ', $ENV{CPPFLAGS}//'', @md5_defines);

    # Autoconf probe workaround
    $ENV{ac_cv_c_undeclared_builtin_options} //= 'none needed';

    # SDK (helps on CLT-only)
    chomp(my $sdk = `xcrun --sdk macosx --show-sdk-path 2>/dev/null`);
    $ENV{SDKROOT} = $sdk if $sdk;

    # Scrub inherited bad libs
    for my $k (qw(LIBS LDFLAGS)) {
        next unless defined $ENV{$k};
        $ENV{$k} =~ s/\s*-lmd\s*/ /g;
        $ENV{$k} =~ s/\s*-lei\s*/ /g;
        $ENV{$k} =~ s/\s+/ /g;
        $ENV{$k} =~ s/^\s+|\s+$//g;
    }

    # Prefer a wx that works (wx 3.2) if present
    if (my $wx_cfg = _prefer_wx_config()) {
        $ENV{WX_CONFIG} = $wx_cfg;
        my ($wx_bin) = $wx_cfg =~ m{^(.+)/wx-config$};
        $ENV{PATH} = join(':', ($wx_bin||()), $ENV{PATH}//'');
    }
}

# In Mcol::Install::MacOS
sub build_erlang_otp_on_macos {
    my ($dir) = @_;

    my $threads         = eval { require POSIX; POSIX::sysconf(POSIX::_SC_NPROCESSORS_ONLN()) } || 2;
    my $erlangSrcDir    = $ENV{MCOL_ERLANG_SRC} // "$dir/opt/erlang-src";
    my $erlangPrefixDir = "$dir/opt/erlang";
    my $erlangRef       = 'OTP-25.3.2.21';
    my $originalDir     = getcwd();

    return if -d $erlangPrefixDir;

    unless (defined $ENV{MCOL_ERLANG_SRC}) {
        my $need_clone = (!-d $erlangSrcDir) || !-x "$erlangSrcDir/configure";
        if ($need_clone) {
            system("rm -rf '$erlangSrcDir'") if -d $erlangSrcDir;
            system("git clone --depth 1 --branch $erlangRef https://github.com/erlang/otp.git '$erlangSrcDir'");
            command_result($?, $!, 'Clone erlang ...',
                "git clone --depth 1 --branch $erlangRef https://github.com/erlang/otp.git $erlangSrcDir");
        }
    } else {
        die "No configure in $erlangSrcDir\n" unless -x "$erlangSrcDir/configure";
    }

    local $ENV{ERL_TOP} = $erlangSrcDir;
    _prepare_build_env_macos_for_erlang();

    my $ossl_prefix   = _brew_prefix('openssl@3');
    my $configure_cmd = "./configure --prefix='$erlangPrefixDir' --with-ssl='$ossl_prefix'";

    chdir $erlangSrcDir or die "chdir $erlangSrcDir: $!";
    system('bash','-lc', $configure_cmd);
    command_result($?, $!, 'Configure Erlang/OTP...', $configure_cmd);

    # 1) Build erl_interface first (no manual -lei!)
    system('bash','-lc', "make -C lib/erl_interface clean && make -C lib/erl_interface -j1");
    command_result($?, $!, "Build erl_interface (serialized)...", ['make','-C','lib/erl_interface','-j1']);

    # 2) Add its libdir (correct path: .../obj/$triplet, no trailing /lib)
    my $triplet = `bash -lc '$erlangSrcDir/erts/autoconf/config.guess'`; chomp $triplet;
    my $ei_libdir = "$erlangSrcDir/lib/erl_interface/obj/$triplet";
    if (-d $ei_libdir) {
        $ENV{LDFLAGS} = join(' ', "-L$ei_libdir", ($ENV{LDFLAGS}//''));
        print "Added erl_interface libdir to LDFLAGS: $ei_libdir\n";
    }

    # 3) Build erts serialized, then all
    system('bash','-lc', "make -C erts clean && make -C erts -j1");
    command_result($?, $!, "Build erts (serialized)...", ['make','-C','erts','-j1']);

    system('bash','-lc', "make -j$threads");
    command_result($?, $!, "Build Erlang/OTP...", ["make","-j$threads"]);

    system('bash','-lc', "make install");
    command_result($?, $!, "Install Erlang/OTP...", ["make","install"]);

    # Bazel: ensure releases/<major>/OTP_VERSION exists
    my $otp_version_file = "$erlangPrefixDir/OTP_VERSION";
    if (-f $otp_version_file) {
        my $otp_release = `cat '$otp_version_file'`; chomp($otp_release);
        my ($otp_major) = $otp_release =~ /^(\d+)/;
        my $release_dir = "$erlangPrefixDir/releases/$otp_major";
        require File::Path; File::Path::make_path($release_dir) unless -d $release_dir;
        my $target_file = "$release_dir/OTP_VERSION";
        require File::Copy; File::Copy::copy($otp_version_file, $target_file) unless -e $target_file;
        print "Copied OTP_VERSION to: $target_file\n";
    } else {
        warn "OTP_VERSION not found at $otp_version_file; skipping Bazel compatibility fix.\n";
    }

    # Platform-arch symlink using Darwin triplet
    if ($triplet) {
        my $platform_bin_dir = "$erlangPrefixDir/bin/$triplet";
        my $platform_erl     = "$platform_bin_dir/erl";
        require File::Path; File::Path::make_path($platform_bin_dir) unless -d $platform_bin_dir;
        unless (-e $platform_erl) {
            symlink("../erl", $platform_erl)
                or warn "Failed platform symlink: $platform_erl -> ../erl ($!)\n";
            print "Created platform symlink: $platform_erl -> ../erl\n";
        }
    } else {
        warn "Could not determine Darwin target triplet; skipping platform symlink.\n";
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
        '--with-curl', '--with-webp', '--with-openssl', '--with-zip', '--with-bz2',
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

# Installs Supervisor using pip, if not already installed.
sub install_supervisor {
    my $check = system('python3', '-m', 'pip', 'show', 'supervisor') == 0;

    if ($check) {
        print "Supervisor already installed (via pip), skipping.\n";
        return;
    }

    print "Installing Supervisor via pip...\n";
    system('python3', '-m', 'pip', 'install', '--user', 'supervisor');
    command_result($?, $!, "Installed Supervisor...", 'python3 -m pip install --user supervisor');

    # Ensure binaries are reachable via your app bin
    chomp(my $user_base = `python3 -m site --user-base`);
    my $user_bin = "$user_base/bin";
    for my $exe (qw(supervisord supervisorctl)) {
        my $src = "$user_bin/$exe";
        my $dst = "$applicationRoot/bin/$exe";
        if (-x $src && !-e $dst) { symlink $src, $dst or warn "symlink $src -> $dst failed: $!"; }
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
