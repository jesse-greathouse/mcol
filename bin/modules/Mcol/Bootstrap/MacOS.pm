#!/usr/bin/env perl

package Mcol::Bootstrap::MacOS;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

# Homebrew packages; CLT must be present for any compiles.
my @brew = qw(
  openssl@3 pkg-config cpanminus
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for macOS...\n";

    # Ensure Xcode Command Line Tools are installed
    system('bash','-lc','xcode-select -p >/dev/null 2>&1');
    if ($? != 0) {
        print "Xcode Command Line Tools are not installed.\n";
        print "Run: xcode-select --install\n";
        die "Aborting bootstrap until CLT is installed.\n";
    }

    # Ensure Homebrew exists
    system('bash','-lc','command -v brew >/dev/null 2>&1');
    if ($? != 0) {
        die "Homebrew is required on macOS. Install from https://brew.sh and re-run bootstrap.\n";
    }

    system('brew','update');
    command_result($?, $!, "Updated Homebrew...", ['brew','update']);

    my @need;
    for my $p (@brew) {
        system("brew list $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        system('brew','install',@need);
        command_result($?, $!, "Installed bootstrap toolchain...", ['brew','install',@need]);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
