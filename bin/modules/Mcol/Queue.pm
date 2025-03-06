#!/usr/bin/perl

package Mcol::Queue;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(command_result is_pid_running splash);
use Term::ANSIScreen qw(cls);

our @EXPORT_OK = qw(queue_start queue_restart queue_stop);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $srcDir = "$applicationRoot/src";
my $etcDir = "$applicationRoot/etc";
my $optDir = "$applicationRoot/opt";
my $varDir = "$applicationRoot/var";
my $logDir = "$varDir/log";
my $supervisorConfig = "$etcDir/supervisor/queue-manager.conf";
my $supervisorLogFile = "$logDir/queue-manager.log";
my $pidFile = "$varDir/pid/queue-manager.pid";

# ====================================
#    Subroutines below this point
# ====================================

# Runs the queue manager supervisor.
sub queue_start {
    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'start', 'all');
        system(@cmd);
        command_result($?, $!, 'Start all Queues...', \@cmd);
    } else {
        start_daemon();
    }

    print "Monitor Queue logging with: \ntail -f $supervisorLogFile\n";
}

# Restarts the queue manager supervisor.
sub queue_restart {
    my $output = "The Queue Daemon was not found.\n";

    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'restart', 'all');
        system(@cmd);

        $output = "The Queue Daemon was signalled to restart all queues.\n";
        command_result($?, $!, 'Restart all queues...', \@cmd);
    }

    print $output;
}

# Stops the queue manager supervisor.
sub queue_stop {
    my $output = "The Queue Daemon was not found.\n";

    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'stop', 'all');
        system(@cmd);

        $output = "The Queue Daemon was signalled to stop all queues.\n";
        command_result($?, $!, 'Stop all queues...', \@cmd);
    }

    print $output;
}

# Starts the supervisor daemon.
sub start_daemon {
    @ENV{qw(DIR ETC OPT VAR SRC LOG_DIR)} =
        ($applicationRoot, $etcDir, $optDir, $varDir, $srcDir, $logDir);

    print "Starting Queue Daemon...\n";

    system('supervisord', '-c', $supervisorConfig);

    sleep(5);
    print_output();
}

sub print_output {
    cls();
    splash();
    system('tail', '-n', '10', $supervisorLogFile);
}

1;
