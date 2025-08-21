#!/usr/bin/env perl
package Mcol::Bootstrap::Arch;

use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

my @pkgs = qw(
  base-devel pkgconf openssl cpanminus curl tar unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for Arch...\n";
    system('sudo','pacman','-Sy');
    command_result($?, $!, "Updated package index...", ['pacman','-Sy']);

    my @need;
    for my $p (@pkgs) {
        system("pacman -Qi $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('sudo','pacman','--noconfirm','-S',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
