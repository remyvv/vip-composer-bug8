# Setup Local Development Environment

This repository contains a Docker setup that is ready to be used as local development environment.

For those who wants to use Docker, there are no useful information in this file, they should follow 
documentation in `/.docker/README.md`.

Developers that don't want to use that are still able to develop locally using any alternative: 
virtual machines, XAMP/MAMP and such or any other setup that meets the requirements.


## Requirements

- a LAMP/LEMP stack with PHP 7.3+
- MySQL 5.5+ with an empty database
- [Composer](https://getcomposer.org/)
- [Git](https://git-scm.com/)
- [WP CLI](https://wp-cli.org) is highly recommended.


## First installation

> *Note for Windows users: be sure to run all the commands mentioned below as an administrator.*

### Step 1: Clone the repo

The first thing to do is to **clone this repository** in a local folder.

Please consider that by following the next steps, a `/public` sub-folder will be created inside the folder where the repo is cloned, and that `/public` folder will be the web-root of the project for the local webserver. So make sure to clone the repository in a folder reachable by the webserver.

### Step 2: Install dependencies

The next step is to run Composer:

```shell
composer install
```

This might take a while.

When Composer finishes all the dependencies are installed, however, there’s still the need to:

- have a folder structure compatible to what VIP Go expects
- have a working local environment, which includes WordPress and VIP Go MU plugins

These two requirements are the duty of [VIP Go Composer Plugin](https://github.com/inpsyde/vip-composer-plugin).

If the output of Composer does not show anything related to the VIP Go plugin, that it means the plugin has to be run manually.

### Step 3: Run Composer VIP plugin

VIP Go Composer plugin can be configured to run automatically every time Composer installs or update dependencies but, by default, it must be called manually. That's straightforward though:

```shell
composer vip --local
```

Note: this *will* take a while. The main reason is the [VIP Go MU plugins](https://github.com/Automattic/vip-go-mu-plugins) repository, a nested Git-submodules repository that accounts for a few hundred’ megabytes of files pulled recursively from several sources. Don’t worry, this will happen only the first time.

The command output at the end should be something like this:

This is the full list of what the command have done if it finished successfully:

- WordPress has been downloaded
- VIP Go MU plugins have been downloaded
- "Dev paths" have been synchronized (more on this later)
- Autoload has been created
- `wp-config.php` has been created and now waits for configuration
- Deploy ID has been created. It is a random UUID that is unique per build. It is saved in a text file in `/private` folder. Because it is unique per build (and so per deploy). It can be used, for example, to bust caches, so that at every deploy caches are invalidated.

#### The new folder structure

Before proceeding with the next steps, might be interesting to have a look at what is the expected files and folders structure after Composer and VIP Go Composer plugin have been executed.

Before seeing the details of new folders and files, it must be noted that:

- the files and folders that composed the repository *before* executing Composer and Composer VIP Plugin are there unchanged
- **the new files and folder are all git-ignored**, so they are specific for the local environment, and will not be pushed to the repository.

The new files and folders tah tare now in the project folder have been created either by Composer or by the Composer VIP Plugin.

They are:

##### /public

This is the **project web-root**, and it contains a standard WordPress installation.

*Note: in the image all the WordPress root files (`wp-load.php`, `wp-login.php`, etc...) have been removed for readability’s sake.*

##### /uploads

This folder will contain all the media files uploaded locally in WordPress. The folder is located outside the `/public` folder to make the latter entirely disposable. However, because `/public` folder is the web-root, `/uploads` folder is symlinked to `/public/wp-content/uploads` that is the standard WordPress uploads folder, so as long as WordPress (and the web-server) are concerned uploads can be served correctly.

Especially relevant for Linux users: make sure the folder is writable by the local web-server and PHP.

##### /vip

This folder contains the **exact 1:1 representation of the VIP Go repository**. It contains exactly the files that will be pushed to the VIP Go repository for the project. It could be possible to make this folder a Git root on its own and push it to the VIP GitHub project repository to obtain a deployment. That almost exactly how we deploy our changes online, but more on this later.

##### /vip-go-mu-plugins

This folder contains [VIP Go MU plugins](https://github.com/Automattic/vip-go-mu-plugins). In the `wp-config.php` file that is also generated, this folder is _already_ configured as the WordPress `WPMU_PLUGIN_DIR` so that locally WordPress will correctly load all the VIP MU plugins.

##### /composer.lock

The Composer lock file.

Those familiar with Composer could wonder where the Composer "vendor" folder and the Composer autoload file are located.

The answer is: inside `/vip/client-mu-plugins`. As written above, the `/vip` folder contains exactly what will be pushed to the VIP Git repository, and because for sure we need Composer libraries and autoload online, we need those to be inside the `/vip` folder.

##### /wp-cli.yml

[WP CLI config file](https://make.wordpress.org/cli/handbook/references/config/#config-files). It contains a reference to the WordPress installation path, so WP CLI commands can be executed from the project root folder without having to pass the `--path` parameter all the time.

##### /wp-config.php

WordPress configuration file for the local environment. This is slightly different from a "standard" `wp-config.php` because it contains
- definition of `WPMU_PLUGIN_DIR` pointing `/vip-go-mu-plugins` as the source of MU plugins.
- a `require` statement to load `vip-config/vip-config.php` simulating what happens in VIP Go

The first time the VIP Go Composer plugin is executed there's the need to fill it with project settings, and that's the next installation step.


### Step 4: Configure wp-config.php

The first thing to do is to add database settings (`DB_NAME`, `DB_USER`, etc...) in the `wp-config.php`.

It also worth setting the nonce and secret salt keys. Using WP CLI it is as easy as:

```shell
wp config shuffle-salts
```

Please also make sure `WP_DEBUG` is set to `true`.


### Step 5: Configure Web Server

To configure web server it is necessary to point to `/public` folder as the project web-root. Very likely it means to create a "virtual host" connected to a local domain and pointing `/public` folder as the project web-root.

The actual procedures to make that happen depends on the webserver in use (nginx or Apache), how it is executed (host machine, container, virtual machine), and the host OS (Windows, macOS, Linux...) We are not going to document here all the steps for all the possible combinations.

Beside configuring the virtual host it makes sense to make sure "URL rewriting" works. This will require a different approach in the case of multisite (more on this below).

When using Apache, to make URL rewriting work, it might be necessary to place a `.htaccess` inside the `/public` folder. Feel free to do it. After all, the `/public` folder is git-ignored.

Anyway, if everything has been set up correctly, visiting the URL associated with the just configured virtual host the browser should show the WordPress installation screen.


### Step 6: Install WordPress

Visiting the URL associated with the just configured virtual host it is possible to proceed with the browser-based installation of WordPress.

However, **it is recommended to use WP CLI for the scope, especially in the case the project must be a multisite installation**.

Using WP CLI it is possible to run a command like the following:

```shell
wp core multisite-install --title="My VIP Go project" \
--admin_user="myself" \
--admin_email="user@example.com" \
--url="http://local.my-vip-go-project.com" \
--skip-email \
--subdomains
```

The above command makes WP CLI install WordPress for multisite.

Make sure to replace `--admin_user`, `--admin_email`, and `--url` values with the correct ones.

In the case of a single site, replace `multisite-install` with `install`.

See documentation for [`core` WP CLI command](https://developer.wordpress.org/cli/commands/core/).

As the command output will suggest, *don't forget to set up rewrite rules (and a .htaccess file, if using Apache)*.

**At this point, the first installation is complete, and the development can start.**

However, before having a functioning local environment *might* be required to "compile" frontend assets and/or download packages' translations.


### Step 7: Compile assets

Many packages developed at Inpsyde do not keep "compiled" frontend assets in version control.

For example, they keep under version control SCSS and TypeScript files and not the generated CSS and JavaScript. These packages usually expose [a building script in their `packages.json`](https://docs.npmjs.com/misc/scripts).

The [*Composer Assets Compiler*](https://github.com/inpsyde/composer-asset-compiler) package by Inpsyde is a Composer plugin capable of looping through all the packages installed via Composer and find those that need this "building script" to be executed. Packages are recognized thanks to configuration placed either in the packages' `composer.json` or in the root project `composer.json`.

To compile assets for the installed packages, call the command:

```shell
composer compile-assets -v
```

Depending on the number of packages that need to be compiled the command might take a while. Sometimes more than a while. In fact, before executing the "build" command the package will install frontend dependencies via npm or Yarn (depending what's available on the system).

The compiler will make sure to don't compile again packages that are already compiled, so the long wait only happens the first time.

Please refer to the Composer plugin [documentation](https://github.com/inpsyde/composer-asset-compiler/blob/master/README.md) for more information.


### Step 8: Download translations (optional)

Many public WordPress plugins and themes, just like many packages developed at Inpsyde, do not keep translation files under version control.

Plugins and themes hosted on wp.org will have their translations hosted separately there, and private Inpsyde packages will have translations available at Inpsyde Glotpress installation.

If the projects support translations (the presence of a `wp-translation-downloader.json` file in project root is a good sign of that) might be desired to download all necessary translations to test them locally.

Of course, all packages will be fully working without translations so this step might be skipped with no issue.

On the other hand, sometimes it is required to test translations locally, especially in the case the support to both LTR and RTL languages has to be guaranteed.

Unless it has been removed due to lack of translations support in the project, [*WP Translation Downloader*](https://github.com/inpsyde/wp-translation-downloader) Composer plugin will be available, and it will allow installing all translations for all packages that are configured in `wp-translation-downloader.json` with a single command:

```shell
composer wp-translation-downloader:download
```

Please refer to the Composer plugin [documentation](https://github.com/inpsyde/wp-translation-downloader/blob/master/README.md) for more details.


## Local Development Workflow

Having a local environment up and running is just the start of the development process.

The workflow of development will be:

1. create a new repository for each theme/plugin/library that has to be used in the project
2. push it to a remote VCS service (GitHub, BitBucket, GitLab...)
3. add it as a dependency to the project's `composer.json`
4. run **`composer update && composer vip --local`** to update the dependencies and refresh the local environment
5. (if needed) run **`composer compile assets && composer wp-translation-downloader:download`** to compile the frontend assets and download the translations

Having to deal with *remote* repositories for each plugin/theme/library might be overkill, especially in the very early stages of development.

### Local Development with Composer Studio

It is much better to deal with _local_ repositories during development. Luckily Composer supports ["path repositories"](https://getcomposer.org/doc/05-repositories.md#path).

In short, instead of using a VCS repository "as source" for a package, Composer can use a local path (even relative) so that it is possible to run `composer install` and get the content of that folder installed just like any other remotely-hosted package.

The nice thing about it is that the local folder used as path repository could be very well the local clone of a remote repository, so that when changes are finalized and tested locally (thanks to path repository) they can be pushed to the remote repository to have them deployed online.

The bad thing about it is that using path repositories requires changing the `composer.json` and that's not an option, because it does not make sense to place local paths in a `composer.json` that is pushed online.

To solve this problem it is possible to make use of [Composer Studio](https://github.com/franzliedke/studio), a Composer plugin that allows the usage of Composer path repositories without changing the `composer.json`, but making use of a separate **`studio.json`** file that can be easily git-ignored and so kept local.

### Local Development with Dev Paths

The default project structure contains a `/mu-plugins` folder that contains project-specific MU plugins, considering that to have a separate repository for each single MU plugin will be overkill.

The same concept might apply to small plugins and/or child themes, often made by just a few files.

In fact, VIP Go Composer plugin supports also plugins and themes to be part of "dev paths" that is the paths that are synced with `vip/` folder and symlinked in `public/wp-content/`.

By only creating a `/plugins` and/or a `/themes` folder in project root, every time the local environment is built (that is via `composer vip --local` or `composer vip --sync-dev-paths`) the whole content of those folders is made available in the `/vip` folder (so can be deployed) and symlinked to `public/wp-content` (so can be tested locally).

### Dev Paths

Having **a copy** of all MU plugins and all configuration files in the `/vip` folder means that **every time a change is made to MU plugins and configuration files, those have to copied again to the  `/vip` folder**.

Even if those files are not changed often, having to copy them manually might be overkill, this is why the VIP Go Composer plugin provides a command to do it automatically:

```shell
composer vip --sync-dev-paths
```

Many IDEs have the feature to add a "watcher" that will run such command automatically when something in those folders changes, removing the need for the developer to care about it.

### How local WordPress works

It has been said several times already how the `/vip` folder contains the exact code that will be deployed to VIP Go.

It has also been said how the local web-root that contains WordPress files is `/public`. How can local WordPress "see" plugins, themes, and other libraries available inside `/vip`?

The answer is very simple: the whole content of `/vip` (only excluding `/vip/config` and `vip/vip-config` folders) is symlinked to `public/wp-content` so WordPress will see them.

Symlinks are created/updated if necessary every time `composer vip --local` (or `composer vip --sync-dev-paths`) is called, ensuring that local WordPress matches exactly what will be deployed online.
