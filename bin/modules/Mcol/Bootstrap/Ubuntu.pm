#!/usr/bin/env perl

package Mcol::Bootstrap::Ubuntu;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

my @pkgs = qw(
  build-essential pkg-config libssl-dev cpanminus curl tar unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for Ubuntu...\n";
    system('sudo','apt-get','update');
    command_result($?, $!, "Updated package index...", ['apt-get','update']);

    my @need;
    for my $p (@pkgs) {
        system("dpkg -s $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('sudo','apt-get','install','-y',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
