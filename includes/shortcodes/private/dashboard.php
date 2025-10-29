<?php
/**
 * Private Dashboard Shortcode
 *
 * @package WHM_Info/Shortcodes
 */
if (!defined('ABSPATH')) exit;

add_shortcode('whmin_private_dashboard', 'whmin_render_private_dashboard_shortcode');

function whmin_render_private_dashboard_shortcode($atts) {
    // The shortcode's only responsibility is to render the template.
    // Asset enqueueing is handled by the main class.
    
    // --- SECURITY CHECK ---
    // Ensure only logged-in users with the 'manage_options' capability can see this.
    if (!current_user_can('manage_options')) {
        return '<p>' . __('You do not have permission to view this content.', 'whmin') . '</p>';
    }

    ob_start();
    // Point to the new private template file
    include_once WHMIN_PLUGIN_DIR . 'templates/private/private-dashboard.php';
    return ob_get_clean();
}