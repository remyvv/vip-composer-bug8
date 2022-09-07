# Vip Composer Bug8 Website Repository

Sample project to demonstrate vip-composer-plugin#8

## Just created from template repository?

If there's a file named `/README_FIRST.md` in the root of this repo, it means some configuration is 
needed before this can be usable, please **read that file before continuing**.


## Looking for help on setup the local development environment?

If you plan to use Docker, read the `/.docker/README.md` file,
otherwise, read the `/LOCAL_DEV_ENV.md` file,


## Understanding Folders and Files Structure

### /config

The config folder that will contain the YAML files used for domain configuration, as described in [VIP Go documentation](https://wpvip.com/documentation/vip-go/syncing-data-on-vip-go/#domain-mapping-config).

### /images

This folder contains site-wide available images, it is part of the standard [VIP Go codebase](https://github.com/Automattic/vip-go-skeleton/tree/master/images).

### /mu-plugins

This folder contains project-specific MU plugins.
Please note that these are not the VIP Go MU plugins, but MU plugins that are specific to the project.

The standard Inpsyde process requires a different repository for each plugin/theme/library then pulled together via the `composer.json` in this repository. However, each MU plugin is a single PHP file, often composed of a few lines, and put each of them in a separate repository is overkill. This is why `/mu-plugins` folder exists: all the files contained in there will be deployed in the [`client-mu-plugins` folder of VIP Go codebase](https://wpvip.com/documentation/vip-go/managing-plugins/#installing-to-the-client-mu-plugins-directory).

### /private

This folder is the equivalent of the namesake folder in VIP Go codebase. As described in [VIP Go documentation](https://wpvip.com/documentation/vip-go/understanding-your-vip-go-codebase/#using%c2%a0private) this folder exists to contain files that are *not* web accessible but can be accessed by your theme or plugins. Typical use-cases are certificates and key files, etc.

Please note that the private folder on VIP is only readable from PHP and **not writable**.

### /vip-config

This is where the site configuration is placed. There is a correspondent and namesake folder [in VIP Go codebase](https://github.com/Automattic/vip-go-skeleton/tree/master/vip-config) that is supposed to contain a **vip-config.php** file, that is used to contain PHP configuration constants that in a “normal” installation would go in `wp-config.php`, considering that is not accessible on VIP Go.

However, our workflow involves more than that. The most important thing is that the code in this folder facilitates having environment specific configuration, for example, configuration for “development” environment and “production” environment can live in different files and so there’s no need to rely on git branches to obtain that.

The environment-specific configuration is done in the files that are located inside the `/vip-config/env/` folder. More in-depth documentation about this whole process will follow below.

Moreover, there’s a workflow in place to facilitate the redirection of whole domains. This is useful when the VIP Go environment is used to migrate to an old environment, and it is desired to redirect some old domains to new sites/addresses.

### /composer.json

This is where “the magic” happens. [Composer](https://getcomposer.org/) is used to pulling together all the dependencies that will be used in the project.

Beside plugin, themes, and libraries used for the project itself there are three dependencies (actually [Composer plugins](https://getcomposer.org/doc/articles/plugins.md)) that are used to make this repository compliant with both the VIP Go repository folders' structure and a local development environment.

They are:

- [**VIP Go Composer Plugin**](https://github.com/inpsyde/vip-composer-plugin) used to build the VIP Go-compatible folder structure, and to prepare the local development environment (download WordPress, download VIP Go MU plugins, initialize wp-config.php, etc).
- [**Composer Assets Compiler**](https://github.com/inpsyde/composer-asset-compiler) used to “compile” frontend assets (CSS, JS...) of dependencies installed via Composer using npm/Yarn scripts.
- [**WP Translation Downloader**](https://github.com/inpsyde/wp-translation-downloader) used to download translation files for WP packages (themes, plugins) installed via Composer. The source of translation files might be wordpress.org for public packages or custom Glotpress installations.

These three dependencies are installed with all the others by when Composer is executed, and they might execute automatically on Composer install/update or be triggered manually by custom commands, depending on their configuration.

Additional information for these plugins will be provided here, and a quite detailed README is available for each of them in their repository.

### /wp-translation-downloader.json

This file contains configuration for the *WP Translation Downloader* Composer plugin mentioned right above.


## Deploy

The `composer vip` command (executed in Docker or manually when using alternative local development workflows),
will create a `/vip` folder that is a **1:1 reflection of VIP Go GitHub repository for the project**, 
and provides an exact preview of how the project looks like, and allow us to "deploy" by pushing this 
folder to GitHub (kind of).

In fact, VIP Go Composer plugin has a command for that:

```shell
composer vip --deploy
```

When called, this command will commit the content of `/vip` folder to the VIP GitHub repository configured in `composer.json`.

To be precise, the command will not push the _exact_ content of the folder but will do some optimizations first, e.g. it will optimize the Composer autoload (by removing dev dependencies, for example) and will not push unnecessary files like tests, `node_modules` and so on.

Please read [the command documentation](https://github.com/inpsyde/vip-composer-plugin#command-reference) to learn (among other things) how to push to different branches, and so deploy to different environments.

### Deploy via CI

More often than not, `composer vip --deploy` command will not be called locally, but there will be a CI service configured to do it "on-demand" and/or automatically when a commit is done on the remote "website" repository.

This repository comes with a GitHub action workflow file located at `.github/workflows/Deployment.yml` that configures a workflow job that deploys the project to VIP Go.

This workflow to work relies on a GitHub "bot" user that must have rights to push to the VIP Go repository, and whose SSH key must be added to repository secrets.

Everytime a commit is done in one of the target branches (configurable on `Deployment.yml`), deployment to VIP starts automatically 
(unless the commit message contains the string `"skip deploy"`).

### Deployment environment variables

The file found at `.github/workflow/Deployment.yml` must be configured using environment variables.

The deployment steps are written to make use of these environment variables, and when those are 
configured **the workflow should work without any further modification**.

Normally you will edit the file only one time, during the very first set up of the project, so if this isn't your case
then probably you don't need to edit it.

#### GitHub user configuration

Most of the tasks performed in that file needs a GitHub user. It is strongly suggested using a "bot"
user instead of a user connected to a human.

The most important thing is that the user has write (push) access to the same VIP repository
configured when running the setup script.

- `GITHUB_USER_EMAIL` This is the email connected to the user
- `GITHUB_USER_NAME` This is the username connected to the user
- `GITHUB_USER_PRIVATE_KEY` This is the private part of an SSL key generated for the user, and whose
  public part has been uploaded to [the user's GitHub profile](https://github.com/settings/keys)
- `GITHUB_API_TOKEN` This is a "Personal Access Token" associated with the user, generated via
  [GitHub "Developer settings"](https://github.com/settings/tokens) which needs to have, at least,
  "repo" permission.
  
#### GitHub branches configuration

- `BRANCH_DEV` the name of the branch in this repo that will contain code that will be deployed on
  the **"development"** environment on VIP repository.
- `BRANCH_STAGING` the name of the branch in this repo that will contain code that will be deployed on
  the **"staging"** environment on VIP repository.
- `BRANCH_PRODUCTION` the name of the branch in this repo that will contain code that will be deployed on
  the **"production"** environment on VIP repository.
  
Defaults are provided for these values, and it is probably fine to keep them as is.

It is a good idea to create the branches and push them right after the configuration is completed.

Please note that the name for these three branches need to match the branches listed under
[`on.push.branches`](https://github.com/inpsyde/vip-go-website-template/blob/master/.github/workflows/Deployment.yml#L30-L33)
in the same file. The default values are already there.

- `VIP_BRANCH_DEV` the name of the branch _on the VIP repository_ that will receive the code for the
  **"development"** environment on VIP repository. This is usually "develop", but please confirm that with VIP support.
- `VIP_BRANCH_STAGING` the name of the branch _on the VIP repository_ that will receive the code for the
  **"staging"** environment on VIP repository. This is usually "preprod", but please confirm that with VIP support.
- `VIP_BRANCH_PRODUCTION` the name of the branch _on the VIP repository_ that will receive the code for the
  **"production"** environment on VIP repository. This is usually "master", but please confirm that with VIP support.


## Environment-specific configuration

It has mentioned already how this repository supports environment-specific configuration.

All the configuration that in "standard" WordPress installation are done in `wp-config.php` on VIP Go are done instead in `vip-config/vip-config.php` file. Even if that file is part of this repository, and it is used for few configuration constants that have to be defined very early, many other configurations values especially those "consumed" by plugins (thinks for example at API keys and such)  can be set per-environment using environment-specific files located inside **`/vip-config/env/`** folder.

Those files are:

- `vip-config/env/all.php` loaded for all environments
- `vip-config/env/develop.php` loaded for "development" environment (and for local environment, unless a `vip-config/env/local.php` exists)
- `vip-config/env/preprod.php` loaded for "pre-production" / "staging" environment
- `vip-config/env/production.php` loaded for "production"environment

The  `vip-config/env/local.php` file is also supported to be loaded for local environment, and it is git-ignored by default. When this file is not present, the `vip-config/env/develop.php` file is loaded, so it is possible to avoid duplications.

Also consider that for local environment it is possible to use `wp-config.php` directly.

Please remember that any change to those files needs to be synced with `/vip` folder via the `composer vip --sync-dev-paths` command, (unless your IDE is configured to do it for you).

### Configuration and wp-app-container

Configuration in the files mentioned above is often used in combination with Inpsyde ["WP App Container"](https://github.com/inpsyde/wp-app-container) (by default part of any VIP Go project), so those configuration files normally contain namespaced constants that can be retrieved by the [`EnvConfig` class provided by that package](https://github.com/inpsyde/wp-app-container#customizing-site-config).

## Use themes and plugins while developing them 

It could happen, above all in the early stage, that you need to use a repository while working on it, such as theme and 
plugins.

For this purpose, we included the [Studio](https://github.com/franzliedke/studio) package in the project, which 
allows you to require packages from the filesystem instead of a packagist repository. Once you require yor dependency, 
Studio will check if it's present in the filesystem, if so it will let composer get the package from there, then Studio 
will symlink the directory so that you can use it as a standard part of the project.

You can place the packages to symlink in the `packages/` directory, Studio watches at the directory automatically, 
after you run the script `update-vip-go.sh`. 

So the steps are:
 1. Place a theme or a plugin directory in `packages/`.
 2. If not already done, add manually the requirement in your `composer.json`.
 3. run `composer update YOUR-PACKAGE`, a message should alert that the files have been copied and symlinked.
 
Now you can work on the files in `packages/YOUR_PACKAGE` and the changes will be automatically propagated to 
proper directories.

NOTE: Also if stored in filesystem, we are still talking about Composer packages, so the folders must contain a valid 
`composer.json` to be used.