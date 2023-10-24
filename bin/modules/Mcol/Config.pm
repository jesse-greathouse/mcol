#!/usr/bin/perl

package Mcol::Config;
use strict;
use File::Basename;
use File::Copy;
use Cwd qw(getcwd abs_path);
use Config::File qw(read_config_file);
use YAML::XS qw(LoadFile DumpFile);
use POSIX qw(strftime);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Utility qw(
    str_replace_in_file
    write_file
);
our @EXPORT_OK = qw(
    get_config_file
    get_configuration
    save_configuration
    parse_env_file
    write_env_file
    write_config_file
);

my $bin = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($bin));
my $configurationFileName = '.mcol-cfg.yml';
my $configFile = "$applicationRoot/$configurationFileName";

if (! -d $applicationRoot) {
    die "Directory: \"$applicationRoot\" doesn't exist\n $!";
}

1;

# ====================================
#    Subroutines below this point
# ====================================

sub get_config_file {
    return $configFile;
}

# Returns the configuration hash.
sub get_configuration {
    my %cfg;

    # Read configuration if it exists. Create it if it does not exist
    if (-e $configFile) {
        %cfg = LoadFile($configFile);
    } else {
        print "Creating configuration file\n";
        my $libyaml = YAML::XS::LibYAML::libyaml_version();
        my $created = strftime("%F %r", localtime);
        %cfg = (
            meta => {
                created_at    => $created,
                libyaml       => $libyaml,
            }
        );
        save_configuration(%cfg);
    }

    return %cfg;
}

sub save_configuration {
    my (%cfg) = @_;
    DumpFile("$applicationRoot/$configurationFileName", %cfg);
    %cfg = LoadFile("$applicationRoot/$configurationFileName");
}

sub write_config_file {
    my ($templateFile, $configFile, %cfg) = @_;

    if (-e $configFile) {
        unlink $configFile;
    }

    copy($templateFile, $configFile) or die "Copy $configFile failed: $!";

    keys %cfg; # reset the internal iterator so a prior each() doesn't affect the loop
    while(my($k, $v) = each %cfg) {
        my $m = '__' . $k . '__';
        str_replace_in_file($m, $v, $configFile);
    }
}

sub parse_env_file {
    my ($file) = @_;
    return read_config_file($file);
}

sub write_env_file {
    my ($filename, %config) = @_;

    # remove the file if it already exists
    if (-e $filename) {
         unlink $filename;
    }

    my $content = '';
    foreach my $key (keys %config)
    {
        $content = $content . $key . "=" . $config{$key} . "\n";
    }

    write_file($filename, $content);
}
