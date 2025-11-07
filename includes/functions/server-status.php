<?php
/**
 * Comprehensive Functions for fetching detailed server status from WHM API.
 * Extracts maximum possible information from available WHM API v1 endpoints.
 */
if (!defined('ABSPATH')) exit;

/**
 * Clear all cached server data
 */
function whmin_clear_server_data_cache() {
    delete_transient( 'whmin_server_data_cache' );
    delete_transient( 'whmin_account_summary_cache' );
    delete_transient( 'whmin_system_info_cache' );
    delete_transient( 'whmin_whm_accounts_cache' ); // NEW: clear cached listaccts data
}

/**
 * AJAX handler to refresh server data
 */
function whmin_ajax_refresh_server_data() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }
    
    // Clear all caches
    whmin_clear_server_data_cache();
    
    // Force fresh fetch by calling the main function
    whmin_get_private_dashboard_data();
    
    wp_send_json_success([
        'message' => __('Server data refreshed successfully!', 'whmin'),
        'timestamp' => current_time('mysql')
    ]);
}
add_action('wp_ajax_whmin_refresh_server_data', 'whmin_ajax_refresh_server_data');

/**
 * Convert WHM size strings to MB.
 * Examples:
 *  "123M"  -> 123
 *  "1G"    -> 1024
 *  "512K"  -> 0.5
 *  "123M/500M" -> 123 (we handle X/Y in the calling code too)
 */
function whmin_parse_whm_size_to_mb( $val ) {
    if ($val === null || $val === '') {
        return 0.0;
    }

    // sometimes WHM already returns plain numbers (assume MB)
    if (is_numeric($val)) {
        return (float) $val;
    }

    $val = trim($val);

    // match "123M", "1.5G", "500K"
    if (preg_match('/^([\d\.]+)\s*([KMG])?/i', $val, $m)) {
        $num  = (float) $m[1];
        $unit = isset($m[2]) ? strtoupper($m[2]) : 'M'; // default MB

        switch ($unit) {
            case 'G':
                return $num * 1024;
            case 'K':
                return $num / 1024;
            default:
                return $num; // M
        }
    }

    return 0.0;
}

/**
 * Get bandwidth usage from showbw, indexed by username.
 * Returns bytes, we will convert to MB in callers.
 */
function whmin_get_bandwidth_map() {
    $bw_data = whmin_make_whm_api_call('showbw');

    if (is_wp_error($bw_data) || empty($bw_data['data']['acct']) || !is_array($bw_data['data']['acct'])) {
        return [];
    }

    $map = [];
    foreach ($bw_data['data']['acct'] as $acct) {
        // 'user' is the most reliable key
        $user = $acct['user'] ?? ($acct['acct'] ?? null);
        if (!$user) {
            continue;
        }

        // totalbytes is the actual total used bandwidth
        $bytes = isset($acct['totalbytes']) ? (float) $acct['totalbytes'] : 0.0;
        $map[$user] = $bytes;
    }

    return $map;
}

/**
 * Fetches comprehensive Account Summary data.
 * FIXED: Proper disk parsing + real bandwidth from showbw
 * @return array
 */
function whmin_get_account_summary() {
    $data = whmin_make_whm_api_call('listaccts');

    if (is_wp_error($data)) {
        return ['error' => $data->get_error_message()];
    }
    if (empty($data['data']['acct']) || !is_array($data['data']['acct'])) {
        return ['error' => __('No account data found.', 'whmin')];
    }

    $accounts = $data['data']['acct'];

    // NEW: real bandwidth map from showbw (bytes, keyed by username)
    $bw_map = whmin_get_bandwidth_map();

    $total_disk_used     = 0;
    $total_disk_limit    = 0;
    $total_bandwidth_used = 0;
    $total_bandwidth_limit = 0;
    $total_emails        = 0;
    $total_domains       = 0;
    $total_subdomains    = 0;
    $total_parked        = 0;
    $total_addon         = 0;
    $packages            = [];
    $suspended_count     = 0;
    $has_bandwidth_data  = false;

    foreach ($accounts as $account) {
        // ---------- DISK (now unit-aware) ----------
        $disk_used_raw = $account['diskused'] ?? 0;
        // sometimes "123M/500M"
        if (is_string($disk_used_raw) && strpos($disk_used_raw, '/') !== false) {
            $parts = explode('/', $disk_used_raw, 2);
            $disk_used_raw = $parts[0];
        }
        $disk_used_mb = whmin_parse_whm_size_to_mb($disk_used_raw);
        $total_disk_used += $disk_used_mb;

        $disk_limit = $account['disklimit'] ?? 'unlimited';
        if ($disk_limit !== 'unlimited' && $disk_limit !== '0') {
            $total_disk_limit += whmin_parse_whm_size_to_mb($disk_limit);
        }

        // ---------- BANDWIDTH (prefer showbw) ----------
        $user   = $account['user'] ?? null;
        $bw_used_mb = 0;

        if ($user && isset($bw_map[$user])) {
            // showbw returns BYTES → MB
            $bw_used_mb = $bw_map[$user] / (1024 * 1024);
            $has_bandwidth_data = true;
        } else {
            // fallback to whatever listaccts gives
            if (isset($account['bwused']) && is_numeric($account['bwused'])) {
                $bw_used_mb = (float) $account['bwused'];
                $has_bandwidth_data = true;
            } elseif (isset($account['bandwidth_used']) && is_numeric($account['bandwidth_used'])) {
                $bw_used_mb = (float) $account['bandwidth_used'];
                $has_bandwidth_data = true;
            }
        }

        $total_bandwidth_used += $bw_used_mb;

        // limit (usually in MB or 'unlimited')
        $bw_limit = $account['bwlimit'] ?? 'unlimited';
        if ($bw_limit !== 'unlimited' && is_numeric($bw_limit)) {
            $total_bandwidth_limit += (float) $bw_limit;
        }

        // ---------- EMAIL / DOMAINS ----------
        $total_emails     += (int) ($account['email'] ?? 0);
        $total_domains    += 1; // main domain
        $total_subdomains += (int) ($account['subdomain'] ?? 0);
        $total_parked     += (int) ($account['parked'] ?? 0);
        $total_addon      += (int) ($account['addon'] ?? 0);

        // ---------- PACKAGES ----------
        if (!empty($account['plan'])) {
            $packages[$account['plan']] = ($packages[$account['plan']] ?? 0) + 1;
        }

        // ---------- STATUS ----------
        if (isset($account['suspended']) && (int) $account['suspended'] === 1) {
            $suspended_count++;
        }
    }

    // ---------- PERCENTAGES ----------
    $disk_percentage = ($total_disk_limit > 0)
        ? round(($total_disk_used / $total_disk_limit) * 100, 2)
        : 0;

    $bandwidth_percentage = 0;
    if ($total_bandwidth_limit > 0 && $has_bandwidth_data) {
        $bandwidth_percentage = round(($total_bandwidth_used / $total_bandwidth_limit) * 100, 2);
    }

    return [
        'summary' => [
            __('Total Accounts', 'whmin')    => count($accounts),
            __('Active Accounts', 'whmin')   => count($accounts) - $suspended_count,
            __('Suspended Accounts', 'whmin') => $suspended_count,
        ],
        'disk' => [
            'used'       => round($total_disk_used, 2),
            'limit'      => $total_disk_limit > 0 ? round($total_disk_limit, 2) : 'unlimited',
            'percentage' => $disk_percentage,
            'unit'       => 'MB',
        ],
        'bandwidth' => [
            'used'       => round($total_bandwidth_used, 2),
            'limit'      => $total_bandwidth_limit > 0 ? round($total_bandwidth_limit, 2) : 'unlimited',
            'percentage' => $bandwidth_percentage,
            'unit'       => 'MB',
            'has_data'   => $has_bandwidth_data,
        ],
        'resources' => [
            __('Email Accounts', 'whmin') => $total_emails,
            __('Total Domains', 'whmin')  => $total_domains,
            __('Subdomains', 'whmin')     => $total_subdomains,
            __('Parked Domains', 'whmin') => $total_parked,
            __('Addon Domains', 'whmin')  => $total_addon,
        ],
        'packages'      => $packages,
        'total_accounts'=> count($accounts),
    ];
}

/**
 * Fetches comprehensive System Information.
 * @return array
 */
function whmin_get_basic_system_info() {
    $result = [
        'load' => [],
        'memory' => [],
        'version' => [],
        'bandwidth' => []
    ];
    
    // Get load average
    $loadavg_data = whmin_make_whm_api_call('systemloadavg');
    if (!is_wp_error($loadavg_data) && isset($loadavg_data['data'])) {
        $result['load'] = [
            '1min' => floatval($loadavg_data['data']['one'] ?? 0),
            '5min' => floatval($loadavg_data['data']['five'] ?? 0),
            '15min' => floatval($loadavg_data['data']['fifteen'] ?? 0),
        ];
    }
    
    // Get bandwidth statistics with proper error handling
    $bw_data = whmin_make_whm_api_call('showbw');
    if (!is_wp_error($bw_data) && isset($bw_data['data'])) {
        if (isset($bw_data['data']['totalused'])) {
            $result['bandwidth']['total_used'] = $bw_data['data']['totalused'];
        }
        if (isset($bw_data['data']['acct']) && is_array($bw_data['data']['acct'])) {
            // Get top bandwidth users
            $bw_accounts = $bw_data['data']['acct'];
            // Sort by totalbytes
            usort($bw_accounts, function($a, $b) {
                return floatval($b['totalbytes'] ?? 0) - floatval($a['totalbytes'] ?? 0);
            });
            // Get top 5 and ensure they have the 'acct' key
            $top_users = array_slice($bw_accounts, 0, 5);
            foreach ($top_users as &$user) {
                // Ensure 'acct' key exists, fallback to 'user' or 'domain'
                if (!isset($user['acct'])) {
                    $user['acct'] = $user['user'] ?? $user['domain'] ?? 'unknown';
                }
            }
            $result['bandwidth']['top_users'] = $top_users;
        }
    }
    
    // Get WHM version
    $version_data = whmin_make_whm_api_call('version');
    if (!is_wp_error($version_data) && isset($version_data['data']['version'])) {
        $result['version'] = [
            __('WHM Version', 'whmin') => $version_data['data']['version'],
        ];
    }
    
    // Get list of packages for system overview
    $pkg_data = whmin_make_whm_api_call('listpkgs');
    if (!is_wp_error($pkg_data) && isset($pkg_data['data']['package']) && is_array($pkg_data['data']['package'])) {
        $result['packages_available'] = count($pkg_data['data']['package']);
    }
    
    return $result;
}

/**
 * Fetches comprehensive MySQL/Database Information.
 * @return array
 */
function whmin_get_mysql_info() {
    $result = [
        'status' => 'unknown',
        'accounts_with_db' => 0,
        'total_databases' => 0,
    ];
    
    // Get account data to count databases
    $accounts_data = whmin_make_whm_api_call('listaccts');
    
    if (!is_wp_error($accounts_data) && !empty($accounts_data['data']['acct'])) {
        $accounts = $accounts_data['data']['acct'];
        foreach ($accounts as $account) {
            if (isset($account['maxsql']) && $account['maxsql'] !== '0') {
                $result['accounts_with_db']++;
            }
            // Some API responses include database count
            if (isset($account['diskusedmysql'])) {
                $result['has_mysql_data'] = true;
            }
        }
    }
    
    // Try to get MySQL service status
    $service_data = whmin_make_whm_api_call('servicestatus', ['service' => 'mysql']);
    if (!is_wp_error($service_data) && isset($service_data['data']['service'][0])) {
        $mysql_service = $service_data['data']['service'][0];
        $result['status'] = $mysql_service['enabled'] ? 'running' : 'stopped';
        if (isset($mysql_service['version'])) {
            $result['version'] = $mysql_service['version'];
        }
    }
    
    return $result;
}

/**
 * Fetches comprehensive SSL Certificate Information.
 * @return array
 */
function whmin_get_ssl_info() {
    $data = whmin_make_whm_api_call('listaccts');

    if (is_wp_error($data)) {
        return ['error' => $data->get_error_message()];
    }
    
    if (empty($data['data']['acct']) || !is_array($data['data']['acct'])) {
        return ['error' => __('No account data found.', 'whmin')];
    }

    $accounts = $data['data']['acct'];
    $ssl_enabled_count = 0;
    $dedicated_ip_count = 0;
    $shared_ip_count = 0;
    $ip_distribution = [];
    
    foreach ($accounts as $account) {
        if (isset($account['ip']) && !empty($account['ip'])) {
            $ip = $account['ip'];
            $ip_distribution[$ip] = ($ip_distribution[$ip] ?? 0) + 1;
            
            // If IP is used by only one account, it's likely dedicated
            if (!isset($ip_distribution[$ip]) || $ip_distribution[$ip] == 1) {
                $ssl_enabled_count++;
            }
        }
    }
    
    // Count dedicated vs shared IPs
    foreach ($ip_distribution as $ip => $count) {
        if ($count == 1) {
            $dedicated_ip_count++;
        } else {
            $shared_ip_count++;
        }
    }
    
    return [
        'summary' => [
            __('Total Accounts', 'whmin') => count($accounts),
            __('Unique IP Addresses', 'whmin') => count($ip_distribution),
            __('Dedicated IPs', 'whmin') => $dedicated_ip_count,
            __('Shared IPs', 'whmin') => $shared_ip_count,
        ],
        'ip_distribution' => $ip_distribution,
    ];
}

/**
 * Fetches comprehensive Apache/Service Status.
 * @return array
 */
function whmin_get_apache_status() {
    $services = ['httpd', 'mysql', 'exim', 'named', 'ftpd', 'sshd'];
    $result = [
        'services' => [],
        'summary' => [
            'running' => 0,
            'stopped' => 0,
            'total' => 0,
        ]
    ];
    
    foreach ($services as $service) {
        $data = whmin_make_whm_api_call('servicestatus', ['service' => $service]);
        
        if (!is_wp_error($data) && isset($data['data']['service'][0])) {
            $service_info = $data['data']['service'][0];
            $is_running = $service_info['enabled'] ?? false;
            
            $result['services'][$service] = [
                'name' => $service_info['name'] ?? $service,
                'display_name' => ucfirst($service),
                'status' => $is_running ? 'running' : 'stopped',
                'enabled' => $is_running,
            ];
            
            $result['summary']['total']++;
            if ($is_running) {
                $result['summary']['running']++;
            } else {
                $result['summary']['stopped']++;
            }
        }
    }
    
    return $result;
}

/**
 * Get comprehensive disk usage information.
 * @return array
 */
function whmin_get_disk_usage_info() {
    $data = whmin_make_whm_api_call('listaccts');

    if (is_wp_error($data)) {
        return ['error' => $data->get_error_message()];
    }

    if (empty($data['data']['acct']) || !is_array($data['data']['acct'])) {
        return ['error' => __('No account data found.', 'whmin')];
    }

    $accounts          = $data['data']['acct'];
    $total_disk_used   = 0;
    $total_disk_limit  = 0;
    $unlimited_count   = 0;
    $top_users         = [];

    foreach ($accounts as $account) {
        $disk_used_raw = $account['diskused'] ?? 0;
        // sometimes "123M/500M"
        if (is_string($disk_used_raw) && strpos($disk_used_raw, '/') !== false) {
            $parts = explode('/', $disk_used_raw, 2);
            $disk_used_raw = $parts[0];
        }
        $disk_used_mb = whmin_parse_whm_size_to_mb($disk_used_raw);

        $disk_limit = $account['disklimit'] ?? 'unlimited';
        if ($disk_limit !== 'unlimited' && $disk_limit !== '0') {
            $disk_limit_mb = whmin_parse_whm_size_to_mb($disk_limit);
            $total_disk_limit += $disk_limit_mb;
        } else {
            $unlimited_count++;
        }

        $total_disk_used += $disk_used_mb;

        // Track top disk users
        $top_users[] = [
            'user'   => $account['user'] ?? 'unknown',
            'domain' => $account['domain'] ?? 'unknown',
            'used'   => $disk_used_mb,
        ];
    }

    // Sort by usage and get top 10
    usort($top_users, function($a, $b) {
        return $b['used'] <=> $a['used'];
    });
    $top_users = array_slice($top_users, 0, 10);

    $usage_percentage = ($total_disk_limit > 0)
        ? round(($total_disk_used / $total_disk_limit) * 100, 2)
        : 0;

    return [
        'total_used'     => round($total_disk_used, 2),
        'total_limit'    => $total_disk_limit > 0 ? round($total_disk_limit, 2) : 'unlimited',
        'percentage'     => $usage_percentage,
        'unlimited_count'=> $unlimited_count,
        'top_users'      => $top_users,
    ];
}


function whmin_get_whm_disk_usage_map() {
    $accounts = whmin_get_whm_accounts();

    if (is_wp_error($accounts) || empty($accounts) || !is_array($accounts)) {
        return [];
    }

    $map = [];

    foreach ($accounts as $account) {
        $user = $account['user'] ?? '';
        if (!$user) {
            continue;
        }

        // diskused can be "123M" or "123M/500M"
        $disk_used_raw = $account['diskused'] ?? 0;
        if (is_string($disk_used_raw) && strpos($disk_used_raw, '/') !== false) {
            $parts         = explode('/', $disk_used_raw, 2);
            $disk_used_raw = $parts[0];
        }
        $disk_used_mb = whmin_parse_whm_size_to_mb($disk_used_raw);

        // disklimit can be "unlimited", "0", or a numeric with unit
        $disk_limit_raw = $account['disklimit'] ?? 'unlimited';
        $disk_limit_mb  = null;

        if ($disk_limit_raw !== 'unlimited' && $disk_limit_raw !== '0') {
            $disk_limit_mb = whmin_parse_whm_size_to_mb($disk_limit_raw);
        }

        $percentage = 0;
        if (!empty($disk_limit_mb) && $disk_limit_mb > 0) {
            $percentage = round(($disk_used_mb / $disk_limit_mb) * 100, 2);
        }

        $map[$user] = [
            'used_mb'    => round($disk_used_mb, 2),
            'limit_mb'   => $disk_limit_mb !== null ? round($disk_limit_mb, 2) : null,
            'limit_raw'  => $disk_limit_raw,
            'percentage' => $percentage,
        ];
    }

    return $map;
}

/**
 * Main function to fetch all private dashboard data.
 * Uses public dashboard data + cached detailed server data for performance.
 *
 * @return array
 */
function whmin_get_private_dashboard_data() {
    // Get base public data (uses latest_statuses + history)
    $public_data = whmin_get_public_dashboard_data();

    // Cache key for all detailed server data
    $cache_key = 'whmin_server_data_cache';

    // Read server data cache TTL (in minutes) from private settings
    $settings = function_exists('whmin_get_private_settings')
        ? whmin_get_private_settings()
        : array();

    $minutes = isset($settings['server_data_cache_minutes'])
        ? (int) $settings['server_data_cache_minutes']
        : 5;

    if ($minutes < 1) {
        $minutes = 1;
    }

    $default_ttl = $minutes * MINUTE_IN_SECONDS;

    /**
     * Filter: override server data cache TTL (in seconds).
     * Example:
     * add_filter( 'whmin_server_data_cache_ttl', function( $ttl ) { return 600; } );
     */
    $cache_ttl = apply_filters('whmin_server_data_cache_ttl', $default_ttl);

    // Try to get cached details
    $server_details = get_transient($cache_key);

    if (false === $server_details || !is_array($server_details)) {
        // No cache or invalid cache: fetch fresh data from WHM API
        $server_details = array(
            'account_summary' => whmin_get_account_summary(),
            'system_info'     => whmin_get_basic_system_info(),
            'disk_usage'      => whmin_get_disk_usage_info(),
            'mysql_info'      => whmin_get_mysql_info(),
            'ssl_info'        => whmin_get_ssl_info(),
            'apache_status'   => whmin_get_apache_status(),
        );

        // Store processed server details in a transient
        set_transient($cache_key, $server_details, $cache_ttl);
    }

    // Attach server details to the main data array
    $public_data['server_details'] = $server_details;

    return $public_data;
}

// Convert MB (numeric) to "123 MB" or "1.23 GB"
function whmin_format_mb_human( $mb, $precision = 2 ) {
    $mb = (float) $mb;
    if ($mb >= 1024) {
        // convert to GB
        return round($mb / 1024, $precision) . ' GB';
    }
    return round($mb, $precision) . ' MB';
}