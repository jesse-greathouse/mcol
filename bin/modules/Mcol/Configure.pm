#!/usr/bin/perl

package Mcol::Configure;
use strict;
use File::Basename;
use Cwd qw(getcwd abs_path);
use List::Util 1.29 qw( pairs );
use Exporter 'import';
use Scalar::Util qw(looks_like_number);
use Term::Prompt;
use Term::Prompt qw(termwrap);
use Term::ANSIScreen qw(cls);
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(
    get_configuration
    save_configuration
    parse_env_file
    write_env_file
);
use Mcol::Utility qw(splash generate_rand_str);

use Data::Dumper;

our @EXPORT_OK = qw(configure);

warn $@ if $@; # handle exception

my $bin = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($bin));
my $lumenEnvFile = "$applicationRoot/src/.env";
my %cfg = get_configuration();
my $defaultKey = generate_rand_str();

my %defaults = (
    lumen => {
        APP_NAME                => 'WP-Toolkit',
        APP_ENV                 => 'local',
        APP_KEY                 => $defaultKey,
        APP_DEBUG               => 'true',
        APP_URL                 => 'http://localhost',
        APP_TIMEZONE            => 'UTC',
        LOG_CHANNEL             => 'stack',
        LOG_SLACK_WEBHOOK_URL   => 'none',
        DB_CONNECTION           => 'mysql',
        DB_HOST                 => '127.0.0.1',
        DB_PORT                 => '3306',
        DB_DATABASE             => 'mcol',
        DB_USERNAME             => 'wheelpros',
        DB_PASSWORD             => 'wheelpros',
        CACHE_DRIVER            => 'file',
        QUEUE_CONNECTION        => 'sync',
        WP_FITMENT_API_TOKEN    => 'none',
        WP_FITMENT_API_HOST     => 'none',
        WP_FITMENT_API_DEBUG    => '1',
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
    print (' This will create your application\'s runtime configuration'."\n");
    print ('================================================================='."\n");
    print (''."\n");

    request_user_input();
    save_configuration(%cfg);
    write_lumen_env();
}

# Runs the user through a series of setup config questions.
# Confirms the answers.
# Returns Hash Table
sub request_user_input {
    merge_lumen_env();

    # APP_NAME
    input('lumen', 'APP_NAME', 'App Name');
    
    # APP_ENV
    input('lumen', 'APP_ENV', 'App Environment');
    
    # APP_KEY
    input('lumen', 'APP_KEY', 'App Key (Security String)');
    
    # APP_DEBUG
    input_boolean('lumen', 'APP_DEBUG', 'App Debug Flag');
    
    # APP_URL
    input('lumen', 'APP_URL', 'App Url');
    
    # APP_TIMEZONE
    input('lumen', 'APP_TIMEZONE', 'App Timezone');
    
    # LOG_CHANNEL
    input('lumen', 'LOG_CHANNEL', 'Log Channel');
    
    # LOG_SLACK_WEBHOOK_URL
    input('lumen', 'LOG_SLACK_WEBHOOK_URL', 'Slack Webhook Url');
    
    # DB_CONNECTION
    input('lumen', 'DB_CONNECTION', 'Database Connection (driver)');
    
    # DB_HOST
    input('lumen', 'DB_HOST', 'Database Hostname');
    
    # DB_PORT
    input('lumen', 'DB_PORT', 'Database Port');
    
    # DB_DATABASE
    input('lumen', 'DB_DATABASE', 'Database Schema Name');
    
    # DB_USERNAME
    input('lumen', 'DB_USERNAME', 'Database Username');
    
    # DB_PASSWORD
    input('lumen', 'DB_PASSWORD', 'Database Password');
    
    # CACHE_DRIVER
    input('lumen', 'CACHE_DRIVER', 'Cache Driver');
    
    # QUEUE_CONNECTION
    input('lumen', 'QUEUE_CONNECTION', 'Queue Connection');
    
    # WP_FITMENT_API_TOKEN
    input('lumen', 'WP_FITMENT_API_TOKEN', 'Fitment API Token');
    
    # WP_FITMENT_API_HOST
    input('lumen', 'WP_FITMENT_API_HOST', 'Fitment API Host');
    
    # WP_FITMENT_API_DEBUG
    input_boolean('lumen', 'WP_FITMENT_API_DEBUG', 'Fitment API Debug Flag');
}

sub merge_lumen_env {
    if (-e $lumenEnvFile) {
        my $env = parse_env_file($lumenEnvFile);

        foreach my $key (keys %$env) {
            $cfg{lumen}{$key} = $env->{$key};
        }

        save_configuration(%cfg);
    }
}

sub write_lumen_env{
    write_env_file($lumenEnvFile, %{$cfg{lumen}});
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
