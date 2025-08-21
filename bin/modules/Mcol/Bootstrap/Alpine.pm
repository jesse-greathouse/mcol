#!/usr/bin/env perl
package Mcol::Bootstrap::Alpine;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

# Alpine splits Perl headers; perl-dev is essential for XS builds.
my @pkgs = qw(
  build-base perl-dev pkgconf openssl-dev perl-app-cpanminus curl tar unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for Alpine...\n";
    system('apk','update');
    command_result($?, $!, "Updated package index...", ['apk','update']);

    my @need;
    for my $p (@pkgs) {
        system("apk info -e $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('apk','add',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
