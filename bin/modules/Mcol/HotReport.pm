#!/usr/bin/perl

package Mcol::HotReport;
use strict;
use File::Basename;
use Cwd qw(getcwd abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(hot_report);

warn $@ if $@; # handle exception

# Folder Paths
my $binDir = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($binDir));
my $srcDir = "$applicationRoot/src";
my $optDir = "$applicationRoot/opt";

1;

# ====================================
#    Subroutines below this point
# ====================================

# Queries a network channel for a hot searches.
sub hot_report {
    my ($network, $channel) = @ARGV;

    if (not defined $network) {
        die "Argument: \"network\" is required.";
    }

    if (not defined $channel) {
        die "Argument: \"channel\" is required.";
    }

    my @cmd = ("$optDir/php/bin/php");
    push @cmd, "$srcDir/artisan";
    push @cmd, 'mcol:hot';
    push @cmd, $network;
    push @cmd, $channel;
    system(@cmd);
}
