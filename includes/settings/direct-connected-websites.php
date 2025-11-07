<?php
/**
 * Functions for the Direct Connected Websites settings tab.
 * ADDED: Include/Exclude from monitoring functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves and prepares the list of directly connected websites.
 * NOW INCLUDES: monitoring_enabled flag
 */
function whmin_get_direct_connected_sites_data() {
    // Fetch the list of accounts from the WHM API
    $accounts_response = whmin_get_whm_accounts();

    if (is_wp_error($accounts_response)) {
        return $accounts_response;
    }
    
    if (empty($accounts_response)) {
        return [];
    }

    // Get custom names and monitoring settings
    $custom_names         = get_option('whmin_custom_site_names', []);
    $monitoring_settings  = get_option('whmin_direct_monitoring_settings', []);
    $connect_status_map   = get_option('whmin_direct_connect_status', []); // NEW
    if (!is_array($connect_status_map)) {
        $connect_status_map = [];
    }

    $sites_data = [];
    $id_counter = 1;

    foreach ($accounts_response as $account) {
        $user_key = $account['user'];
        
        // Determine the display name
        $display_name = !empty($custom_names[$user_key]) 
            ? $custom_names[$user_key] 
            : $user_key;

        // Check if monitoring is enabled (default: true)
        $monitoring_enabled = isset($monitoring_settings[$user_key]) 
            ? (bool)$monitoring_settings[$user_key] 
            : true;

        // Disk usage handling
        $disk_used_raw        = $account['diskused'];
        $disk_usage_formatted = '';
        $disk_used_bytes      = 0;

        if (strpos($disk_used_raw, '/') !== false) {
            $parts       = explode('/', $disk_used_raw);
            $disk_used_mb = $parts[0];
        } else {
            $disk_used_mb = $disk_used_raw;
        }

        if (is_numeric($disk_used_mb)) {
            $disk_used_bytes      = (float)$disk_used_mb * 1024 * 1024;
            $disk_usage_formatted = whmin_format_bytes($disk_used_bytes);
        } else {
            $disk_usage_formatted = esc_html(ucfirst($disk_used_mb));
            $disk_used_bytes      = PHP_INT_MAX;
        }

        // Determine WHM/monitoring status
        if (!$monitoring_enabled) {
            $status = ['text' => __('Monitoring Disabled', 'whmin'), 'class' => 'secondary'];
        } elseif (!empty($account['suspended'])) {
            $status = ['text' => __('Suspended', 'whmin'), 'class' => 'danger'];
        } else {
            $status = ['text' => __('Active', 'whmin'), 'class' => 'success'];
        }

        // NEW: Connect plugin / agent connection status
        $conn_row = $connect_status_map[$user_key] ?? null;
        if (is_array($conn_row) && ($conn_row['status'] ?? '') === 'activated') {
            $connection_status = 'activated';
        } else {
            $connection_status = 'not_activated';
        }
            
        $sites_data[] = [
            'id'                 => $id_counter++,
            'user'               => esc_html($account['user']),
            'name'               => esc_html($display_name),
            'url'                => esc_url('http://' . $account['domain']),
            'setup_date'         => esc_html(date_i18n(get_option('date_format'), $account['unix_startdate'])),
            'setup_timestamp'    => $account['unix_startdate'],
            'disk_used'          => $disk_usage_formatted,
            'disk_used_bytes'    => $disk_used_bytes,
            'status'             => $status,
            'monitoring_enabled' => $monitoring_enabled,
            'connection_status'  => $connection_status, // NEW
        ];
    }

    return $sites_data;
}

/**
 * AJAX handler to update a website's custom name.
 */
function whmin_ajax_update_site_name() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

    if (empty($user) || empty($new_name)) {
        wp_send_json_error(['message' => __('Invalid data provided.', 'whmin')], 400);
    }
    
    $custom_names = get_option('whmin_custom_site_names', []);
    $custom_names[$user] = $new_name;
    
    update_option('whmin_custom_site_names', $custom_names);

    wp_send_json_success([
        'message' => __('Website name updated successfully.', 'whmin'),
        'newName' => $new_name
    ]);
}
add_action('wp_ajax_whmin_update_site_name', 'whmin_ajax_update_site_name');

/**
 * NEW: AJAX handler to toggle monitoring for a direct site
 */
function whmin_ajax_toggle_direct_monitoring() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';

    // Parse "enabled" robustly: accept 1/0, true/false, "on"/"off", etc.
    $enabled_raw = $_POST['enabled'] ?? null;
    $enabled = filter_var($enabled_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if (empty($user) || $enabled === null) {
        wp_send_json_error(['message' => __('Invalid data provided.', 'whmin')], 400);
    }
    
    $monitoring_settings = get_option('whmin_direct_monitoring_settings', []);
    if (!is_array($monitoring_settings)) {
        $monitoring_settings = [];
    }
    $monitoring_settings[$user] = $enabled;

    update_option('whmin_direct_monitoring_settings', $monitoring_settings);

    $message = $enabled 
        ? __('Monitoring enabled successfully.', 'whmin')
        : __('Monitoring disabled successfully.', 'whmin');

    wp_send_json_success([
        'message' => $message,
        'enabled' => $enabled
    ]);
}
add_action('wp_ajax_whmin_toggle_direct_monitoring', 'whmin_ajax_toggle_direct_monitoring');

/**
 * Helper function to format bytes into KB, MB, GB, etc.
 */
function whmin_format_bytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= (pow(1024, $pow) > 0 ? pow(1024, $pow) : 1);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}