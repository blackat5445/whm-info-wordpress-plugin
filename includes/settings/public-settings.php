<?php
/**
 * Functions for the Public Settings tab.
 */
if (!defined('ABSPATH')) exit;

// Register all settings for the public page
add_action('admin_init', 'whmin_register_public_settings');

function whmin_register_public_settings() {
    // Register one setting that stores all options as an array
    register_setting('whmin_public_page_settings', 'whmin_public_settings', 'whmin_sanitize_public_settings');
}

/**
 * Helper function to get all public settings with defaults.
 * @return array
 */
function whmin_get_public_settings() {
    $defaults = [
        // WHM Server Graph
        'enable_server_graph' => true,
        'server_graph_timeframes' => ['24h', '7d', '1m', '3m', '6m', '12m', '60m'],
        'server_graph_bar_color' => '#075b63',
        'server_graph_button_bg' => '#075b63',
        'server_graph_button_text' => '#ffffff',

        // Managed Servers Graph
        'enable_managed_graph' => true,
        'managed_graph_timeframes' => ['24h', '7d', '1m', '3m', '6m', '12m', '60m'],
        'managed_graph_bar_color' => '#075b63',
        'managed_graph_button_bg' => '#075b63',
        'managed_graph_button_text' => '#ffffff',

        // Site Counters
        'enable_hosted_counter' => true,
        'enable_managed_counter' => true,
        'show_hosting_breakdown' => true,
        'counter_color' => '#075b63',

        // Maintenance Section
        'enable_maintenance_news' => true,
    ];

    $settings = get_option('whmin_public_settings', []);
    return wp_parse_args($settings, $defaults);
}

/**
 * Sanitization callback for the settings array.
 */
function whmin_sanitize_public_settings($input) {
    $sanitized = [];
    $defaults = whmin_get_public_settings();

    foreach ($defaults as $key => $default_value) {
        if (isset($input[$key])) {
            if (is_bool($default_value)) {
                $sanitized[$key] = rest_sanitize_boolean($input[$key]);
            } elseif (is_array($default_value)) {
                $sanitized[$key] = is_array($input[$key]) ? array_map('sanitize_text_field', $input[$key]) : [];
            } elseif (strpos($key, '_color') !== false) {
                $sanitized[$key] = sanitize_hex_color($input[$key]);
            } else {
                $sanitized[$key] = sanitize_text_field($input[$key]);
            }
        } else {
             // Handle checkboxes being unset
            if (is_bool($default_value)) {
                $sanitized[$key] = false;
            }
        }
    }
    return $sanitized;
}


// AJAX handler for manual refresh (remains the same)
add_action('wp_ajax_whmin_manual_status_refresh', 'whmin_ajax_manual_status_refresh');
function whmin_ajax_manual_status_refresh() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')]);
    }
    whmin_check_and_log_statuses();
    wp_send_json_success(['message' => __('Site statuses have been successfully refreshed.', 'whmin')]);
}