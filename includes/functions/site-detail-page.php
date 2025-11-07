<?php
/**
 * Virtual "Site Detail" page for direct + remote sites.
 *
 * URL pattern: /whmin-site-info/{type}/{key}/
 *   - type: 'direct' or 'remote'
 *   - key:  cPanel user (direct) or UID (remote/API site)
 */

if (!defined('ABSPATH')) exit;

/**
 * Add rewrite rule: /whmin-site-info/{type}/{key}/
 */
function whmin_register_site_detail_rewrite() {
    add_rewrite_rule(
        '^whmin-site-info/([^/]+)/([^/]+)/?$',
        'index.php?whmin_site_info=1&whmin_site_type=$matches[1]&whmin_site_key=$matches[2]',
        'top'
    );
}
add_action('init', 'whmin_register_site_detail_rewrite');

/**
 * Register custom query vars.
 */
function whmin_site_detail_query_vars($vars) {
    $vars[] = 'whmin_site_info';
    $vars[] = 'whmin_site_type';
    $vars[] = 'whmin_site_key';
    return $vars;
}
add_filter('query_vars', 'whmin_site_detail_query_vars');

/**
 * Use our custom template when whmin_site_info is present.
 * STRICTLY ADMIN-ONLY: non-admins get a 404.
 */
function whmin_site_detail_template_loader($template) {
    if (get_query_var('whmin_site_info')) {

        // Only allow users who can manage_options (admins)
        if (!current_user_can('manage_options')) {
            global $wp_query;

            // Force 404
            $wp_query->set_404();
            status_header(404);

            $not_found = get_404_template();
            return $not_found ? $not_found : $template;
        }

        $custom = WHMIN_PLUGIN_DIR . 'templates/site-detail.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
}
add_filter('template_include', 'whmin_site_detail_template_loader');
