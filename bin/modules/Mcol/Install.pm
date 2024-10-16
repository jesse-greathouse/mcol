#!/usr/bin/perl

package Mcol::Install;
use strict;
use File::Basename;
use File::Copy;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use lib(dirname(abs_path(__FILE__))  . "/modules");
use Mcol::Utility qw(
    str_replace_in_file
    get_operating_system
    command_result
);
use Exporter 'import';
our @EXPORT_OK = qw(install);

my $bin = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($bin));
my $os = get_operating_system();
my $osModule = 'Mcol::Install::' . $os;

eval "use $osModule qw(install_system_dependencies install_php)";

my @perlModules = (
    'JSON',
    'Archive::Zip',
    'Bytes::Random::Secure',
    'Config::File',
    'LWP::Protocol::https',
    'LWP::UserAgent',
    'File::Slurper',
    'File::HomeDir',
    'File::Find::Rule',
    'File::Touch',
    'Term::ANSIScreen',
    'Term::Menus',
    'Term::Prompt',
    'Term::ReadKey',
    'Text::Wrap',
    'YAML::XS',
);

1;

# ====================================
#    Subroutines below this point
# ====================================

# Performs the install routine.
sub install {
    printf "Installing mcol at: $applicationRoot\n",

    my %options = handle_options();

    if ($options{'system'}) {
        install_system_dependencies();
    }

    if ($options{'perl'}) {
        install_perl_modules();
    }

    if ($options{'openresty'}) {
        install_openresty($applicationRoot);
    }

    if ($options{'php'}) {
        configure_php($applicationRoot);
        install_php($applicationRoot);
        install_rar($applicationRoot);
    }

    if ($options{'composer'}) {
        install_composer($applicationRoot);
        install_composer_dependencies($applicationRoot);
    }

    install_symlinks($applicationRoot);

    cleanup($applicationRoot);
}

sub handle_options {
    my $defaultInstall = 1;
    my @components =  ('system', 'perl', 'openresty', 'php', 'composer');
    my %skips;
    my %installs;

    GetOptions ("skip-system"       => \$skips{'system'},
                "skip-openresty"    => \$skips{'openresty'},
                "skip-perl"         => \$skips{'perl'},
                "skip-php"          => \$skips{'php'},
                "skip-composer"     => \$skips{'composer'},
                "system"            => \$installs{'system'},
                "openresty"         => \$installs{'openresty'},
                "perl"              => \$installs{'perl'},
                "php"               => \$installs{'php'},
                "composer"          => \$installs{'composer'})
    or die("Error in command line arguments\n");

    # If any of the components are requested for install...
    # Flip the $defaultInstall flag to negative.
    foreach (@components) {
        if (defined $installs{$_}) {
            $defaultInstall = 0;
            last;
        }
    }

    # Set up an options hash with the default install flag.
    my  %options = (
        system      => $defaultInstall,
        openresty   => $defaultInstall,
        perl        => $defaultInstall,
        php         => $defaultInstall,
        composer    => $defaultInstall
    );

    # If the component is listed on the command line...
    # Set the option for true.
    foreach (@components) {
        if (defined $installs{$_}) {
            $options{$_} = 1;
        }
    }

    # If the component is set to skip on the command line...
    # Set the option for false.
    foreach (@components) {
        if (defined $skips{$_}) {
            $options{$_} = 0;
        }
    }

    return %options;
}

# installs Openresty.
sub install_openresty {
    my ($dir) = @_;
    my @configureOpenresty = ('./configure');
    push @configureOpenresty, '--prefix=' . $dir . '/opt/openresty';
    push @configureOpenresty, '--with-pcre-jit';
    push @configureOpenresty, '--with-ipv6';
    push @configureOpenresty, '--with-http_iconv_module';
    push @configureOpenresty, '--with-http_realip_module';
    push @configureOpenresty, '--with-http_ssl_module';
    push @configureOpenresty, '-j2';

    my $originalDir = getcwd();

    # Unpack
    system(('bash', '-c', "tar -xzf $dir/opt/openresty-*.tar.gz -C $dir/opt/"));
    command_result($?, $!, 'Unpack Openresty Archive...', 'tar -xzf ' . $dir . '/opt/openresty-*.tar.gz -C ' . $dir . ' /opt/');

    chdir glob("$dir/opt/openresty-*/");

    # configure
    system(@configureOpenresty);
    command_result($?, $!, 'Configure Openresty...', \@configureOpenresty);

    # make
    system('make');
    command_result($?, $!, 'Make Openresty...', 'make');

    # install
    system(('make', 'install'));
    command_result($?, $!, 'Install Openresty...', 'make install');

    chdir $originalDir;
}

# configures PHP.
sub configure_php {
    my ($dir) = @_;
    my $etcDir = $dir . '/etc';
    my $optDir = $dir . '/opt';
    my $phpExecutable = "$optDir/php/bin/php";
    my $phpIniFile = "$etcDir/php/php.ini";
    my $phpIniDist = "$etcDir/php/php.dist.ini";
    my $phpFpmConfFile = "$etcDir/php-fpm.d/php-fpm.conf";
    my $phpFpmConfDist = "$etcDir/php-fpm.d/php-fpm.dist.conf";

    copy($phpIniDist, $phpIniFile) or die "Copy $phpIniDist failed: $!";
    copy($phpFpmConfDist, $phpFpmConfFile) or die "Copy $phpFpmConfDist failed: $!";
    str_replace_in_file('__DIR__', $dir, $phpIniFile);
    str_replace_in_file('__DIR__', $dir, $phpFpmConfFile);
    str_replace_in_file('__APP_NAME__', 'mcol', $phpFpmConfFile);
    str_replace_in_file('__USER__', $ENV{"LOGNAME"}, $phpFpmConfFile);
}

# installs symlinks.
sub install_symlinks {
    my ($dir) = @_;
    my $binDir = $dir . '/bin';
    my $optDir = $dir . '/opt';

    unlink "$binDir/php";
    symlink("$optDir/php/bin/php", "$binDir/php");
}

# installs Perl Modules.
sub install_perl_modules {
    foreach my $perlModule (@perlModules) {
        my @cmd = ('sudo');
        push @cmd, 'cpanm';
        push @cmd, $perlModule;
        system(@cmd);

        command_result($?, $!, "Shared library pass for: $_", \@cmd);
    }
}

# installs Pear.
sub install_pear {
    my ($dir) = @_;
    my $phpIniFile = $dir . '/etc/php/php.ini';
    my $phpIniBackupFile = $phpIniFile . '.' . time() . '.bak';

    # If php.ini exists, hide it before pear installs
    if (-e $phpIniFile) {
        move($phpIniFile, $phpIniBackupFile);
    }

    system(('bash', '-c', "yes n | $dir/bin/install-pear.sh $dir/opt"));
    command_result($?, $!, 'Install Pear...', "yes n | $dir/bin/install-pear.sh $dir/opt");

    # Replace the php.ini file
    if (-e $phpIniBackupFile) {
         move($phpIniBackupFile, $phpIniFile);
    }
}

# installs Imagemagick.
sub install_imagick {
    my ($dir) = @_;
    my $phpIniFile = $dir . '/etc/php/php.ini';
    my $phpIniBackupFile = $phpIniFile . '.' . time() . '.bak';
    my $cmd = 'yes n | PATH="' . $dir . '/opt/php/bin:$PATH" ' . $dir . '/opt/pear/bin/pecl install imagick';

    # If php.ini exists, hide it before pear installs
    if (-e $phpIniFile) {
        move($phpIniFile, $phpIniBackupFile);
    }

    system(('bash', '-c', $cmd));
    command_result($?, $!, 'Install Imagemagick...', "...");

    # Replace the php.ini file
    if (-e $phpIniBackupFile) {
         move($phpIniBackupFile, $phpIniFile);
    }
}

# installs rar.
sub install_rar {
    my ($dir) = @_;
    my $optDir = $dir . '/opt';
    my $phpizeBinary = $optDir . '/php/bin/phpize';
    my $phpconfigBinary = $optDir . '/php/bin/php-config';
    my $phpRarRepo = 'https://github.com/cataphract/php-rar.git';
    my $originalDir = getcwd();

    # Download Repo Command
    my @downloadPhpRar = ('git');
    push @downloadPhpRar, 'clone';
    push @downloadPhpRar, $phpRarRepo;

    # Configure Command
    my @phpRarConfigure = ('./configure');
    push @phpRarConfigure, '--prefix=' . $optDir;
    push @phpRarConfigure, '--with-php-config=' . $phpconfigBinary;

    # Delete Repo Command
    my @phpRarDeleteRepo = ('rm');
    push @phpRarDeleteRepo, '-rf';
    push @phpRarDeleteRepo, "$originalDir/php-rar";

    system(@downloadPhpRar);
    command_result($?, $!, 'Downloading php-rar repo...', \@downloadPhpRar);
    chdir glob("$originalDir/php-rar");

    system($phpizeBinary);
    command_result($?, $!, 'phpize...', \$phpizeBinary);

    system(@phpRarConfigure);
    command_result($?, $!, 'Configuring php-rar...', \@phpRarConfigure);

    system('make');
    command_result($?, $!, 'make php-rar...', 'make');

    system('make install');
    command_result($?, $!, 'make install php-rar', 'make install');

    chdir glob("$originalDir");

    system(@phpRarDeleteRepo);
    command_result($?, $!, 'Deleting php-rar repo...', \@phpRarDeleteRepo);
}

# installs Composer.
sub install_composer {
    my ($dir) = @_;
    my $binDir = $dir . '/bin';
    my $phpExecutable = $dir . '/opt/php/bin/php';
    my $composerInstallScript = $binDir . '/composer-setup.php';
    my $composerHash = 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6';
    my $composerDownloadCommand = "$phpExecutable -r \"copy('https://getcomposer.org/installer', '$composerInstallScript');\"";
    my $composerCheckHashCommand = "$phpExecutable -r \"if (hash_file('sha384', '$composerInstallScript') === '$composerHash') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('$composerInstallScript'); } echo PHP_EOL;\"";
    my $composerInstallCommand = "$phpExecutable $composerInstallScript --filename=composer";
    my $removeIntallScriptCommand = "$phpExecutable -r \"unlink('$composerInstallScript');\"";
    my $composerArtifact = "composer";

    # Remove the composer artifact if it already exists.
    if (-e "$binDir/$composerArtifact") {
         unlink "$binDir/$composerArtifact";
    }

    system(('bash', '-c', $composerDownloadCommand));
    command_result($?, $!, 'Download Composer Install Script...', $composerDownloadCommand);

    system(('bash', '-c', $composerCheckHashCommand));
    command_result($?, $!, 'Verify Composer Hash...', $composerCheckHashCommand);

    system(('bash', '-c', $composerInstallCommand));
    command_result($?, $!, 'Installing Composer...', $composerInstallCommand);

    system(('bash', '-c', $removeIntallScriptCommand));
    command_result($?, $!, 'Removing Composer Install Script...', $removeIntallScriptCommand);

    # Move the composer artifact to the right place in bin/
    if (-e $composerArtifact) {
         move($composerArtifact, "$binDir/$composerArtifact");
    }
}

# installs composer dependencies.
sub install_composer_dependencies {
    my ($dir) = @_;
    my $originalDir = getcwd();
    my $binDir = $dir . '/bin';
    my $srcDir = $dir . '/src';
    my $phpExecutable = $dir . '/opt/php/bin/php';
    my $composerExecutable = "$phpExecutable $binDir/composer";
    my $composerInstallCommand = "$composerExecutable install";

    chdir $srcDir;

    system(('bash', '-c', $composerInstallCommand));
    command_result($?, $!, 'Installing Composer Dependencies...', $composerInstallCommand);

    chdir $originalDir
}

sub cleanup {
    my ($dir) = @_;
    my $phpBuildDir = glob("$dir/opt/php-*/");
    my $openrestyBuildDir = glob("$dir/opt/openresty-*/");
    system(('bash', '-c', "rm -rf $phpBuildDir"));
    command_result($?, $!, 'Remove PHP Build Dir...', "rm -rf $phpBuildDir");
    system(('bash', '-c', "rm -rf $openrestyBuildDir"));
    command_result($?, $!, 'Remove Openresty Build Dir...', "rm -rf $openrestyBuildDir");
}
