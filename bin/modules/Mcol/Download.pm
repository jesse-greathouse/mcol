#!/usr/bin/perl

package Mcol::Download;
use strict;
use File::Basename;
use Cwd qw(getcwd abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(download);

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

# Queues a packet for download.
sub download {
    my ($packetId) = @ARGV;

    if (not defined $packetId) {
        die "A Numeric Packet ID is required (e.g.) download #";
    }

    my @cmd = ("$optDir/php/bin/php");
    push @cmd, "$srcDir/artisan";
    push @cmd, 'mcol:download';
    push @cmd, $packetId;
    system(@cmd);
}
