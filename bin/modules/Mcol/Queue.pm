#!/usr/bin/perl

package Mcol::Queue;
use strict;
use warnings;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);;
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(command_result is_pid_running splash);
use Term::ANSIScreen qw(cls);

our @EXPORT_OK = qw(queue_start queue_restart queue_stop queue_kill);

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

# Get Configuration
my %cfg = get_configuration();

# ====================================
#    Subroutines below this point
# ====================================

# Runs the queue manager supervisor.
sub queue_start {
    if ( -e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'start', 'all');
        system(@cmd);
        command_result($?, $!, 'Start all Queues...', \@cmd);
    } else {
        start_daemon();
    }
}

# Restarts the queue manager supervisor.
sub queue_restart {
    my $output = "The Queue Daemon was not found.\n";

    if ( -e $pidFile && is_pid_running($pidFile)) {
        stop_bazel();

        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'restart', 'all');
        system(@cmd);

        $output = "The Queue Daemon was signalled to restart all Queues.\n";
        command_result($?, $!, 'Restart all Queues...', \@cmd);
    }

    print $output;
}

# Stops the queue manager supervisor.
sub queue_stop {
    my $output = "The Queue Daemon was not found.\n";

    if ( -e $pidFile && is_pid_running($pidFile)) {
        stop_bazel();

        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'stop', 'all');
        system(@cmd);

        $output = "The Queue Daemon was signalled to stop all Queues.\n";
        command_result($?, $!, 'Stop all Queues...', \@cmd);
    }

    print $output;
}

# Kills the supervisor daemon (Useful to change configuration.).
# Usually you just want to stop, start, restart.
# Killing the daemon will shut off supervisor controls.
# Only use this to change a configuration file setting.
sub queue_kill {
    my $output = "The Queue Daemon was not found.\n";

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
    @ENV{qw(DIR ETC OPT VAR SRC LOG_DIR APP_NAME RABBITMQ_HOST RABBITMQ_NODENAME
            RABBITMQ_PORT RABBITMQ_USERNAME RABBITMQ_PASSWORD RABBITMQ_VHOST)} =
        ($applicationRoot, $etcDir, $optDir, $varDir, $srcDir, $logDir,
        $cfg{laravel}{APP_NAME}, $cfg{rabbitmq}{RABBITMQ_HOST}, $cfg{rabbitmq}{RABBITMQ_NODENAME},
        $cfg{rabbitmq}{RABBITMQ_PORT}, $cfg{rabbitmq}{RABBITMQ_USERNAME}, $cfg{rabbitmq}{RABBITMQ_PASSWORD}, $cfg{rabbitmq}{RABBITMQ_VHOST});

    print "Starting Queue Daemon...\n";

    system('supervisord', '-c', $supervisorConfig);

    sleep(5);
    print_output();
}

sub print_output {
    cls();
    splash();
    system('tail', '-n', '15', $supervisorLogFile);
}

sub stop_bazel {
    my $bazelDir = "$optDir/rabbitmq";
    my $originalDir = getcwd();
    my $nodeName = $cfg{rabbitmq}{RABBITMQ_NODENAME};

    # Ensure $originalDir is defined before proceeding
    if (!defined $originalDir) {
        die "Error: getcwd() failed to return a directory path\n";
    }

    system(('bash', '-c', 'PATH="' . $binDir . ':$PATH" rabbitmqctl --node='. $nodeName . ' shutdown'));
    command_result($?, $!, 'Shut down rabbitmq node...', 'rabbitmqctl --node='. $nodeName . ' shutdown');

    chdir glob($bazelDir) or die "Failed to change to directory $bazelDir: $!";

    system(('bash', '-c', 'PATH="' . $binDir . ':$PATH" bazel shutdown'));
    command_result($?, $!, 'Shut down Bazel...', 'bazel shutdown');

    chdir glob($originalDir) or die "Failed to change back to original directory $originalDir: $!";
}

1;
