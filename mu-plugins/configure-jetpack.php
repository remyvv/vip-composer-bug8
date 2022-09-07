<?php
/**
 * Plugin Name: Configure Jetpack
 */

declare(strict_types=1);

if (defined('WP_INSTALLING') && \WP_INSTALLING) {
    return;
}

add_filter(
    'jetpack_get_available_modules',
    static function ($modules): array {
        return (array)$modules;
    }
);
