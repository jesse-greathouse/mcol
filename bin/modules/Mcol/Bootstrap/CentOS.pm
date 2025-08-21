#!/usr/bin/env perl

package Mcol::Bootstrap::CentOS;
use strict;
use Mcol::Utility qw(command_result);
use Exporter 'import';
our @EXPORT_OK = qw(install_bootstrap_toolchain);

# CentOS/RHEL minimal toolchain
my @pkgs = qw(
  epel-release gcc make pkgconfig openssl-devel perl-App-cpanminus curl tar unzip
);

sub install_bootstrap_toolchain {
    print "Installing minimal build toolchain for CentOS/RHEL...\n";
    system('sudo','yum','makecache','-y');
    command_result($?, $!, "Updated package index...", ['yum','makecache']);

    my @need;
    for my $p (@pkgs) {
        next if $p eq 'epel-release' && system("rpm -q epel-release >/dev/null 2>&1") == 0;

        system("rpm -q $p >/dev/null 2>&1");
        if ($? != 0) { push @need, $p } else { print "[OK] $p already installed.\n" }
    }

    if (@need) {
        my @cmd = ('sudo','yum','install','-y',@need);
        system(@cmd);
        command_result($?, $!, "Installed bootstrap toolchain...", \@cmd);
    } else {
        print "All bootstrap dependencies are present.\n";
    }
}

1;
