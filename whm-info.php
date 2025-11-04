<?php
/**
 * Plugin Name: WHM Info
 * Plugin URI: https://www.agenziamagma.it
 * Description: Key plugin to connect to WHM software and show the status of the services.
 * Version: 0.1.1
 * Author: Kasra Falahati, Agenzia Magma
 * Author URI: https://www.kasra.eu
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: whmin
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WHMIN_VERSION', '0.1.1');
define('WHMIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHMIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHMIN_PLUGIN_BASENAME', plugin_basename(__FILE__));


// Include the main plugin class
require_once WHMIN_PLUGIN_DIR . 'includes/class-whm-info.php';

// Initialize the plugin
function whmin_init() {
    return WHMIN::get_instance();
}
add_action('plugins_loaded', 'whmin_init');

/**
 * Activation: schedule whmin_status_check_event every 15 minutes.
 * IMPORTANT: Use an inline cron_schedules filter so we don't depend on other files being loaded yet.
 */
register_activation_hook(__FILE__, function () {

    // Ensure the custom interval exists during THIS request.
    add_filter('cron_schedules', function ($schedules) {
        if (!isset($schedules['fifteen_minutes'])) {
            $schedules['fifteen_minutes'] = array(
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => __('Every 15 Minutes', 'whmin'),
            );
        }
        return $schedules;
    }, 10);

    // Clear any orphaned instance and schedule aligned to the next quarter-hour
    wp_clear_scheduled_hook('whmin_status_check_event');

    $now  = time(); // UTC
    $next = $now + (15 * MINUTE_IN_SECONDS - ($now % (15 * MINUTE_IN_SECONDS)));

    if (!wp_next_scheduled('whmin_status_check_event')) {
        wp_schedule_event($next, 'fifteen_minutes', 'whmin_status_check_event');
    }
});

/**
 * Deactivation: clear the cron and plugin options.
 * Keep deactivation ONLY in this file.
 */
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('whmin_status_check_event');
    delete_option('whmin_latest_statuses');
    delete_option('whmin_status_history_log');
    delete_option('whmin_last_status_check');
    WHMIN::deactivate(); // your existing rewrite flush
});

/**
 * Runtime safety net: if the event disappears, recreate it after all files are loaded.
 */
add_action('plugins_loaded', function () {
    if (!wp_next_scheduled('whmin_status_check_event')) {
        // By now, the normal cron_schedules filter in sites-status.php is loaded.
        $now  = time();
        $next = $now + (15 * MINUTE_IN_SECONDS - ($now % (15 * MINUTE_IN_SECONDS)));
        wp_schedule_event($next, 'fifteen_minutes', 'whmin_status_check_event');
    }
});