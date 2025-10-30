<?php
/**
 * Functions for detailed site status display in private dashboard
 * UPDATED: Filters out sites with monitoring disabled
 */
if (!defined('ABSPATH')) exit;

/**
 * Get detailed status for all direct (hosted) sites
 * UPDATED: Filters by monitoring_enabled setting
 * 
 * @return array Array of sites with detailed status information
 */
function whmin_get_direct_sites_detailed_status() {
    $direct_sites = whmin_get_direct_connected_sites_data();
    $latest_statuses = get_option('whmin_latest_statuses', ['direct' => []]);
    $monitoring_settings = get_option('whmin_direct_monitoring_settings', []);
    
    if (is_wp_error($direct_sites) || !is_array($direct_sites)) {
        return [];
    }
    
    $detailed_status = [];
    
    foreach ($direct_sites as $site) {
        $user_key = $site['user'];
        
        // Check if monitoring is disabled
        $monitoring_enabled = isset($monitoring_settings[$user_key]) 
            ? (bool)$monitoring_settings[$user_key] 
            : true;
        
        $status_data = isset($latest_statuses['direct'][$user_key]) 
            ? $latest_statuses['direct'][$user_key] 
            : null;
        
        if (!$monitoring_enabled) {
            // Site has monitoring disabled - show as deactivated
            $detailed_status[] = [
                'name' => $site['name'],
                'url' => $site['url'],
                'user' => $site['user'],
                'status' => 'deactivated',
                'status_code' => 0,
                'response_time' => 0,
                'last_check' => 0,
                'monitoring_enabled' => false,
            ];
        } elseif ($status_data) {
            // Site has monitoring enabled and has status data
            $detailed_status[] = [
                'name' => $site['name'],
                'url' => $site['url'],
                'user' => $site['user'],
                'status' => $status_data['status'],
                'status_code' => $status_data['status_code'],
                'response_time' => $status_data['response_time'],
                'last_check' => $status_data['timestamp'],
                'monitoring_enabled' => true,
            ];
        } else {
            // No status data yet - show as unknown
            $detailed_status[] = [
                'name' => $site['name'],
                'url' => $site['url'],
                'user' => $site['user'],
                'status' => 'unknown',
                'status_code' => 0,
                'response_time' => 0,
                'last_check' => 0,
                'monitoring_enabled' => true,
            ];
        }
    }
    
    // Sort: deactivated last, then by status (down first, then degraded, then operational)
    usort($detailed_status, function($a, $b) {
        // Deactivated sites go to the end
        if ($a['status'] === 'deactivated' && $b['status'] !== 'deactivated') return 1;
        if ($a['status'] !== 'deactivated' && $b['status'] === 'deactivated') return -1;
        
        // Otherwise sort by status priority
        $order = ['down' => 0, 'degraded' => 1, 'unknown' => 2, 'operational' => 3, 'deactivated' => 4];
        $a_order = $order[$a['status']] ?? 99;
        $b_order = $order[$b['status']] ?? 99;
        return $a_order - $b_order;
    });
    
    return $detailed_status;
}

/**
 * Get detailed status for all indirect (managed) sites
 * UPDATED: Filters by monitoring_enabled field
 * 
 * @return array Array of sites with detailed status information
 */
function whmin_get_indirect_sites_detailed_status() {
    $indirect_sites = whmin_get_indirect_sites_data();
    $latest_statuses = get_option('whmin_latest_statuses', ['indirect' => []]);
    
    if (!is_array($indirect_sites)) {
        return [];
    }
    
    $detailed_status = [];
    
    foreach ($indirect_sites as $site) {
        // Only include sites with API connection
        if (isset($site['connection']) && $site['connection'] === 'Standard API Connection') {
            $uid = $site['uid'];
            
            // Check if monitoring is disabled
            $monitoring_enabled = isset($site['monitoring_enabled']) 
                ? (bool)$site['monitoring_enabled'] 
                : true;
            
            $status_data = isset($latest_statuses['indirect'][$uid]) 
                ? $latest_statuses['indirect'][$uid] 
                : null;
            
            if (!$monitoring_enabled) {
                // Site has monitoring disabled - show as deactivated
                $detailed_status[] = [
                    'name' => $site['name'],
                    'url' => $site['url'],
                    'uid' => $uid,
                    'hosting' => $site['hosting'] ?? 'Unknown',
                    'status' => 'deactivated',
                    'status_code' => 0,
                    'response_time' => 0,
                    'last_check' => 0,
                    'monitoring_enabled' => false,
                ];
            } elseif ($status_data) {
                // Site has monitoring enabled and has status data
                $detailed_status[] = [
                    'name' => $site['name'],
                    'url' => $site['url'],
                    'uid' => $uid,
                    'hosting' => $site['hosting'] ?? 'Unknown',
                    'status' => $status_data['status'],
                    'status_code' => $status_data['status_code'],
                    'response_time' => $status_data['response_time'],
                    'last_check' => $status_data['timestamp'],
                    'monitoring_enabled' => true,
                ];
            } else {
                // No status data yet - show as unknown
                $detailed_status[] = [
                    'name' => $site['name'],
                    'url' => $site['url'],
                    'uid' => $uid,
                    'hosting' => $site['hosting'] ?? 'Unknown',
                    'status' => 'unknown',
                    'status_code' => 0,
                    'response_time' => 0,
                    'last_check' => 0,
                    'monitoring_enabled' => true,
                ];
            }
        }
    }
    
    // Sort: deactivated last, then by status (down first, then degraded, then operational)
    usort($detailed_status, function($a, $b) {
        // Deactivated sites go to the end
        if ($a['status'] === 'deactivated' && $b['status'] !== 'deactivated') return 1;
        if ($a['status'] !== 'deactivated' && $b['status'] === 'deactivated') return -1;
        
        // Otherwise sort by status priority
        $order = ['down' => 0, 'degraded' => 1, 'unknown' => 2, 'operational' => 3, 'deactivated' => 4];
        $a_order = $order[$a['status']] ?? 99;
        $b_order = $order[$b['status']] ?? 99;
        return $a_order - $b_order;
    });
    
    return $detailed_status;
}

/**
 * Get status badge HTML for display
 * UPDATED: Added deactivated status
 * 
 * @param string $status The status (operational, degraded, down, unknown, deactivated)
 * @param int $status_code HTTP status code
 * @return string HTML for status badge
 */
function whmin_get_status_badge_html($status, $status_code) {
    $badges = [
        'operational' => [
            'class' => 'success',
            'icon' => 'check-circle',
            'text' => __('Operational', 'whmin')
        ],
        'degraded' => [
            'class' => 'warning',
            'icon' => 'alert-circle',
            'text' => __('Degraded', 'whmin')
        ],
        'down' => [
            'class' => 'danger',
            'icon' => 'close-circle',
            'text' => __('Down', 'whmin')
        ],
        'unknown' => [
            'class' => 'secondary',
            'icon' => 'help-circle',
            'text' => __('Unknown', 'whmin')
        ],
        'deactivated' => [
            'class' => 'muted',
            'icon' => 'eye-off',
            'text' => __('Monitoring Disabled', 'whmin')
        ]
    ];
    
    $badge = $badges[$status] ?? $badges['unknown'];
    $code_text = ($status_code > 0 && $status !== 'deactivated') ? " ({$status_code})" : '';
    
    return sprintf(
        '<span class="whmin-status-badge-table whmin-badge-%s"><i class="mdi mdi-%s"></i> %s%s</span>',
        esc_attr($badge['class']),
        esc_attr($badge['icon']),
        esc_html($badge['text']),
        esc_html($code_text)
    );
}

/**
 * Format response time for display
 * 
 * @param float $response_time Response time in milliseconds
 * @return string Formatted response time
 */
function whmin_format_response_time($response_time) {
    if ($response_time == 0) {
        return '<span class="whmin-response-time whmin-response-unknown">—</span>';
    }
    
    $class = 'whmin-response-fast';
    if ($response_time > 3000) {
        $class = 'whmin-response-slow';
    } elseif ($response_time > 1000) {
        $class = 'whmin-response-medium';
    }
    
    if ($response_time >= 1000) {
        $formatted = round($response_time / 1000, 2) . 's';
    } else {
        $formatted = round($response_time, 0) . 'ms';
    }
    
    return sprintf('<span class="whmin-response-time %s">%s</span>', $class, $formatted);
}

/**
 * Get statistics for status overview
 * UPDATED: Counts deactivated separately
 * 
 * @param array $sites Array of sites with status
 * @return array Statistics
 */
function whmin_get_status_statistics($sites) {
    $stats = [
        'total' => count($sites),
        'operational' => 0,
        'degraded' => 0,
        'down' => 0,
        'unknown' => 0,
        'deactivated' => 0,
        'avg_response_time' => 0
    ];
    
    $total_response_time = 0;
    $response_count = 0;
    
    foreach ($sites as $site) {
        $status = $site['status'] ?? 'unknown';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        
        // Only count response times for active monitoring
        if (isset($site['response_time']) && $site['response_time'] > 0 && $status !== 'deactivated') {
            $total_response_time += $site['response_time'];
            $response_count++;
        }
    }
    
    if ($response_count > 0) {
        $stats['avg_response_time'] = $total_response_time / $response_count;
    }
    
    return $stats;
}

/**
 * Calculate uptime percentage for a site
 * 
 * @param string $site_key Site identifier (user or uid)
 * @param string $type 'direct' or 'indirect'
 * @param string $period Period to calculate (24h, 7d, 30d)
 * @return float Uptime percentage
 */
function whmin_calculate_uptime_percentage($site_key, $type, $period = '24h') {
    $history_log = get_option('whmin_status_history_log', ['direct' => [], 'indirect' => []]);
    
    if (!isset($history_log[$type][$site_key])) {
        return 100; // No history means we assume it's up
    }
    
    $entries = $history_log[$type][$site_key];
    
    // Calculate time range
    $now = current_time('timestamp');
    $periods = [
        '24h' => 24 * 60 * 60,
        '7d' => 7 * 24 * 60 * 60,
        '30d' => 30 * 24 * 60 * 60
    ];
    
    $cutoff_time = $now - ($periods[$period] ?? $periods['24h']);
    
    // Filter entries within time range
    $relevant_entries = array_filter($entries, function($entry) use ($cutoff_time) {
        return $entry['timestamp'] >= $cutoff_time;
    });
    
    if (empty($relevant_entries)) {
        return 100;
    }
    
    $total = count($relevant_entries);
    $operational = count(array_filter($relevant_entries, function($entry) {
        return $entry['status'] === 'operational';
    }));
    
    return round(($operational / $total) * 100, 2);
}