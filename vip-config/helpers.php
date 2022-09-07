<?php

/**
 * Configuration helpers.
 */

declare(strict_types=1);

namespace Inpsyde\Vip;

/**
 * @return bool
 */
function isLocalEnv(): bool {
    return determineEnv() === 'local';
}

/**
 * @return string
 */
function privateDirPath(): string {
    $local = dirname(INPSYDE_VIP_CONFIG_PATH) . '/private';

    return isLocalEnv()
        ? $local
        : (defined('WPCOM_VIP_PRIVATE_DIR') ? WPCOM_VIP_PRIVATE_DIR : $local);
}

/**
 * @return string
 */
function determineEnv(): string
{
    static $env;
    if ($env) {
        return $env;
    }

    $configEnv = false;
    if (defined('VIP_GO_APP_ENVIRONMENT')) {
        $configEnv = \VIP_GO_APP_ENVIRONMENT;
    } elseif (defined('VIP_GO_ENV')) {
        $configEnv = \VIP_GO_ENV;
    }

    ($configEnv && is_string($configEnv)) or $configEnv = 'local';

    /**
     * We support:
     * - "local"
     * - "development"
     * - "staging"
     * - "production"
     */
    $normalizationMap = [
        'local' => 'local',
        'development' =>  'development',
        'dev' =>  'development',
        'develop' =>  'development',
        'staging' =>  'staging',
        'stage' =>  'staging',
        'pre' =>  'staging',
        'preprod' =>  'staging',
        'pre-prod' =>  'staging',
        'pre-production' =>  'staging',
        'test' =>  'staging',
        'uat' =>  'staging',
        'production' =>  'production',
        'prod' =>  'production',
        'live' =>   'production',
    ];

    $env = $normalizationMap[$configEnv] ?? 'production';

    return $env;
}

/**
 * @param array<string, array<string, string>> $redirectToDomainsMap
 * @param string $currentEnv
 * @return bool
 */
function handleEarlyRedirect(array $redirectToDomainsMap, string $currentEnv): bool
{
    $redirectToHosts = $redirectToDomainsMap[$currentEnv] ?? null;
    // If there's nothing to redirect or is WP CLI let's do nothing.
    if (!$redirectToHosts || (defined('WP_CLI') && \WP_CLI)) {
        return false;
    }

    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $redirectHost = $httpHost ? ($redirectToHosts[$httpHost] ?? null) : null;

    if (!$redirectHost) {
        return false;
    }

    $requestPath = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

    // Don't redirect '/cache-healthcheck' since it breaks monitoring if it's redirected.
    if (strpos($requestPath, '/cache-healthcheck') === 0) {
        return false;
    }

    $redirectTo = $redirectHost . $requestPath;
    if ($_GET) {
        $redirectTo .= '?' . http_build_query($_GET);
    }

    $redirectToSafe = filter_var($redirectTo, FILTER_SANITIZE_URL);
    if ($redirectToSafe) {
        header("Location: https://{$redirectToSafe}", true, 301);

        return true;
    }

    return false;
}
