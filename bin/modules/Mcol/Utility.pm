#!/usr/bin/perl

package Mcol::Utility;
use strict;
use Exporter 'import';

our @EXPORT_OK = qw(
  command_result
  get_operating_system
  read_file
  write_file
  trim
  splash
  str_replace_in_file
  generate_rand_str
);

1;

# ====================================
#    Subroutines below this point
# ====================================

# Trim the whitespace from a string.
sub  trim { my $s = shift; $s =~ s/^\s+|\s+$//g; return $s };

# Returns string associated with operating system.
sub get_operating_system {
    # From the source code of File::Spec
    my %osNames = (
        MSWin32 => 'Win32',
        os2     => 'OS2',
        VMS     => 'VMS',
        NetWare => 'Win32', # Yes, File::Spec::Win32 works on NetWare.
        symbian => 'Win32', # Yes, File::Spec::Win32 works on symbian.
        dos     => 'OS2',   # Yes, File::Spec::OS2 works on DJGPP.
        cygwin  => 'Cygwin',
        amigaos => 'AmigaOS',
        linux   => 'Ubuntu',
        darwin  => 'MacOS');

    return $osNames{$^O} or 'Ubuntu';
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

# Prints a spash screen message.
sub splash {
  print (''."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Thank you for choosing mcol                                                    |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Copyright (c) 2023 Jesse Greathouse (https://github.com/jesse-greathouse/mcol)       |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| mcol is free software: you can redistribute it and/or modify it under the      |'."\n");
  print ('| terms of thethe Free Software Foundation, either version 3 of the License, or GNU    |'."\n");
  print ('| General Public License as published by (at your option) any later version.           |'."\n");
  print ('|                                                                                      |'."\n");
  print ('| mcol is distributed in the hope that it will be useful, but WITHOUT ANY        |'."\n");
  print ('| WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A      |'."\n");
  print ('| PARTICULAR PURPOSE.  See the GNU General Public License for more details.            |'."\n");
  print ('|                                                                                      |'."\n");
  print ('| You should have received a copy of the GNU General Public License along with         |'."\n");
  print ('| mcol. If not, see <http://www.gnu.org/licenses/>.                              |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print ('| Author: Jesse Greathouse <jesseg@wheelpros.com>                                      |'."\n");
  print ('+--------------------------------------------------------------------------------------+'."\n");
  print (''."\n");
}
