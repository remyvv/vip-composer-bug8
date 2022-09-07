# Vip Composer Bug8 Docker Setup

## Requisites

The setup works with major systems: Linux, macOS, and Windows.

- Docker + Docker compose is required.
- Current version of Git (2.25+)

For _Windows_ users:
- Current "Docker for Windows" running on **WLS 2**.
- OpenSSH installed either on the host or WLS 2 image.


## Preparatory steps

### 1. Configuration

The file `docker-compose.yml` contains the primary and shared configuration.

For user-specific tweaking, it is possible to use [`docker-compose.override.yml`](https://docs.docker.com/compose/extends/#multiple-compose-files)
which is git-ignored in this repository and can contain the "personal" configuration for each developer.

`docker-compose.override.yml` is where, for example, developers can override default settings like, for example, mapped
ports in the case the ports defined in `docker-compose.yml` are occupied on their system.

### 2. Stop other Docker containers

Make sure no other Docker container is currently running to avoid
networking conflicts.
In, respectively, Windows and Mac,  the "Docker for Windows" or "Docker Desktop" apps have a GUI for that. CLI-savvy and Linux
users can use via command line:

```shell
docker stop $(docker ps -a -q)
```

### 3. Environment variables

The repository comes with an `example.docker-compose.override.yml` that developers can use as a base.

Start by renaming that `example.docker-compose.override.yml` into `docker-compose.override.yml`.

In that file, there are **3 environment variables**:

- `COMPOSER_AUTH`
- `GITHUB_API_TOKEN`
- `GITHUB_USER_NAME`

In the example file, the first two variables contain a string similar to `<ADD YOUR GITHUB PAT HERE>`.
Replace that string with a **GitHub Personal Access Token** (PAT).
Heads to [github.com/settings/tokens](https://github.com/settings/tokens) to create a new Personal
Access Token for your user.

Replace the third variable has to with the GitHub username the PAT belongs to.

### 4. Setup SSH key

In the repository's `example.docker-compose.override.yml` (which at this point should be already 
renamed `docker-compose.override.yml`), there's also a `volumes` setting, which maps a local SSH
key into the container.

Make sure the path used there is the valid local path for the key that gives tou access to the MHH
project repositories.

### 5. Setup hosts file

The next step consists of forwarding to the localhost the domains used by the Docker services.

To do that, add the following lines:

```
127.0.0.1	vip-composer-bug8.local
127.0.0.1	pma.vip-composer-bug8.local
```

to the `/etc/hosts` file for Linux and Mac users.
For Windows users the file is located at `%SYSTEMDRIVE%/Windows/System32/Drivers/etc/hosts`
(where `%SYSTEMDRIVE%` is usually `C:`).

### 6. For MAC users

On a MAC, in the Docker Desktop app, go to `Preferences` → `General`.
Turn off _"Use gRPC Fuse for file sharing."_
That is likely on by default and will cause performance issues.


## First installation

With all requirements satisfied, and after the preparatory steps are done,
it is possible to start the Docker services.

Move to **the `.docker` folder** and run:

```shell
docker-compose up -d --remove-orphans --build
```

It will take a while for the process to complete, especially the first time.

After Docker services are up, it is time to run the setup script in the PHP service:

```shell
docker-compose run --rm php update-vip-go
```

That command will:

1. Install all dependencies via Composer
2. Prepare the repository for VIP configuration using [Inpsyde VIP Composer plugin](https://github.com/inpsyde/vip-composer-plugin)
3. Make sure the WordPress database is installed correctly

Please **be patient** because this will take a while.

When the process completes, the just-installed website should be accessible
at **`https://vip-composer-bug8.local/`**.

To log in, visit `https://vip-composer-bug8.local/wp-login.php` and use **`root`** as both username and
password.


## Configuration changes 

If something changes in `docker-compose.yml` (or in `docker-compose.override.yml`), it is
necessary to stop the services via `docker-compose down`, and then start them again, making sure
they are re-built, via `docker-compose up -d --remove-orphans --build`.

Please refer to [Docker compose](https://docs.docker.com/compose/) documentation for any doubt.


## Certificates

The Docker services build script also creates an SSL certificate. That permits us to visit the local site using HTTPS protocol.
However, the "root certificate" from which the certificate is generated
will not be recognized by browsers, and that could prevent us from
seeing the website, or at least will show a notification.

We can add the "root certificate" as a custom authority on the
browser to overcome that issue.
To do that, the browser will ask for the path of the root certificate (PEM file), that is 
`.docker/certs/rootCA.pem`.

An online search should provide enough directions on installing custom root certificates for your 
browser of choice. For example:
- [Chrome docs](https://support.google.com/chrome/a/answer/6342302)
- [Firefox docs](https://support.mozilla.org/en-US/kb/setting-certificate-authorities-firefox)

## Updating code and dependencies

When something changes in the code, for example, to update a Composer dependency or some PHP file in
the repo changes, it is necessary to:

- update Composer dependencies
- run Inpsyde VIP Go Composer plugin command

That can be done by executing, **from the `.docker` folder**:

```bash
./composer update && ./composer vip --local
```

The `.docker/composer` file is a bash script that forwards received arguments to the Composer binary inside the PHP container.
In other words, the one line above equals running:

```bash
docker-compose run --rm php sh -c "composer update && composer vip --local"
```


## WP CLI in the container

In the `.docker` folder, there's also a `wp` script that forward received arguments to the WP CLI 
binary inside the PHP container.

For example, it is possible to execute, **from the `.docker` folder**:

```bash
./wp user list --format=ids"
```

and that will be the same as running:


```bash
docker-compose run --rm php sh -c "wp user list --format=ids --allow-root"
```

Note how the script appends the  `--allow-root` flag automatically to avoid issues keeping the command as short as possible.


## Installed services

### Webservice

The web service is powered by PHP + NGINX. You can reach the home of your site at `https://vip-composer-bug8.local`.

If you didn't add the root CA to your browser (as described above in the "Certificates" section), 
you'd need to deal with a "not secure connection".
It is a local environment which means, besides the annoyance of an additional click, you won't 
experience any risk.

### phpMyAdmin

If you want, you can use phpMyAdmin at `http://pma.vip-composer-bug8.local/`.

### MailHog

[MailHog](https://github.com/mailhog/MailHog) is a friendly tool that will catch all the web server's emails.
That means you can review all the emails sent by your WordPress installation in the MailHog dashboard.

The dashboard is available at the address: `http://127.0.0.1:8081`


## Connect the database using a client

To interact with the database using a standalone client configuration details are as follows:

- **Host:** `127.0.0.1`
- **DB Name:** `wordpress`
- **User:** `root`
- **Password:** `root`
- **Port**: `53307`
