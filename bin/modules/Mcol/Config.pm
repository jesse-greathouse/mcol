#!/usr/bin/perl

package Mcol::Config;
use strict;
use File::Basename;
use Cwd qw(getcwd abs_path);
use Config::File qw(read_config_file);
use YAML::XS qw(LoadFile DumpFile);
use POSIX qw(strftime);
use Exporter 'import';
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Utility qw(write_file);
our @EXPORT_OK = qw(
    get_configuration
    save_configuration
    parse_env_file
    write_env_file
);

my $bin = abs_path(dirname(__FILE__) . '/../../');
my $applicationRoot = abs_path(dirname($bin));
my $configurationFileName = '.mcol-cfg.yml';

if (! -d $applicationRoot) {
    die "Directory: \"$applicationRoot\" doesn't exist\n $!";
}

1;

# ====================================
#    Subroutines below this point
# ====================================

# Returns the configuration hash.
sub get_configuration {
    my %cfg;

    # Read configuration if it exists. Create it if it does not exist
    if (-e "$applicationRoot/$configurationFileName") {
        %cfg = LoadFile("$applicationRoot/$configurationFileName");
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
