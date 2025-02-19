#!/usr/bin/perl

package Mcol::System;
use strict;
use warnings;
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

    # Retrieve the system CPU load for the last 1 minute (DCPU_LOAD_LAST_01)
    my $load = $cpu->load(DCPU_LOAD_LAST_01);

    # Get the total number of CPU threads (logical CPUs)
    my $cpu_count = $cpu->count;

    # If there's no load or invalid load value, default to using all available threads
    if (defined $load && $load =~ /^\d+(\.\d+)?$/) {
        # Calculate the inverse percentage of load (1 - load) to get the proportion of available CPU capacity
        my $available_capacity = 1 - $load;

        # The max CPU threads to use would be the available capacity multiplied by the total CPU count
        my $max_threads = int($available_capacity * $cpu_count);

        # Ensure that at least one CPU thread is used
        $max_threads = 1 if $max_threads < 1;

        return $max_threads;
    } else {
        # If load is unavailable or invalid, default to using all CPU threads
        return $cpu_count;
    }
}

1;
