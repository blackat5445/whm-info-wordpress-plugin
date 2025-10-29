<?php
/**
 * Public Dashboard Shortcode
 *
 * @package WHM_Info/Shortcodes
 */
if (!defined('ABSPATH')) exit;

add_shortcode('whmin_public_dashboard', 'whmin_render_public_dashboard_shortcode');

function whmin_render_public_dashboard_shortcode($atts) {
    // The shortcode's only responsibility is now to render the template.
    // Asset enqueueing is handled by the main class.
    ob_start();
    include_once WHMIN_PLUGIN_DIR . 'templates/public/public-dashboard.php';
    return ob_get_clean();
}