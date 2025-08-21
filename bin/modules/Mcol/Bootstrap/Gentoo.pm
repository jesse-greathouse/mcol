#!/usr/bin/env perl

package Mcol::Bootstrap::Gentoo;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

# Keep names aligned with your existing Gentoo usage style.
my @pkgs = qw(
  sys-devel/gcc sys-devel/make dev-util/pkgconfig dev-libs/openssl
  app-cpanminus net-misc/curl app-arch/tar app-arch/unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for Gentoo...\n";
    system('emerge','--sync');
    command_result($?, $!, "Synced Portage...", ['emerge','--sync']);

    # Very light presence check; qlist may not exist everywhere, so just try emerge -pv
    my @need;
    for my $p (@pkgs) {
        system("qlist -I $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('emerge','--nospinner','--quiet',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
