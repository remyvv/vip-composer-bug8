<?php
/**
 * Plugin Name: Inpsyde Application bootstrap
 */

declare(strict_types=1);

namespace Inpsyde\App\Bootstrap;

use Inpsyde\App;
use Inpsyde\Vip\MaintenanceMode;

use function Inpsyde\Vip\isLocalEnv;
use function Inpsyde\Vip\privateDirPath;

/**
 * Loads configuration files from '/vip-config/env/' based on current environment.
 *
 * @return void
 */
function loadConfigFiles(): void {
    static $loaded;
    if ($loaded || !defined('INPSYDE_VIP_CONFIG_PATH') || !defined('WP_ENVIRONMENT_TYPE')) {
        return;
    }

    $loaded = true;

    defined('WPCOM_VIP_PRIVATE_DIR') or define('VIPGO_VIP_PRIVATE_DIR', privateDirPath());

    $configPath = INPSYDE_VIP_CONFIG_PATH . '/env/' . WP_ENVIRONMENT_TYPE . '.php';
    // If local specific file is not there, try to use "development" file.
    if (isLocalEnv() && !file_exists($configPath)) {
        $configPath = INPSYDE_VIP_CONFIG_PATH . '/env/development.php';
    }

    if (file_exists($configPath)) {
        require_once $configPath;
    }

    if (file_exists(INPSYDE_VIP_CONFIG_PATH . '/env/all.php')) {
        require_once INPSYDE_VIP_CONFIG_PATH . '/env/all.php';
    }

    if (WP_ENVIRONMENT_TYPE === 'local' && file_exists(WP_CONTENT_DIR . '/db.php')) {
        @unlink(WP_CONTENT_DIR . '/db.php');
    }
}

/**
 * Helper to reliable get a singleton instance of `App`.
 * @return App\App
 */
function app(): App\App {
    static $app;
    if (!$app) {
        loadConfigFiles();
        $app = App\App::new(new App\Container(new App\EnvConfig('Inpsyde\\Vip\\Config')));
    }

    return $app;
}

\Inpsyde\App\Bootstrap\app()
    ->boot();
