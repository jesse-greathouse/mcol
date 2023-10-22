#!/usr/bin/perl

package Mcol::Stop;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(read_file trim);

our @EXPORT_OK = qw(stop);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $etcDir = "$applicationRoot/etc";
my $varDir = "$applicationRoot/var";

my $supervisorConfig = "$etcDir/supervisor/conf.d/supervisord.service.conf";
my $pidFile = "$varDir/pid/supervisord.pid";

1;

# ====================================
#    Subroutines below this point
# ====================================

# Performs the install routine.
sub stop {
    my $pid = trim(read_file($pidFile));

    my @cmd = ('supervisorctl');
    push @cmd, '-c';
    push @cmd, $supervisorConfig;
    push @cmd, 'stop';
    push @cmd, 'all';
    system(@cmd);
    system("kill -SIGTERM -- $pid")
}
