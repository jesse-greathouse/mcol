#!/usr/bin/env perl

package Mcol::Bootstrap::Rocky;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

# Minimal toolchain for XS builds
my @pkgs = qw(
  gcc make pkgconf openssl-devel perl-App-cpanminus curl tar unzip
  git zip xz rsync which jq python3
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for Fedora...\n";
    system('sudo','dnf','makecache');
    command_result($?, $!, "Updated package index...", ['dnf','makecache']);

    my @need;
    for my $p (@pkgs) {
        system("rpm -q $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('sudo','dnf','install','-y',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
