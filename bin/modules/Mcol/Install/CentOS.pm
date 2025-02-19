#!/usr/bin/perl

package Mcol::Install::CentOS;
use strict;
use Cwd qw(getcwd abs_path);
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php);

# CentOS system dependencies
my @systemDependencies = qw(
    epel-release supervisor authbind expect openssl-devel gcc curl
    pkgconfig mysql-devel imagemagick pcre-devel libcurl-devel
    libxml2-devel libicu-devel libxslt-devel libzip-devel oniguruma-devel
    libsodium-devel glib2-devel libwebp-devel
);

# ====================================
# Subroutines
# ====================================

# Installs OS-level system dependencies for CentOS
sub install_system_dependencies {
    my $username = getpwuid($<);
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privileges.\n";

    # Update system
    my @updateCmd = ('sudo', 'yum', 'update', '-y');
    system(@updateCmd);
    command_result($?, $!, "Updated system dependencies...", \@updateCmd);

    # Install system dependencies
    my @cmd = ('sudo', 'yum', 'install', '-y', @systemDependencies);
    system(@cmd);
    command_result($?, $!, "Installed system dependencies...", \@cmd);
}

# Installs PHP on CentOS
sub install_php {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();

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
        '--with-iconv'
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
    system('make', "-j$threads");
    command_result($?, $!, 'Made PHP...', 'make');

    system('make', 'install');
    command_result($?, $!, 'Installed PHP...', 'make install');

    chdir $originalDir;
}

1;
