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
    delete_transient('whmin_server_data_cache');
    delete_transient('whmin_account_summary_cache');
    delete_transient('whmin_system_info_cache');
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
 * Fetches comprehensive Account Summary data.
 * FIXED: Proper bandwidth calculation
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
    $total_disk_used = 0;
    $total_disk_limit = 0;
    $total_bandwidth_used = 0;
    $total_bandwidth_limit = 0;
    $total_emails = 0;
    $total_domains = 0;
    $total_subdomains = 0;
    $total_parked = 0;
    $total_addon = 0;
    $packages = [];
    $suspended_count = 0;
    $has_bandwidth_data = false;
    
    foreach ($accounts as $account) {
        // Disk usage - handle X/Y format
        $disk_used_raw = $account['diskused'] ?? 0;
        if (is_string($disk_used_raw) && strpos($disk_used_raw, '/') !== false) {
            $parts = explode('/', $disk_used_raw);
            $disk_used_raw = $parts[0];
        }
        $total_disk_used += floatval($disk_used_raw);
        
        $disk_limit = $account['disklimit'] ?? 'unlimited';
        if ($disk_limit !== 'unlimited' && is_numeric($disk_limit)) {
            $total_disk_limit += floatval($disk_limit);
        }
        
        // Bandwidth - FIXED: Check multiple possible keys
        $bw_used = 0;
        if (isset($account['bwused']) && is_numeric($account['bwused'])) {
            $bw_used = floatval($account['bwused']);
            $has_bandwidth_data = true;
        } elseif (isset($account['bandwidth_used']) && is_numeric($account['bandwidth_used'])) {
            $bw_used = floatval($account['bandwidth_used']);
            $has_bandwidth_data = true;
        }
        $total_bandwidth_used += $bw_used;
        
        $bw_limit = $account['bwlimit'] ?? 'unlimited';
        if ($bw_limit !== 'unlimited' && is_numeric($bw_limit)) {
            $total_bandwidth_limit += floatval($bw_limit);
        }
        
        // Email accounts
        $total_emails += intval($account['email'] ?? 0);
        
        // Domains
        $total_domains += intval($account['domain'] ?? 1);
        $total_subdomains += intval($account['subdomain'] ?? 0);
        $total_parked += intval($account['parked'] ?? 0);
        $total_addon += intval($account['addon'] ?? 0);
        
        // Packages
        if (!empty($account['plan'])) {
            $packages[$account['plan']] = ($packages[$account['plan']] ?? 0) + 1;
        }
        
        // Status checks
        if (isset($account['suspended']) && $account['suspended'] == 1) {
            $suspended_count++;
        }
    }
    
    // Calculate percentages
    $disk_percentage = ($total_disk_limit > 0) 
        ? round(($total_disk_used / $total_disk_limit) * 100, 2) 
        : 0;
    
    // FIXED: Handle bandwidth properly
    $bandwidth_percentage = 0;
    if ($total_bandwidth_limit > 0 && $has_bandwidth_data) {
        $bandwidth_percentage = round(($total_bandwidth_used / $total_bandwidth_limit) * 100, 2);
    }

    return [
        'summary' => [
            __('Total Accounts', 'whmin') => count($accounts),
            __('Active Accounts', 'whmin') => count($accounts) - $suspended_count,
            __('Suspended Accounts', 'whmin') => $suspended_count,
        ],
        'disk' => [
            'used' => round($total_disk_used, 2),
            'limit' => $total_disk_limit > 0 ? round($total_disk_limit, 2) : 'unlimited',
            'percentage' => $disk_percentage,
            'unit' => 'MB'
        ],
        'bandwidth' => [
            'used' => round($total_bandwidth_used, 2),
            'limit' => $total_bandwidth_limit > 0 ? round($total_bandwidth_limit, 2) : 'unlimited',
            'percentage' => $bandwidth_percentage,
            'unit' => 'MB',
            'has_data' => $has_bandwidth_data
        ],
        'resources' => [
            __('Email Accounts', 'whmin') => $total_emails,
            __('Total Domains', 'whmin') => $total_domains,
            __('Subdomains', 'whmin') => $total_subdomains,
            __('Parked Domains', 'whmin') => $total_parked,
            __('Addon Domains', 'whmin') => $total_addon,
        ],
        'packages' => $packages,
        'total_accounts' => count($accounts),
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

    $accounts = $data['data']['acct'];
    $total_disk_used = 0;
    $total_disk_limit = 0;
    $unlimited_count = 0;
    $top_users = [];
    
    foreach ($accounts as $account) {
        $disk_used = floatval($account['diskused'] ?? 0);
        $disk_limit = $account['disklimit'] ?? 'unlimited';
        
        $total_disk_used += $disk_used;
        
        if ($disk_limit === 'unlimited') {
            $unlimited_count++;
        } else {
            $total_disk_limit += floatval($disk_limit);
        }
        
        // Track top disk users
        $top_users[] = [
            'user' => $account['user'] ?? 'unknown',
            'domain' => $account['domain'] ?? 'unknown',
            'used' => $disk_used,
        ];
    }
    
    // Sort by usage and get top 10
    usort($top_users, function($a, $b) {
        return $b['used'] - $a['used'];
    });
    $top_users = array_slice($top_users, 0, 10);
    
    $usage_percentage = ($total_disk_limit > 0) 
        ? round(($total_disk_used / $total_disk_limit) * 100, 2) 
        : 0;
    
    return [
        'total_used' => round($total_disk_used, 2),
        'total_limit' => $total_disk_limit > 0 ? round($total_disk_limit, 2) : 'unlimited',
        'percentage' => $usage_percentage,
        'unlimited_count' => $unlimited_count,
        'top_users' => $top_users,
    ];
}

/**
 * Main function to fetch all private dashboard data.
 * @return array
 */
function whmin_get_private_dashboard_data() {
    // Get base public data
    $public_data = whmin_get_public_dashboard_data();
    
    // Add comprehensive server details
    $public_data['server_details'] = [
        'account_summary' => whmin_get_account_summary(),
        'system_info' => whmin_get_basic_system_info(),
        'disk_usage' => whmin_get_disk_usage_info(),
        'mysql_info' => whmin_get_mysql_info(),
        'ssl_info' => whmin_get_ssl_info(),
        'apache_status' => whmin_get_apache_status(),
    ];
    
    return $public_data;
}