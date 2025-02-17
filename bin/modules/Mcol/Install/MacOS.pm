#!/usr/bin/perl

package Mcol::Install::MacOS;
use strict;
use Cwd qw(getcwd abs_path);
use Env;
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php);

my $bin = abs_path(dirname(__FILE__) . '/../../../');
my $applicationRoot = abs_path(dirname($bin));
my @systemDependencies = qw(
    intltool autoconf automake expect gcc pcre2 curl libiconv pkg-config
    openssl@3.0 mysql-client oniguruma libxml2 icu4c imagemagick mysql
    libsodium libzip glib webp
);

# ====================================
# Subroutines
# ====================================

# Installs OS level system dependencies.
sub install_system_dependencies {
    print "Brew is required for updating and installing system dependencies.\n";

    system('brew upgrade');
    command_result($?, $!, "Updated system dependencies...");

    # Install all dependencies in one command to minimize the system calls.
    system('brew install', @systemDependencies);
    command_result($?, $!, "Installed system dependencies...", 'brew install', @systemDependencies);

    install_pip();
    install_supervisor();
}

# Installs PHP.
sub install_php {
    my ($dir) = @_;

    $ENV{'PKG_CONFIG_PATH'} = '/usr/local/opt/icu4c/lib/pkgconfig';

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
        '--with-sodium', '--with-mysqli', '--with-pdo-mysql', '--with-mysql-sock',
        '--with-iconv=/usr/local/opt/libiconv'
    );

    my $originalDir = getcwd();

    # Unpack PHP Archive
    system('bash', '-c', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");
    command_result($?, $!, 'Unpacked PHP Archive...', 'tar -xf ' . $dir . '/opt/php-*.tar.gz -C ' . $dir . '/opt/');

    chdir glob("$dir/opt/php-*/");

    # Configure PHP
    system(@configurePhp);
    command_result($?, $!, 'Configured PHP...', \@configurePhp);

    # Make and Install PHP
    system('make');
    command_result($?, $!, 'Made PHP...', 'make');

    system('make install');
    command_result($?, $!, 'Installed PHP...', 'make install');

    chdir $originalDir;
}

# Installs Pip if not already installed.
sub install_pip {
    my $pipStatus = `python3 -m pip --version`;
    return if $pipStatus =~ /^pip/;  # No need to install if pip is already present

    my $pipInstallScript = 'get-pip.py';
    system("curl https://bootstrap.pypa.io/$pipInstallScript -o $pipInstallScript");
    command_result($?, $!, "Downloaded Pip...");

    system("chmod +x $pipInstallScript");
    command_result($?, $!, "Gave Pip Installer Execute Permission...");

    system("python3 $pipInstallScript");
    command_result($?, $!, "Installed Pip...");

    unlink($pipInstallScript);
}

# Installs Supervisor using pip.
sub install_supervisor {
    system('python3', '-m', 'pip', 'install', 'supervisor');
    command_result($?, $!, "Installed Supervisor...", 'python3', '-m', 'pip', 'install', 'supervisor');
}

1;
