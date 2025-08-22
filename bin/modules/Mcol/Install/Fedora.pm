#!/usr/bin/env perl

package Mcol::Install::Fedora;
use strict;
use Cwd qw(getcwd abs_path);
use File::Basename;
use lib dirname(abs_path(__FILE__)) . "/modules";
use Mcol::Utility qw(command_result);
use Mcol::System qw(how_many_threads_should_i_use);
use Exporter 'import';

our @EXPORT_OK = qw(install_system_dependencies install_php install_bazelisk);

my @systemDependencies = qw(
    supervisor authbind expect openssl-devel gcc curl pkgconf perl-App-cpanminus
    ncurses-devel pcre-devel libcurl-devel ImageMagick-devel libxslt-devel
    mariadb-connector-c-devel libxml2-devel libicu-devel ImageMagick-c++-devel
    libzip-devel oniguruma-devel libsodium-devel glib2-devel libwebp-devel
    mariadb ImageMagick bash make golang redis valkey libpng-devel libjpeg-turbo-devel
    mesa-libGL-devel mesa-libGLU-devel bzip2-devel
    autoconf automake libtool m4 re2c
);

# helper: find a command in PATH
sub _has_cmd {
    my ($cmd) = @_;
    for my $d (split /:/, ($ENV{PATH} // '')) {
        return 1 if -x "$d/$cmd";
    }
    return 0;
}

# Rocky/RHEL: enable CRB to unlock many *-devel packages (no-op on Fedora)
sub _maybe_enable_crb {
    return unless _has_cmd('dnf');
    my $os = do {
        local $/;
        my $s = '';
        if (open my $fh, '<', '/etc/os-release') {
                $s = readline($fh);   # avoids the <> parser weirdness
                close $fh;
        }
        $s;
    };
    if ($os =~ /\bID=(?:rocky|rhel)\b/) {
        system('sudo', 'dnf', 'config-manager', '--set-enabled', 'crb');
        # ignore exit status; harmless if already enabled/unavailable
    }
}

# Prime environment for THIS process and all its children (no profile files)
sub _prepare_build_env {
    # 1) Build sane CFLAGS: keep user flags; guarantee -std=gnu99 and an -O*
    my $cflags_in  = $ENV{CFLAGS} // '';
    my $has_opt    = ($cflags_in =~ /(^|\s)-O[0-3]\b/);      # user already set -O0..-O3?
    my @cflags     = ('-std=gnu99');
    push @cflags, ('-O2', '-g') unless $has_opt;             # satisfy OTP configure
    push @cflags, $cflags_in if length $cflags_in;
    $ENV{CFLAGS} = join(' ', @cflags);

    # (Optional) do the same for C++ compilers used by some deps
    my $cxx_in   = $ENV{CXXFLAGS} // '';
    my $has_optc = ($cxx_in =~ /(^|\s)-O[0-3]\b/);
    my @cxx      = ();
    push @cxx, ('-O2', '-g') unless $has_optc;
    push @cxx, $cxx_in if length $cxx_in;
    $ENV{CXXFLAGS} = join(' ', @cxx) if @cxx;

    # 2) Make /usr/local visible to autotools/cmake
    my @incs = grep { -d $_ } ('/usr/local/include');
    my @libs = grep { -d $_ } ('/usr/local/lib64', '/usr/local/lib');
    my $cpp  = join(' ', map { "-I$_" } @incs);
    my $ld   = join(' ', map { "-L$_" } @libs);
    $ENV{CPPFLAGS} = join(' ', grep { length } ($cpp, $ENV{CPPFLAGS} // ''));
    $ENV{LDFLAGS}  = join(' ', grep { length } ($ld,  $ENV{LDFLAGS}  // ''));

    # 3) pkg-config and PATH hygiene
    my @pc = grep { defined && length } (
        (-d '/usr/local/lib64/pkgconfig' ? '/usr/local/lib64/pkgconfig' : ()),
        (-d '/usr/local/lib/pkgconfig'   ? '/usr/local/lib/pkgconfig'   : ()),
        $ENV{PKG_CONFIG_PATH}
    );
    $ENV{PKG_CONFIG_PATH} = join(':', @pc);

    my @path = ('/usr/local/bin', '/usr/local/sbin', split(/:/, ($ENV{PATH} // '')));
    my %seen; @path = grep { !$seen{$_}++ } @path;
    $ENV{PATH} = join(':', @path);

    # 4) Speed up cpanm later in the run
    $ENV{PERL_CPANM_OPT} //= '--notest --quiet --no-man-pages --skip-satisfied';

    # 5) kerl/asdf users: only inject CFLAGS if not already specified in options
    my $kerl = $ENV{KERL_CONFIGURE_OPTIONS} // '';
    $kerl .= ' --without-wx' if $ENV{MCOL_WITHOUT_WX};
    if ($kerl !~ /\bCFLAGS=/) {
        # Pass through the resolved environment CFLAGS (includes -O*)
        $kerl = join(' ', grep { length } ($kerl, "CFLAGS=$ENV{CFLAGS}"));
    }
    $ENV{KERL_CONFIGURE_OPTIONS} = $kerl;

    # Optional: faster make by default
    $ENV{MAKEFLAGS} //= '-j' . (eval { require POSIX; POSIX::sysconf(POSIX::_SC_NPROCESSORS_ONLN()) } || 2);

    print "Build env primed (CFLAGS='$ENV{CFLAGS}').\n";
}

sub install_system_dependencies {
    my $dnf = _has_cmd('dnf5') ? 'dnf5'
              : _has_cmd('dnf')  ? 'dnf'
              :                     undef;
    die "No dnf/dnf5 found in PATH\n" unless $dnf;

    my $username = getpwuid($<);
    print "Sudo is required for updating and installing system dependencies.\n";
    print "Please enter sudoers password for: $username elevated privileges.\n";

    _maybe_enable_crb();

    # Refresh metadata
    my @updateCmd = ('sudo', $dnf, 'makecache', '--refresh');
    system(@updateCmd);
    command_result($?, $!, "Updated package index...", \@updateCmd);

    # Install build groups in a dnf4/dnf5-safe way.
    # dnf5 prefers group IDs with '@'; dnf4 accepts this too.
    for my $gid ('@development-tools') {
        my @gcmd = ('sudo', $dnf, 'install', '-y', $gid, '--setopt=install_weak_deps=False');
        system(@gcmd);
        # If it failed (e.g., group name differs), try the name via the 'group' subcommand.
        if ($? != 0) {
            my @alt = ('sudo', $dnf, 'group', 'install', '-y', 'Development Tools', '--with-optional');
            system(@alt);
        }
    }

    # Package diff
    my @to_install;
    for my $pkg (@systemDependencies) {
        my $check = system("rpm -q $pkg > /dev/null 2>&1");
        if ($check != 0) { push @to_install, $pkg }
        else { print "✓ $pkg already installed, skipping.\n" }
    }

    if (@to_install) {
        my @installCmd = ('sudo', $dnf, 'install', '-y', @to_install);
        system(@installCmd);
        command_result($?, $!, "Installed missing dependencies...", \@installCmd);
    } else {
        print "All system dependencies already installed.\n";
    }

    # *** CRITICAL: prime the current runtime for the rest of this long install ***
    _prepare_build_env();

    # Enable redis
    my @redis = ('sudo', 'systemctl', 'enable', '--now', 'redis');
    system(@redis);


}

sub install_php {
    my ($dir) = @_;
    my $threads = how_many_threads_should_i_use();

    my @configurePhp = (
        './configure',
        '--prefix=' . $dir . '/opt/php',
        '--sysconfdir=' . $dir . '/etc',
        '--with-config-file-path=' . $dir . '/etc/php',
        '--with-config-file-scan-dir=' . $dir . '/etc/php/conf.d',
        '--enable-opcache', '--enable-fpm', '--enable-dom', '--enable-exif',
        '--enable-fileinfo', '--enable-mbstring', '--enable-bcmath',
        '--enable-intl', '--enable-ftp', '--enable-pcntl', '--enable-gd',
        '--enable-soap', '--enable-sockets', '--without-sqlite3',
        '--without-pdo-sqlite', '--with-libxml', '--with-xsl', '--with-zlib',
        '--with-curl', '--with-webp', '--with-openssl', '--with-zip', '--with-bz2',
        '--with-sodium', '--with-mysqli', '--with-pdo-mysql', '--with-mysql-sock',
        '--with-iconv'
    );

    my $originalDir = getcwd();

    system('bash', '-c', "tar -xzf $dir/opt/php-*.tar.gz -C $dir/opt/");
    command_result($?, $!, 'Unpacked PHP Archive...', 'tar -xzf ' . $dir . '/opt/php-*.tar.gz -C ' . $dir . '/opt/');

    chdir glob("$dir/opt/php-*/");

    system(@configurePhp);
    command_result($?, $!, 'Configured PHP...', \@configurePhp);

    print "\n=================================================================\n";
    print " Compiling PHP...\n";
    print "=================================================================\n\n";
    print "Running make using $threads threads in concurrency.\n\n";

    system('make', "-j$threads");
    command_result($?, $!, 'Made PHP...', 'make');

    system('make install');
    command_result($?, $!, 'Installed PHP...', 'make install');

    chdir $originalDir;
}

sub install_bazelisk {
    my ($dir) = @_;
    my $originalDir = getcwd();
    my $bazeliskDir = "$dir/opt/bazelisk/";

    if (-d $bazeliskDir) {
        print "Bazel dependency already exists, skipping...(`rm -rf $bazeliskDir` to rebuild)\n";
        return;
    }

    system(('bash', '-c', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/"));
    command_result($?, $!, 'Unpack Bazelisk...', "tar -xzf $dir/opt/bazelisk-*.tar.gz -C $dir/opt/");

    system(('bash', '-c', "mv $dir/opt/bazelisk-*/ $bazeliskDir"));
    command_result($?, $!, 'Renaming Bazelisk Dir...', "mv $dir/opt/bazelisk-*/ $bazeliskDir");

    chdir glob($bazeliskDir);

    print "\n=================================================================\n";
    print " Installing Bazelisk....\n";
    print "=================================================================\n\n";

    system('bash', '-c', 'go install github.com/bazelbuild/bazelisk@latest');
    command_result($?, $!, 'Install Bazelisk...', 'go install github.com/bazelbuild/bazelisk@latest');

    system('bash', '-c', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");
    command_result($?, $!, 'Build Bazelisk...', "GOOS=linux GOARCH=amd64 go build -o $dir/bin/bazel");

    system('bash', '-c', "$dir/bin/bazel version");
    command_result($?, $!, 'Run Bazelisk...', "$dir/bin/bazel version");

    chdir $originalDir;
}

1;
