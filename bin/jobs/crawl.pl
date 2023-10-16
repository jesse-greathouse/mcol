#!/usr/bin/perl

use strict;

use Cwd qw(getcwd abs_path);
use File::Basename;
use lib(dirname(abs_path(__FILE__))  . "/../modules");
use Mcol::Jobs::Crawl qw(crawl);

crawl();
