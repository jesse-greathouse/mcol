#!/usr/bin/perl

package Mcol::PacketSearch;
use strict;
use File::Basename;
use Cwd qw(getcwd abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(packet_search);

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

# Searches a network channel for a string.
sub packet_search {
    my ($network, $channel, $search) = @ARGV;

    if (not defined $network) {
        die "Argument: \"network\" is required.";
    }

    if (not defined $channel) {
        die "Argument: \"channel\" is required.";
    }

    if (not defined $search) {
        die "Argument: \"search\" is required.";
    }

    my @cmd = ("$optDir/php/bin/php");
    push @cmd, "$srcDir/artisan";
    push @cmd, 'mcol:packet-search';
    push @cmd, $network;
    push @cmd, $channel;
    push @cmd, $search;
    system(@cmd);
}
