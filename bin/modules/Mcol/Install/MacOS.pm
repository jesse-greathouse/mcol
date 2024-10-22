#!/usr/bin/perl

package Mcol::Install::MacOS;
use strict;
use Cwd qw(getcwd abs_path);
use Env;
use File::Basename;
use File::Path;
use lib(dirname(abs_path(__FILE__))  . "/modules");
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_system_dependencies install_php);

my $bin = abs_path(dirname(__FILE__) . '/../../../');
my $applicationRoot = abs_path(dirname($bin));
my @systemDependencies = (
    'intltool',
    'autoconf',
    'automake',
    'expect',
    'gcc',
    'pcre2',
    'curl',
    'libiconv',
    'pkg-config',
    'openssl@3.0',
    'mysql-client',
    'oniguruma',
    'libxml2',
    'icu4c',
    'imagemagick',
    'mysql',
    'libsodium',
    'libzip',
    'glib',
    'webp',
);

1;

# ====================================
#    Subroutines below this point
# ====================================

# installs OS level system dependencies.
sub install_system_dependencies {
    print "Brew is required for updating and installing system dependencies.\n";

    system('brew upgrade');
    command_result($?, $!, "Updated system dependencies...");

    my @cmd = ('brew');
    push @cmd, 'install';
    foreach my $dependency (@systemDependencies) {
        push @cmd, $dependency;
    }
    system(@cmd);
    command_result($?, $!, "Installed system dependencies...", \@cmd);

    install_pip();
    install_supervisor();
}

# installs PHP.
sub install_php {
    my ($dir) = @_;

    $ENV{'PKG_CONFIG_PATH'} = '/usr/local/opt/icu4c/lib/pkgconfig';

    my @configurePhp = ('./configure');
    push @configurePhp, '--prefix=' . $dir . '/opt/php';
    push @configurePhp, '--sysconfdir=' . $dir . '/etc';
    push @configurePhp, '--with-config-file-path=' . $dir . '/etc/php';
    push @configurePhp, '--with-config-file-scan-dir=' . $dir . '/etc/php/conf.d';
    push @configurePhp, '--enable-opcache';
    push @configurePhp, '--enable-fpm';
    push @configurePhp, '--enable-dom';
    push @configurePhp, '--enable-exif';
    push @configurePhp, '--enable-fileinfo';
    push @configurePhp, '--enable-mbstring';
    push @configurePhp, '--enable-bcmath';
    push @configurePhp, '--enable-intl';
    push @configurePhp, '--enable-ftp';
    push @configurePhp, '--enable-pcntl';
    push @configurePhp, '--enable-gd';
    push @configurePhp, '--enable-soap';
    push @configurePhp, '--enable-sockets';
    push @configurePhp, '--without-sqlite3';
    push @configurePhp, '--without-pdo-sqlite';
    push @configurePhp, '--with-libxml';
    push @configurePhp, '--with-xsl';
    push @configurePhp, '--with-zlib';
    push @configurePhp, '--with-curl';
    push @configurePhp, '--with-webp';
    push @configurePhp, '--with-openssl';
    push @configurePhp, '--with-zip';
    push @configurePhp, '--with-sodium';
    push @configurePhp, '--with-mysqli';
    push @configurePhp, '--with-pdo-mysql';
    push @configurePhp, '--with-mysql-sock';
    push @configurePhp, '--with-iconv=/usr/local/opt/libiconv';

    my $originalDir = getcwd();

    # Unpack
    system(('bash', '-c', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/"));
    command_result($?, $!, 'Unpack PHP Archive...', 'tar -xf ' . $dir . '/opt/php-*.tar.gz -C ' . $dir . ' /opt/');

    chdir glob("$dir/opt/php-*/");

    # configure
    system(@configurePhp);
    command_result($?, $!, 'Configure PHP...', \@configurePhp);

    # make
    system('make');
    command_result($?, $!, 'Make PHP...', 'make');

    # install
    system('make install');
    command_result($?, $!, 'Install PHP...', 'make install');

    chdir $originalDir;
}

sub install_pip {
    # Check if pip is installed
    my $pipStatus = `python3 -m pip --version`;
    my $pip = substr($pipStatus, 0, 3);

    # If pip is already installed break out
    if ($pip eq 'pip') { return; }

    # Download pip install script
    my $pipInstallScript = 'get-pip.py';
    system("curl https://bootstrap.pypa.io/$pipInstallScript -o $pipInstallScript");
    command_result($?, $!, "Downloaded Pip...");

    # Chmod +x install script
    system("chmod +x $pipInstallScript");
    command_result($?, $!, "Gave Pip Installer Execute Permission...");

    # Run pip install script
    system("python3 $pipInstallScript");
    command_result($?, $!, "Installed Pip...");

    #Remove pip install script
    unlink($pipInstallScript);
}

sub install_supervisor {
    my @installSupervisorCmd = ('python3');
    push @installSupervisorCmd, '-m';
    push @installSupervisorCmd, 'pip';
    push @installSupervisorCmd, 'install';
    push @installSupervisorCmd, 'supervisor';
    system(@installSupervisorCmd);
    command_result($?, $!, "Installed Supervisor...", @installSupervisorCmd);
}
