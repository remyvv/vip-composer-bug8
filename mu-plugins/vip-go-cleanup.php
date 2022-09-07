<?php

/**
 * Plugin Name: Vip GO Cleanup
 * Description: Disable features we don't need.
 */

declare(strict_types=1);

if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

// Disable XML RPC
add_filter('xmlrpc_enabled', '__return_false');

// Remove RSD and wlwmanifest from <head>
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// Remove WordPress version from <head>
remove_action('wp_head', 'wp_generator');

// Hide some very annoying things from dashboard
add_action(
    'admin_print_scripts',
    function () {
        ?>
        <style>
			#footer-thankyou,
			#vp-notice,
			.wrap > .vp-notice.notice,
			.plugins-php .featured-plugins,
			.welcome-panel-close,
			.plugins-php #akismet_setup_prompt,
			form[name="akismet_activate"],
			#toplevel_page_jetpack,
			#toplevel_page_vip-dashboard {
				display: none !important;
			}
        </style>
        <?php
    }
);
