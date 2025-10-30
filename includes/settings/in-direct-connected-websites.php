<?php
/**
 * Functions for the In-direct Connected Websites settings tab.
 * ADDED: Include/Exclude from monitoring functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves the list of indirect sites from the database.
 * NOW INCLUDES: monitoring_enabled flag
 */
function whmin_get_indirect_sites_data() {
    $sites = get_option('whmin_indirect_sites', []);
    $sites_data = [];
    $id_counter = 1;
    foreach ($sites as $site) {
        $site['id'] = $id_counter++;
        // Ensure monitoring_enabled exists (default: true)
        if (!isset($site['monitoring_enabled'])) {
            $site['monitoring_enabled'] = true;
        }
        $sites_data[] = $site;
    }
    return $sites_data;
}

/**
 * Returns a predefined list of hosting providers.
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

    $connection_type = sanitize_text_field($site_data['connection']);
    $status = ($connection_type === 'Without API') ? 'activated_manual' : 'not_activated';

    $sanitized_data = [
        'uid'         => isset($site_data['uid']) && !empty($site_data['uid']) ? sanitize_text_field($site_data['uid']) : uniqid('site_'),
        'name'        => sanitize_text_field($site_data['name']),
        'url'         => esc_url_raw($site_data['url']),
        'connection'  => $connection_type,
        'hosting'     => sanitize_text_field($site_data['hosting']),
        'status'      => isset($site_data['status']) ? sanitize_text_field($site_data['status']) : $status,
        'monitoring_enabled' => isset($site_data['monitoring_enabled']) ? (bool)$site_data['monitoring_enabled'] : true,
    ];

    $sites = get_option('whmin_indirect_sites', []);
    $is_update = false;

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
 * NEW: AJAX handler to toggle monitoring for an indirect site
 */
function whmin_ajax_toggle_indirect_monitoring() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $uid = isset($_POST['uid']) ? sanitize_text_field($_POST['uid']) : '';
    $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : true;

    if (empty($uid)) {
        wp_send_json_error(['message' => __('Invalid Site ID.', 'whmin')], 400);
    }

    $sites = get_option('whmin_indirect_sites', []);
    $found = false;

    foreach ($sites as $key => $site) {
        if (isset($site['uid']) && $site['uid'] === $uid) {
            $sites[$key]['monitoring_enabled'] = $enabled;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        wp_send_json_error(['message' => __('Site not found.', 'whmin')], 404);
    }

    update_option('whmin_indirect_sites', $sites);

    $message = $enabled 
        ? __('Monitoring enabled successfully.', 'whmin')
        : __('Monitoring disabled successfully.', 'whmin');

    wp_send_json_success([
        'message' => $message,
        'enabled' => $enabled
    ]);
}
add_action('wp_ajax_whmin_toggle_indirect_monitoring', 'whmin_ajax_toggle_indirect_monitoring');

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

    foreach ($sites as $site) {
        if (!isset($site['uid']) || $site['uid'] !== $uid) {
            $updated_sites[] = $site;
        }
    }
    
    update_option('whmin_indirect_sites', $updated_sites);

    wp_send_json_success(['message' => __('Website removed successfully.', 'whmin')]);
}
add_action('wp_ajax_whmin_delete_indirect_site', 'whmin_ajax_delete_indirect_site');