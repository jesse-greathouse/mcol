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

sub _ensure_xcode_clt {
    # Is xcode-select available?
    system('bash','-lc','command -v xcode-select >/dev/null 2>&1');
    if ($? != 0) {
        print "\n❌ Apple Command Line Tools not detected (xcode-select missing).\n\n";
        print "To install:\n";
        print "  • Run:  xcode-select --install\n";
        print "  • If that says “Unable to find…”, open System Settings → Software Update and install “Command Line Tools”.\n";
        print "  • Or install full Xcode (Mac App Store) and set it: sudo xcode-select -s /Applications/Xcode.app/Contents/Developer\n\n";
        die "Aborting bootstrap until Command Line Tools are installed.\n";
    }

    # Does xcode-select point to a real developer dir?
    chomp(my $devdir = `xcode-select -p 2>/dev/null`);
    if (!$devdir || !-d $devdir) {
        print "\n❌ xcode-select is present but no active developer directory is set.\n";
        print "Fix with: xcode-select --install\n\n";
        die "Aborting bootstrap until a developer directory is set.\n";
    }

    # Is clang reachable?
    system('bash','-lc','command -v clang >/dev/null 2>&1');
    if ($? != 0) {
        print "\n❌ clang not found on PATH (CLT dir: $devdir).\n";
        print "If you have Xcode/CLT installed, try:\n";
        print "  sudo xcode-select -switch \"$devdir\"\n\n";
        die "Aborting bootstrap until clang is available.\n";
    }

    return 1;
}


sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for macOS...\n";

    # Ensure Xcode Command Line Tools are installed & usable
    _ensure_xcode_clt();

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
