<?php
/**
 * Functions for the Direct Connected Websites settings tab.
 *
 * @package WHM_Info/Includes/Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves and prepares the list of directly connected websites.
 * It merges data from the WHM API with custom names stored in WordPress.
 *
 * @return array|WP_Error An array of site data or a WP_Error on failure.
 */
function whmin_get_direct_connected_sites_data() {
    // Fetch the list of accounts from the WHM API
    $accounts_response = whmin_get_whm_accounts();

    // Handle API errors or no accounts found
    if (is_wp_error($accounts_response)) {
        return $accounts_response; // Pass the error along
    }
    
    if (empty($accounts_response)) {
        return []; // Return an empty array if no accounts exist
    }

    // Get custom names saved in WordPress
    $custom_names = get_option('whmin_custom_site_names', []);
    $sites_data = [];
    $id_counter = 1;

    foreach ($accounts_response as $account) {
        // Determine the display name
        $display_name = !empty($custom_names[$account['user']]) 
            ? $custom_names[$account['user']] 
            : $account['user'];

        // --- START: CORRECTED DISK USAGE LOGIC ---
        $disk_used_raw = $account['diskused'];
        $disk_usage_formatted = '';
        $disk_used_bytes = 0; // Raw value for sorting

        if (strpos($disk_used_raw, '/') !== false) {
            $parts = explode('/', $disk_used_raw);
            $disk_used_mb = $parts[0];
        } else {
            $disk_used_mb = $disk_used_raw;
        }

        if (is_numeric($disk_used_mb)) {
            $disk_used_bytes = (float)$disk_used_mb * 1024 * 1024;
            $disk_usage_formatted = whmin_format_bytes($disk_used_bytes);
        } else {
            // FIX: Handle non-numeric values like 'unlimited' gracefully for display and sorting.
            $disk_usage_formatted = esc_html(ucfirst($disk_used_mb));
            // By setting bytes to a very large number, "unlimited" will correctly sort as the largest.
            $disk_used_bytes = PHP_INT_MAX; 
        }
        // --- END: CORRECTED DISK USAGE LOGIC ---

        // Determine status
        $status = $account['suspended'] == 1 
            ? ['text' => __('Suspended', 'whmin'), 'class' => 'danger']
            : ['text' => __('Active', 'whmin'), 'class' => 'success'];
            
        $sites_data[] = [
            'id'               => $id_counter++,
            'user'             => esc_html($account['user']),
            'name'             => esc_html($display_name),
            'url'              => esc_url('http://' . $account['domain']),
            'setup_date'       => esc_html(date_i18n(get_option('date_format'), $account['unix_startdate'])),
            'setup_timestamp'  => $account['unix_startdate'],
            'disk_used'        => $disk_usage_formatted,
            'disk_used_bytes'  => $disk_used_bytes,
            'status'           => $status,
        ];
    }

    return $sites_data;
}

/**
 * AJAX handler to update a website's custom name.
 */
function whmin_ajax_update_site_name() {
    // Security checks
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    // Sanitize input
    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

    if (empty($user) || empty($new_name)) {
        wp_send_json_error(['message' => __('Invalid data provided.', 'whmin')], 400);
    }
    
    // Get existing names, update the specific one, and save
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
 * Helper function to format bytes into KB, MB, GB, etc.
 */
function whmin_format_bytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    // Avoid division by zero
    $bytes /= (pow(1024, $pow) > 0 ? pow(1024, $pow) : 1);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}