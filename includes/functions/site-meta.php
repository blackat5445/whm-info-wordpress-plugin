<?php
/**
 * Unified site metadata handling (direct + remote/API sites).
 *
 * IMPORTANT:
 * - BOTH direct (WHM) and indirect (external) sites get their
 *   PHP/WP/theme/plugins/php.ini info from the AGENT (WHM Info Connect plugin)
 *   via /wp-json/whmin-connect/v1/site-meta.
 * - WHM is used ONLY for disk usage on direct (hosted) sites.
 *
 * @package WHM_Info/Includes/Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Get the site metadata refresh interval in seconds (both direct+remote).
 * Default: 24 hours, configurable in Private Settings.
 *
 * @return int
 */
function whmin_get_site_meta_refresh_interval_seconds() {
    if (!function_exists('whmin_get_private_settings')) {
        return 24 * HOUR_IN_SECONDS;
    }

    $settings = whmin_get_private_settings();
    $hours    = isset($settings['site_meta_refresh_hours'])
        ? (int) $settings['site_meta_refresh_hours']
        : 24;

    if ($hours < 1) {
        $hours = 1;
    } elseif ($hours > 168) {
        $hours = 168; // up to 7 days
    }

    return $hours * HOUR_IN_SECONDS;
}

/**
 * Hooked into the existing status cron.
 * Decides when to refresh all site metadata (direct + remote).
 */
function whmin_maybe_refresh_site_meta_on_status_cron() {
    $interval = whmin_get_site_meta_refresh_interval_seconds();

    $last = (int) get_option('whmin_last_site_meta_refresh', 0);
    $now  = current_time('timestamp');

    if ($last > 0 && ($now - $last) < $interval) {
        return;
    }

    whmin_refresh_all_site_meta();
}
add_action('whmin_status_check_event', 'whmin_maybe_refresh_site_meta_on_status_cron', 20);

/**
 * Refresh metadata for ALL sites:
 * - Direct (WHM-hosted) sites → agent for tech info + WHM disk usage.
 * - Remote/API-connected sites → agent for tech info (no WHM disk).
 */
function whmin_refresh_all_site_meta() {
    $meta = get_option('whmin_site_meta', [
        'direct' => [],
        'remote' => [],
    ]);

    if (!is_array($meta)) {
        $meta = ['direct' => [], 'remote' => []];
    }
    if (!isset($meta['direct']) || !is_array($meta['direct'])) {
        $meta['direct'] = [];
    }
    if (!isset($meta['remote']) || !is_array($meta['remote'])) {
        $meta['remote'] = [];
    }

    // 1) Refresh WHM-hosted (direct) accounts
    $meta['direct'] = whmin_refresh_direct_site_meta($meta['direct']);

    // 2) Refresh remote/API (indirect, external) sites
    $meta['remote'] = whmin_refresh_remote_site_meta($meta['remote']);

    update_option('whmin_site_meta', $meta);
    update_option('whmin_last_site_meta_refresh', current_time('timestamp'));
}

/**
 * Get the binoculars/agent API token used by Connect plugins.
 *
 * This is the same token the Connect plugin is configured with as "API key".
 *
 * @return string
 */
function whmin_get_agent_api_token() {
    if (!function_exists('whmin_is_binoculars_enabled') || !function_exists('whmin_get_binoculars_token')) {
        return '';
    }

    if (!whmin_is_binoculars_enabled()) {
        return '';
    }

    return (string) whmin_get_binoculars_token();
}

/**
 * Internal helper: fetch /site-meta from a given WordPress site using the agent.
 *
 * @param string $url Base site URL (e.g. https://example.com).
 * @param string $api_key API token (same as Connect plugin's API key).
 * @return array|null Returns decoded payload array on success, or null on failure.
 */
function whmin_fetch_agent_site_meta($url, $api_key) {
    if (empty($url) || empty($api_key)) {
        return null;
    }

    $endpoint = trailingslashit($url) . 'wp-json/whmin-connect/v1/site-meta';

    $response = wp_remote_get($endpoint, [
        'headers'   => [
            'X-API-Token' => $api_key,
        ],
        'timeout'   => 12,
        'sslverify' => false,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data)) {
        return null;
    }

    return $data;
}

/**
 * Refresh metadata for WHM-hosted direct sites.
 *
 * For direct sites we:
 * - Use AGENT (Connect plugin) for:
 *   PHP, WordPress, theme, plugins, php.ini (whitelisted) and general info.
 * - Use WHM (indirectly via whmin_get_direct_connected_sites_data) ONLY
 *   for disk usage / space occupied on WHM.
 *
 * @param array $existing Existing direct meta array keyed by user.
 * @return array Updated meta array for direct sites.
 */
function whmin_refresh_direct_site_meta(array $existing) {
    if (!function_exists('whmin_get_direct_connected_sites_data')) {
        return $existing;
    }

    $api_key = whmin_get_agent_api_token();
    if (empty($api_key)) {
        // Agent API not enabled on main plugin → keep whatever we had.
        return $existing;
    }

    $direct_sites = whmin_get_direct_connected_sites_data();
    if (!is_array($direct_sites) || empty($direct_sites)) {
        return $existing;
    }

    foreach ($direct_sites as $site) {
        $user = isset($site['user']) ? $site['user'] : '';
        $url  = isset($site['url'])  ? $site['url']  : '';
        $monitoring_enabled = isset($site['monitoring_enabled']) ? (bool) $site['monitoring_enabled'] : true;

        if (empty($user) || empty($url)) {
            continue;
        }

        // Only fetch meta if monitoring is enabled; but keep old data if we already have some.
        $agent_meta = null;
        if ($monitoring_enabled) {
            $agent_meta = whmin_fetch_agent_site_meta($url, $api_key);
        }

        // Ensure entry exists
        if (!isset($existing[$user])) {
            $existing[$user] = [
                'user'       => $user,
                'name'       => $site['name'] ?? '',
                'url'        => $url,
                'hosting'    => 'WHM',
                'data'       => [],
                'fetched_at' => 0,
            ];
        }

        // Always update basic info
        $existing[$user]['name'] = $site['name'] ?? $existing[$user]['name'];
        $existing[$user]['url']  = $url;

        // Disk usage from WHM (already prepared by whmin_get_direct_connected_sites_data)
        // 'disk_used_bytes' is a numeric value; 'disk_used' is human readable.
        $disk_used_bytes = isset($site['disk_used_bytes']) ? (float) $site['disk_used_bytes'] : 0.0;
        $disk_used_label = isset($site['disk_used']) ? $site['disk_used'] : '';

        $existing[$user]['data']['whm_disk'] = [
            'bytes' => $disk_used_bytes,
            'label' => $disk_used_label,
        ];

        // Agent payload (PHP/WP/theme/plugins/php.ini/etc)
        if (is_array($agent_meta)) {
            $existing[$user]['data']['agent'] = $agent_meta;
            $existing[$user]['agent_connected'] = true;
        } else {
            // Keep previous agent data if any, but mark as not recently fetched
            if (!isset($existing[$user]['agent_connected'])) {
                $existing[$user]['agent_connected'] = false;
            }
        }

        $existing[$user]['fetched_at'] = current_time('timestamp');
    }

    return $existing;
}

/**
 * Refresh metadata for remote/API-connected sites (indirect + any other WordPress with the connect plugin).
 *
 * For remote/API sites we:
 * - Use AGENT (Connect plugin) for all tech info,
 *   just like direct sites — except we have NO WHM disk info.
 *
 * @param array $existing Existing remote meta array keyed by uid.
 * @return array Updated meta array for remote sites.
 */
function whmin_refresh_remote_site_meta(array $existing) {
    if (!function_exists('whmin_get_indirect_sites_data')) {
        return $existing;
    }

    $api_key = whmin_get_agent_api_token();
    if (empty($api_key)) {
        return $existing;
    }

    $sites = whmin_get_indirect_sites_data();
    if (!is_array($sites) || empty($sites)) {
        return $existing;
    }

    foreach ($sites as $site) {
        $connection = isset($site['connection']) ? $site['connection'] : '';
        $monitoring = isset($site['monitoring_enabled']) ? (bool) $site['monitoring_enabled'] : true;
        $uid        = isset($site['uid']) ? $site['uid'] : '';
        $url        = isset($site['url']) ? $site['url'] : '';

        // Only remote/API sites (Standard API Connection) with monitoring enabled.
        if ($connection !== 'Standard API Connection' || !$monitoring || empty($uid) || empty($url)) {
            continue;
        }

        $agent_meta = whmin_fetch_agent_site_meta($url, $api_key);
        if (!is_array($agent_meta)) {
            // Could choose to keep existing[$uid] as is, but don't overwrite it with null.
            if (!isset($existing[$uid])) {
                // No previous data at all; skip.
                continue;
            }
            // Mark as not recently connected
            $existing[$uid]['agent_connected'] = false;
            continue;
        }

        $existing[$uid] = [
            'uid'             => $uid,
            'name'            => $site['name']    ?? '',
            'url'             => $url,
            'hosting'         => $site['hosting'] ?? '',
            'connection'      => $connection,
            'data'            => [
                'agent' => $agent_meta,
                // No WHM disk here (not hosted on our WHM).
            ],
            'agent_connected' => true,
            'fetched_at'      => current_time('timestamp'),
        ];
    }

    return $existing;
}

/**
 * Helper: get stored metadata for a specific site.
 *
 * @param string $type 'direct' or 'remote'
 * @param string $key  'user' for direct, 'uid' for remote
 * @return array|null
 */
function whmin_get_site_meta($type, $key) {
    $all = get_option('whmin_site_meta', ['direct' => [], 'remote' => []]);
    if (!is_array($all) || empty($all[$type]) || !is_array($all[$type])) {
        return null;
    }

    return isset($all[$type][$key]) ? $all[$type][$key] : null;
}

/**
 * Build the URL to the site detail page.
 *
 * @param string $type 'direct' or 'remote'
 * @param string $key  'user' for direct, 'uid' for remote
 * @return string
 */
function whmin_get_site_detail_url($type, $key) {
    $type = ($type === 'direct') ? 'direct' : 'remote';
    $key  = rawurlencode($key);

    $base = home_url('/');
    return trailingslashit($base) . 'whmin-site-info/' . $type . '/' . $key . '/';
}

/**
 * Helper: try to resolve a remote site's UID from a status row.
 *
 * Expects $site array from whmin_get_indirect_sites_detailed_status().
 * If uid not present in the row, tries to match by URL against indirect sites.
 *
 * @param array $site
 * @return string UID or empty string if not found
 */
function whmin_get_remote_site_uid_from_status_row(array $site) {
    if (!empty($site['uid'])) {
        return $site['uid'];
    }

    if (empty($site['url']) || !function_exists('whmin_get_indirect_sites_data')) {
        return '';
    }

    $target = rtrim($site['url'], '/');
    $list   = whmin_get_indirect_sites_data();

    foreach ($list as $item) {
        if (empty($item['uid']) || empty($item['url'])) {
            continue;
        }
        if (rtrim($item['url'], '/') === $target) {
            return $item['uid'];
        }
    }

    return '';
}


/**
 * AJAX: Manually refresh agent / site metadata (Connect plugin data).
 * Uses the same function that the cron hook uses.
 */
function whmin_ajax_refresh_site_meta_manual() {
    // Security
    check_ajax_referer('whmin_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(
            array('message' => __('Permission denied.', 'whmin')),
            403
        );
    }

    if (!function_exists('whmin_refresh_all_site_meta')) {
        wp_send_json_error(
            array('message' => __('Site metadata refresh function is not available.', 'whmin')),
            500
        );
    }

    // Run the refresh (direct + remote)
    whmin_refresh_all_site_meta();

    wp_send_json_success(array(
        'message' => __('Site metadata refreshed successfully.', 'whmin'),
    ));
}
add_action('wp_ajax_whmin_refresh_site_meta_manual', 'whmin_ajax_refresh_site_meta_manual');
