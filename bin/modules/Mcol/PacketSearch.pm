#!/usr/bin/perl

package Mcol::PacketSearch;
use strict;
use warnings;
use File::Basename;
use Cwd qw(abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(packet_search);

warn $@ if $@; # handle exception

# Folder Paths
my $applicationRoot = abs_path(dirname(abs_path(__FILE__)) . '/../../../');
my $srcDir = "$applicationRoot/src";
my $optDir = "$applicationRoot/opt";

# ====================================
#    Subroutines below this point
# ====================================

# Searches a network channel for a string.
sub packet_search {
    my ($network, $channel, $search) = @ARGV;

    die "Argument: \"network\" is required." unless defined $network;
    die "Argument: \"channel\" is required." unless defined $channel;
    die "Argument: \"search\" is required." unless defined $search;

    system("$optDir/php/bin/php", "$srcDir/artisan", 'mcol:packet-search', $network, $channel, $search);
}

1;
