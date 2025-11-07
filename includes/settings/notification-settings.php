<?php
/**
 * Functions for the Notification Settings tab.
 *
 * @package WHM_Info/Includes/Settings
 */
if (!defined('ABSPATH')) exit;

// -----------------------------------------------------------------------------
// Option registration + helpers for global notification settings
// -----------------------------------------------------------------------------

/**
 * Register notification settings (global behaviour).
 */
function whmin_register_notification_settings() {
    register_setting(
        'whmin_notification_settings',
        'whmin_notification_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'whmin_sanitize_notification_settings',
            'default'           => array(),
        )
    );
}
add_action('admin_init', 'whmin_register_notification_settings');

/**
 * Sanitize notification settings.
 *
 * @param array $settings
 * @return array
 */
function whmin_sanitize_notification_settings($settings) {
    $settings = is_array($settings) ? $settings : array();

    $allowed_intervals = array(
        'server_refresh',
        '30min',
        '1h',
        '3h',
        '12h',
        '24h',
    );

    $interval = isset($settings['interval']) ? sanitize_text_field($settings['interval']) : 'server_refresh';
    if (!in_array($interval, $allowed_intervals, true)) {
        $interval = 'server_refresh';
    }

    return array(
        'interval' => $interval,
    );
}

/**
 * Get global notification settings (with defaults).
 *
 * @return array
 */
function whmin_get_notification_settings() {
    $defaults = array(
        'interval' => 'server_refresh',
    );

    $stored = get_option('whmin_notification_settings', array());
    if (!is_array($stored)) {
        $stored = array();
    }

    return wp_parse_args($stored, $defaults);
}

/**
 * Helper: get current notification interval in seconds.
 * - server_refresh => uses current status cron interval
 * - others => fixed intervals
 *
 * @return int
 */
function whmin_get_notification_interval_seconds() {
    $settings = whmin_get_notification_settings();
    $slug     = isset($settings['interval']) ? $settings['interval'] : 'server_refresh';

    switch ($slug) {
        case '30min':
            return 30 * MINUTE_IN_SECONDS;
        case '1h':
            return HOUR_IN_SECONDS;
        case '3h':
            return 3 * HOUR_IN_SECONDS;
        case '12h':
            return 12 * HOUR_IN_SECONDS;
        case '24h':
            return 24 * HOUR_IN_SECONDS;
        case 'server_refresh':
        default:
            // Tie to current status cron schedule if available
            if (function_exists('whmin_get_status_cron_schedule') && function_exists('whmin_get_interval_minutes_for_schedule')) {
                $schedule_slug = whmin_get_status_cron_schedule();
                $mins          = whmin_get_interval_minutes_for_schedule($schedule_slug);
                return (int) max(1, $mins) * MINUTE_IN_SECONDS;
            }
            // Fallback
            return 15 * MINUTE_IN_SECONDS;
    }
}

// -----------------------------------------------------------------------------
// Recipients storage + AJAX
// -----------------------------------------------------------------------------

// Hook all AJAX actions
add_action('wp_ajax_whmin_save_recipient', 'whmin_ajax_save_recipient');
add_action('wp_ajax_whmin_delete_recipient', 'whmin_ajax_delete_recipient');
add_action('wp_ajax_whmin_send_test_notification', 'whmin_ajax_send_test_notification');

/**
 * Raw recipients as stored in the option (no injected numeric id),
 * with normalised flags for backward compatibility.
 *
 * @return array
 */
function whmin_get_notification_recipients_raw() {
    $recipients = get_option('whmin_notification_recipients', array());
    if (!is_array($recipients)) {
        $recipients = array();
    }

    foreach ($recipients as &$recipient) {
        // Ensure UID exists (for older data, just to be safe)
        if (empty($recipient['uid'])) {
            $recipient['uid'] = uniqid('recipient_');
        }

        // Normalise flags
        $recipient['notify_email'] = isset($recipient['notify_email']) ? (bool) $recipient['notify_email'] : true;

        // Telegram support is "coming soon": always disable it at runtime.
        $recipient['notify_telegram'] = false;

        // Ensure optional fields exist
        if (!isset($recipient['telegram_chat'])) {
            $recipient['telegram_chat'] = '';
        }
        if (!isset($recipient['telephone'])) {
            $recipient['telephone'] = '';
        }
    }
    unset($recipient);

    return $recipients;
}

/**
 * Retrieves the list of notification recipients from the database (for table).
 *
 * @return array An array of recipient data.
 */
function whmin_get_notification_recipients() {
    $recipients = whmin_get_notification_recipients_raw();
    $data       = array();
    $id_counter = 1;

    foreach ($recipients as $recipient) {
        $recipient['id'] = $id_counter++;

        // Ensure flags exist & are boolean-like
        $recipient['notify_email']    = isset($recipient['notify_email']) ? (bool) $recipient['notify_email'] : true;
        $recipient['notify_telegram'] = isset($recipient['notify_telegram']) ? (bool) $recipient['notify_telegram'] : false;
        $recipient['telegram_chat']   = isset($recipient['telegram_chat']) ? $recipient['telegram_chat'] : '';

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

    $data = isset($_POST['recipient_data']) ? (array) $_POST['recipient_data'] : array();
    
    // Server-side validation
    if (empty($data['name']) || empty($data['email']) || !is_email($data['email'])) {
        wp_send_json_error(['message' => __('A valid Name and Email are required.', 'whmin')], 400);
    }

    // Sanitize all fields
    $sanitized_data = [
        'uid'            => isset($data['uid']) && !empty($data['uid']) ? sanitize_text_field($data['uid']) : uniqid('recipient_'),
        'name'           => sanitize_text_field($data['name']),
        'email'          => sanitize_email($data['email']),
        'telephone'      => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : '',
        'notify_email'   => !empty($data['notify_email']) ? 1 : 0,
        'notify_telegram'=> 0,
        'telegram_chat'  => '',
    ];

    $recipients = get_option('whmin_notification_recipients', []);
    if (!is_array($recipients)) {
        $recipients = [];
    }

    $is_update = false;

    // Find and update if UID exists
    foreach ($recipients as $key => $recipient) {
        if (isset($recipient['uid']) && $recipient['uid'] === $sanitized_data['uid']) {
            $recipients[$key] = $sanitized_data;
            $is_update        = true;
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

    $recipients         = get_option('whmin_notification_recipients', []);
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

/**
 * AJAX: send a test notification to all active recipients.
 */
function whmin_ajax_send_test_notification() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    // Make sure helper functions exist
    if (!function_exists('whmin_send_email_notification')) {
        wp_send_json_error(['message' => __('Email helpers are not available.', 'whmin')], 500);
    }

    $recipients = whmin_get_notification_recipients_raw();

    if (empty($recipients)) {
        wp_send_json_error(['message' => __('No recipients have been configured yet.', 'whmin')], 400);
    }

    $site_name = get_bloginfo('name');
    $subject   = sprintf('[WHM Info] %s', __('Test notification', 'whmin'));
    $timestamp = wp_date(
        get_option('date_format') . ' ' . get_option('time_format'),
        current_time('timestamp')
    );

    // Build a simple HTML body; per-recipient we only personalise the name
    foreach ($recipients as $recipient) {
        $name            = isset($recipient['name']) ? $recipient['name'] : '';
        $email           = isset($recipient['email']) ? $recipient['email'] : '';
        $notify_email    = isset($recipient['notify_email']) ? (bool) $recipient['notify_email'] : true;
        $notify_telegram = isset($recipient['notify_telegram']) ? (bool) $recipient['notify_telegram'] : false;
        $telegram_chat   = isset($recipient['telegram_chat']) ? $recipient['telegram_chat'] : '';

        // Skip recipients with all channels disabled
        if (!$notify_email) {
            continue;
        }

        // HTML email content
        $greeting = $name
            ? sprintf(esc_html__('Hi %s,', 'whmin'), esc_html($name))
            : esc_html__('Hi,', 'whmin');

        $message_html  = '<!DOCTYPE html><html><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;background-color:#f5f5f5;padding:20px;">';
        $message_html .= '<div style="max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:8px;padding:20px;">';
        $message_html .= '<h2 style="margin-top:0;color:#075b63;">' . esc_html($site_name) . ' &mdash; ' . esc_html__('Test Notification', 'whmin') . '</h2>';
        $message_html .= '<p>' . $greeting . '</p>';
        $message_html .= '<p>' . esc_html__('This is a test notification from the WHM Info plugin.', 'whmin') . '</p>';
        $message_html .= '<p>' . esc_html__('If you can read this message, your notification configuration is working correctly.', 'whmin') . '</p>';
        $message_html .= '<p style="font-size:12px;color:#868e96;margin-top:16px;">' .
            sprintf(esc_html__('Timestamp: %s', 'whmin'), esc_html($timestamp)) .
            '</p>';
        $message_html .= '<p style="font-size:12px;color:#adb5bd;margin-top:8px;">' .
            esc_html__('This message was generated automatically by the WHM Info plugin (test mode).', 'whmin') .
            '</p>';
        $message_html .= '</div></body></html>';

        if ($notify_email && is_email($email)) {
            whmin_send_email_notification($email, $subject, $message_html);
        }

        // if ($notify_telegram && !empty($telegram_chat) && function_exists('whmin_send_telegram_notification')) {
        //    $telegram_text  = sprintf(
                /* translators: 1: site name, 2: timestamp */
        //        __('Test notification from %1$s at %2$s. If you see this, Telegram alerts are working.', 'whmin'),
        //        $site_name,
        //        $timestamp
        //    );
        //    whmin_send_telegram_notification($telegram_chat, $telegram_text);
        //}
    }

    wp_send_json_success([
        'message' => __('Test notification sent to all active recipients.', 'whmin'),
    ]);
}