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
    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'start', 'all');
        system(@cmd);
        command_result($?, $!, 'Start all web services...', \@cmd);
    } else {
        start_daemon();
    }

    print "Monitor Web logging with: \ntail -f $supervisorLogFile\n";
}

# Restarts the web manager supervisor.
sub web_restart {
    my $output = "The Web Daemon was not found.\n";

    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'restart', 'all');
        system(@cmd);

        $output = "The Web Daemon was signalled to restart all web services.\n";
        command_result($?, $!, 'Restart all web services...', \@cmd);
    }

    print $output;
}

# Stops the web manager supervisor.
sub web_stop {
    my $output = "The Web Daemon was not found.\n";

    if (-e $pidFile && is_pid_running($pidFile)) {
        my @cmd = ('supervisorctl', '-c', $supervisorConfig, 'stop', 'all');
        system(@cmd);

        $output = "The Web Daemon was signalled to stop all web services.\n";
        command_result($?, $!, 'Stop all web services...', \@cmd);
    }

    print $output;
}

# Starts the supervisor daemon.
sub start_daemon {
    @ENV{qw(USER BIN DIR ETC OPT TMP VAR SRC WEB CACHE_DIR LOG_DIR PORT SSL REDIS_HOST)} =
        ($user, $binDir, $applicationRoot, $etcDir, $optDir, $tmpDir, $varDir, $srcDir, $webDir,
         $cacheDir, $logDir, $cfg{nginx}{PORT}, $cfg{nginx}{IS_SSL}, $cfg{redis}{REDIS_HOST});

    print "Starting web Daemon...\n";

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
