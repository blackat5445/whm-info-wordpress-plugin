<?php
/**
 * Functions for the In-direct Connected Websites settings tab.
 *
 * @package WHM_Info/Includes/Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves the list of indirect sites from the database.
 *
 * @return array An array of site data.
 */
function whmin_get_indirect_sites_data() {
    $sites = get_option('whmin_indirect_sites', []);
    // Ensure keys are reset if items are deleted, and add a numeric ID
    $sites_data = [];
    $id_counter = 1;
    foreach ($sites as $site) {
        $site['id'] = $id_counter++;
        $sites_data[] = $site;
    }
    return $sites_data;
}

/**
 * Returns a predefined list of hosting providers.
 *
 * @return array
 */
function whmin_get_hosting_providers() {
    return [
        '-- ' . __('Popular International', 'whmin') . ' --' => [
            'GoDaddy' => 'GoDaddy',
            'Bluehost' => 'Bluehost',
            'HostGator' => 'HostGator',
            'SiteGround' => 'SiteGround',
            'A2 Hosting' => 'A2 Hosting',
            'DreamHost' => 'DreamHost',
            'Cloudways' => 'Cloudways',
            'Kinsta' => 'Kinsta',
        ],
        '-- ' . __('Popular in Italy', 'whmin') . ' --' => [
            'Aruba.it' => 'Aruba.it',
            'SiteGround.it' => 'SiteGround.it',
            'Netsons' => 'Netsons',
            'VHosting Solution' => 'VHosting Solution',
            'Serverplan' => 'Serverplan',
        ],
        '-- ' . __('Other', 'whmin') . ' --' => [
            'other' => __('Other (Please specify)', 'whmin'),
        ]
    ];
}

/**
 * AJAX handler to add or update an indirect website.
 */
function whmin_ajax_save_indirect_site() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $site_data = isset($_POST['site_data']) ? $_POST['site_data'] : [];
    
    if (empty($site_data['name']) || empty($site_data['url'])) {
        wp_send_json_error(['message' => __('Website Name and URL are required.', 'whmin')], 400);
    }

    // --- LOGIC CHANGE: Determine status based on connection type ---
    $connection_type = sanitize_text_field($site_data['connection']);
    $status = ($connection_type === 'Without API') ? 'activated_manual' : 'not_activated';

    $sanitized_data = [
        'uid'         => isset($site_data['uid']) && !empty($site_data['uid']) ? sanitize_text_field($site_data['uid']) : uniqid('site_'),
        'name'        => sanitize_text_field($site_data['name']),
        'url'         => esc_url_raw($site_data['url']),
        'connection'  => $connection_type,
        'hosting'     => sanitize_text_field($site_data['hosting']),
        'status'      => isset($site_data['status']) ? sanitize_text_field($site_data['status']) : $status, // Use new status logic
    ];

    $sites = get_option('whmin_indirect_sites', []);
    $is_update = false;

    // ... (rest of the function is the same)
    foreach ($sites as $key => $site) {
        if (isset($site['uid']) && $site['uid'] === $sanitized_data['uid']) {
            $sites[$key] = $sanitized_data;
            $is_update = true;
            break;
        }
    }
    if (!$is_update) {
        $sites[] = $sanitized_data;
    }
    update_option('whmin_indirect_sites', $sites);

    wp_send_json_success([
        'message' => __('Website saved successfully.', 'whmin'),
        'site'    => $sanitized_data,
    ]);
}
add_action('wp_ajax_whmin_save_indirect_site', 'whmin_ajax_save_indirect_site');

/**
 * AJAX handler to delete an indirect website.
 */
function whmin_ajax_delete_indirect_site() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $uid = isset($_POST['uid']) ? sanitize_text_field($_POST['uid']) : '';
    if (empty($uid)) {
        wp_send_json_error(['message' => __('Invalid Site ID.', 'whmin')], 400);
    }

    $sites = get_option('whmin_indirect_sites', []);
    $updated_sites = [];

    // Rebuild the array, excluding the one to be deleted
    foreach ($sites as $site) {
        if (!isset($site['uid']) || $site['uid'] !== $uid) {
            $updated_sites[] = $site;
        }
    }
    
    update_option('whmin_indirect_sites', $updated_sites);

    wp_send_json_success(['message' => __('Website removed successfully.', 'whmin')]);
}
add_action('wp_ajax_whmin_delete_indirect_site', 'whmin_ajax_delete_indirect_site');