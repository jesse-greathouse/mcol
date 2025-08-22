#!/usr/bin/env perl

package Mcol::Install::Ubuntu;
use strict;
use Cwd qw(getcwd abs_path);
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk);

my @systemDependencies = qw(
    supervisor authbind expect openssl build-essential intltool autoconf unixodbc-dev
    automake gcc curl pkg-config cpanminus libncurses-dev libpcre3-dev libgl1-mesa-dev
    libcurl4t64 libcurl4-openssl-dev libmagickwand-dev libssl-dev libxslt1-dev default-jdk
    libmysqlclient-dev libxml2 libxml2-dev libicu-dev libmagick++-dev libglu1-mesa-dev
    libzip-dev libonig-dev libsodium-dev libglib2.0-dev libwebp-dev libwxgtk3.2-dev
    mysql-client imagemagick golang-go xsltproc fop libxml2-utils libexpat1-dev libwxgtk-webview3.2-dev redis
);

# ====================================
# Subroutines
# ====================================

# Installs OS-level system dependencies.
sub install_system_dependencies {
    my $username = getpwuid($<);
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privileges.\n";

    # Check if the golang PPA is already present
    my $ppa_check_cmd = q{
        grep -rq 'longsleep/golang-backports' /etc/apt/sources.list /etc/apt/sources.list.d/ 2>/dev/null
    };
    my $ppa_exists = system('bash', '-c', $ppa_check_cmd);

    if ($ppa_exists != 0) {
        my @addPpaCmd = ('sudo', 'add-apt-repository', '-y', 'ppa:longsleep/golang-backports');
        system(@addPpaCmd);
        command_result($?, $!, "Added Golang Repository...", \@addPpaCmd);
    } else {
        print "✓ Golang PPA already exists, skipping.\n";
    }

    # Update apt cache
    my @updateCmd = ('sudo', 'apt-get', 'update');
    system(@updateCmd);
    command_result($?, $!, "Updated package index...", \@updateCmd);

    # Filter system dependencies: only keep those that aren't already installed
    my @to_install;
    foreach my $pkg (@systemDependencies) {
        my $check = system("dpkg -s $pkg > /dev/null 2>&1");
        if ($check != 0) {
            push @to_install, $pkg;
        } else {
            print "✓ $pkg already installed, skipping.\n";
        }
    }

    # Install only what’s missing
    if (@to_install) {
        my @installCmd = ('sudo', 'apt-get', 'install', '-y', @to_install);
        system(@installCmd);
        command_result($?, $!, "Installed missing dependencies...", \@installCmd);
    } else {
        print "All system dependencies already installed.\n";
    }
}

# Installs PHP.
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
        '--with-curl', '--with-webp', '--with-openssl', '--with-zip', '--with-bz2',
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

# installs Bazelisk.
sub install_bazelisk {
    my ($dir) = @_;
    my $originalDir = getcwd();
    my $bazeliskDir = "$dir/opt/bazelisk/";

    # If elixir directory exists, delete it.
    if (-d $bazeliskDir) {
        print "Bazel dependency already exists, skipping...(`rm -rf $bazeliskDir` to rebuild)\n";
        return;
    }

    # Unpack
    system(('bash', '-c', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/"));
    command_result($?, $!, 'Unpack Bazelisk...', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/");

    # Rename
    system(('bash', '-c', "mv $dir/opt/bazelisk-*/ $bazeliskDir"));
    command_result($?, $!, 'Renaming Bazelisk Dir...', "mv -xzf $dir/opt/bazelisk-*/ $bazeliskDir");

    chdir glob($bazeliskDir);

    # Install Bazelisk
    print "\n=================================================================\n";
    print " Installing Bazelisk....\n";
    print "=================================================================\n\n";

    # Install
    system('bash', '-c', 'go install github.com/bazelbuild/bazelisk@latest');
    command_result($?, $!, 'Install Bazelisk...', 'go install github.com/bazelbuild/bazelisk@latest');

    # Binary
    system('bash', '-c', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");
    command_result($?, $!, 'Build Bazelisk...', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");

    system('bash', '-c', "$dir/bin/bazel version");
    command_result($?, $!, 'Run Bazelisk...', "$dir/bin/bazel version");

    chdir $originalDir;
}

1;
