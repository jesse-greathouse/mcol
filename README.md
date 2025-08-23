# Media Collector

![Mcol -- Media Collector](https://i.imgflip.com/9mmeq8.jpg "Mcol Browse Web Interface")

- Built with [PHP 8.4](https://www.php.net/releases/8.4/en.php) and [Openresty](https://openresty.org/en/).
- Uses [Laravel](https://laravel.com/) and [ReactPHP](https://reactphp.org/) to connect to IRC networks and collect information about shared files.
- With [Intertia](https://inertiajs.com/) and [Vue 3](https://vuejs.org/) to create a simple User Interface to download and interact with media.
- Recommended for Linux servers (Not supported on Windows).
- Pre Alpha (Not for distribution)

## Install

### Download

Currently the only way to download the project is with git:

```bash
git clone https://github.com/jesse-greathouse/mcol.git

cd mcol
```

### TLDR

```bash
# Verifies perl and cpan are available and installs perl modules.
bin/bootstrap
```

> If you dont have a local Perl library set up on your PATH, you may need to add thse variables to your terminal session:

```bash
    export PERL5LIB="\$HOME/perl5/lib/perl5:\$PERL5LIB"
    export PATH="\$HOME/perl5/bin:\$PATH"
```

```bash
# Installs all necessary dependencies (Requires sudo/elevated privelages)

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
    export PERL5LIB="\$HOME/perl5/lib/perl5:\$PERL5LIB"
    export PATH="\$HOME/perl5/bin:\$PATH"
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

### Download

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

```
myuser@mycomputer:~/mcol$ bin/hot-report "Abjects" "#mg-chat"
Looking for the hottest search terms on: #mg-chat@Abjects ...
What's hot in #moviegods: - 978 Releases with a total of 254.4 downloads per hour
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

```
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

        The "name" of the app.

  #### `VITE_APP_NAME` `mcol`

        Vite needs an application name, usually just mirrors APP_NAME

  #### `APP_DEBUG` `'false'`

        Puts the app in debug mode.

  #### `APP_ENV` `local`

        The "name" of the environment.

  #### `APP_KEY` `base64:cjePqIw0DrwVlC8E/JHPtPQutbTPNenWDBsp1dGKecI=`

        Auto generated base64 encoded string. Do not alter.

  #### `APP_TIMEZONE` `UTC`

        Should be the same as the host server.

  #### `APP_URL` `http://myserver:8080`

        The web address that will have the user interface.

  #### `CACHE_DIR` `/home/myuser/mcol/var/cache`

        System "cache" directory.

  #### `CACHE_DRIVER` `redis`

        Laravel value for cache driver.

  #### `DB_CONNECTION` `mysql`

        Only MySQL is supported presently. Theoretically it could be using an alternate database using the underlying Laravel database connection, however I never intended to support different database platforms and so there is a large chunk of raw SQL submitted in the `App\Packet\Browse` class that would need to be adapted. It's recommended just to use MySQL because that's the only way I can guarantee the best performance.

  #### `DB_DATABASE` `mcol`

        The schema name.

  #### `DB_HOST` `127.0.0.1`

        The Database Host Address.

  #### `DB_USERNAME` `mcol`

        The Database User.

  #### `DB_PASSWORD` `mcol`

        The Database Password.

  #### `DB_PORT` `'3306'`

        The Database Port.

  #### `SESSION_DOMAIN` `myserver`

        The domain that is hosting the web application. Variable to strictly limit sessions to that domain.

  #### `SANCTUM_STATEFUL_DOMAINS` `myserver`

        For supporting authentication, should simply mirror SESSION_DOMAINS.

  #### `SESSION_DRIVER` `cookie`

        Session driver.

  #### `DIR` `/home/myuser/mcol`

        Home directory of the running user.

  #### `VAR` `/home/myuser/mcol/var`

        The "var" directory. Handles logs and files that get bigger.

  #### `WEB` `/home/myuser/mcol/src/public`

        The directory whehere the web root is served from.

  ### `ETC` `/home/myuser/mcol/etc`

        The Configuration directory.

  ### `SRC` `/home/myuser/mcol/src`

        The Source Code directory.

  #### `DOWNLOAD_DIR` `/home/myuser/mcol/var/download`

        Directory where downloads will be held.

  #### `LOG` `/home/myuser/mcol/var/log/error.log`

  #### `LOG_CHANNEL` `stack`

  #### `LOG_DIR` `/home/myuser/mcol/var/log`

  #### `LOG_SLACK_WEBHOOK_URL` `''`

  #### `LOG_URI` `/home/myuser/mcol/var/log/error.log`

        The logging variables.

  #### `QUEUE_CONNECTION` `database`

  #### `REDIS_CLIENT` `phpredis`

  #### `REDIS_DB` `'0'`

  #### `REDIS_HOST` `/var/run/redis/redis.sock`

  #### `REDIS_PASSWORD` `'null'`

  #### `REDIS_PORT` `'0'`

        Redis configurations

  #### `INSTANCECTL_PORT` `5859`

  #### `INSTANCECTL_SECRET` `...`

  #### `INSTANCECTL_USER` `myuser`

  #### `QUEUECTL_PORT` `5860`

  #### `QUEUECTL_SECRET` `...`

  #### `QUEUECTL_USER` `myuser`

  #### `SUPERVISORCTL_PORT` `5861`

  #### `SUPERVISORCTL_SECRET` `...`

  #### `SUPERVISORCTL_USER` `myuser`

        The Application does not currently send signals to supervisor. Theoretically it might be added.

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

        These are supervisor daemon configurations. They can be used to send signals to the supervisor daemons.

- ### NGINX

  #### `DIR` `/home/myuser/mcol`

        The application user's home directory.

  #### `DOMAINS` `localhost`

        The value for the domains directive in nginx.

  #### `USER` `myuser`

        The system username.

  #### `ETC` `/home/myuser/mcol/etc`

        The Cxnfiguration directory.

  #### `VAR` `/home/myuser/mcol/var`

        The "var" directory. Handles logs and files that get bigger.

  #### `SRC` `/home/myuser/mcol/src`

        The directory where source code resides.

  #### `WEB` `/home/myuser/mcol/src/public`

        The directory of the web files.

  #### `LOG` `/home/myuser/mcol/var/log/error.log`

        The error log.

  #### `PORT` `'8080'`

        The port that the web UI will be served at.

  #### `SESSION_SECRET` `...`

        The secret that authenticates the session. This is dynamically generated when you run the

  #### `IS_SSL` `'false'`

        Flags using SSL in the webserver configuration.

  #### `SSL` `''`

        The SSL configuration (auto-generated.)

  #### `INCLUDE_FORCE_SSL_LINE` `''`

        The "force" SSL Line (auto-generated.)

  #### `SSL_CERT_LINE` `''`

        The line to include the SSL cert.

  #### `SSL_KEY_LINE` `''`

        The line to unclude the SSL key.

- ### REDIS

  #### `REDIS_DB` `'0'`

        Used to specify a specific DB in Redis.

  #### `REDIS_HOST` `/var/run/redis/redis.sock`

        The redis host (Can be a unix socket file).

  #### `REDIS_PASSWORD` `'null'`

        The redis password.

  #### `REDIS_PORT` `'0'`

        The redis port.
