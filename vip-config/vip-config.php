<?php

/**
 * This is the network main configuration file that is loaded automatically and very early,
 * think of it as the equivalent of `wp-config.php` in VIP Go environment.
 *
 * We do most of the configuration in environment-specific config files that are located in the
 * `/env` sub-directory of current folder, namely we support:
 * - `/vip-config/env/local.php`       # for LOCAL environments
 * - `/vip-config/env/development.php` # for DEVELOPMENT environments
 * - `/vip-config/env/staging.php`     # for PRE-PRODUCTION environments
 * - `/vip-config/env/production.php`  # for PRODUCTION environments
 *
 * The file `/vip-config/env/all.php`, if present, is loaded for ALL environments.
 *
 * Environment-specific files are **not** loaded automatically by VIP code, like this file, but they
 * are loaded thanks to the function `Inpsyde\App\Bootstrap\loadConfigFiles()` that is located in the
 * MU plugin at `/mu-plugins/aaa-mah-application.php`.
 *
 * We use a MU plugin for environment-specific configuration (instead of this file) because in the
 * MU plugin it is possible to use all the symbols defined in VIP Go platform MU plugins.
 * In this file we do just what is necessary to be done as soon as WordPress starts loading.
 */

declare(strict_types=1);

/**
 * Helpers functions used in this file are loaded.
 */
HELPERS_FUNCTIONS: {
    define('INPSYDE_VIP_CONFIG_PATH', __DIR__);
    require_once __DIR__ . '/helpers.php';
}

/**
 * Determine environment and set the constants that will be used to all the env-specific logic.
 */
ENVIRONMENT_SETUP: {
    define('WP_ENVIRONMENT_TYPE', Inpsyde\Vip\determineEnv());
}

/**
 * Environment-specific constants that must be loaded as soon as possible.
 */
ENV_SPECIFIC_WP_CONSTANTS: {

    switch (WP_ENVIRONMENT_TYPE) {
        case 'local':
            defined('WP_LOCAL_DEV') or define('WP_LOCAL_DEV', true);
            define('WPCOM_VIP_JETPACK_LOCAL', true);
        case 'development':
            defined('WP_DEBUG') or define('WP_DEBUG', true);
            defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', true);
            defined('SAVEQUERIES') or define('SAVEQUERIES', true);
            defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', true);
            defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', true);
            defined('WP_DISABLE_FATAL_ERROR_HANDLER') or define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
            break;
        case 'staging':
            define('WP_DEBUG', false);
            define('WP_DEBUG_LOG', true);
            define('SAVEQUERIES', false);
            define('SCRIPT_DEBUG', false);
            define('WP_DEBUG_DISPLAY', false);
            break;
        case 'production':
        default:
            define('WP_DEBUG', false);
            define('WP_DEBUG_LOG', false);
            define('SAVEQUERIES', false);
            define('SCRIPT_DEBUG', false);
            define('WP_DEBUG_DISPLAY', false);
            break;
    }
}

/**
 * Handle domain redirect based on env-specific config in `/vip-config/redirect-domain-config.php`.
 */
DOMAIN_REDIRECT_HANDLING: {

    require_once __DIR__ . '/redirect-domain-config.php';
    if (Inpsyde\Vip\handleEarlyRedirect(INPSYDE_VIP_REDIRECT_DOMAINS_MAP, WP_ENVIRONMENT_TYPE)) {
        exit;
    }
}

/**
 * Some other generic WP constants that might be too late to define in MU plugin.
 */
GENERIC_WP_CONSTANTS: {

    if (defined('MULTISITE') && MULTISITE) {
        defined('COOKIE_DOMAIN') or define('COOKIE_DOMAIN', '');
        define('ADMIN_COOKIE_PATH', '/');
        define('COOKIEPATH', '/');
        define('SITECOOKIEPATH', '/');
    }

    define('DISALLOW_FILE_EDIT', true);
    define('DISALLOW_FILE_MODS', true);
    define('AUTOMATIC_UPDATER_DISABLED', true);
    define('VIP_JETPACK_IS_PRIVATE', true);
}
