#!/usr/bin/perl

package Mcol::Utility;
use strict;
use Exporter 'import';
use Errno;

our @EXPORT_OK = qw(
  command_result
  get_operating_system
  read_file
  write_file
  trim
  splash
  str_replace_in_file
  generate_rand_str
  is_pid_running
);

# ====================================
#    Subroutines below this point
# ====================================

# Trim the whitespace from a string.
sub  trim { my $s = shift; $s =~ s/^\s+|\s+$//g; return $s };

# Returns string associated with operating system.
sub get_operating_system {
    my %osNames = (
        MSWin32 => 'Win32',
        NetWare => 'Win32',
        symbian => 'Win32',
        darwin  => 'MacOS'
    );

    # Check for Linux-based OS and delegate to a separate function
    if ($^O eq 'linux') {
        return get_linux_distribution();
    }

    # If $^O is not found in the hash, die with an error message
    die "Unsupported operating system: $^O\n" unless exists $osNames{$^O};

    return $osNames{$^O};
}

# Detects the Linux distribution.
sub get_linux_distribution {
    # Arrays for different types of distribution identification
    my @os_release_dists = (
        { pattern => 'centos',          name => 'CentOS' },
        { pattern => 'ubuntu',          name => 'Ubuntu' },
        { pattern => 'fedora',          name => 'Fedora' },
        { pattern => 'debian',          name => 'Debian' },
        { pattern => 'opensuse',        name => 'OpenSUSE' },
        { pattern => 'arch',            name => 'Arch' },
        { pattern => 'alpine',          name => 'Alpine' },
        { pattern => 'gentoo',          name => 'Gentoo' },
        { pattern => 'openmandriva',    name => 'Mandriva' },
    );

    # Check /etc/os-release first (most modern distros)
    if (open my $fh, '<', '/etc/os-release') {
        while (my $line = <$fh>) {
            foreach my $dist (@os_release_dists) {
                if ($line =~ /^ID=$dist->{pattern}/) {
                    return $dist->{name};
                }
            }
        }
    }

    # Fallback to other common files
    if (-e '/etc/lsb-release') {
        if (open my $fh, '<', '/etc/lsb-release') {
            while (my $line = <$fh>) {
                foreach my $dist (@os_release_dists) {
                    if ($line =~ /DISTRIB_ID=$dist->{name}/i) {
                        return $dist->{name};
                    }
                }
            }
        }
    }

    if (-e '/etc/redhat-release') {
        if (open my $fh, '<', '/etc/redhat-release') {
            while (my $line = <$fh>) {
                foreach my $dist (@os_release_dists) {
                    if ($line =~ /$dist->{name}/i) {
                        return $dist->{name};
                    }
                }
            }
        }
    }

    # Check /etc/debian_version for Debian-based distros
    if (-e '/etc/debian_version') {
        return 'Debian';
    }

    # Use uname as a last resort (generic fallback)
    my $uname = `uname -a`;
    foreach my $dist (@os_release_dists) {
        if ($uname =~ /$dist->{name}/i) {
            return $dist->{name};
        }
    }

    # If no distribution was found, throw an error
    die "Unable to determine Linux distribution.\n";
}

sub str_replace_in_file {
  my ($string, $replacement, $file) = @_;
  my $data = read_file($file);
  $data =~ s/\Q$string/$replacement/g;
  write_file($file, $data);
}

sub read_file {
    my ($filename) = @_;

    open my $in, '<:encoding(UTF-8)', $filename or die "Could not open '$filename' for reading $!";
    local $/ = undef;
    my $all = <$in>;
    close $in;

    return $all;
}

sub write_file {
    my ($filename, $content) = @_;

    open my $out, '>:encoding(UTF-8)', $filename or die "Could not open '$filename' for writing $!";;
    print $out $content;
    close $out;

    return;
}

sub command_result {
    my ($exit, $err, $operation_str, @cmd) = @_;

    if ($exit == -1) {
        print "failed to execute: $err \n";
        exit $exit;
    }
    elsif ($exit & 127) {
        printf "child died with signal %d, %s coredump\n",
            ($exit & 127),  ($exit & 128) ? 'with' : 'without';
        exit $exit;
    }
    else {
        printf "$operation_str exited with value %d\n", $exit >> 8;
    }
}

sub generate_rand_str {
    my ($length) = @_;

    if (!defined $length) {
        $length = 64;
    }

    my @set = ('0' ..'9', 'A' .. 'F');
    my $str = join '' => map $set[rand @set], 1 .. $length;
    return $str;
}

sub is_pid_running {
    my ($pidFile) = @_;

    open my $fh, '<', $pidFile or die "Can't open $pidFile $!";
    my $pid = do { local $/; <$fh> };

    my $not_running=(!kill(0,$pid) && $! == Errno::ESRCH);

    return(!$not_running);
}

# Prints a spash screen message.
sub splash {
  print (''."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Thank you for choosing mcol                                                          |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Copyright (c) 2023 Jesse Greathouse (https://github.com/jesse-greathouse/mcol)       |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| mcol is free software: you can redistribute it and/or modify it under the            |'."\n");
  print ('| terms of thethe Free Software Foundation, either version 3 of the License, or GNU    |'."\n");
  print ('| General Public License as published by (at your option) any later version.           |'."\n");
  print ('|                                                                                      |'."\n");
  print ('| mcol is distributed in the hope that it will be useful, but WITHOUT ANY              |'."\n");
  print ('| WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A      |'."\n");
  print ('| PARTICULAR PURPOSE.  See the GNU General Public License for more details.            |'."\n");
  print ('|                                                                                      |'."\n");
  print ('| You should have received a copy of the GNU General Public License along with         |'."\n");
  print ('| mcol. If not, see <http://www.gnu.org/licenses/>.                                    |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Author: Jesse Greathouse <jesse.greathouse@gmail.com>                                |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print (''."\n");
}

1;
