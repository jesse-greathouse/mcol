#!/usr/bin/env perl
package Mcol::Bootstrap::OpenSUSE;

use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

my @pkgs = qw(
  gcc make pkg-config libopenssl-devel perl-App-cpanminus curl tar unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for openSUSE...\n";
    system('sudo','zypper','refresh');
    command_result($?, $!, "Refreshed repos...", ['zypper','refresh']);

    my @need;
    for my $p (@pkgs) {
        system("rpm -q $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('sudo','zypper','--non-interactive','install',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
