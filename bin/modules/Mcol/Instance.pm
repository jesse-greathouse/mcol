#!/usr/bin/perl

package Mcol::Instance;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(command_result is_pid_running splash);
use Term::ANSIScreen qw(cls);

our @EXPORT_OK = qw(instance_start instance_restart instance_stop instance_kill);

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
my $supervisorLogFile = "$logDir/instance-manager.log";
my $pidFile = "$varDir/pid/instance-manager.pid";

# Get Configuration
my %cfg = get_configuration();

# ====================================
#    Subroutines below this point
# ====================================

# Runs the instance manager supervisor.
sub instance_start {
    if ( -e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'start', 'all');
        system(@cmd);
        command_result($?, $!, 'Start all instances...', \@cmd);
    } else {
        start_daemon();
    }
}

# Restarts the instance manager supervisor.
sub instance_restart {
    my $output = "The Instance Daemon was not found.\n";

    if ( -e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'restart', 'all');
        system(@cmd);

        $output = "The Instance Daemon was signalled to restart all Instances.\n";
        command_result($?, $!, 'Restart all Instances...', \@cmd);
    }

    print $output;
}

# Stops the instance manager supervisor.
sub instance_stop {
    my $output = "The Instance Daemon was not found.\n";

    if ( -e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'stop', 'all');
        system(@cmd);

        $output = "The Instance Daemon was signalled to stop all Instances.\n";
        command_result($?, $!, 'Stop all Instances...', \@cmd);
    }

    print $output;
}

# Kills the supervisor daemon (Useful to change configuration.).
# Usually you just want to stop, start, restart.
# Killing the daemon will shut off supervisor controls.
# Only use this to change a configuration file setting.
sub instance_kill {
    my $output = "The Instance Daemon was not found.\n";

    if ( -e $pidFile && is_pid_running($pidFile)) {
        open my $fh, '<', $pidFile or die "Can't open $pidFile: $!";
        my $content = do { local $/; <$fh> };
        close $fh;

        my ($pid) = $content =~ /^.*?(\d+).*?$/s or die "Invalid PID format in $pidFile\n";

        # First try a graceful shutdown
        if (kill 'TERM', $pid) {
            $output = "Sent SIGTERM to process $pid.\n";
        } else {
            warn "Failed to send SIGTERM to $pid, trying SIGKILL...\n";
            if (kill 9, $pid) {
                $output = "Forcefully killed process $pid with SIGKILL.\n";
            } else {
                warn "Failed to kill process $pid.\n";
            }
        }
    }

    print $output;
}

# Starts the supervisor daemon.
sub start_daemon {
    @ENV{qw(DIR ETC OPT VAR SRC LOG_DIR APP_NAME)} =
        ($applicationRoot, $etcDir, $optDir, $varDir, $srcDir, $logDir, $cfg{laravel}{APP_NAME});

    print "Starting Instance Daemon...\n";

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
