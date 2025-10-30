<?php
/**
 * Functions for the Notification Settings tab.
 *
 * @package WHM_Info/Includes/Settings
 */
if (!defined('ABSPATH')) exit;

// Hook all AJAX actions
add_action('wp_ajax_whmin_save_recipient', 'whmin_ajax_save_recipient');
add_action('wp_ajax_whmin_delete_recipient', 'whmin_ajax_delete_recipient');

/**
 * Retrieves the list of notification recipients from the database.
 *
 * @return array An array of recipient data.
 */
function whmin_get_notification_recipients() {
    $recipients = get_option('whmin_notification_recipients', []);
    $data = [];
    $id_counter = 1;
    foreach ($recipients as $recipient) {
        $recipient['id'] = $id_counter++;
        $data[] = $recipient;
    }
    return $data;
}

/**
 * AJAX handler to add or update a notification recipient.
 */
function whmin_ajax_save_recipient() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $data = isset($_POST['recipient_data']) ? $_POST['recipient_data'] : [];
    
    // Server-side validation
    if (empty($data['name']) || empty($data['email']) || !is_email($data['email'])) {
        wp_send_json_error(['message' => __('A valid Name and Email are required.', 'whmin')], 400);
    }

    // Sanitize all fields
    $sanitized_data = [
        'uid'       => isset($data['uid']) && !empty($data['uid']) ? sanitize_text_field($data['uid']) : uniqid('recipient_'),
        'name'      => sanitize_text_field($data['name']),
        'email'     => sanitize_email($data['email']),
        'telephone' => sanitize_text_field($data['telephone']),
    ];

    $recipients = get_option('whmin_notification_recipients', []);
    $is_update = false;

    // Find and update if UID exists
    foreach ($recipients as $key => $recipient) {
        if (isset($recipient['uid']) && $recipient['uid'] === $sanitized_data['uid']) {
            $recipients[$key] = $sanitized_data;
            $is_update = true;
            break;
        }
    }

    // Add if it's a new recipient
    if (!$is_update) {
        $recipients[] = $sanitized_data;
    }

    update_option('whmin_notification_recipients', $recipients);

    wp_send_json_success(['message' => __('Recipient saved successfully.', 'whmin')]);
}

/**
 * AJAX handler to delete a notification recipient.
 */
function whmin_ajax_delete_recipient() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $uid = isset($_POST['uid']) ? sanitize_text_field($_POST['uid']) : '';
    if (empty($uid)) {
        wp_send_json_error(['message' => __('Invalid Recipient ID.', 'whmin')], 400);
    }

    $recipients = get_option('whmin_notification_recipients', []);
    $updated_recipients = [];

    // Rebuild the array, excluding the one to be deleted
    foreach ($recipients as $recipient) {
        if (!isset($recipient['uid']) || $recipient['uid'] !== $uid) {
            $updated_recipients[] = $recipient;
        }
    }
    
    update_option('whmin_notification_recipients', $updated_recipients);

    wp_send_json_success(['message' => __('Recipient removed successfully.', 'whmin')]);
}