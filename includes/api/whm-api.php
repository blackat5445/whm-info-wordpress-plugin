<?php
/**
 * Fetches a list of accounts from the WHM server.
 *
 * @return array|WP_Error An array of account objects on success, or a WP_Error on failure.
 */
function whmin_get_whm_accounts() {
    $server_url = get_option('whmin_whm_server_url');
    $username = get_option('whmin_whm_username');
    $api_token = get_option('whmin_whm_api_token');

    // Pre-flight check for API credentials
    if (empty($server_url) || empty($username) || empty($api_token)) {
        return new WP_Error('api_credentials_missing', __('WHM API credentials are not configured.', 'whmin'));
    }

    $url = rtrim($server_url, '/') . '/json-api/listaccts?api.version=1';

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'whm ' . $username . ':' . $api_token,
        ],
        'timeout' => 20, // 20-second timeout
    ]);

    // Handle connection errors
    if (is_wp_error($response)) {
        return new WP_Error('connection_error', __('Could not connect to the WHM server.', 'whmin') . ' ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for API-level errors
    if (isset($data['metadata']['reason']) && $data['metadata']['reason'] !== 'OK') {
        return new WP_Error('api_error', $data['metadata']['reason']);
    }

    // Check if the accounts data exists
    if (!isset($data['data']['acct']) || !is_array($data['data']['acct'])) {
        return []; // Return empty array if there are no accounts
    }

    return $data['data']['acct'];
}

/**
 * A generic helper function to make WHM API 1 calls.
 *
 * @param string $function The WHM API function to call (e.g., 'accountsummary').
 * @param array  $params   Optional parameters for the API call.
 * @return array|WP_Error The decoded JSON response on success, or a WP_Error on failure.
 */
function whmin_make_whm_api_call($function, $params = []) {
    $creds = whmin_get_whm_credentials();
    if (empty($creds['server_url']) || empty($creds['username']) || empty($creds['api_token'])) {
        return new WP_Error('api_credentials_missing', __('WHM API credentials are not configured.', 'whmin'));
    }

    // Build the URL with parameters
    $url = sprintf(
        '%s/json-api/%s?api.version=1%s',
        rtrim($creds['server_url'], '/'),
        $function,
        !empty($params) ? '&' . http_build_query($params) : ''
    );
    
    $response = wp_remote_get($url, [
        'headers' => ['Authorization' => 'whm ' . $creds['username'] . ':' . $creds['api_token']],
        'timeout' => 20,
        'sslverify' => false,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('connection_error', $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', __('Failed to decode API response.', 'whmin'));
    }

    if (isset($data['metadata']['reason']) && $data['metadata']['reason'] !== 'OK') {
        return new WP_Error('api_error', $data['metadata']['reason']);
    }

    return $data;
}