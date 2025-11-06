<?php
/**
 * Functions for checking and logging server and site statuses.
 * UPDATED: Respects include/exclude monitoring settings + new overall status thresholds
 */
if (!defined('ABSPATH')) exit;

/**
 * Register custom cron intervals for status checks.
 * Keeps the old 'fifteen_minutes' for backward compatibility and adds
 * new WHMIN-specific intervals.
 */
function whmin_add_custom_cron_intervals( $schedules ) {
    // Backwards compatibility: existing 15-minute interval.
    if ( ! isset( $schedules['fifteen_minutes'] ) ) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'whmin' ),
        );
    }

    // New WHMIN intervals (used by settings).
    $custom = array(
        'whmin_5_minutes'  => 5,
        'whmin_10_minutes' => 10,
        'whmin_15_minutes' => 15,
        'whmin_30_minutes' => 30,
        'whmin_60_minutes' => 60,
    );

    foreach ( $custom as $slug => $minutes ) {
        if ( ! isset( $schedules[ $slug ] ) ) {
            $schedules[ $slug ] = array(
                'interval' => $minutes * MINUTE_IN_SECONDS,
                'display'  => sprintf( __( 'Every %d Minutes', 'whmin' ), $minutes ),
            );
        }
    }

    return $schedules;
}
add_filter( 'cron_schedules', 'whmin_add_custom_cron_intervals' );

/**
 * Get the selected cron schedule slug for site status checks.
 *
 * @return string
 */
function whmin_get_status_cron_schedule() {
    $settings = function_exists( 'whmin_get_private_settings' )
        ? whmin_get_private_settings()
        : array();

    $slug = isset( $settings['status_cron_schedule'] )
        ? $settings['status_cron_schedule']
        : 'whmin_15_minutes';

    $allowed = array(
        'whmin_5_minutes',
        'whmin_10_minutes',
        'whmin_15_minutes',
        'whmin_30_minutes',
        'whmin_60_minutes',
        // Accept legacy slug just in case
        'fifteen_minutes',
    );

    if ( ! in_array( $slug, $allowed, true ) ) {
        $slug = 'whmin_15_minutes';
    }

    return $slug;
}

/**
 * Map schedule slug to interval in minutes.
 *
 * @param string $schedule_slug
 * @return int
 */
function whmin_get_interval_minutes_for_schedule( $schedule_slug ) {
    $map = array(
        'whmin_5_minutes'   => 5,
        'whmin_10_minutes'  => 10,
        'whmin_15_minutes'  => 15,
        'whmin_30_minutes'  => 30,
        'whmin_60_minutes'  => 60,
        'fifteen_minutes'   => 15,
    );

    return isset( $map[ $schedule_slug ] ) ? (int) $map[ $schedule_slug ] : 15;
}

/**
 * Ensure the whmin_status_check_event is scheduled with the current settings.
 *
 * If $force is true, the event is rescheduled even if it already exists
 * (used when settings change).
 *
 * @param bool $force
 * @return void
 */
function whmin_schedule_status_event_with_current_settings( $force = false ) {
    $schedule_slug   = whmin_get_status_cron_schedule();
    $interval_min    = whmin_get_interval_minutes_for_schedule( $schedule_slug );
    $interval        = $interval_min * MINUTE_IN_SECONDS;

    // Try to read the existing event (WP 5.1+)
    if ( function_exists( 'wp_get_scheduled_event' ) ) {
        $existing = wp_get_scheduled_event( 'whmin_status_check_event' );
    } else {
        $existing = false;
    }

    if ( $existing && ! $force ) {
        // If schedule + interval already match, nothing to do.
        $existing_interval = isset( $existing->interval ) ? (int) $existing->interval : 0;
        if ( $existing->schedule === $schedule_slug && $existing_interval === $interval ) {
            return;
        }
    }

    // Clear and reschedule.
    wp_clear_scheduled_hook( 'whmin_status_check_event' );

    $now  = time(); // UTC
    $next = $now + ( $interval - ( $now % $interval ) );

    wp_schedule_event( $next, $schedule_slug, 'whmin_status_check_event' );
}

/**
 * After private settings are updated, reschedule cron with the new interval.
 */
function whmin_reschedule_status_event_after_settings( $old_value, $value ) {
    whmin_schedule_status_event_with_current_settings( true );
}
add_action( 'update_option_whmin_private_settings', 'whmin_reschedule_status_event_after_settings', 10, 2 );



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
    whmin_maybe_send_status_notifications($latest_statuses);

    
    error_log('WHM Monitor: Status check completed at ' . date('Y-m-d H:i:s', $current_timestamp));
}

/**
 * Check individual site status with proper error handling
 * Optimised: shorter default timeout + filterable HTTP args
 *
 * @param string $url
 * @return array
 */
/**
 * Check individual site status with proper error handling.
 * Optimised: timeout taken from Private Settings.
 *
 * @param string $url
 * @return array
 */
function whmin_check_site_status( $url ) {
    $start_time = microtime( true );

    $timeout = whmin_get_site_status_timeout();

    // Default request args (optimised, using dynamic timeout)
    $args = array(
        'timeout'     => $timeout,
        'sslverify'   => false,
        'redirection' => 3,
        'user-agent'  => 'WHM-Monitor-Status-Checker/1.0',
    );

    /**
     * Filter to customise the HTTP request args for site status checks.
     *
     * @param array  $args Request args.
     * @param string $url  Site URL.
     */
    $args = apply_filters( 'whmin_site_status_request_args', $args, $url );

    $response = wp_remote_head( $url, $args );

    $end_time      = microtime( true );
    $response_time = round( ( $end_time - $start_time ) * 1000, 2 );

    if ( is_wp_error( $response ) ) {
        return array(
            'status'        => 'down',
            'status_code'   => 0,
            'response_time' => $response_time,
            'error'         => $response->get_error_message(),
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );

    if ( $status_code >= 200 && $status_code < 400 ) {
        $status = 'operational';
    } elseif ( $status_code >= 400 && $status_code < 500 ) {
        $status = 'degraded';
    } else {
        $status = 'down';
    }

    return array(
        'status'        => $status,
        'status_code'   => $status_code,
        'response_time' => $response_time,
    );
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
 * Build a simple list of "problem keys" (here we use URLs) from overall status.
 *
 * @param array $overall
 * @return array
 */
function whmin_build_problem_keys_from_overall($overall) {
    $keys = array();

    if (!empty($overall['problems']) && is_array($overall['problems'])) {
        foreach ($overall['problems'] as $item) {
            if (!empty($item['url'])) {
                $keys[] = $item['url'];
            }
        }
    }

    return array_values(array_unique($keys));
}

/**
 * Decide if/when to send notifications based on current vs previous overall state.
 *
 * Rules:
 * - When uptime goes from 100% => <100% => send immediately (down/degraded).
 * - While still <100%, send reminders no more often than global interval.
 * - When going from <100% => 100% => send "all up" notification immediately.
 * - If new sites go down after being OK, send again even if interval not reached.
 *
 * @param array $latest_statuses
 * @return void
 */
function whmin_maybe_send_status_notifications($latest_statuses) {
    if (!function_exists('whmin_get_notification_recipients_raw')) {
        return;
    }

    $recipients = whmin_get_notification_recipients_raw();
    if (empty($recipients)) {
        // No one to notify; just keep state updated elsewhere if needed.
        return;
    }

    $overall = whmin_calculate_overall_status($latest_statuses);
    $percent = (float) ($overall['percent'] ?? 100.0);
    $status  = $overall['status'] ?? 'operational';
    $now     = current_time('timestamp');

    $state = get_option('whmin_notification_state', array());

    $prev_percent          = isset($state['last_overall_percent']) ? (float) $state['last_overall_percent'] : 100.0;
    $prev_status           = isset($state['last_overall_status']) ? $state['last_overall_status'] : 'operational';
    $prev_problem_keys     = isset($state['last_problem_keys']) && is_array($state['last_problem_keys']) ? $state['last_problem_keys'] : array();
    $last_problem_notified = isset($state['last_problem_notification']) ? (int) $state['last_problem_notification'] : 0;

    $current_problem_keys = whmin_build_problem_keys_from_overall($overall);
    $new_problem_keys     = array_diff($current_problem_keys, $prev_problem_keys);

    $interval = function_exists('whmin_get_notification_interval_seconds')
        ? (int) whmin_get_notification_interval_seconds()
        : 0;

    $went_from_ok_to_bad = ($prev_percent >= 100.0 && $percent < 100.0);
    $still_bad           = ($percent < 100.0);
    $was_bad             = ($prev_percent < 100.0);
    $went_back_to_ok     = ($prev_percent < 100.0 && $percent >= 100.0);

    // 1) Problem notifications
    if ($went_from_ok_to_bad || !empty($new_problem_keys)) {
        // Immediate notification when we enter a problem state OR new sites go down
        whmin_send_status_notifications('problem', $overall, $recipients);
        $state['last_problem_notification'] = $now;

    } elseif ($still_bad && $was_bad && $interval > 0 && ($now - $last_problem_notified) >= $interval) {
        // Optional reminder while things are still not 100%
        whmin_send_status_notifications('problem', $overall, $recipients);
        $state['last_problem_notification'] = $now;
    }

    // 2) Resolution notification (not throttled)
    if ($went_back_to_ok) {
        whmin_send_status_notifications('resolved', $overall, $recipients);
        $state['last_resolved_notification'] = $now;
    }

    // 3) Persist snapshot for next run
    $state['last_overall_percent'] = $percent;
    $state['last_overall_status']  = $status;
    $state['last_problem_keys']    = $current_problem_keys;
    $state['last_seen']            = $now;

    update_option('whmin_notification_state', $state);
}

/**
 * Send email + Telegram notifications to all recipients for a given overall state.
 *
 * @param string $type 'problem' or 'resolved'
 * @param array  $overall Overall status array from whmin_calculate_overall_status()
 * @param array  $recipients Recipients list (from whmin_get_notification_recipients_raw()).
 * @return void
 */
function whmin_send_status_notifications( $type, $overall, $recipients ) {
    if ( empty( $recipients ) || ! is_array( $recipients ) ) {
        return;
    }

    $counts    = $overall['counts'] ?? array();
    $percent   = isset( $overall['percent'] ) ? (float) $overall['percent'] : 0.0;
    $status_tx = $overall['text'] ?? '';
    $problems  = ! empty( $overall['problems'] ) && is_array( $overall['problems'] )
        ? $overall['problems']
        : array();

    $timestamp = wp_date(
        get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
        current_time( 'timestamp' )
    );

    // SUBJECT
    if ( $type === 'problem' ) {
        $subject = sprintf(
            '[WHM Info] %s – %s%%',
            __( 'Issues detected', 'whmin' ),
            number_format_i18n( $percent, 2 )
        );
    } else {
        $subject = sprintf(
            '[WHM Info] %s',
            __( 'All systems operational again', 'whmin' )
        );
    }

    foreach ( $recipients as $recipient ) {
        $name            = $recipient['name']            ?? '';
        $email           = $recipient['email']           ?? '';
        $notify_email    = isset( $recipient['notify_email'] )   ? (bool) $recipient['notify_email']   : true;
        $notify_telegram = isset( $recipient['notify_telegram'] )? (bool) $recipient['notify_telegram']: false;
        $telegram_chat   = $recipient['telegram_chat']   ?? '';

        // Email body via template
        $context = array(
            'type'        => $type,
            'overall'     => $overall,
            'recipient'   => $recipient,
            'recipient_name' => $name,
            'percent'     => $percent,
            'status_text' => $status_tx,
            'counts'      => $counts,
            'problems'    => $problems,
            'timestamp'   => $timestamp,
            'subject'     => $subject,
            'site_name'   => get_bloginfo( 'name' ),
        );

        $message_html = whmin_render_email_template( 'email-status-notification', $context );

        // If template missing, fall back to a simple preformatted text email
        if ( $message_html === '' ) {
            $message_html = whmin_build_fallback_status_email_html( $context );
        }

        if ( $notify_email && is_email( $email ) ) {
            whmin_send_email_notification( $email, $subject, $message_html );
        }

        if ( $notify_telegram && $telegram_chat !== '' ) {
            // Telegram gets a plain-text version of the same message
            $telegram_text = wp_strip_all_tags( $message_html );
            whmin_send_telegram_notification( $telegram_chat, $telegram_text );
        }
    }
}



/**
 * Get the per-site status HTTP timeout (seconds) from settings.
 *
 * @return int
 */
function whmin_get_site_status_timeout() {
    $settings = function_exists( 'whmin_get_private_settings' )
        ? whmin_get_private_settings()
        : array();

    $timeout = isset( $settings['site_status_timeout'] )
        ? (int) $settings['site_status_timeout']
        : 8;

    // Clamp between 1 and 60 seconds
    if ( $timeout < 1 ) {
        $timeout = 1;
    } elseif ( $timeout > 60 ) {
        $timeout = 60;
    }

    /**
     * Filter to allow overriding the site status timeout programmatically.
     *
     * @param int    $timeout Timeout in seconds.
     * @param string $context Context string, currently 'default'.
     */
    return (int) apply_filters( 'whmin_site_status_timeout', $timeout, 'default' );
}
/**
 * Prepares all data needed for the public (and private) dashboard template.
 * Optimised:
 * - Only triggers a synchronous status check when there is NO data yet.
 * - Regular refreshes are handled by WP-Cron and the manual AJAX button.
 *
 * @return array
 */
function whmin_get_public_dashboard_data() {
    $latest_statuses = get_option('whmin_latest_statuses', array());
    $last_check      = (int) get_option('whmin_last_status_check', 0);
    $now             = current_time('timestamp');

    // Detect stale data (not currently used in logic but kept for possible UI usage)
    $is_stale = ($last_check > 0)
        ? (($now - $last_check) > (30 * MINUTE_IN_SECONDS))
        : true;

    /**
     * Only perform a synchronous status check when:
     * - There is NO status data at all (fresh install / after cleanup)
     * - We are NOT in a cron or AJAX context
     * - A safety transient is not already set (avoid duplicate runs)
     *
     * Regular refreshes should happen via:
     * - The scheduled cron: whmin_status_check_event (every 15 minutes)
     * - The manual AJAX refresh: whmin_manual_status_check
     */
    if (
        empty($latest_statuses)
        && !wp_doing_cron()
        && !wp_doing_ajax()
        && false === get_transient('whmin_initial_status_check')
    ) {
        set_transient('whmin_initial_status_check', 'running', 5 * MINUTE_IN_SECONDS);

        whmin_check_and_log_statuses();

        $latest_statuses = get_option('whmin_latest_statuses', array());
        $last_check      = (int) get_option('whmin_last_status_check', 0);

        delete_transient('whmin_initial_status_check');
    }

    $direct_sites   = whmin_get_direct_connected_sites_data();
    $indirect_sites = whmin_get_indirect_sites_data();
    $history_log    = get_option('whmin_status_history_log', array('direct' => array(), 'indirect' => array()));

    $hosting_groups = array();
    if (is_array($indirect_sites)) {
        foreach ($indirect_sites as $site) {
            $host = isset($site['hosting']) && $site['hosting'] !== '' ? $site['hosting'] : 'Unknown';
            if (!isset($hosting_groups[$host])) {
                $hosting_groups[$host] = 0;
            }
            $hosting_groups[$host]++;
        }
    }

    return array(
        'overall_status' => whmin_calculate_overall_status($latest_statuses),
        'stats'          => array(
            'direct_count'   => is_array($direct_sites)   ? count($direct_sites)   : 0,
            'indirect_count' => is_array($indirect_sites) ? count($indirect_sites) : 0,
            'hosting_groups' => $hosting_groups,
        ),
        'latest_statuses' => $latest_statuses,
        'history'         => $history_log,
        'last_check'      => $last_check,
        // $is_stale is available if you want to show a "data is stale" badge later
    );
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