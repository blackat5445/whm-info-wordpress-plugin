<?php
/**
 * API Settings Handler
 * 
 * @package WHM_Info
 * @subpackage Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook into WordPress
add_action('admin_init', 'whmin_api_settings_init');
add_action('wp_ajax_whmin_generate_api_token', 'whmin_ajax_generate_api_token');
add_action('wp_ajax_whmin_save_whm_api', 'whmin_ajax_save_whm_api');
add_action('wp_ajax_whmin_regenerate_api_token', 'whmin_ajax_regenerate_api_token');
add_action('wp_ajax_whmin_test_whm_connection', 'whmin_ajax_test_whm_connection');
add_action('wp_ajax_whmin_revoke_api_token', 'whmin_ajax_revoke_api_token');

/**
 * Initialize API settings
 */
function whmin_api_settings_init() {
    // Register settings
    register_setting('whmin_api_settings', 'whmin_whm_api_token', array(
        'type' => 'string',
        'sanitize_callback' => 'whmin_sanitize_api_token',
        'default' => ''
    ));
    
    register_setting('whmin_api_settings', 'whmin_whm_server_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));
    
    register_setting('whmin_api_settings', 'whmin_whm_username', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('whmin_api_settings', 'whmin_binoculars_api_token', array(
        'type' => 'string',
        'sanitize_callback' => 'whmin_sanitize_api_token',
        'default' => ''
    ));
    
    register_setting('whmin_api_settings', 'whmin_binoculars_api_enabled', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false
    ));
}

/**
 * Sanitize API token
 */
function whmin_sanitize_api_token($token) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $token);
}

/**
 * Generate a secure API token
 */
function whmin_generate_api_token() {
    return bin2hex(random_bytes(32));
}

/**
 * AJAX handler for generating API token
 */
function whmin_ajax_generate_api_token() {
    // Check nonce and permissions
    if (!check_ajax_referer('whmin_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'message' => __('Unauthorized', 'whmin'))));
    }
    
    $token = whmin_generate_api_token();
    
    // Save the token
    update_option('whmin_binoculars_api_token', $token);
    update_option('whmin_binoculars_api_enabled', true);
    
    // Store token generation metadata
    update_option('whmin_binoculars_api_generated', array(
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
    
    wp_send_json_success(array(
        'token' => $token,
        'message' => __('API token generated successfully!', 'whmin')
    ));
}

/**
 * AJAX handler for regenerating API token
 */
function whmin_ajax_regenerate_api_token() {
    // Check nonce and permissions
    if (!check_ajax_referer('whmin_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'message' => __('Unauthorized', 'whmin'))));
    }
    
    $old_token = get_option('whmin_binoculars_api_token');
    $token = whmin_generate_api_token();
    
    // Save the token
    update_option('whmin_binoculars_api_token', $token);
    
    // Store token history
    $history = get_option('whmin_binoculars_api_history', array());
    $history[] = array(
        'old_token' => substr($old_token, 0, 8) . '...',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    );
    
    // Keep only last 5 entries
    if (count($history) > 5) {
        $history = array_slice($history, -5);
    }
    
    update_option('whmin_binoculars_api_history', $history);
    
    wp_send_json_success(array(
        'token' => $token,
        'message' => __('API token regenerated successfully!', 'whmin')
    ));
}

/**
 * AJAX handler for saving WHM API settings
 */
function whmin_ajax_save_whm_api() {
    // Check nonce and permissions
    if (!check_ajax_referer('whmin_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'message' => __('Unauthorized', 'whmin'))));
    }
    
    $server_url = isset($_POST['server_url']) ? esc_url_raw($_POST['server_url']) : '';
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $api_token = isset($_POST['api_token']) ? whmin_sanitize_api_token($_POST['api_token']) : '';
    
    // Validate inputs
    if (empty($server_url) || empty($username) || empty($api_token)) {
        wp_send_json_error(array(
            'message' => __('All fields are required', 'whmin')
        ));
    }
    
    // Save settings
    update_option('whmin_whm_server_url', $server_url);
    update_option('whmin_whm_username', $username);
    update_option('whmin_whm_api_token', $api_token);
    
    // Store connection metadata
    update_option('whmin_whm_last_saved', array(
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
    
    wp_send_json_success(array(
        'message' => __('WHM API settings saved successfully!', 'whmin')
    ));
}

/**
 * Test WHM API connection
 */
function whmin_ajax_test_whm_connection() {
    // Check nonce and permissions
    if (!check_ajax_referer('whmin_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'message' => __('Unauthorized', 'whmin'))));
    }
    
    $server_url = get_option('whmin_whm_server_url');
    $username = get_option('whmin_whm_username');
    $api_token = get_option('whmin_whm_api_token');
    
    if (empty($server_url) || empty($username) || empty($api_token)) {
        wp_send_json_error(array(
            'message' => __('Please configure WHM API settings first', 'whmin')
        ));
    }
    
    // Test connection (basic validation for now)
    // In production, you'd make an actual API call here
    $test_url = trailingslashit($server_url) . 'json-api/version?api.version=1';
    
    $response = wp_remote_get($test_url, array(
        'headers' => array(
            'Authorization' => 'whm ' . $username . ':' . $api_token
        ),
        'timeout' => 10,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => __('Connection failed: ', 'whmin') . $response->get_error_message()
        ));
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        wp_send_json_success(array(
            'message' => __('Connection successful!', 'whmin')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Connection failed. HTTP Code: ', 'whmin') . $code
        ));
    }
}

/**
 * AJAX handler for revoking API token
 */
function whmin_ajax_revoke_api_token() {
    // Check nonce and permissions
    if (!check_ajax_referer('whmin_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'message' => __('Unauthorized', 'whmin'))));
    }
    
    // Clear the token
    delete_option('whmin_binoculars_api_token');
    update_option('whmin_binoculars_api_enabled', false);
    delete_option('whmin_binoculars_api_generated');
    
    wp_send_json_success(array(
        'message' => __('API token revoked successfully!', 'whmin')
    ));
}

/**
 * Get WHM API credentials
 */
function whmin_get_whm_credentials() {
    return array(
        'server_url' => get_option('whmin_whm_server_url', ''),
        'username' => get_option('whmin_whm_username', ''),
        'api_token' => get_option('whmin_whm_api_token', '')
    );
}

/**
 * Get Binoculars API token
 */
function whmin_get_binoculars_token() {
    return get_option('whmin_binoculars_api_token', '');
}

/**
 * Check if Binoculars API is enabled
 */
function whmin_is_binoculars_enabled() {
    return get_option('whmin_binoculars_api_enabled', false);
}

/**
 * Validate incoming Binoculars API request
 */
function whmin_validate_api_request($token) {
    if (!whmin_is_binoculars_enabled()) {
        return false;
    }
    
    $stored_token = whmin_get_binoculars_token();
    return !empty($stored_token) && hash_equals($stored_token, $token);
}


/**
 * Permission check for API endpoints
 */
function whmin_api_permission_check($request) {
    $token = $request->get_header('X-API-Token');
    return whmin_validate_api_request($token);
}

/**
 * API status callback
 */
function whmin_api_status_callback($request) {
    return new WP_REST_Response(array(
        'status' => 'active',
        'plugin_version' => WHMIN_VERSION,
        'api_version' => 'v1'
    ), 200);
}

/**
 * API server info callback
 */
function whmin_api_server_info_callback($request) {
    // This would fetch actual server info from WHM
    return new WP_REST_Response(array(
        'server_name' => get_bloginfo('name'),
        'wp_version' => get_bloginfo('version'),
        'php_version' => phpversion(),
        'timestamp' => current_time('c')
    ), 200);
}

/**
 * Register ALL REST API endpoints for Binoculars API
 */
add_action('rest_api_init', function() {
    // Existing route for status
    register_rest_route('whmin/v1', '/status', array(
        'methods' => 'GET',
        'callback' => 'whmin_api_status_callback',
        'permission_callback' => 'whmin_api_permission_check'
    ));
    
    // Existing route for server info
    register_rest_route('whmin/v1', '/server-info', array(
        'methods' => 'GET',
        'callback' => 'whmin_api_server_info_callback',
        'permission_callback' => 'whmin_api_permission_check'
    ));

    // Route for activating a connection
    register_rest_route('whmin/v1', '/activate-connection', array(
        'methods' => 'POST',
        'callback' => 'whmin_api_activate_callback',
        'permission_callback' => 'whmin_api_permission_check',
        'args' => [
            'site_url' => [
                'required' => true,
                'validate_callback' => 'esc_url_raw'
            ]
        ]
    ));
});



/**
* 
* API callback to activate an indirect site's status.
*/
function whmin_api_activate_callback($request) {
$remote_url = trailingslashit($request->get_param('site_url'));
$sites = get_option('whmin_indirect_sites', []);
$site_found = false;

foreach ($sites as $key => $site) {
    // Compare URLs, making sure they both have a trailing slash for consistency
    if (trailingslashit($site['url']) === $remote_url) {
        $sites[$key]['status'] = 'activated';
        $site_found = true;
        break;
    }
}

if ($site_found) {
    update_option('whmin_indirect_sites', $sites);
    return new WP_REST_Response(['message' => 'Connection activated successfully.'], 200);
}

return new WP_REST_Response(['message' => 'Site not found in the in-direct connection list.'], 404);
}