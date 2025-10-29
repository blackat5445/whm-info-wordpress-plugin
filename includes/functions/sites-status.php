<?php
/**
 * Functions for checking and logging server and site statuses.
 */
if (!defined('ABSPATH')) exit;

// Schedule cron if not already scheduled
if (!wp_next_scheduled('whmin_status_check_event')) {
    wp_schedule_event(time(), 'fifteen_minutes', 'whmin_status_check_event');
}
add_action('whmin_status_check_event', 'whmin_check_and_log_statuses');

/**
 * Main cron function to check and log all statuses.
 */
function whmin_check_and_log_statuses() {
    $latest_statuses = ['direct' => [], 'indirect' => []];
    $history_log = get_option('whmin_status_history_log', ['direct' => [], 'indirect' => []]);

    // 1. Check Direct Websites (Hosted on Our Server)
    $direct_sites = whmin_get_direct_connected_sites_data();
    foreach ($direct_sites as $site) {
        $response = wp_remote_head($site['url'], ['timeout' => 10, 'sslverify' => false]);
        $status_code = is_wp_error($response) ? 500 : wp_remote_retrieve_response_code($response);
        $status = ($status_code >= 200 && $status_code < 400) ? 'operational' : 'degraded';
        
        $latest_statuses['direct'][$site['user']] = ['status' => $status, 'timestamp' => current_time('timestamp')];
        $history_log['direct'][$site['user']][] = ['status' => $status, 'timestamp' => current_time('timestamp')];
    }
    
    // 2. Check In-direct Websites (API Connected Only)
    $indirect_sites = whmin_get_indirect_sites_data();
    foreach ($indirect_sites as $site) {
        // We only actively monitor sites set to use the API connection
        if ($site['connection'] === 'Standard API Connection') {
            $response = wp_remote_head($site['url'], ['timeout' => 10, 'sslverify' => false]);
            $status_code = is_wp_error($response) ? 500 : wp_remote_retrieve_response_code($response);
            $status = ($status_code >= 200 && $status_code < 400) ? 'operational' : 'degraded';

            $latest_statuses['indirect'][$site['uid']] = ['status' => $status, 'timestamp' => current_time('timestamp')];
            $history_log['indirect'][$site['uid']][] = ['status' => $status, 'timestamp' => current_time('timestamp')];
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
}

/**
 * Calculates the overall system status based on latest checks.
 */
function whmin_calculate_overall_status($latest_statuses) {
    // --- FIX: Ensure the keys exist before trying to count them ---
    $direct_statuses = isset($latest_statuses['direct']) && is_array($latest_statuses['direct']) ? $latest_statuses['direct'] : [];
    $indirect_statuses = isset($latest_statuses['indirect']) && is_array($latest_statuses['indirect']) ? $latest_statuses['indirect'] : [];

    $total_sites = count($direct_statuses) + count($indirect_statuses);
    $down_sites = 0;

    if ($total_sites === 0) {
        // If there are no sites being monitored yet, the status is operational by default.
        return ['status' => 'operational', 'text' => __('All Systems Operational', 'whmin')];
    }

    // Combine both arrays to loop through them
    $all_statuses = array_merge($direct_statuses, $indirect_statuses);

    foreach ($all_statuses as $site) {
        if (isset($site['status']) && $site['status'] !== 'operational') {
            $down_sites++;
        }
    }

    if ($down_sites === 0) {
        return ['status' => 'operational', 'text' => __('All Systems Operational', 'whmin')];
    }

    $down_percentage = ($down_sites / $total_sites) * 100;

    if ($down_percentage >= 70) {
        return ['status' => 'major_outage', 'text' => __('Major Outage', 'whmin')];
    }
    
    return ['status' => 'degraded', 'text' => __('Degraded Performance', 'whmin')];
}

/**
 * Prepares all data needed for the public dashboard template.
 */
function whmin_get_public_dashboard_data() {
    $latest_statuses = get_option('whmin_latest_statuses', []);

    // --- START: NEW ON-DEMAND FETCH LOGIC ---
    // Check if the status data is empty AND if a check isn't already running (transient lock).
    if (empty($latest_statuses) && false === get_transient('whmin_initial_status_check')) {
        
        // Set a "lock" transient that expires in 5 minutes.
        // This prevents the heavy check from running on every single page load if there's an issue.
        set_transient('whmin_initial_status_check', 'running', 5 * MINUTE_IN_SECONDS);

        // Manually trigger the status check function one time.
        whmin_check_and_log_statuses();
        
        // Re-fetch the data now that it has been populated.
        $latest_statuses = get_option('whmin_latest_statuses', []);

        // Delete the lock immediately after a successful run.
        delete_transient('whmin_initial_status_check');
    }
    // --- END: NEW ON-DEMAND FETCH LOGIC ---

    $direct_sites = whmin_get_direct_connected_sites_data();
    $indirect_sites = whmin_get_indirect_sites_data();

    $hosting_groups = [];
    foreach ($indirect_sites as $site) {
        $host = $site['hosting'] ?? 'Unknown';
        $hosting_groups[$host] = ($hosting_groups[$host] ?? 0) + 1;
    }

    return [
        'overall_status' => whmin_calculate_overall_status($latest_statuses),
        'stats' => [
            'direct_count' => count($direct_sites),
            'indirect_count' => count($indirect_sites),
            'hosting_groups' => $hosting_groups
        ],
    ];
}

function whmin_clear_scheduled_status_checks() {
    wp_clear_scheduled_hook('whmin_status_check_event');
}
register_deactivation_hook(WP_PLUGIN_DIR . '/' . WHMIN_PLUGIN_BASENAME, 'whmin_clear_scheduled_status_checks');