#!/usr/bin/perl

package Mcol::Instance;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Utility qw(command_result is_pid_running splash);
use Term::ANSIScreen qw(cls);

our @EXPORT_OK = qw(instance_start instance_restart instance_stop);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $srcDir = "$applicationRoot/src";
my $etcDir = "$applicationRoot/etc";
my $optDir = "$applicationRoot/opt";
my $varDir = "$applicationRoot/var";
my $logDir = "$varDir/log";
my $supervisorConfig = "$etcDir/supervisor/instance-manager.conf";
my $pidFile = "$varDir/pid/instance-manager.pid";
my $supervisorLogFile = "$logDir/instance-manager.log";

1;

# ====================================
#    Subroutines below this point
# ====================================

# Runs the instance manager supervisor.
sub instance_start {
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'start';
            push @cmd, 'all';
            system(@cmd);

            command_result($?, $!, 'Start all instances...', \@cmd);
        } else {
            start_daemon();
        }
    } else {
        start_daemon();
    }

    print "Monitor instance logging with: \ntail -f $logDir/supervisord.log\n";
}

# Restarts the instance manager supervisor.
sub instance_restart {
    my $output = "The instance Daemon was not found.\n";
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'restart';
            push @cmd, 'all';
            system(@cmd);

            $output = "The instance Daemon was signalled to restart all instances.\n";
            command_result($?, $!, 'Restart all instances...', \@cmd);
            
        }
    }

    print $output;
}

# Stops the instance manager supervisor.
sub instance_stop {
    my $output = "The instance Daemon was not found.\n";
    if (-e $pidFile) {
        if (is_pid_running($pidFile)) {
            my @cmd = ('supervisorctl');
            push @cmd, '-c';
            push @cmd, $supervisorConfig;
            push @cmd, 'stop';
            push @cmd, 'all';
            system(@cmd);

            $output = "The instance Daemon was signalled to stop all instances.\n";
            command_result($?, $!, 'Stop all instances...', \@cmd);
        }
    }

    print $output;
}

sub start_daemon {
    $ENV{'DIR'} = $applicationRoot;
    $ENV{'ETC'} = $etcDir;
    $ENV{'OPT'} = $optDir;
    $ENV{'VAR'} = $varDir;
    $ENV{'SRC'} = $srcDir;
    $ENV{'LOG_DIR'} = $logDir;

    # Clear the log
    if (-e $supervisorLogFile) {
        unlink($supervisorLogFile)  or die "Can't delete $supervisorLogFile: $!\n";
    }

    print "Starting instance Daemon...\n";

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
