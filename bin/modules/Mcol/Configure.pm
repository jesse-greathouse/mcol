#!/usr/bin/perl

package Mcol::Configure;
use strict;
use File::Basename;
use File::Touch;
use Cwd qw(getcwd abs_path);
use List::Util 1.29 qw( pairs );
use Exporter 'import';
use Scalar::Util qw(looks_like_number);
use Term::Prompt;
use Term::Prompt qw(termwrap);
use Term::ANSIScreen qw(cls);
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(
    get_config_file
    get_configuration
    save_configuration
    parse_env_file
    write_env_file
    write_config_file
);
use Mcol::Utility qw(splash generate_rand_str write_file);
use Data::Dumper;

our @EXPORT_OK = qw(configure);

warn $@ if $@; # handle exception

my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $srcDir = "$applicationRoot/src";
my $webDir = "$srcDir/public";
my $varDir = "$applicationRoot/var";
my $etcDir = "$applicationRoot/etc";
my $tmpDir = "$applicationRoot/tmp";
my $logDir = "$varDir/log";
my $uploadDir = "$varDir/upload";
my $cacheDir = "$varDir/cache";
my $downloadDir = "$varDir/download";

# Files and Dist
my $configFile = get_config_file();
my $laravelEnvFile = "$applicationRoot/src/.env";
my $errorLog = "$logDir/error.log";
my $sslCertificate = "$etcDir/ssl/certs/mcol.cert";
my $sslKey = "$etcDir/ssl/private/mcol.key";

# Config files
#  -template
#  -configuration
#
# These lines declare the configuration files,
# and the template files that they will be created from.
#
my $initdDist = "$etcDir/init.d/init-template.sh.dist";
my $initdFile = "$etcDir/init.d/mcol";

my $phpFpmDist = "$etcDir/php-fpm.d/php-fpm.dist.conf";
my $phpFpmFile = "$etcDir/php-fpm.d/php-fpm.conf";

my $forceSslDist = "$etcDir/nginx/force-ssl.dist.conf";
my $forceSslFile = "$etcDir/nginx/force-ssl.conf";

my $sslParamsDist = "$etcDir/nginx/ssl-params.dist.conf";
my $sslParamsFile = "$etcDir/nginx/ssl-params.conf";

my $opensslConfDist = "$etcDir/ssl/openssl.dist.cnf";
my $opensslConfFile = "$etcDir/ssl/openssl.cnf";

my $nginxConfDist = "$etcDir/nginx/nginx.dist.conf";
my $nginxConfFile = "$etcDir/nginx/nginx.conf";

my $supervisordServiceDist = "$etcDir/supervisor/conf.d/supervisord.service.conf.dist";
my $supervisordServiceFile = "$etcDir/supervisor/conf.d/supervisord.service.conf";

my $instanceManagerDist = "$etcDir/supervisor/instance-manager.conf.dist";
my $instanceManagerFile = "$etcDir/supervisor/instance-manager.conf";

# Initialize laravel .env
my $appKey = generate_app_key();

# Generate secret string.
my $secret = generate_rand_str();

# Get Configuration and Defaults
my %cfg = get_configuration();

my %defaults = (
    laravel => {
        APP_NAME                    => 'mcol',
        APP_ENV                     => 'local',
        APP_KEY                     => $appKey,
        APP_DEBUG                   => 'true',
        SESSION_DRIVER              => 'cookie',
        APP_URL                     => 'localhost',
        SESSION_DOMAIN              => 'localhost',
        SANCTUM_STATEFUL_DOMAINS    => 'localhost',
        APP_TIMEZONE                => 'UTC',
        DOWNLOAD_DIR                => $downloadDir,
        LOG_CHANNEL                 => 'stack',
        LOG_SLACK_WEBHOOK_URL       => 'none',
        DB_CONNECTION               => 'mysql',
        DB_HOST                     => '127.0.0.1',
        DB_PORT                     => '3306',
        DB_DATABASE                 => 'mcol',
        DB_USERNAME                 => 'mcol',
        DB_PASSWORD                 => 'mcol',
        CACHE_DRIVER                => 'file',
        QUEUE_CONNECTION            => 'sync',
    },
    nginx => {
        DOMAINS                     => '127.0.0.1',
        IS_SSL                      => 'no',
        PORT                        => '8080',
        SSL_CERT                    => $sslCertificate,
        SSL_KEY                     => $sslKey,
    },
    redis => {
        REDIS_HOST                  => 'localhost',
    }
);

1;

# ====================================
#    Subroutines below this point
# ====================================

# Performs the install routine.
sub configure {
    cls();
    splash();

    print (''."\n");
    print ('================================================================='."\n");
    print (" This will create the mcol configuration                            \n");
    print ('================================================================='."\n");
    print (''."\n");

    request_user_input();
    merge_defaults();
    save_configuration(%cfg);

    # Create configuration files
    write_initd_script();
    write_phpfpm_conf();
    write_force_ssl_conf();
    write_ssl_params_conf();
    write_openssl_conf();
    write_nginx_conf();
    write_supervisord_service_conf();
    write_instance_manager_conf();
    write_laravel_env();
}

sub generate_app_key {
    # Laravel needs an .env file with this empty APP_KEY to encrypt a key with the console.
    if (!-e $laravelEnvFile) {
        touch($laravelEnvFile);
        write_file($laravelEnvFile, "APP_KEY=");
    }

    return `$binDir/php $srcDir/artisan key:generate`;
}

sub write_initd_script {
    my $mode = 0755;
    my %c = %{$cfg{nginx}};

    $c{'APP_NAME'} = $cfg{laravel}{'APP_NAME'};

    write_config_file($initdDist, $initdFile, %c);
    chmod $mode, $initdFile;
}

sub write_phpfpm_conf {
    my %c = %{$cfg{nginx}};

    $c{'APP_NAME'} = $cfg{laravel}{'APP_NAME'};

    write_config_file($phpFpmDist, $phpFpmFile, %c);
}

sub write_force_ssl_conf {
    my %c = %{$cfg{nginx}};
    write_config_file($forceSslDist, $forceSslFile, %c);
}

sub write_ssl_params_conf {
    my %c = %{$cfg{nginx}};
    write_config_file($sslParamsDist, $sslParamsFile, %c);
}

sub write_openssl_conf {
    my %c = %{$cfg{nginx}};
    write_config_file($opensslConfDist, $opensslConfFile, %c);
}

sub write_nginx_conf {
    my %c = %{$cfg{nginx}};
    write_config_file($nginxConfDist, $nginxConfFile, %c);
}

sub write_supervisord_service_conf {
    my %c = %{$cfg{supervisor}};
    write_config_file($supervisordServiceDist, $supervisordServiceFile, %c);
}

sub write_instance_manager_conf {
    my %c = %{$cfg{instance_manager}};
    write_config_file($instanceManagerDist, $instanceManagerFile, %c);
}

sub write_laravel_env {
    write_env_file($laravelEnvFile, %{$cfg{laravel}});
}

sub merge_laravel_env {
    if (-e $laravelEnvFile) {
        my $env = parse_env_file($laravelEnvFile);

        foreach my $key (keys %$env) {
            $cfg{laravel}{$key} = $env->{$key};
        }

        save_configuration(%cfg);
    }
}

# Runs the user through a series of setup config questions.
# Confirms the answers.
# Returns Hash Table
sub request_user_input {
    merge_laravel_env();

    # APP_NAME
    input('laravel', 'APP_NAME', 'App Name');
    
    # APP_ENV
    input('laravel', 'APP_ENV', 'App Environment');
    
    # APP_KEY
    input('laravel', 'APP_KEY', 'App Key (Security String)');
    
    # APP_DEBUG
    input_boolean('laravel', 'APP_DEBUG', 'App Debug Flag');
    
    # APP_URL
    input('laravel', 'APP_URL', 'App Url');
    
    # APP_TIMEZONE
    input('laravel', 'APP_TIMEZONE', 'App Timezone');

    # DOWNLOAD_DIR
    input('laravel', 'DOWNLOAD_DIR', 'Download Directory');
    
    # LOG_CHANNEL
    input('laravel', 'LOG_CHANNEL', 'Log Channel');
    
    # LOG_SLACK_WEBHOOK_URL
    input('laravel', 'LOG_SLACK_WEBHOOK_URL', 'Slack Webhook Url');
    
    # DB_CONNECTION
    input('laravel', 'DB_CONNECTION', 'Database Connection (driver)');
    
    # DB_HOST
    input('laravel', 'DB_HOST', 'Database Hostname');
    
    # DB_PORT
    input('laravel', 'DB_PORT', 'Database Port');
    
    # DB_DATABASE
    input('laravel', 'DB_DATABASE', 'Database Schema Name');
    
    # DB_USERNAME
    input('laravel', 'DB_USERNAME', 'Database Username');
    
    # DB_PASSWORD
    input('laravel', 'DB_PASSWORD', 'Database Password');
    
    # CACHE_DRIVER
    input('laravel', 'CACHE_DRIVER', 'Cache Driver');
    
    # QUEUE_CONNECTION
    input('laravel', 'QUEUE_CONNECTION', 'Queue Connection');

    # DOMAINS
    input('nginx', 'DOMAINS', 'Web Domains');

    # SSL
    input_boolean('nginx', 'IS_SSL', 'Use SSL (https)');

    if ('true' eq $cfg{nginx}{IS_SSL}) {
        $cfg{nginx}{SSL} = 'ssl';
        $cfg{nginx}{PORT} = '443';

        # SSL_CERT
        input('nginx', 'SSL_CERT', 'SSL Certificate Path');
        $cfg{nginx}{SSL_CERT_LINE} = 'ssl_certificate ' . $cfg{nginx}{SSL_CERT};

        # SSL_KEY
        input('nginx', 'SSL_KEY', 'SSL Key Path');
        $cfg{nginx}{SSL_CERT_LINE} = 'ssl_certificate_key ' . $cfg{nginx}{SSL_KEY};
        $cfg{nginx}{INCLUDE_FORCE_SSL_LINE} = "include $etcDir/nginx/force-ssl.conf";

        # SUPERVISORCTL_PORT
        input('supervisor', 'SUPERVISORCTL_PORT', 'Application Control Port (5000 - 9000)');
    } else {
        # PORT
        input('nginx', 'PORT', 'Web Port');

        # Configure supervisorctl port
        my $portInt = int($cfg{nginx}{PORT});
        $portInt++;
        $cfg{supervisor}{SUPERVISORCTL_PORT} = $portInt;

        $cfg{nginx}{SSL} = '';
        $cfg{nginx}{SSL_CERT_LINE} = '';
        $cfg{nginx}{SSL_KEY_LINE} = '';
        $cfg{nginx}{INCLUDE_FORCE_SSL_LINE} = '';
    }

    # Set Instance Control Port.
    my $supervisorPortInt = int($cfg{supervisor}{SUPERVISORCTL_PORT});
    $supervisorPortInt++;
    $cfg{instance_manager}{INSTANCECTL_PORT} = $supervisorPortInt;
    
    # REDIS_HOST
    input('redis', 'REDIS_HOST', 'Redis Host');
}

sub merge_defaults {

    if (exists($cfg{laravel}{APP_URL})) {
        if (!exists($cfg{laravel}{SESSION_DOMAIN})) {
            $cfg{laravel}{SESSION_DOMAIN} = $cfg{laravel}{APP_URL};
        }

        if (!exists($cfg{laravel}{SANCTUM_STATEFUL_DOMAINS})) {
            $cfg{laravel}{SANCTUM_STATEFUL_DOMAINS} = $cfg{laravel}{APP_URL};
        }
    }

    if (!exists($cfg{supervisor}{SUPERVISORCTL_USER})) {
        $cfg{supervisor}{SUPERVISORCTL_USER} = $ENV{"LOGNAME"};
        $cfg{laravel}{SUPERVISORCTL_USER} = $ENV{"LOGNAME"};
    }

    if (!exists($cfg{supervisor}{SUPERVISORCTL_SECRET})) {
        $cfg{supervisor}{SUPERVISORCTL_SECRET} = $secret;
        $cfg{laravel}{SUPERVISORCTL_SECRET} = $secret;
    }

    if (!exists($cfg{instance_manager}{INSTANCECTL_USER})) {
        $cfg{instance_manager}{INSTANCECTL_USER} = $ENV{"LOGNAME"};
        $cfg{laravel}{INSTANCECTL_USER} = $ENV{"LOGNAME"};
    }

    if (!exists($cfg{instance_manager}{INSTANCECTL_SECRET})) {
        $cfg{instance_manager}{INSTANCECTL_SECRET} = $secret;
        $cfg{laravel}{INSTANCECTL_SECRET} = $secret;
    }

    if (!exists($cfg{laravel}{LOG_URI})) {
        $cfg{laravel}{LOG_URI} = $errorLog;
    }

    if (!exists($cfg{laravel}{DOWNLOAD_DIR})) {
        $cfg{laravel}{DOWNLOAD_DIR} = $downloadDir;
    }

    if (!exists($cfg{laravel}{CACHE_DIR})) {
        $cfg{laravel}{CACHE_DIR} = $cacheDir;
    }

    if (!exists($cfg{laravel}{LOG_DIR})) {
        $cfg{laravel}{LOG_DIR} = $logDir;
    }

    if (!exists($cfg{nginx}{USER})) {
        $cfg{laravel}{USER} = $ENV{"LOGNAME"};
        $cfg{nginx}{USER} = $ENV{"LOGNAME"};
    }

    if (!exists($cfg{nginx}{LOG})) {
        $cfg{laravel}{LOG} = $errorLog;
        $cfg{nginx}{LOG} = $errorLog;
    }

    if (!exists($cfg{nginx}{DIR})) {
        $cfg{laravel}{DIR} = $applicationRoot;
        $cfg{nginx}{DIR} = $applicationRoot;
    }

    if (!exists($cfg{nginx}{VAR})) {
        $cfg{laravel}{VAR} = $varDir;
        $cfg{nginx}{VAR} = $varDir;
    }

    if (!exists($cfg{nginx}{ETC})) {
        $cfg{laravel}{ETC} = $etcDir;
        $cfg{nginx}{ETC} = $etcDir;
    }

    if (!exists($cfg{nginx}{WEB})) {
        $cfg{laravel}{WEB} = $webDir;
        $cfg{nginx}{WEB} = $webDir;
    }

    if (!exists($cfg{nginx}{SRC})) {
        $cfg{laravel}{SRC} = $srcDir;
        $cfg{nginx}{SRC} = $srcDir;
    }

    if (!exists($cfg{nginx}{SESSION_SECRET})) {
        $cfg{nginx}{SESSION_SECRET} = $secret;
    }
}

sub input {
    my ($varDomain, $varName, $promptText) = @_;
    my $default = $defaults{$varDomain}{$varName};

    if ($cfg{$varDomain}{$varName} ne '') {
        $default = $cfg{$varDomain}{$varName};
    }

    my $answer = prompt('x', "$promptText:", $varName, $default);

    # Translating the none response to an empty string.
    # This avoids the akward experience of showing the user a default of: ""
    # "default none" is a better user exerience for the cli.
    if ($answer eq 'none') {
        $answer = '';
    }

    $cfg{$varDomain}{$varName} = $answer;
}

sub input_boolean {
    my ($varDomain, $varName, $promptText) = @_;
    my $default = 'no';

    if ($cfg{$varDomain}{$varName} eq 'true') {
        $default = 'yes';
    } elsif ($defaults{$varDomain}{$varName} eq 'true') {
        $default = 'yes';
    }

    my $answer = prompt('y', "$promptText:", $varName, $default);

    if ($answer eq 'yes') {
        $cfg{$varDomain}{$varName} = 'true';
    } else {
        $cfg{$varDomain}{$varName} = 'false';
    }
}
