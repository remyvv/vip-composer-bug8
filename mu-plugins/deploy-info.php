<?php
/**
 * Plugin Name: Deploy Info
 */

declare(strict_types=1);

namespace Inpsyde\Vip;

if (defined('WP_INSTALLING') && \WP_INSTALLING) {
    return;
}

/**
 * @return string|null
 */
function deployIdFile(): ?string  {
    static $deployFile, $deployFileChecked;
    if ($deployFileChecked) {
        return $deployFile;
    }

    $deployFileChecked = true;
    $deployFile = null;

    $privateDir = privateDirPath();
    if (file_exists("{$privateDir}/deploy-id") && is_readable("{$privateDir}/deploy-id")) {
        $deployFile = "{$privateDir}/deploy-id";

        return $deployFile;
    }

    return null;
}

/**
 * @return string|null
 */
function deployId(): ?string  {
    static $deployId, $deployIdChecked;
    if ($deployIdChecked) {
        return $deployId;
    }

    $deployIdChecked = true;
    $deployId = null;

    $deployIdFile = deployIdFile();
    if ($deployIdFile) {
        $deployId = trim(@file_get_contents($deployIdFile));
        if (!$deployId) {
            $deployId = isLocalEnv() ? wp_generate_uuid4() : null;
        }
    }

    return $deployId;
}

/**
 * @return string|null
 */
function deployVersion(): ?string  {
    static $deployVer, $deployVerChecked;
    if ($deployVerChecked) {
        return $deployVer;
    }

    $deployVerChecked = true;
    $deployVer = null;

    $privateDir = privateDirPath();

    if (file_exists("{$privateDir}/deploy-ver")) {
        $deployVer = trim(@file_get_contents("{$privateDir}/deploy-ver"));
        $deployVer or $deployVer = null;
    }

    return $deployVer;
}

add_filter(
    'admin_footer_text',
    static function ($text) {
        $version = deployVersion();
        $id = deployId();
        $deployIdFile = deployIdFile();
        $timestamp = $deployIdFile ? @filemtime($deployIdFile) : null;
        $datetime = $timestamp ? date('Y-m-d H:i', $timestamp) : null;

        if (!$version && !$id && !$datetime) {
            return $text;
        }

        $format = ' <i>%s:</i>&nbsp;<b>%s</b> |';

        return $text . sprintf(
            '<br>| <b>Deploy</b>%s%s%s',
            $version ? sprintf($format, 'Version', esc_html($version)) : '',
            $id ? sprintf($format, 'ID', esc_html($id)) : '',
            $datetime ? sprintf($format, 'Date/Time', esc_html($datetime) . '&nbsp;UTC') : ''
        );
    }
);
