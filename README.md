# Media Collector

![Mcol -- Media Collector](https://i.imgur.com/xZEnK1s.png "Mcol Browse Web Interface")

* Uses PHP and IRC to collect media.
* Only Supported on Ubuntu 20.04+
* Pre Alpha (Not for distribution)

## Install

```bash
bin/install
```

* The install Script will ask you to gain evelated privelages to install the program. 
* The application uses a specialized PHP and Nginx build and configuration that's suitable for running the program.
* It's strongly recommended to use redis. Defaulting to filesystem cache will hurt performance.
* Install adds the system libraries that the downstream PHP executable will need to have in its build.
* Install bulds the Nginx, PHP, PHP Extensions, Installs composer and builds composer dependencies.
* The PHP Extensions are: msgpack-php, php-rar, phpredis

* Install also depends on NVM to add the correct Node and NPM dependencies in building the front end application.
* Once the installation of all dependencies is completed, the program itself will never run at elevated privelages. 

## Configure

```bash
bin/configure
```

* The Nginx, PHP, and other system configurations in etc/ will be dymically created by the bin/configure script.
* If you need to backup everything before running bin/configure... you should and,.. good luck!
    * I would like to see an easier way of handling config restores but currently not invested in it.
* Plan on running bin/configure the first time and from then on out, just edit the configuration.

The created configuration file: `.mcol-cfg.yml` can be found in the root directory of the project.
The creation of this config .yml is to populate the configuration strings of all the subsystems.

## Run

To start Mcol for the first time, run from the command line like this:

```bash
bin/mcol start
```
This starts Mcol's 3 subsystems:
* web
* queue
* Instances

These services will be run by supervisor.

The output will be in the following logs:
* var/log/supervisord.log
* var/log/error.log

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

If you prefer to stop, start or restart a subystem individually, you can do that too!

### IRC Instances

```bash
bin/instance [start|stop|restart]
```

### Job Queue

```bash
bin/queue [start|stop|restart]
```

### Web UI

```bash
bin/web [start|stop|restart]
```

## Config Appendix


* laravel

`VITE_APP_NAME` `mcol`

The Vite needs an application name.

`APP_DEBUG` `'false'`

Puts the app in debug mode.

`APP_ENV` `local`

The "name" of the environment.

`APP_KEY` `base64:cjePqIw0DrwVlC8E/JHPtPQutbTPNenWDBsp1dGKecI=`

Auto generated base64 encoded string. Do not alter.

`APP_NAME` `mcol`

The "name" of the app.

`APP_TIMEZONE` `UTC`

Should be the same as the host server.

`APP_URL` `http://myserver:8080`

The web address that will have the user interface.

`CACHE_DIR` `/home/myuser/mcol/var/cache`

System "cache" directory.

`CACHE_DRIVER` `redis`

Laravel value for cache driver.

`DB_CONNECTION` `mysql`

Only MySQL is supported presently. Theoretically it could be using an alternate database using the underlying Laravel database connection, however I never intended to support different database platforms and so there is a large chunk of raw SQL submitted in the `App\Packet\Browse` class that would need to be adapted. It's recommended just to use MySQL because that's the only way I can guarantee the best performance.

`DB_DATABASE` `mcol`

The schema name.

`DB_HOST` `127.0.0.1`

The Database Host Address.

`DB_USERNAME` `mcol`

The Database User.

`DB_PASSWORD` `mcol`

The Database Password.

`DB_PORT` `'3306'`

The Database Port.

`DIR` `/home/myuser/mcol`

Home directory of the running user.

`VAR` `/home/myuser/mcol/var`

The "var" directory. Handles logs and files that get bigger.

`WEB` `/home/myuser/mcol/src/public`

The directory whehere the web root is served from.

`DOWNLOAD_DIR` `/home/myuser/mcol/var/download`

Directory where downloads will be held.

`ETC` `/home/myuser/mcol/etc`

The Configuration directory.

`INSTANCECTL_PORT` `5859`
`INSTANCECTL_SECRET` `...`
`INSTANCECTL_USER` `myuser`
`QUEUECTL_PORT` `5860`
`QUEUECTL_SECRET` `...`
`QUEUECTL_USER` `myuser`
`SUPERVISORCTL_PORT` `5861`
`SUPERVISORCTL_SECRET` `...`
`SUPERVISORCTL_USER` `myuser`

These are supervisor daemon configurations. They can be used to send signals to supervisor.
The Application does not currently send signals to supervisor. Theoretically it might be added.

`LOG` `/home/myuser/mcol/var/log/error.log`
`LOG_CHANNEL` `stack`
`LOG_DIR` `/home/myuser/mcol/var/log`
`LOG_SLACK_WEBHOOK_URL` `''`
`LOG_URI` `/home/myuser/mcol/var/log/error.log`

The logging variables.

`QUEUE_CONNECTION` `database`
`REDIS_CLIENT` `phpredis`
`REDIS_DB` `'0'`
`REDIS_HOST` `/var/run/redis/redis.sock`
`REDIS_PASSWORD` `'null'`
`REDIS_PORT` `'0'`
`SANCTUM_STATEFUL_DOMAINS` `myserver`
`SESSION_DOMAIN` `myserver`
`SESSION_DRIVER` `cookie`
`SRC` `/home/myuser/mcol/src`

Redis and caching variables. 

* nginx

`DIR` `/home/myuser/mcol`

The application user's home directory.

`DOMAINS` `localhost`

The value for the domains directive in nginx.

`USER` `myuser`

The system username.

`ETC` `/home/myuser/mcol/etc`

The Cxnfiguration directory.

`VAR` `/home/myuser/mcol/var`

The "var" directory. Handles logs and files that get bigger.

`SRC` `/home/myuser/mcol/src`

The directory where source code resides.

`WEB` `/home/myuser/mcol/src/public`

The directory of the web files.

`LOG` `/home/myuser/mcol/var/log/error.log`

The error log.

`PORT` `'8080'`

The port that the web UI will be served at.

`SESSION_SECRET` `...`

The secret that authenticates the session. This is dynamically generated when you run the 

`IS_SSL` `'false'`

Flags using SSL in the webserver configuration.

`SSL` `''`

The SSL configuration (auto-generated.)

`INCLUDE_FORCE_SSL_LINE` `''`

The "force" SSL Line (auto-generated.)

`SSL_CERT_LINE` `''`

The line to include the SSL cert.

`SSL_KEY_LINE` `''`

The line to unclude the SSL key.

* redis

`REDIS_DB` `'0'`

Used to specify a specific DB in Redis.

`REDIS_HOST` `/var/run/redis/redis.sock`

The redis host (Can be a unix socket file).

`REDIS_PASSWORD` `'null'`

The redis password.

`REDIS_PORT` `'0'`

The redis port.
