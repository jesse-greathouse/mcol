#!/usr/bin/perl

package Mcol::Web;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(command_result is_pid_running splash);
use Term::ANSIScreen qw(cls);

our @EXPORT_OK = qw(web_start web_restart web_stop);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $srcDir = "$applicationRoot/src";
my $webDir = "$srcDir/public";
my $etcDir = "$applicationRoot/etc";
my $optDir = "$applicationRoot/opt";
my $tmpDir = "$applicationRoot/tmp";
my $varDir = "$applicationRoot/var";
my $cacheDir = "$varDir/cache";
my $logDir = "$varDir/log";
my $user = $ENV{"LOGNAME"};
my $errorLog = "$logDir/error.log";
my $supervisorConfig = "$etcDir/supervisor/conf.d/supervisord.conf";
my $supervisorLogFile = "$logDir/supervisord.log";
my $pidFile = "$varDir/pid/supervisord.pid";

# Get Configuration
my %cfg = get_configuration();

# ====================================
#    Subroutines below this point
# ====================================

# Runs the web manager supervisor.
sub web_start {
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'start';
            push @cmd, 'all';
            system(@cmd);

            command_result($?, $!, 'Start all web services...', \@cmd);
        } else {
            start_daemon();
        }
    } else {
        start_daemon();
    }

    print "Monitor Web logging with: \ntail -f $supervisorLogFile\n";
}

# Restarts the web manager supervisor.
sub web_restart {
    my $output = "The Web Daemon was not found.\n";
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'restart';
            push @cmd, 'all';
            system(@cmd);

            $output = "The Web Daemon was signalled to restart all web services.\n";
            command_result($?, $!, 'Restart all web services...', \@cmd);

        }
    }

    print $output;
}

# Stops the web manager supervisor.
sub web_stop {
    my $output = "The Web Daemon was not found.\n";
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'stop';
            push @cmd, 'all';
            system(@cmd);

            $output = "The Web Daemon was signalled to stop all web services.\n";
            command_result($?, $!, 'Stop all web services...', \@cmd);
        }
    }

    print $output;
}

sub start_daemon {
    $ENV{'USER'} = $user;
    $ENV{'BIN'} = $binDir;
    $ENV{'DIR'} = $applicationRoot;
    $ENV{'ETC'} = $etcDir;
    $ENV{'OPT'} = $optDir;
    $ENV{'TMP'} = $tmpDir;
    $ENV{'VAR'} = $varDir;
    $ENV{'SRC'} = $srcDir;
    $ENV{'WEB'} = $webDir;
    $ENV{'CACHE_DIR'} = $cacheDir;
    $ENV{'LOG_DIR'} = $logDir;
    $ENV{'PORT'} = $cfg{nginx}{PORT};
    $ENV{'SSL'} = $cfg{nginx}{IS_SSL};
    $ENV{'REDIS_HOST'} = $cfg{redis}{REDIS_HOST};

    # Clear the log
    if (-e $supervisorLogFile) {
        unlink($supervisorLogFile)  or die "Can't delete $supervisorLogFile: $!\n";
    }

    print "Starting web Daemon...\n";

    my @cmd = ('supervisord');
    push @cmd, '-c';
    push @cmd, $supervisorConfig;
    system(@cmd);

    sleep(5);
    print_output();
}

sub print_output {
    cls();
    splash();

    my @cmd = ('tail');
    push @cmd, '-n';
    push @cmd, '10';
    push @cmd, $supervisorLogFile;
    system(@cmd);
}

1;
