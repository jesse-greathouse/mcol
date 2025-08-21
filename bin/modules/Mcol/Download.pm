#!/usr/bin/env perl

package Mcol::Download;
use strict;
use warnings;
use File::Basename;
use Cwd qw(abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(download);

warn $@ if $@; # handle exception

# Folder Paths
my $applicationRoot = abs_path(dirname(abs_path(__FILE__)) . '/../../../');
my $srcDir = "$applicationRoot/src";
my $optDir = "$applicationRoot/opt";

# Queues a packet for download.
sub download {
    my ($packetId) = @ARGV;

    die "A Numeric Packet ID is required (e.g., download #)" unless defined $packetId;

    system("$optDir/php/bin/php", "$srcDir/artisan", 'mcol:download', $packetId);
}

1;
