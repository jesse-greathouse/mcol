#!/usr/bin/env perl

package Mcol::Install;
use strict;
use File::Basename;
use File::Copy;
use File::Path qw(make_path);
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use lib(dirname(abs_path(__FILE__))  . "/modules");
use Mcol::Utility qw(
    str_replace_in_file
    get_operating_system
    command_result
    mcol_splash
);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';
our @EXPORT_OK = qw(install);

my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $os = get_operating_system();
my $osModule = 'Mcol::Install::' . $os;
my $nodeVersion = '22.14';
my $npmVersion = '11.2.0';

print "OS Module Selected: $osModule\n";

my $ok = eval "use $osModule qw(install_system_dependencies install_php install_bazelisk); 1;";
die "Failed to load $osModule: $@" unless $ok;

# sanity: did the symbols arrive?
for my $sym (qw(install_system_dependencies install_php install_bazelisk)) {
    die "$osModule did not export $sym"
        unless __PACKAGE__->can($sym);
}

eval "use $osModule qw(install_system_dependencies install_php install_bazelisk)";

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
    'Sys::Info',
    'Term::ANSIScreen',
    'Term::Menus',
    'Term::Prompt',
    'Term::ReadKey',
    'Text::Wrap',
    'YAML::XS',
);

# ====================================
#    Subroutines below this point
# ====================================

# Performs the install routine.
sub install {
    printf "Installing mcol at: $applicationRoot\n",

    my %options = handle_options();

    if ($options{'system'}) {
        mcol_splash();
        install_system_dependencies();
    }

    if ($options{'rabbitmq'}) {
        install_erlang($applicationRoot);
        install_elixir($applicationRoot);
        install_bazelisk($applicationRoot);
        install_rabbitmq($applicationRoot);
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
        install_msgpack($applicationRoot);
        install_phpredis($applicationRoot);
        install_rar($applicationRoot);
    }

    if ($options{'composer'}) {
        install_composer($applicationRoot);
        install_composer_dependencies($applicationRoot);
    }

    if ($options{'node'}) {
        install_node($applicationRoot);
        node_build($applicationRoot);
    }

    install_symlinks($applicationRoot);

    cleanup($applicationRoot);
}

sub handle_options {
    my $defaultInstall = 1;
    my @components =  ('system', 'node', 'rabbitmq', 'perl', 'openresty', 'php', 'composer');
    my %skips;
    my %installs;

    GetOptions ("skip-system"       => \$skips{'system'},
                "skip-node"         => \$skips{'node'},
                "skip-rabbitmq"     => \$skips{'rabbitmq'},
                "skip-openresty"    => \$skips{'openresty'},
                "skip-perl"         => \$skips{'perl'},
                "skip-php"          => \$skips{'php'},
                "skip-composer"     => \$skips{'composer'},
                "system"            => \$installs{'system'},
                "node"              => \$installs{'node'},
                "rabbitmq"          => \$installs{'rabbitmq'},
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
        node        => $defaultInstall,
        rabbitmq    => $defaultInstall,
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
    my $threads = how_many_threads_should_i_use();
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
    command_result($?, $!, 'Unpack Nginx (Openresty)... Archive...', 'tar -xzf ' . $dir . '/opt/openresty-*.tar.gz -C ' . $dir . ' /opt/');

    chdir glob("$dir/opt/openresty-*/");

    # configure
    system(@configureOpenresty);
    command_result($?, $!, 'Configure Nginx (Openresty)......', \@configureOpenresty);

    # Make and Install Nginx(Openresty)
    print "\n=================================================================\n";
    print " Compiling Nginx...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'Compile Nginx (Openresty)...', "make -j$threads");

    # install
    system(('make', 'install'));
    command_result($?, $!, 'Install (Openresty)...', 'make install');

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
    my $optDir = $dir . '/opt';

    unlink "$binDir/php";
    symlink("$optDir/php/bin/php", "$binDir/php");

    unlink "$binDir/elixir";
    symlink("$optDir/elixir/bin/elixir", "$binDir/elixir");

    unlink "$binDir/mix";
    symlink("$optDir/elixir/bin/mix", "$binDir/mix");

    unlink "$binDir/iex";
    symlink("$optDir/elixir/bin/iex", "$binDir/iex");

    unlink "$binDir/elixirc";
    symlink("$optDir/elixir/bin/elixirc", "$binDir/elixirc");

    unlink "$binDir/erlc";
    symlink("$optDir/erlang/bin/erlc", "$binDir/erlc");

    unlink "$binDir/escript";
    symlink("$optDir/erlang/bin/escript", "$binDir/escript");

    unlink "$binDir/typer";
    symlink("$optDir/erlang/bin/typer", "$binDir/typer");

    unlink "$binDir/erl";
    symlink("$optDir/erlang/bin/erl", "$binDir/erl");

    unlink "$binDir/cerl";
    symlink("$optDir/erlang/bin/cerl", "$binDir/cerl");
}

# installs Perl Modules.
sub install_perl_modules {
    my @cmd = ();
    push @cmd, 'cpanm', '--notest', '--quiet', '--no-man-pages', '--skip-satisfied', @perlModules;

    system(@cmd);
    command_result($?, $!, 'Installed Perl modules (no tests)', \@cmd);
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

    # If Pear directory exists, delete it.
    if (-d "$dir/opt/pear") {
        system(('bash', '-c', "rm -rf $dir/opt/pear"));
        command_result($?, $!, "Removing existing Pear directory...", "rm -rf $dir/opt/pear");
    }

    system(('bash', '-c', "yes n | $dir/bin/install-pear.sh $dir/opt"));
    command_result($?, $!, 'Install Pear...', "yes n | $dir/bin/install-pear.sh $dir/opt");

    # Replace the php.ini file
    if (-e $phpIniBackupFile) {
         move($phpIniBackupFile, $phpIniFile);
    }
}

# installs pear/PHP_Archive.
sub install_phparchive {
    my ($dir) = @_;
    my $pear = $dir . '/opt/pear/bin/pear';

    system(('bash', '-c', "$pear install pear/PHP_Archive-0.14.0"));
    command_result($?, $!, 'pear/PHP_Archive...', "$pear install pear/PHP_Archive-0.14.0");
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

# installs msgpack-php.
sub install_msgpack {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();
    my $optDir = $dir . '/opt';
    my $phpizeBinary = $optDir . '/php/bin/phpize';
    my $phpconfigBinary = $optDir . '/php/bin/php-config';
    my $msgpackRepo = 'https://github.com/msgpack/msgpack-php.git';
    my $originalDir = getcwd();

    # Download Repo Command
    my @downloadmsgpack = ('git');
    push @downloadmsgpack, 'clone';
    push @downloadmsgpack, $msgpackRepo;

    # Configure Command
    my @msgpackConfigure = ('./configure');
    push @msgpackConfigure, '--prefix=' . $optDir;
    push @msgpackConfigure, '--with-php-config=' . $phpconfigBinary;

    # Delete Repo Command
    my @msgpackDeleteRepo = ('rm');
    push @msgpackDeleteRepo, '-rf';
    push @msgpackDeleteRepo, "$originalDir/msgpack-php";

    system(@downloadmsgpack);
    command_result($?, $!, 'Downloading msgpack-php repo...', \@downloadmsgpack);
    chdir glob("$originalDir/msgpack-php");

    system($phpizeBinary);
    command_result($?, $!, 'phpize...', \$phpizeBinary);

    system(@msgpackConfigure);
    command_result($?, $!, 'Configuring msgpack-php...', \@msgpackConfigure);

    # Make and Install msgpack-php
    print "\n=================================================================\n";
    print " Compiling msgpack-php Extension...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'make msgpack-php...', "make -j$threads");

    system('make install');
    command_result($?, $!, 'make install msgpack-php', 'make install');

    chdir glob("$originalDir");

    system(@msgpackDeleteRepo);
    command_result($?, $!, 'Deleting msgpack-php repo...', \@msgpackDeleteRepo);
}

# installs rar.
sub install_rar {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();
    my $optDir = $dir . '/opt';
    my $phpizeBinary = $optDir . '/php/bin/phpize';
    my $phpconfigBinary = $optDir . '/php/bin/php-config';
    my $phpRarRepo = 'https://github.com/jesse-greathouse/php-rar.git';
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

    # Make and Install php-rar
    print "\n=================================================================\n";
    print " Compiling php-rar Extension...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'make php-rar...', "make -j$threads");

    system('make install');
    command_result($?, $!, 'make install php-rar', 'make install');

    chdir glob("$originalDir");

    system(@phpRarDeleteRepo);
    command_result($?, $!, 'Deleting php-rar repo...', \@phpRarDeleteRepo);
}

# installs phpredis.
sub install_phpredis {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();
    my $optDir = $dir . '/opt';
    my $phpizeBinary = $optDir . '/php/bin/phpize';
    my $phpconfigBinary = $optDir . '/php/bin/php-config';
    my $phpredisRepo = 'https://github.com/phpredis/phpredis.git';
    my $originalDir = getcwd();

    # Download Repo Command
    my @downloadphpredis = ('git');
    push @downloadphpredis, 'clone';
    push @downloadphpredis, $phpredisRepo;

    # Configure Command
    my @phpredisConfigure = ('./configure');
    push @phpredisConfigure, '--prefix=' . $optDir;
    push @phpredisConfigure, '--with-php-config=' . $phpconfigBinary;

    # Delete Repo Command
    my @phpredisDeleteRepo = ('rm');
    push @phpredisDeleteRepo, '-rf';
    push @phpredisDeleteRepo, "$originalDir/phpredis";

    system(@downloadphpredis);
    command_result($?, $!, 'Downloading phpredis repo...', \@downloadphpredis);
    chdir glob("$originalDir/phpredis");

    system($phpizeBinary);
    command_result($?, $!, 'phpize...', \$phpizeBinary);

    system(@phpredisConfigure);
    command_result($?, $!, 'Configuring phpredis...', \@phpredisConfigure);

    # Make and Install phpredis
    print "\n=================================================================\n";
    print " Compiling phpredis Extension...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'make phpredis...', "make -j$threads");

    system('make install');
    command_result($?, $!, 'make install phpredis', 'make install');

    chdir glob("$originalDir");

    system(@phpredisDeleteRepo);
    command_result($?, $!, 'Deleting phpredis repo...', \@phpredisDeleteRepo);
}

# installs Composer.
sub install_composer {
    my ($dir) = @_;
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
    my $srcDir = $dir . '/src';
    my $vendorDir = $srcDir . '/vendor';
    my $phpExecutable = $dir . '/opt/php/bin/php';
    my $composerExecutable = "$phpExecutable $binDir/composer";
    my $composerInstallCommand = "$composerExecutable install";

    chdir $srcDir;

    # If elixir directory exists, delete it.
    if (-d $vendorDir) {
        system(('bash', '-c', "rm -rf $vendorDir"));
        command_result($?, $!, "Removing existing composer vendors directory...", "rm -rf $vendorDir");
    }

    system(('bash', '-c', $composerInstallCommand));
    command_result($?, $!, 'Installing Composer Dependencies...', $composerInstallCommand);

    chdir $originalDir
}

sub cleanup {
    my ($dir) = @_;
    my $phpBuildDir = glob("$dir/opt/php-*/");
    my $openrestyBuildDir = glob("$dir/opt/openresty-*/");
    my $erlangSrcDir     = "$dir/opt/erlang-src";

    system(('bash', '-c', "rm -rf $phpBuildDir"));
    command_result($?, $!, 'Remove PHP Build Dir...', "rm -rf $phpBuildDir");
    system(('bash', '-c', "rm -rf $openrestyBuildDir"));
    command_result($?, $!, 'Remove Openresty Build Dir...', "rm -rf $openrestyBuildDir");
    system(('bash', '-c', "rm -rf $erlangSrcDir"));
    command_result($?, $!, 'Remove Erlang Src Dir...', "rm -rf $erlangSrcDir");
}

sub install_elixir {
    my ($dir) = @_;
    my $elixirVersion = 'v1.16.3';
    my $originalDir = getcwd();
    my $erlangDir = glob("$dir/opt/erlang");
    my $elixirDir = glob("$dir/opt/elixir");

    if (-d $elixirDir) {
        print "Elixir dependency already exists, skipping...(`rm -rf $elixirDir` to rebuild)\n";
        return;
    }

    system("git clone --depth 1 --branch $elixirVersion https://github.com/elixir-lang/elixir.git $dir/opt/elixir");
    command_result($?, $!, 'Clone elixir ...', "git clone --depth 1 --branch $elixirVersion https://github.com/elixir-lang/elixir.git $dir/opt/elixir");

    chdir $elixirDir;

    system("ERLANG_HOME=\"$erlangDir\" PATH=\"$erlangDir/bin:\$PATH\" make clean compile");
    command_result($?, $!, 'Make elixir ...', 'make clean compile');

    chdir $originalDir;
}

sub install_erlang {
    my ($dir) = @_;

    # ---- macOS fast path ----
    if (defined $os && $os eq 'MacOS') {
        # Load only on macOS to avoid hard dep on other platforms
        eval { require Mcol::Install::MacOS; 1 }
            or die "Failed to load Mcol::Install::MacOS: $@";

        # No need to import; call fully-qualified
        Mcol::Install::MacOS::build_erlang_otp_on_macos($dir);
        return;  # return early; skip Linux-specific flow below
    }
    # ---- end macOS fast path ----

    my $threads = how_many_threads_should_i_use();

    my $erlangSrcDir     = "$dir/opt/erlang-src";  # source checkout lives here
    my $erlangPrefixDir  = "$dir/opt/erlang";      # installed runtime lives here
    my $erlangVersion    = 'OTP-25.3.2.21';
    my $originalDir      = getcwd();

    # Clean slate if desired:
    # system("rm -rf '$erlangSrcDir' '$erlangPrefixDir'");

    if (-d $erlangPrefixDir) {
        print "Erlang already installed at $erlangPrefixDir, skipping... (`rm -rf $erlangPrefixDir` to rebuild)\n";
        return;
    }

    # Clone sources
    unless (-d $erlangSrcDir) {
        system("git clone --depth 1 --branch $erlangVersion https://github.com/erlang/otp.git '$erlangSrcDir'");
        command_result($?, $!, 'Clone erlang ...', "git clone --depth 1 --branch $erlangVersion https://github.com/erlang/otp.git $erlangSrcDir");
    }

    chdir $erlangSrcDir;

    # Configure to install OUTSIDE the source tree
    system("./configure --prefix='$erlangPrefixDir'");
    command_result($?, $!, 'Configure erlang ...', "./configure --prefix=$erlangPrefixDir");

    # Optionally skip apps like tftp
    #my @otp_skip = qw(tftp);
    my @otp_skip = ();
    my $skip = join(' ', @otp_skip);

    for my $app (@otp_skip) {
        my $marker = "$erlangSrcDir/lib/$app/SKIP";
        if (-d "$erlangSrcDir/lib/$app" && !-e $marker) {
            open my $fh, '>', $marker or die "Can't create $marker: $!";
            close $fh;
        }
    }

    system('make', "SKIP=$skip", "-j$threads");
    command_result($?, $!, 'Make erlang ...', "make SKIP=$skip -j$threads");

    system('make', "SKIP=$skip", 'install');
    command_result($?, $!, 'Install erlang ...', "make SKIP=$skip install");

    # Add expected OTP_VERSION layout for Bazel rules
    my $otp_version_file = "$erlangPrefixDir/OTP_VERSION";
    if (-f $otp_version_file) {
        my $otp_release = `cat '$otp_version_file'`; chomp($otp_release);
        my ($otp_major) = $otp_release =~ /^(\d+)/;
        my $release_dir = "$erlangPrefixDir/releases/$otp_major";
        require File::Path; File::Path::make_path($release_dir) unless -d $release_dir;
        my $target_file = "$release_dir/OTP_VERSION";
        require File::Copy;
        File::Copy::copy($otp_version_file, $target_file) unless -e $target_file;
        print "Copied OTP_VERSION to: $target_file\n";
    } else {
        warn "OTP_VERSION not found at $otp_version_file; skipping Bazel compatibility fix.\n";
    }

    # RabbitMQ compatibility symlink
    my $erl_bin = "$erlangPrefixDir/bin/erl";
    my $platform_bin_dir = "$erlangPrefixDir/bin/x86_64-pc-linux-gnu";
    my $platform_erl = "$platform_bin_dir/erl";
    unless (-e $platform_erl) {
        require File::Path; File::Path::make_path($platform_bin_dir);
        symlink("../erl", $platform_erl)
            or warn "Failed to create RabbitMQ-compatible symlink: $platform_erl → ../erl ($!)\n";
        print "Created RabbitMQ-compatible symlink: $platform_erl → ../erl\n";
    }

    chdir $originalDir;
}

sub install_rabbitmq {
    my ($dir) = @_;
    my $rabbitmqVersion = 'v3.12.13';
    my $originalDir = getcwd();
    my $rabbitmqDir = glob("$dir/opt/rabbitmq");

    my $rabbitmqSbin;
    if (defined $os && $os eq 'MacOS') {
        chomp(my $arch = `uname -m 2>/dev/null`);             # arm64 or x86_64
        my $cfg = ($arch && $arch eq 'arm64')
                ? 'darwin_arm64-fastbuild'
                : 'darwin_x86_64-fastbuild';
        $rabbitmqSbin = "$rabbitmqDir/bazel-out/$cfg/bin/broker-home/sbin";
    } else {
        # Linux defaults (what you had before)
        $rabbitmqSbin = "$rabbitmqDir/bazel-out/k8-fastbuild/bin/broker-home/sbin";
    }

    my $erlangDir = glob("$dir/opt/erlang");
    my $erlangPath = "$erlangDir/bin";
    my $elixirPath = glob("$dir/opt/elixir/bin");
    my $otpRelease = '25';
    local %ENV = %ENV;  # localize to avoid leaking changes
    $ENV{PATH} = "$erlangPath:$elixirPath:$binDir:$ENV{PATH}";
    $ENV{ERLANG_HOME} = $erlangDir;
    $ENV{OTP_VERSION} = $otpRelease;

    # Ensure OTP_VERSION file exists (required by Bazel's Erlang rules)
    my $otpVersion = `"$erlangPath/erl" -noshell -eval 'io:format("~s", [erlang:system_info(otp_release)]), halt().'`;
    chomp $otpVersion;

    my $otpFileDir = "$erlangDir/releases/$otpRelease";
    my $otpVersionFile = "$otpFileDir/OTP_VERSION";

    unless (-e $otpVersionFile) {
        print "Creating missing OTP_VERSION file at $otpVersionFile...\n";
        make_path($otpFileDir) unless -d $otpFileDir;
        open(my $fh, '>', $otpVersionFile) or die "Cannot create $otpVersionFile: $!";
        print $fh $otpVersion;
        close($fh);
    }

    # delete
    if (-d $rabbitmqDir) {
        my $deleteCmd =  "rm -rf $rabbitmqDir";
        system($deleteCmd);
        command_result($?, $!, 'Deleting rabbitmq dir...', $deleteCmd);
    }

    my $cloneCmd = "git clone --depth 1 --branch $rabbitmqVersion https://github.com/rabbitmq/rabbitmq-server.git $rabbitmqDir";
    system($cloneCmd);
    command_result($?, $!, 'Clone rabbitmq ...', $cloneCmd);

    chdir $rabbitmqDir;

    # Make and Install rabbitmq
    print "\n=================================================================\n";
    print " Make and Install rabbitmq...\n";
    print "=================================================================\n\n";

    # make
    my $makeCmd = "make -j1 package-generic-unix";
    system($makeCmd);
    command_result($?, $!, 'Make rabbitmq ...', $makeCmd);

    # Build rabbitmq broker and sbin
    print "\n=================================================================\n";
    print " Building rabbitmq broker...\n";
    print "=================================================================\n\n";

    # Broker
    my $buildCmd = "bazel build //:broker --verbose_failures";
    system($buildCmd);
    command_result($?, $!, 'bazel build broker...', $buildCmd);

    # Build rabbitmq broker and sbin
    print "\n=================================================================\n";
    print " Building rabbitmq sbin tools...\n";
    print "=================================================================\n\n";

    # Sbin
    my $buildSbinCmd = "bazel build //:sbin-files";
    system($buildSbinCmd);
    command_result($?, $!, 'bazel build sbin...', $buildSbinCmd);

    # Link Rabbitmq scripts into bin/
    chdir $binDir;
    for my $script (qw(
        rabbitmq-defaults rabbitmq-diagnostics rabbitmq-env rabbitmq-plugins
        rabbitmq-queues rabbitmq-server rabbitmq-streams rabbitmq-upgrade
        rabbitmqctl vmware-rabbitmq
    )) {
        unlink $script if -l $script;
        symlink "$rabbitmqSbin/$script", $script;
    }

    chdir $originalDir;
}

# Helper to source NVM in a fresh bash -lc
sub _nvm_source {
    return q{export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"};
}

# Run a command in a shell with NVM loaded; optionally require `nvm use $nodeVersion` first
sub sh_nvm {
    my ($cmd, $use_node) = @_;
    my $env = _nvm_source();
    my $prefix = $use_node ? "nvm use $nodeVersion >/dev/null 2>&1 && " : "";
    return system('bash', '-lc', "$env; $prefix$cmd");
}

sub install_node {
    my ($dir) = @_;
    my $nvmInstallScript = 'https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh';
    my $nvmDir = "$ENV{HOME}/.nvm";  # <-- NVM installs here

    # Is NVM available?
    my $nvmCheck = system('bash','-lc', _nvm_source() . q{; command -v nvm >/dev/null});
    if ($nvmCheck != 0) {
        # Fresh install
        unless (-d $nvmDir) {
            system('bash', '-c', "curl -o- $nvmInstallScript | bash");
            command_result($?, $!, 'Installing NVM...', "curl -o- $nvmInstallScript | bash");
        }
        # Re-check
        $nvmCheck = system('bash','-lc', _nvm_source() . q{; command -v nvm >/dev/null});
        die "Failed to install NVM\n" if $nvmCheck != 0;
    }

    # Ensure the requested Node version exists (no `nvm use` gate here)
    my $haveNode = sh_nvm("nvm ls $nodeVersion >/dev/null 2>&1", 0);
    if ($haveNode != 0) {
        sh_nvm("nvm install $nodeVersion", 0);
        command_result($?, $!, "Installing Node.js via NVM...", "nvm install $nodeVersion");
    } else {
        print "Node.js $nodeVersion is already installed. Skipping installation.\n";
    }

    # Set default and switch to it
    sh_nvm("nvm alias default $nodeVersion", 0);
    sh_nvm("nvm use $nodeVersion", 0);
    command_result($?, $!, "Switching to Node.js $nodeVersion...", "nvm use $nodeVersion");

    # Check/upgrade npm (this DOES require the active node)
    my $npmCheck = `bash -lc '@{[_nvm_source()]} ; nvm use $nodeVersion >/dev/null 2>&1 ; npm -v 2>/dev/null'`;
    chomp($npmCheck //= '');
    if ($npmCheck ne $npmVersion) {
        my $npm_cmd = "npm install -g npm\@$npmVersion";
        sh_nvm($npm_cmd, 1);
        command_result($?, $!, "Upgrading NPM to $npmVersion...", $npm_cmd);
    } else {
        print "NPM $npmVersion is already installed. Skipping upgrade.\n";
    }
}

sub node_build {
    my ($dir) = @_;
    my $originalDirectory = getcwd();
    my $srcDir = "$dir/src";
    my $modulesDir = "$srcDir/node_modules";

    chdir($srcDir) or die "Failed to change directory to $srcDir: $!";

    # Ensure the correct Node is active for all Node-dependent steps
    sh_nvm("nvm use $nodeVersion", 0);
    command_result($?, $!, "Switching to Node.js $nodeVersion...", "nvm use $nodeVersion");

    # Remove node_modules if present (doesn't need Node, but harmless)
    if (-d $modulesDir) {
        system('rm', '-rf', $modulesDir);
        command_result($?, $!, "Removing existing node_modules...", "rm -rf $modulesDir");
    }

    # Install & build
    sh_nvm("npm ci || npm install", 1);
    command_result($?, $!, "Installing dependencies...", "npm ci || npm install");

    sh_nvm("npm run build", 1);
    command_result($?, $!, "Building project...", "npm run build");

    chdir($originalDirectory) or die "Failed to change back to $originalDirectory: $!";
}

1;
