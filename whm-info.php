<?php
/**
 * Plugin Name: WHM Info
 * Plugin URI: https://www.agenziamagma.it
 * Description: Key plugin to connect to WHM software and show the status of the services.
 * Version: 0.0.1
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
define('WHMIN_VERSION', '0.0.1');
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

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('WHMIN', 'activate'));
register_deactivation_hook(__FILE__, array('WHMIN', 'deactivate'));