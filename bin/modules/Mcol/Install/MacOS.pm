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

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk);

my $bin = abs_path(dirname(__FILE__) . '/../../../');
my $applicationRoot = abs_path(dirname($bin));
my @systemDependencies = qw(
    intltool autoconf automake expect gcc pcre2 curl libiconv pkg-config
    openssl@3 mysql-client oniguruma libxml2 libxslt icu4c imagemagick mysql
    libsodium libzip glib webp go cpanminus redis python@3.12
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
            print "✓ $pkg already installed, skipping.\n";
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
        print "✓ Pip already installed: $pipStatus";
        return;
    }

    # Try ensurepip first (works with Homebrew python too), fall back to get-pip.py
    print "Installing pip via ensurepip…\n";
    system('python3','-m','ensurepip','--upgrade');
    if ($? != 0) {
        print "ensurepip failed; falling back to get-pip.py…\n";
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
        print "✓ Supervisor already installed (via pip), skipping.\n";
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
        my $dst = "$dir/bin/$exe";
        if (-x $src && !-e $dst) { symlink $src, $dst or warn "symlink $src -> $dst failed: $!"; }
    }
}

# installs Bazelisk.
sub install_bazelisk {
    my ($dir) = @_;
    print "\n=================================================================\n";
    print " Installing Bazelisk…\n";
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
