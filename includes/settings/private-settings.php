<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {
    if ( ! wp_next_scheduled('whmin_status_check_event') ) {
        $now  = current_time('timestamp'); // site tz for alignment only
        $next = $now + (15 * MINUTE_IN_SECONDS - ($now % (15 * MINUTE_IN_SECONDS)));
        wp_schedule_event($next, 'fifteen_minutes', 'whmin_status_check_event');
    }
});

/**
 * Ensure the 15-minute status-check event exists when viewing admin.
 * This is a safety net in case activation scheduling failed.
 */
function whmin_ensure_status_cron() {
    if ( ! wp_next_scheduled('whmin_status_check_event') ) {
        $now  = current_time('timestamp');
        // align to next quarter hour for neat scheduling
        $next = $now + (15 * MINUTE_IN_SECONDS - ($now % (15 * MINUTE_IN_SECONDS)));
        wp_schedule_event($next, 'fifteen_minutes', 'whmin_status_check_event');
    }
}
add_action('admin_init', 'whmin_ensure_status_cron');
