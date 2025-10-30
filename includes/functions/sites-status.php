<?php
/**
 * Functions for checking and logging server and site statuses.
 * UPDATED: Respects include/exclude monitoring settings + new overall status thresholds
 */
if (!defined('ABSPATH')) exit;

/**
 * Register custom 15-minute cron interval
 */
function whmin_add_fifteen_minute_cron_interval($schedules) {
    if (!isset($schedules['fifteen_minutes'])) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * 60,
            'display'  => __('Every 15 Minutes', 'whmin')
        );
    }
    return $schedules;
}
add_filter('cron_schedules', 'whmin_add_fifteen_minute_cron_interval');

/**
 * Schedule cron on plugin activation
 */
function whmin_schedule_status_checks() {
    wp_clear_scheduled_hook('whmin_status_check_event');
    
    if (!wp_next_scheduled('whmin_status_check_event')) {
        wp_schedule_event(time(), 'fifteen_minutes', 'whmin_status_check_event');
    }
}

/**
 * Hook the status check to the cron event
 */
add_action('whmin_status_check_event', 'whmin_check_and_log_statuses');

/**
 * Main cron function to check and log all statuses.
 * UPDATED: Respects monitoring_enabled settings
 */
function whmin_check_and_log_statuses() {
    $latest_statuses = ['direct' => [], 'indirect' => []];
    $history_log = get_option('whmin_status_history_log', ['direct' => [], 'indirect' => []]);
    $current_timestamp = current_time('timestamp');

    // 1. Check Direct Websites (Hosted on Our Server)
    $direct_sites = whmin_get_direct_connected_sites_data();
    $monitoring_settings = get_option('whmin_direct_monitoring_settings', []);
    
    if (!is_wp_error($direct_sites) && is_array($direct_sites)) {
        foreach ($direct_sites as $site) {
            // Skip if monitoring is disabled
            if (isset($monitoring_settings[$site['user']]) && !$monitoring_settings[$site['user']]) {
                continue;
            }
            
            $check_result = whmin_check_site_status($site['url']);
            
            $status_entry = [
                'status'        => $check_result['status'],
                'status_code'   => $check_result['status_code'],
                'response_time' => $check_result['response_time'],
                'timestamp'     => $current_timestamp,
                'url'           => $site['url'],
                'name'          => $site['name']
            ];
            
            $latest_statuses['direct'][$site['user']] = $status_entry;
            
            if (!isset($history_log['direct'][$site['user']])) {
                $history_log['direct'][$site['user']] = [];
            }
            $history_log['direct'][$site['user']][] = [
                'status'    => $check_result['status'],
                'timestamp' => $current_timestamp
            ];
        }
    }
    
    // 2. Check Indirect Websites (API Connected Only)
    $indirect_sites = whmin_get_indirect_sites_data();
    
    if (is_array($indirect_sites)) {
        foreach ($indirect_sites as $site) {
            // Skip if monitoring is disabled
            if (isset($site['monitoring_enabled']) && !$site['monitoring_enabled']) {
                continue;
            }
            
            // Only monitor sites with Standard API Connection
            if (isset($site['connection']) && $site['connection'] === 'Standard API Connection') {
                $check_result = whmin_check_site_status($site['url']);
                
                $status_entry = [
                    'status'        => $check_result['status'],
                    'status_code'   => $check_result['status_code'],
                    'response_time' => $check_result['response_time'],
                    'timestamp'     => $current_timestamp,
                    'url'           => $site['url'],
                    'name'          => $site['name']
                ];
                
                $latest_statuses['indirect'][$site['uid']] = $status_entry;
                
                if (!isset($history_log['indirect'][$site['uid']])) {
                    $history_log['indirect'][$site['uid']] = [];
                }
                $history_log['indirect'][$site['uid']][] = [
                    'status'    => $check_result['status'],
                    'timestamp' => $current_timestamp
                ];
            }
        }
    }
    
    // Trim history logs to prevent excessive size
    foreach (['direct', 'indirect'] as $type) {
        if (!empty($history_log[$type])) {
            foreach ($history_log[$type] as &$entries) {
                if (count($entries) > 1000) {
                    $entries = array_slice($entries, -1000);
                }
            }
        }
    }

    update_option('whmin_latest_statuses', $latest_statuses);
    update_option('whmin_status_history_log', $history_log);
    update_option('whmin_last_status_check', $current_timestamp);
    
    error_log('WHM Monitor: Status check completed at ' . date('Y-m-d H:i:s', $current_timestamp));
}

/**
 * Check individual site status with proper error handling
 */
function whmin_check_site_status($url) {
    $start_time = microtime(true);
    
    $response = wp_remote_head($url, [
        'timeout'     => 30,
        'sslverify'   => false,
        'redirection' => 5,
        'user-agent'  => 'WHM-Monitor-Status-Checker/1.0'
    ]);
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    if (is_wp_error($response)) {
        return [
            'status'        => 'down',
            'status_code'   => 0,
            'response_time' => $response_time,
            'error'         => $response->get_error_message()
        ];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code >= 200 && $status_code < 400) {
        $status = 'operational';
    } elseif ($status_code >= 400 && $status_code < 500) {
        $status = 'degraded';
    } else {
        $status = 'down';
    }
    
    return [
        'status'        => $status,
        'status_code'   => $status_code,
        'response_time' => $response_time
    ];
}

/**
 * Calculates the overall system status from latest checks (monitored only).
 * - Considers direct+indirect entries in latest_statuses
 * - Only counts statuses in ['operational','degraded','down']
 * - Deactivated sites are never written into latest_statuses by design
 * - Thresholds: 100% => operational; 75–<100% => degraded; <75% => down
 * - Returns exact percent + counts + list of problematic sites
 */
function whmin_calculate_overall_status($latest_statuses) {
    $direct_statuses   = (isset($latest_statuses['direct'])   && is_array($latest_statuses['direct']))   ? $latest_statuses['direct']   : [];
    $indirect_statuses = (isset($latest_statuses['indirect']) && is_array($latest_statuses['indirect'])) ? $latest_statuses['indirect'] : [];

    $all = array_merge($direct_statuses, $indirect_statuses);

    $valid = ['operational', 'degraded', 'down'];
    $operational = 0;
    $degraded    = 0;
    $down        = 0;
    $problem_sites = [];

    foreach ($all as $entry) {
        $s = strtolower(trim($entry['status'] ?? ''));
        if ($s === 'operational') {
            $operational++;
        } elseif ($s === 'degraded') {
            $degraded++;
            $problem_sites[] = [
                'name' => $entry['name'] ?? 'Unknown',
                'url'  => $entry['url'] ?? '',
                'status' => 'degraded'
            ];
        } elseif ($s === 'down') {
            $down++;
            $problem_sites[] = [
                'name' => $entry['name'] ?? 'Unknown',
                'url'  => $entry['url'] ?? '',
                'status' => 'down'
            ];
        }
        // anything else (unknown/empty/etc) is ignored
    }

    $monitored_total = $operational + $degraded + $down;

    // If nothing to consider yet (e.g., first run), show 100% operational
    if ($monitored_total === 0) {
        return [
            'status'  => 'operational',
            'text'    => __('All Systems Operational', 'whmin'),
            'percent' => 100.00,
            'counts'  => [
                'operational' => 0,
                'degraded'    => 0,
                'down'        => 0,
                'total'       => 0,
            ],
            'problems' => [],
        ];
    }

    $uptime = ($operational / $monitored_total) * 100;
    $uptime = round($uptime, 2);

    if ($uptime >= 100) {
        $status = 'operational';
        $text   = __('All Systems Operational', 'whmin');
    } elseif ($uptime >= 75) {
        $status = 'degraded';
        $text   = __('Degraded Performance', 'whmin');
    } else {
        $status = 'down';
        $text   = __('Downtime Detected', 'whmin');
    }

    return [
        'status'  => $status,
        'text'    => $text,
        'percent' => $uptime,
        'counts'  => [
            'operational' => $operational,
            'degraded'    => $degraded,
            'down'        => $down,
            'total'       => $monitored_total,
        ],
        'problems' => $problem_sites,
    ];
}
/**
 * Prepares all data needed for the public dashboard template.
 */
function whmin_get_public_dashboard_data() {
    $latest_statuses = get_option('whmin_latest_statuses', []);
    $last_check = get_option('whmin_last_status_check', 0);

    $is_stale = (current_time('timestamp') - $last_check) > (30 * 60);
    
    if ((empty($latest_statuses) || $is_stale) && false === get_transient('whmin_initial_status_check')) {
        set_transient('whmin_initial_status_check', 'running', 5 * MINUTE_IN_SECONDS);
        whmin_check_and_log_statuses();
        $latest_statuses = get_option('whmin_latest_statuses', []);
        delete_transient('whmin_initial_status_check');
    }

    $direct_sites   = whmin_get_direct_connected_sites_data();
    $indirect_sites = whmin_get_indirect_sites_data();
    $history_log    = get_option('whmin_status_history_log', ['direct' => [], 'indirect' => []]);

    $hosting_groups = [];
    if (is_array($indirect_sites)) {
        foreach ($indirect_sites as $site) {
            $host = $site['hosting'] ?? 'Unknown';
            $hosting_groups[$host] = ($hosting_groups[$host] ?? 0) + 1;
        }
    }

    return [
        'overall_status' => whmin_calculate_overall_status($latest_statuses),
        'stats' => [
            'direct_count'   => is_array($direct_sites) ? count($direct_sites) : 0,
            'indirect_count' => is_array($indirect_sites) ? count($indirect_sites) : 0,
            'hosting_groups' => $hosting_groups
        ],
        'latest_statuses' => $latest_statuses,
        'history'         => $history_log,
        'last_check'      => $last_check
    ];
}

/**
 * Manual status check trigger
 */
function whmin_manual_status_check() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }
    
    whmin_check_and_log_statuses();
    
    wp_send_json_success([
        'message'   => __('Site statuses refreshed successfully!', 'whmin'),
        'timestamp' => current_time('mysql')
    ]);
}
add_action('wp_ajax_whmin_manual_status_check', 'whmin_manual_status_check');

/**
 * Clear scheduled status checks on plugin deactivation
 */
function whmin_clear_scheduled_status_checks() {
    wp_clear_scheduled_hook('whmin_status_check_event');
    delete_option('whmin_latest_statuses');
    delete_option('whmin_status_history_log');
    delete_option('whmin_last_status_check');
}
register_deactivation_hook(WP_PLUGIN_DIR . '/' . WHMIN_PLUGIN_BASENAME, 'whmin_clear_scheduled_status_checks');

/**
 * Get next scheduled cron time
 */
if ( ! function_exists('whmin_get_next_cron_time') ) {
    function whmin_get_next_cron_time() {
        $ts = wp_next_scheduled('whmin_status_check_event');
        if ( ! $ts ) {
            return __('Not scheduled', 'whmin');
        }

        // wp_next_scheduled() returns a UTC timestamp.
        // wp_date() converts it to the site’s timezone correctly (handles DST).
        $fmt      = get_option('date_format') . ' ' . get_option('time_format');
        $absolute = wp_date($fmt, $ts);
        $relative = human_time_diff(current_time('timestamp'), $ts);

        return sprintf(__('in %1$s (%2$s)', 'whmin'), $relative, $absolute);
    }
}