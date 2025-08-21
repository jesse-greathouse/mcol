#!/usr/bin/env perl

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
sub how_many_threads_should_i_use {
    my $info = Sys::Info->new;
    my $cpu  = $info->device('CPU');

    my $load      = $cpu->load(DCPU_LOAD_LAST_01);
    my $cpu_count = $cpu->count;

    if (defined $load && $load =~ /^\d+(\.\d+)?$/) {
        my $available_capacity = 1 - ($load / $cpu_count);
        $available_capacity = 0 if $available_capacity < 0;
        $available_capacity = 1 if $available_capacity > 1;

        my $max_threads = ceil($available_capacity * $cpu_count);
        $max_threads = 1           if $max_threads < 1;
        $max_threads = $cpu_count if $max_threads > $cpu_count;

        return $max_threads;
    } else {
        return $cpu_count;
    }
}

1;
