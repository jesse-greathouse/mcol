#!/usr/bin/perl

package Mcol::Queue;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);

our @EXPORT_OK = qw(queue);

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

1;

# ====================================
#    Subroutines below this point
# ====================================

# Runs the queue manager supervisor.
sub queue {
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

    print "Starting queue Daemon...\n";

    my @cmd = ('supervisord');
    push @cmd, '-c';
    push @cmd, $supervisorConfig;
    system(@cmd);
    
    sleep(5);
    print_output();

    print "Monitor queue logging with: \ntail -f $logDir/supervisord.log\n";
}

sub print_output {
    my @cmd = ('tail');
    push @cmd, '-n';
    push @cmd, '25';
    push @cmd, $supervisorLogFile;
    system(@cmd);
}
