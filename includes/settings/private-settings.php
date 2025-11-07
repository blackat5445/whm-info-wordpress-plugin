<?php
if (!defined('ABSPATH')) exit;

/**
 * Default values for private settings.
 *
 * @return array
 */
function whmin_get_private_settings_defaults() {
    return array(
        // Cron schedule slug for site status checks.
        'status_cron_schedule'      => 'whmin_15_minutes',

        // WHM server data cache TTL for private dashboard (in minutes).
        'server_data_cache_minutes' => 5,

        // Per-site HTTP timeout for site status checks (in seconds).
        'site_status_timeout'       => 8,

        // NEW: sites metadata refresh interval (hours)
        'site_meta_refresh_hours'     => 24,
    );
}

/**
 * Get merged private settings (saved values + defaults).
 *
 * @return array
 */
function whmin_get_private_settings() {
    $defaults = whmin_get_private_settings_defaults();
    $saved    = get_option('whmin_private_settings', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    return wp_parse_args($saved, $defaults);
}

/**
 * Sanitize private settings before saving.
 *
 * @param array $input
 * @return array
 */
function whmin_sanitize_private_settings($input) {
    $defaults = whmin_get_private_settings_defaults();
    $output   = $defaults;

    $input = is_array($input) ? $input : array();

    // 1) Cron schedule
    $allowed_cron = array(
        'whmin_5_minutes',
        'whmin_10_minutes',
        'whmin_15_minutes',
        'whmin_30_minutes',
        'whmin_60_minutes',
        'fifteen_minutes', // legacy compatibility
    );

    if (!empty($input['status_cron_schedule']) && in_array($input['status_cron_schedule'], $allowed_cron, true)) {
        $output['status_cron_schedule'] = $input['status_cron_schedule'];
    }

    // 2) Server data cache interval (minutes)
    if (isset($input['server_data_cache_minutes'])) {
        $minutes = (int) $input['server_data_cache_minutes'];
        if ($minutes < 1) {
            $minutes = 1;
        } elseif ($minutes > 1440) {
            $minutes = 1440; // Max 24h
        }
        $output['server_data_cache_minutes'] = $minutes;
    }

    // 3) Site status timeout (seconds)
    if (isset($input['site_status_timeout'])) {
        $timeout = (int) $input['site_status_timeout'];
        if ($timeout < 1) {
            $timeout = 1;
        } elseif ($timeout > 60) {
            $timeout = 60;
        }
        $output['site_status_timeout'] = $timeout;
    }

    // 4) Site meta refresh interval (direct+remote)
    if (isset($input['site_meta_refresh_hours'])) {
        $hours = (int) $input['site_meta_refresh_hours'];
        if ($hours < 1) {
            $hours = 1;
        } elseif ($hours > 168) {
            $hours = 168;
        }
        $output['site_meta_refresh_hours'] = $hours;
    }

    return $output;
}

/**
 * Register the private settings option group.
 */
function whmin_register_private_settings() {
    register_setting(
        'whmin_private_settings',      // settings_fields() group
        'whmin_private_settings',      // option name in wp_options
        'whmin_sanitize_private_settings' // sanitization callback
    );
}
add_action('admin_init', 'whmin_register_private_settings');
