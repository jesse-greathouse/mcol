#!/usr/bin/perl

package Mcol::System;
use strict;
use warnings;
use POSIX qw(ceil);
use Cwd qw(getcwd abs_path);
use Sys::Info;
use Sys::Info::Constants qw( :device_cpu );
use Exporter 'import';

our @EXPORT_OK = qw( how_many_threads_should_i_use );

# ====================================
# Subroutines
# ====================================

# This subroutine scales the number of threads to use based on system load.
# When the load is high, fewer threads will be used,
# and when the system is less loaded, more threads will be used.
# It defaults to using all available threads if the load cannot be determined.
sub how_many_threads_should_i_use {
    my $info = Sys::Info->new;
    my $cpu = $info->device('CPU');

    # Retrieve the system CPU load for the last 1 minute
    my $load = $cpu->load(DCPU_LOAD_LAST_01);

    # Get the total number of CPU threads (logical CPUs)
    my $cpu_count = $cpu->count;

    if (defined $load && $load =~ /^\d+(\.\d+)?$/) {
        # Normalize load to be relative to CPU count
        my $available_capacity = 1 - ($load / $cpu_count);

        # Ensure the available capacity is within a valid range (0 to 1)
        $available_capacity = 0 if $available_capacity < 0;
        $available_capacity = 1 if $available_capacity > 1;

        # Calculate max threads, ensuring we use at least 1
        my $max_threads = ceil($available_capacity * $cpu_count);
        $max_threads = 1 if $max_threads < 1;
        $max_threads = $cpu_count if $max_threads > $cpu_count;

        return $max_threads;
    } else {
        # If load is unavailable or invalid, default to using all CPU threads
        return $cpu_count;
    }
}

1;
