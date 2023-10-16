#!/usr/bin/perl

package Mcol::Install::Ubuntu;
use strict;
use Cwd qw(getcwd abs_path);
use File::Basename;
use lib(dirname(abs_path(__FILE__))  . "/modules");
use Mcol::Utility qw(command_result);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php);

my @systemDependencies = (
    'supervisor',
    'authbind',
    'expect',
    'openssl',
    'build-essential',
    'intltool',
    'autoconf',
    'automake',
    'gcc',
    'curl',
    'pkg-config',
    'cpanminus',
    'mysql-client',
    'mysql-client',
    'imagemagick',
    'libpcre++-dev',
    'libcurl4',
    'libcurl4-openssl-dev',
    'libmagickwand-dev',
    'libssl-dev',
    'libxslt1-dev',
    'libmysqlclient-dev',
    'libpcre2-dev',
    'libxml2',
    'libxml2-dev',
    'libicu-dev',
    'libmagick++-dev',
    'libzip-dev',
    'libonig-dev',
    'libsodium-dev',
    'libglib2.0-dev',
    'libwebp-dev',
);

1;

# ====================================
#    Subroutines below this point
# ====================================

# installs OS level system dependencies.
sub install_system_dependencies {
    my $username = getpwuid( $< );
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privelages.\n";

    my @updateCmd = ('sudo');
    push @updateCmd, 'apt-get';
    push @updateCmd, 'update';
    system(@updateCmd);
    command_result($?, $!, "Updated system dependencies...", \@updateCmd);

    my @cmd = ('sudo');
    push @cmd, 'apt-get';
    push @cmd, 'install';
    push @cmd, '-y';
    foreach my $dependency (@systemDependencies) {
        push @cmd, $dependency;
    }

    system(@cmd);
    command_result($?, $!, "Installed system dependencies...", \@cmd);
}

# installs PHP.
sub install_php {
    my ($dir) = @_;
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
    push @configurePhp, '--with-iconv';

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
