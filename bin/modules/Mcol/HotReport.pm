#!/usr/bin/env perl

package Mcol::HotReport;
use strict;
use warnings;
use File::Basename;
use Cwd qw(abs_path);
use Exporter 'import';

our @EXPORT_OK = qw(hot_report);

warn $@ if $@; # handle exception

# Folder Paths
my $applicationRoot = abs_path(dirname(abs_path(__FILE__)) . '/../../../');
my $srcDir = "$applicationRoot/src";
my $optDir = "$applicationRoot/opt";

# Queries a network channel for a hot searches.
sub hot_report {
    my ($network, $channel) = @ARGV;

    die "Argument: \"network\" is required." unless defined $network;
    die "Argument: \"channel\" is required." unless defined $channel;

    system("$optDir/php/bin/php", "$srcDir/artisan", 'mcol:hot', $network, $channel);
}

1;
