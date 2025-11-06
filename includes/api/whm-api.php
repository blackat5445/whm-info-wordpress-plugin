<?php
/**
 * Fetches a list of accounts from the WHM server.
 *
 * Optimised:
 * - Uses whmin_make_whm_api_call('listaccts') so it benefits from
 *   per-request caching.
 * - Adds a short transient cache to avoid hitting WHM on every page load.
 *
 * @return array|WP_Error An array of account objects on success, or a WP_Error on failure.
 */
function whmin_get_whm_accounts() {
    // Per-request local cache to avoid duplicate work.
    static $local_cache = null;
    if ( $local_cache !== null ) {
        return $local_cache;
    }

    /**
     * Transient cache for accounts.
     * Default TTL: 5 minutes (300 seconds).
     * Change via:
     * add_filter( 'whmin_whm_accounts_cache_ttl', function( $ttl ) { return 600; } );
     */
    $transient_key = 'whmin_whm_accounts_cache';
    $cache_ttl     = apply_filters( 'whmin_whm_accounts_cache_ttl', 300 );

    $cached = get_transient( $transient_key );
    if ( $cached !== false && is_array( $cached ) ) {
        $local_cache = $cached;
        return $cached;
    }

    // Use the generic helper so we share the same underlying HTTP call.
    $data = whmin_make_whm_api_call( 'listaccts' );

    if ( is_wp_error( $data ) ) {
        return $data;
    }

    if ( ! isset( $data['data']['acct'] ) || ! is_array( $data['data']['acct'] ) ) {
        $local_cache = array();
        return array(); // no accounts
    }

    $accounts = $data['data']['acct'];

    // Store in transient for future requests.
    if ( $cache_ttl > 0 ) {
        set_transient( $transient_key, $accounts, $cache_ttl );
    }

    $local_cache = $accounts;

    return $accounts;
}

/**
 * A generic helper function to make WHM API 1 calls.
 *
 * Optimised:
 * - Lower default timeout (filterable).
 * - Per-request in-memory cache so the same endpoint+params
 *   is only called once per PHP request.
 *
 * @param string $function The WHM API function to call (e.g., 'accountsummary').
 * @param array  $params   Optional parameters for the API call.
 * @return array|WP_Error The decoded JSON response on success, or a WP_Error on failure.
 */
function whmin_make_whm_api_call( $function, $params = array() ) {
    $creds = whmin_get_whm_credentials();
    if ( empty( $creds['server_url'] ) || empty( $creds['username'] ) || empty( $creds['api_token'] ) ) {
        return new WP_Error( 'api_credentials_missing', __( 'WHM API credentials are not configured.', 'whmin' ) );
    }

    // Build the URL with parameters.
    $url = sprintf(
        '%s/json-api/%s?api.version=1%s',
        rtrim( $creds['server_url'], '/' ),
        $function,
        ! empty( $params ) ? '&' . http_build_query( $params ) : ''
    );

    /**
     * Per-request in-memory cache.
     * Keyed by full URL (function + params).
     */
    static $request_cache = array();
    $cache_key = md5( $url );

    if ( isset( $request_cache[ $cache_key ] ) ) {
        return $request_cache[ $cache_key ];
    }

    /**
     * Default timeout reduced to 8 seconds to avoid long hangs.
     * You can change it via:
     * add_filter( 'whmin_whm_api_timeout', function( $timeout ) { return 5; } );
     */
    $timeout = apply_filters( 'whmin_whm_api_timeout', 8 );

    $response = wp_remote_get(
        $url,
        array(
            'headers'   => array(
                'Authorization' => 'whm ' . $creds['username'] . ':' . $creds['api_token'],
            ),
            'timeout'   => $timeout,
            'sslverify' => false, // as you already had
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'connection_error', $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_error', __( 'Failed to decode API response.', 'whmin' ) );
    }

    if ( isset( $data['metadata']['reason'] ) && $data['metadata']['reason'] !== 'OK' ) {
        return new WP_Error( 'api_error', $data['metadata']['reason'] );
    }

    // Store in per-request cache and return.
    $request_cache[ $cache_key ] = $data;

    return $data;
}