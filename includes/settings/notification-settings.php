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
 * Register notification settings (global behaviour + custom texts).
 */
function whmin_register_notification_settings() {
    // Existing: Interval Settings
    register_setting(
        'whmin_notification_settings',
        'whmin_notification_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'whmin_sanitize_notification_settings',
            'default'           => array(),
        )
    );

    // Custom Email Text Settings
    register_setting(
        'whmin_notification_texts',
        'whmin_notification_texts',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'whmin_sanitize_notification_texts',
            'default'           => array(),
        )
    );

    // NEW: Automatic Expiration Email Settings
    register_setting(
        'whmin_auto_expiration_settings',
        'whmin_auto_expiration_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'whmin_sanitize_auto_expiration_settings',
            'default'           => array(),
        )
    );
}
add_action('admin_init', 'whmin_register_notification_settings');

/**
 * Sanitize notification settings (Interval).
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
 * Sanitize Notification Texts.
 */
function whmin_sanitize_notification_texts($input) {
    $sanitized = [];
    $fields = [
        // Expiration Email
        'email_subject', 
        'greeting_text',
        'header_expired', 
        'body_expired', 
        'header_soon', 
        'body_soon', 
        'footer_text',
        'table_header_service',
        'table_header_price',
        'table_header_expiration',
        
        // Renewal Email
        'renewal_subject',
        'renewal_greeting',
        'renewal_header',
        'renewal_body',
        'renewal_footer',
        'renewal_table_header_service',
        'renewal_table_header_price',
        'renewal_table_header_new_expiration',
        
        // News Email
        'news_subject',
        'news_greeting',
        'news_header',
        'news_body',
        'news_footer'
    ];

    foreach ($fields as $field) {
        $sanitized[$field] = isset($input[$field]) ? sanitize_textarea_field($input[$field]) : '';
    }
    return $sanitized;
}

/**
 * NEW: Sanitize automatic expiration settings.
 */
function whmin_sanitize_auto_expiration_settings($input) {
    $sanitized = [];
    
    $sanitized['enable_auto_emails'] = !empty($input['enable_auto_emails']) ? 1 : 0;
    $sanitized['days_before'] = isset($input['days_before']) ? absint($input['days_before']) : 21;
    $sanitized['enabled_sites'] = isset($input['enabled_sites']) && is_array($input['enabled_sites']) 
        ? array_map('sanitize_text_field', $input['enabled_sites']) 
        : [];
    
    return $sanitized;
}

/**
 * Get global notification settings (with defaults).
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
 * Get Custom Email Texts (with English Defaults).
 */
function whmin_get_notification_texts() {
    $defaults = [
        // Expiration Email
        'email_subject'   => 'Service Expiration Notice',
        'greeting_text'   => 'Dear Client,',
        'header_expired'  => 'Services Already Expired',
        'body_expired'    => "The following services have expired.\nImmediate action is required to restore full functionality.",
        'header_soon'     => 'Services Expiring Soon',
        'body_soon'       => "The following services are expiring soon.\nPlease arrange for renewal to avoid interruption.",
        'footer_text'     => 'This is an automated notification. Please contact us to renew your services.',
        'table_header_service' => 'Service',
        'table_header_price' => 'Price',
        'table_header_expiration' => 'Expiration',
        
        // Renewal Email
        'renewal_subject' => 'Service Renewal Confirmation',
        'renewal_greeting' => 'Dear Client,',
        'renewal_header' => 'Services Successfully Renewed',
        'renewal_body' => "We are pleased to confirm that the following services have been renewed.\nYour services will continue without interruption.",
        'renewal_footer' => 'Thank you for your continued business.',
        'renewal_table_header_service' => 'Service',
        'renewal_table_header_price' => 'Price',
        'renewal_table_header_new_expiration' => 'New Expiration Date',
        
        // News Email
        'news_subject' => 'New Announcement: %title%',
        'news_greeting' => 'Dear Client,',
        'news_header' => 'Latest News & Updates',
        'news_body' => 'We have published a new announcement that may be of interest to you:',
        'news_footer' => 'Stay tuned for more updates.'
    ];
    
    $stored = get_option('whmin_notification_texts', []);
    if (!is_array($stored)) {
        $stored = [];
    }
    
    return wp_parse_args($stored, $defaults);
}

/**
 * NEW: Get automatic expiration settings.
 */
function whmin_get_auto_expiration_settings() {
    $defaults = [
        'enable_auto_emails' => 0,
        'days_before' => 21,
        'enabled_sites' => []
    ];
    
    $stored = get_option('whmin_auto_expiration_settings', []);
    if (!is_array($stored)) {
        $stored = [];
    }
    
    return wp_parse_args($stored, $defaults);
}

/**
 * Helper: get current notification interval in seconds.
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
            if (function_exists('whmin_get_status_cron_schedule') && function_exists('whmin_get_interval_minutes_for_schedule')) {
                $schedule_slug = whmin_get_status_cron_schedule();
                $mins          = whmin_get_interval_minutes_for_schedule($schedule_slug);
                return (int) max(1, $mins) * MINUTE_IN_SECONDS;
            }
            return 15 * MINUTE_IN_SECONDS;
    }
}

// -----------------------------------------------------------------------------
// Recipients storage + AJAX
// -----------------------------------------------------------------------------

add_action('wp_ajax_whmin_save_recipient', 'whmin_ajax_save_recipient');
add_action('wp_ajax_whmin_delete_recipient', 'whmin_ajax_delete_recipient');
add_action('wp_ajax_whmin_send_test_notification', 'whmin_ajax_send_test_notification');

/**
 * Raw recipients as stored in the option.
 */
function whmin_get_notification_recipients_raw() {
    $recipients = get_option('whmin_notification_recipients', array());
    if (!is_array($recipients)) {
        $recipients = array();
    }

    foreach ($recipients as &$recipient) {
        if (empty($recipient['uid'])) {
            $recipient['uid'] = uniqid('recipient_');
        }
        $recipient['notify_email'] = isset($recipient['notify_email']) ? (bool) $recipient['notify_email'] : true;
        $recipient['notify_telegram'] = false;
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
 * Retrieves the list of notification recipients (for table).
 */
function whmin_get_notification_recipients() {
    $recipients = whmin_get_notification_recipients_raw();
    $data       = array();
    $id_counter = 1;

    foreach ($recipients as $recipient) {
        $recipient['id'] = $id_counter++;
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
    
    if (empty($data['name']) || empty($data['email']) || !is_email($data['email'])) {
        wp_send_json_error(['message' => __('A valid Name and Email are required.', 'whmin')], 400);
    }

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

    foreach ($recipients as $key => $recipient) {
        if (isset($recipient['uid']) && $recipient['uid'] === $sanitized_data['uid']) {
            $recipients[$key] = $sanitized_data;
            $is_update        = true;
            break;
        }
    }

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

    foreach ($recipients as $recipient) {
        $name            = isset($recipient['name']) ? $recipient['name'] : '';
        $email           = isset($recipient['email']) ? $recipient['email'] : '';
        $notify_email    = isset($recipient['notify_email']) ? (bool) $recipient['notify_email'] : true;

        if (!$notify_email) {
            continue;
        }

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
    }

    wp_send_json_success([
        'message' => __('Test notification sent to all active recipients.', 'whmin'),
    ]);
}