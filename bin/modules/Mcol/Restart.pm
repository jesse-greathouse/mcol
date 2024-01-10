#!/usr/bin/perl

package Mcol::Restart;
use strict;
use File::Basename;
use Getopt::Long;
use Cwd qw(getcwd abs_path);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Config qw(get_configuration);
use Mcol::Utility qw(read_file trim);

our @EXPORT_OK = qw(restart);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $etcDir = "$applicationRoot/etc";
my $supervisorConfig = "$etcDir/supervisor/conf.d/supervisord.service.conf";

1;

# ====================================
#    Subroutines below this point
# ====================================

# Performs the install routine.
sub restart {
    my @cmd = ('supervisorctl');
    push @cmd, '-c';
    push @cmd, $supervisorConfig;
    push @cmd, 'restart';
    push @cmd, 'all';
    system(@cmd);
}
