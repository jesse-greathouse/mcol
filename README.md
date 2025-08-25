# Media Collector

![Mcol -- Media Collector](https://i.imgflip.com/9mmeq8.jpg "Mcol Browse Web Interface")

- Built with [PHP 8.4](https://www.php.net/releases/8.4/en.php) and [Openresty](https://openresty.org/en/).
- Uses [Laravel](https://laravel.com/) and [ReactPHP](https://reactphp.org/) to connect to IRC networks and collect information about shared files.
- With [Intertia](https://inertiajs.com/) and [Vue 3](https://vuejs.org/) to create a simple User Interface to download and interact with media.
- Recommended for Linux servers and MacOS (Not supported on Windows).
- Pre Alpha (Not for distribution)

## 📚 Table of Contents

- [Media Collector](#media-collector)
  - [📚 Table of Contents](#-table-of-contents)
  - [System Requirements](#system-requirements)
  - [Install](#install)
    - [Download](#download)
    - [TLDR](#tldr)
    - [Detailed instructions](#detailed-instructions)
  - [Configure](#configure)
  - [Run](#run)
    - [IRC Instances](#irc-instances)
    - [Job Queue](#job-queue)
    - [Web UI](#web-ui)
  - [Other commands](#other-commands)
    - [Migrate](#migrate)
    - [Download Command](#download-command)
    - [Hot Report](#hot-report)
    - [Packet Search](#packet-search)
  - [Config Appendix](#config-appendix)

## System Requirements

### Supported & tested platforms

- **Ubuntu** 24.x, 25.x
- **Debian** 12, 13
- **Fedora** 40, 42
- **Rocky Linux** 9, 10
- **openSUSE Leap** 15.6
- **openSUSE Tumbleweed**
- **macOS Sequoia 15.1** — **Apple Silicon (arm64) only**; x86_64 macOS is **not** supported

### Experimental / not supported (stubs exist)

The repo includes install stubs for the following, but they are **untested and not supported**:
**Alpine**, **Arch**, **CentOS**, **Gentoo**, **OpenMandriva**.
Patches to improve these are welcome.

### Hardware & connectivity

- **CPU**:
  - Linux: **x86_64**, **4+ cores**
  - macOS: **Apple Silicon (arm64)**
- **Memory**: **4+ GB RAM** (more is better)
- **Storage**: **30 GB** free for system components; **200+ GB** recommended if you’ll download larger media
- **Network**: **10 Mbit/s** or better

### Privileges & user model

- **Install/build**: requires **elevated privileges** (e.g., `sudo`) to install dependencies and perform builds.
- **Runtime**: the application should be run as a **non-privileged user**.

## Install

### Download

> Currently the only way to download the project is with git:

```bash
git clone https://github.com/jesse-greathouse/mcol.git

cd mcol
```

### TLDR

#### _Some of the install operations require elevated privelages. DO NOT run as root or sudo, the scripts will prompt you for operations that require elevated privelages_

> First run the bootstrap script

```bash
# Installs a small amount of utilities that the installer runtime depends on

bin/bootstrap
```

> If you dont have a local [Perl library](https://www.cpan.org/modules/index.html) on your `$PATH`, you may need to set these variables in your terminal session

```bash
export PERL5LIB="$HOME/perl5/lib/perl5:$PERL5LIB"
export PATH="$HOME/perl5/bin:$PATH"
```

> Next run the scripts in this sequence

```bash
# Installs all necessary dependencies (It will prompt when it needs sudo/elevated privelages)

bin/install

# wait...

bin/configure

# Answer questions to configure for the environment...

bin/mcol start

# Starts the Mcol Daemons
# UI running at:
# http://localhost:8080
```

### Detailed instructions

```bash
bin/bootstrap
```

A minimal system build toolchain is required to run the installer. `bin/bootstrap` just sets up the installer to be able to build.

It's best if you use the local perl library. Set these variables if your local perl library is not on your PATH:

```bash
    export PERL5LIB="$HOME/perl5/lib/perl5:$PERL5LIB"
    export PATH="$HOME/perl5/bin:$PATH"
```

```bash
bin/install
```

- The install Script will ask you to gain evelated privelages to install the program.
- The application uses a specialized PHP and Nginx build and configuration that's suitable for running the program.
- It's strongly recommended to use redis. Defaulting to filesystem cache will hurt performance.
- Install adds the system libraries that the downstream PHP executable will need to have in its build.
- Install bulds the Nginx, PHP, PHP Extensions, Installs composer and builds composer dependencies.
- The PHP Extensions are: msgpack-php, php-rar, phpredis

- Install also depends on NVM to add the correct Node and NPM dependencies in building the front end application.
- Once the installation of all dependencies is completed, the program itself will never run at elevated privelages.

## Configure

```bash
bin/configure
```

- The Nginx, PHP, and other system configurations in etc/ will be dymically created by the bin/configure script.
- If you need to backup everything before running bin/configure... you should and,.. good luck!
  - I would like to see an easier way of handling config restores but currently not invested in it.
- Plan on running bin/configure the first time and from then on out, just edit the configuration.
- Database Migrations
  - At the end of configure, the script will prompt you to migrate databases.
  - Database migrations update the database schema to the latest schema design.
  - Running migrations is necessary if the application is newly installed.

The created configuration file: `.mcol-cfg.yml` can be found in the root directory of the project.
The creation of this config .yml is to populate the configuration strings of all the subsystems.

## Run

To start Mcol for the first time, run from the command line like this:

```bash
bin/mcol start
```

This starts Mcol's 3 subsystems:

- web
- queue
- Instances

These services will be run by supervisor.

The output will be in the following logs:

- var/log/supervisord.log
- var/log/error.log

You can actively monitor Mcol's output...

for errors:

```bash
tail -f var/log/error.log
```

for all other logging:

```bash
tail -f var/log/supervisord.log
```

You can stop all of mcol's subsystems like this:

```bash
bin/mcol stop
```

You can also restart all of mcol's subsystems like this:

```bash
bin/mcol restart
```

If you want to stop the mcol supervisr daemons, use the `kill` action.

(After a daemon has been killed, the only action useable on it is Start, to restart the supervisor daemons.)

```bash
bin/mcol kill
```

If you prefer to stop, start or restart a subystem individually, you can do that too!

### IRC Instances

```bash
bin/instance [start|stop|restart|kill]
```

### Job Queue

```bash
bin/queue [start|stop|restart|kill]
```

### Web UI

```bash
bin/web [start|stop|restart|kill]
```

## Other commands

### Migrate

```bash
bin/migrate
```

Migrates the database to the newest schema design. This may be necessary after the application has recieved an update. If it's not needed, no migrations will be run.

### Download Command

```bash
bin/download packetId
```

Queues the system to download a packet by the packet's id field in the packets table.

### Hot Report

```bash
bin/hot-report network channel
```

Given the inputs of a network and a channel, this command will print a report to the terminal about which media is hot with users in the channel.

ex:

```bash
myuser@mycomputer:~/mcol$ bin/hot-report "Abjects" "#mg-chat"
Looking for the hottest search terms on: #mg-chat@Abjects ...
Whats hot in #moviegods: - 978 Releases with a total of 254.4 downloads per hour

+------+--------+------------------------------------------+
| rank | rating | term                                     |
+------+--------+------------------------------------------+
| 1    | 7.8    | Yellowjackets.S03E05                     |
| 2    | 5.7    | Captain.America.Brave.New.World.2025.21  |
| 3    | 4.8    | Reacher.S03E05                           |
| 4    | 4.5    | Matlock.2024.S01E14                      |
| 5    | 3.4    | Severance.S02E08.Sweet.Vitriol.2160p.AT  |
| 6    | 3      | 9-1-1.S08E09                             |
| 7    | 2.5    | georgie.and.mandys.first.marriage.s01e13 |
| 8    | 2.5    | daredevil.born.again.s01e01              |
| 9    | 2.3    | Greys.Anatomy.S21E09                     |
| 10   | 2.2    | Reacher.S03E05.Smackdown                 |
| 11   | 2.1    | Elsbeth.S02E14                           |
| 12   | 2      | The.Pitt.S01E10.400.P.M                  |
| 13   | 1.5    | Captain.America.Brave.New.World.2025     |
| 14   | 1.4    | severance.s02e08                         |
+------+--------+------------------------------------------+

... done!
```

### Packet Search

```bash
bin/packet-search network channel "search string"
```

Given the inputs of a network and a channel and search string, this command will print a of which bots are offering files related to the search string.

ex:

```bash
myuser@mycomputer:~/mcol$ bin/packet-search "Abjects" "#mg-chat" "Breaking.Bad"
Searching for: Breaking.Bad ...
Found 10 results in #moviegods

+-------+------+-------------------------------------------------------------------------------------+
| id    | size | file                                                                                |
+-------+------+-------------------------------------------------------------------------------------+
| 4692  | 24G  | Breaking.Bad.S01.1080p.BluRay.DTS5.1.x264-iNGOT.tar                                 |
| 45201 | 13G  | Breaking.Bad.S01.German.DL.AC3.1080p.BluRay.x265-FuN.tar                            |
| 4720  | 23G  | Breaking.Bad.S02.1080p.WEB-DL.DD5.1.H264-BTN.tar                                    |
| 45205 | 28G  | Breaking.Bad.S02.German.DL.AC3.1080p.BluRay.x265-FuN.tar                            |
| 4738  | 23G  | Breaking.Bad.S03.1080p.WEB-DL.DD5.1.H264-BTN.tar                                    |
| 45208 | 13G  | Breaking.Bad.S03.German.DL.AC3.1080p.BluRay.x265-FuN.tar                            |
| 45210 | 10G  | Breaking.Bad.S04.German.DL.AC3.1080p.BluRay.x265-FuN.tar                            |
| 4785  | 30G  | Breaking.Bad.S05.1080p.WEB-DL.DD5.1.H264-BTN.tar                                    |
| 45212 | 13G  | Breaking.Bad.S05.German.DL.AC3.1080p.BluRay.x265-FuN.tar                            |
| 46161 | 6.2G | El.Camino.A.Breaking.Bad.Movie.2019.German.AC3.AAC.5.1.DL.1080p.BluRay.x264-oWn.mkv |
+-------+------+-------------------------------------------------------------------------------------+

Search completed successfully.
myuser@mycomputer:~/mcol$ bin/download 4692
Requested packet: 4692 -- Breaking.Bad.S01.1080p.BluRay.DTS5.1.x264-iNGOT.tar
```

## Config Appendix

- ### LARAVEL

  #### `APP_NAME` `mcol`

  ##### _The "name" of the app_

  #### `VITE_APP_NAME` `mcol`

  ##### _Vite needs an application name, usually just mirrors APP_NAME_

  #### `APP_DEBUG` `'false'`

  ##### _Puts the app in debug mode_

  #### `APP_ENV` `local`

  ##### _The "name" of the environment_

  #### `APP_KEY` `base64:cjePqIw0DrwVlC8E/JHPtPQutbTPNenWDBsp1dGKecI=`

  ##### _Auto generated base64 encoded string. Do not alter_

  #### `APP_TIMEZONE` `UTC`

  ##### _Should be the same as the host server_

  #### `APP_URL` `http://myserver:8080`

  ##### _The web address that will have the user interface_

  #### `CACHE_DIR` `/home/myuser/mcol/var/cache`

  ##### _System "cache" directory_

  #### `CACHE_DRIVER` `redis`

  ##### _Laravel value for cache driver_

  #### `DB_CONNECTION` `mysql`

  ##### _Only MySQL is supported presently. Theoretically it could be using an alternate database using the underlying Laravel database connection, however I never intended to support different database platforms and so there is a large chunk of raw SQL submitted in the `App\Packet\Browse` class that would need to be adapted. It's recommended just to use MySQL because that's the only way I can guarantee the best performance._

  #### `DB_DATABASE` `mcol`

  ##### _The schema name_

  #### `DB_HOST` `127.0.0.1`

  ##### _The Database Host Address_

  #### `DB_USERNAME` `mcol`

  ##### _The Database User_

  #### `DB_PASSWORD` `mcol`

  ##### _The Database Password_

  #### `DB_PORT` `'3306'`

  ##### _The Database Port_

  #### `SESSION_DOMAIN` `myserver`

  ##### _The domain that is hosting the web application. Variable to strictly limit sessions to that domain_

  #### `SANCTUM_STATEFUL_DOMAINS` `myserver`

  ##### _For supporting authentication, should simply mirror SESSION_DOMAINS_

  #### `SESSION_DRIVER` `cookie`

  ##### _Session driver_

  #### `DIR` `/home/myuser/mcol`

  ##### _Home directory of the running user_

  #### `VAR` `/home/myuser/mcol/var`

  ##### _The "var" directory. Handles logs and files that get bigger_

  #### `WEB` `/home/myuser/mcol/src/public`

  ##### _The directory whehere the web root is served from_

  #### `ETC` `/home/myuser/mcol/etc`

  ##### _The Configuration directory_

  #### `SRC` `/home/myuser/mcol/src`

  ##### _The Source Code directory_

  #### `DOWNLOAD_DIR` `/home/myuser/mcol/var/download`

  ##### _Directory where downloads will be held_

  #### `LOG` `/home/myuser/mcol/var/log/error.log`

  #### `LOG_CHANNEL` `stack`

  #### `LOG_DIR` `/home/myuser/mcol/var/log`

  #### `LOG_SLACK_WEBHOOK_URL` `''`

  #### `LOG_URI` `/home/myuser/mcol/var/log/error.log`

  ##### _The logging variables_

  #### `QUEUE_CONNECTION` `database`

  ##### _Tells Laravel which Queue adapter to use. It's recommended to stick with `database`_

- ### SUPERVISOR

  #### `INSTANCECTL_PORT` `5859`

  #### `INSTANCECTL_SECRET` `...`

  #### `INSTANCECTL_USER` `myuser`

  #### `QUEUECTL_PORT` `5860`

  #### `QUEUECTL_SECRET` `...`

  #### `QUEUECTL_USER` `myuser`

  #### `SUPERVISORCTL_PORT` `5861`

  #### `SUPERVISORCTL_SECRET` `...`

  #### `SUPERVISORCTL_USER` `myuser`

  ##### _These are supervisor daemon configuration values_

- ### NGINX

  #### `DOMAINS` `localhost`

  ##### _The value for the domains directive in nginx_

  #### `USER` `myuser`

  ##### _The system username_

  #### `PORT` `'8080'`

  ##### _The port that the web UI will be served at_

  #### `SESSION_SECRET` `...`

  ##### _The secret that authenticates the session. This is dynamically generated_

  #### `IS_SSL` `'false'`

  ##### _Flags using SSL in the webserver configuration_

  #### `SSL` `''`

  ##### _The SSL configuration (auto-generated)_

  #### `INCLUDE_FORCE_SSL_LINE` `''`

  ##### _The "force" SSL Line (auto-generated)_

  #### `SSL_CERT_LINE` `''`

  ##### _The line to include the SSL cert_

  #### `SSL_KEY_LINE` `''`

  ##### _The line to unclude the SSL key_

- ### REDIS

  #### `REDIS_DB` `'0'`

  ##### _Used to specify a specific DB in Redis_

  #### `REDIS_HOST` `127.0.0.1`

  ##### _The redis host (Can be a unix socket file)_

  #### `REDIS_PASSWORD` `'null'`

  ##### _The redis password_

  #### `REDIS_PORT` `6379`

  ##### _The redis port_
