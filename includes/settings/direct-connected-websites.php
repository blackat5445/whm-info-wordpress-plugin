<?php
/**
 * Functions for the Direct Connected Websites settings tab.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves and prepares the list of directly connected websites.
 */
function whmin_get_direct_connected_sites_data() {
    $accounts_response = whmin_get_whm_accounts();

    if (is_wp_error($accounts_response)) {
        return $accounts_response;
    }
    
    if (empty($accounts_response)) {
        return [];
    }

    $custom_names         = get_option('whmin_custom_site_names', []);
    $monitoring_settings  = get_option('whmin_direct_monitoring_settings', []);
    $connect_status_map   = get_option('whmin_direct_connect_status', []);
    $services_data        = get_option('whmin_site_services_data', []); 

    if (!is_array($connect_status_map)) $connect_status_map = [];
    if (!is_array($services_data)) $services_data = [];

    $sites_data = [];
    $id_counter = 1;

    foreach ($accounts_response as $account) {
        $user_key = $account['user'];
        
        $display_name = !empty($custom_names[$user_key]) ? $custom_names[$user_key] : $user_key;
        $monitoring_enabled = isset($monitoring_settings[$user_key]) ? (bool)$monitoring_settings[$user_key] : true;

        $disk_used_raw        = $account['diskused'];
        $disk_usage_formatted = '';
        $disk_used_bytes      = 0;

        if (strpos($disk_used_raw, '/') !== false) {
            $parts       = explode('/', $disk_used_raw);
            $disk_used_mb = $parts[0];
        } else {
            $disk_used_mb = $disk_used_raw;
        }

        if (is_numeric($disk_used_mb)) {
            $disk_used_bytes      = (float)$disk_used_mb * 1024 * 1024;
            $disk_usage_formatted = whmin_format_bytes($disk_used_bytes);
        } else {
            $disk_usage_formatted = esc_html(ucfirst($disk_used_mb));
            $disk_used_bytes      = PHP_INT_MAX;
        }

        if (!$monitoring_enabled) {
            $status = ['text' => __('Monitoring Disabled', 'whmin'), 'class' => 'secondary'];
        } elseif (!empty($account['suspended'])) {
            $status = ['text' => __('Suspended', 'whmin'), 'class' => 'danger'];
        } else {
            $status = ['text' => __('Active', 'whmin'), 'class' => 'success'];
        }

        $conn_row = $connect_status_map[$user_key] ?? null;
        $connection_status = (is_array($conn_row) && ($conn_row['status'] ?? '') === 'activated') ? 'activated' : 'not_activated';

        if (isset($services_data[$user_key])) {
            $site_services = $services_data[$user_key];
        } else {
            $setup_date = date('Y-m-d', $account['unix_startdate']);
            $expiration = date('Y-m-d', strtotime('+1 year', $account['unix_startdate']));
            
            $site_services = [
                'emails' => [
                    'primary' => $account['email'] ?? '', 
                    'secondary' => ''
                ],
                'items' => [
                    [
                        'name' => 'Hosting',
                        'price' => '',
                        'domain_detail' => '',
                        'start_date' => $setup_date,
                        'expiration_date' => $expiration,
                        'unlimited' => false
                    ]
                ]
            ];
        }

        $sites_data[] = [
            'id'                 => $id_counter++,
            'user'               => esc_html($account['user']),
            'name'               => esc_html($display_name),
            'url'                => esc_url('http://' . $account['domain']),
            'setup_date'         => esc_html(date_i18n(get_option('date_format'), $account['unix_startdate'])),
            'setup_timestamp'    => $account['unix_startdate'],
            'disk_used'          => $disk_usage_formatted,
            'disk_used_bytes'    => $disk_used_bytes,
            'status'             => $status,
            'monitoring_enabled' => $monitoring_enabled,
            'connection_status'  => $connection_status,
            'services'           => $site_services
        ];
    }

    return $sites_data;
}

/**
 * AJAX: Update site friendly name
 */
function whmin_ajax_update_site_name() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

    if (empty($user) || empty($new_name)) {
        wp_send_json_error(['message' => __('Invalid data provided.', 'whmin')], 400);
    }
    
    $custom_names = get_option('whmin_custom_site_names', []);
    $custom_names[$user] = $new_name;
    update_option('whmin_custom_site_names', $custom_names);

    wp_send_json_success([
        'message' => __('Website name updated successfully.', 'whmin'),
        'newName' => $new_name
    ]);
}
add_action('wp_ajax_whmin_update_site_name', 'whmin_ajax_update_site_name');

/**
 * AJAX: Toggle monitoring
 */
function whmin_ajax_toggle_direct_monitoring() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')], 403);
    }

    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $enabled_raw = $_POST['enabled'] ?? null;
    $enabled = filter_var($enabled_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if (empty($user) || $enabled === null) {
        wp_send_json_error(['message' => __('Invalid data provided.', 'whmin')], 400);
    }
    
    $monitoring_settings = get_option('whmin_direct_monitoring_settings', []);
    if (!is_array($monitoring_settings)) $monitoring_settings = [];
    $monitoring_settings[$user] = $enabled;

    update_option('whmin_direct_monitoring_settings', $monitoring_settings);

    wp_send_json_success([
        'message' => $enabled ? __('Monitoring enabled.', 'whmin') : __('Monitoring disabled.', 'whmin'),
        'enabled' => $enabled
    ]);
}
add_action('wp_ajax_whmin_toggle_direct_monitoring', 'whmin_ajax_toggle_direct_monitoring');

/**
 * AJAX: Save Site Services
 */
function whmin_ajax_save_site_services() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')]);
    }

    $user = sanitize_text_field($_POST['user']);
    $data = $_POST['data'];

    if (empty($user) || empty($data)) {
        wp_send_json_error(['message' => __('Invalid data.', 'whmin')]);
    }

    $clean_data = [
        'emails' => [
            'primary' => sanitize_email($data['emails']['primary']),
            'secondary' => sanitize_email($data['emails']['secondary']),
        ],
        'items' => []
    ];

    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            $clean_data['items'][] = [
                'name' => sanitize_text_field($item['name']),
                'price' => sanitize_text_field($item['price']), 
                'domain_detail' => isset($item['domain_detail']) ? sanitize_text_field($item['domain_detail']) : '',
                'start_date' => sanitize_text_field($item['start_date']),
                'expiration_date' => sanitize_text_field($item['expiration_date']),
                'unlimited' => filter_var($item['unlimited'], FILTER_VALIDATE_BOOLEAN)
            ];
        }
    }

    $all_services_data = get_option('whmin_site_services_data', []);
    $all_services_data[$user] = $clean_data;
    update_option('whmin_site_services_data', $all_services_data);
    
    wp_send_json_success(['message' => __('Services saved successfully.', 'whmin')]);
}
add_action('wp_ajax_whmin_save_site_services', 'whmin_ajax_save_site_services');

/**
 * AJAX: Send Service Expiration Email
 */
function whmin_ajax_send_service_email() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')]);
    }

    $user = sanitize_text_field($_POST['user']);
    $selected_services_indices = isset($_POST['services']) ? $_POST['services'] : [];

    $all_data = get_option('whmin_site_services_data', []);
    if (!isset($all_data[$user])) {
        wp_send_json_error(['message' => __('No service data found. Please save services first.', 'whmin')]);
    }

    $site_data = $all_data[$user];
    $recipient_email = $site_data['emails']['primary'];
    $cc_email = $site_data['emails']['secondary'];

    if (!is_email($recipient_email)) {
        wp_send_json_error(['message' => __('Primary email address is missing.', 'whmin')]);
    }

    // Check for other expiring/expired services
    $all_services_to_notify = whmin_get_services_needing_notification($user, $site_data['items'], $selected_services_indices);

    if (empty($all_services_to_notify['expired']) && empty($all_services_to_notify['soon'])) {
        wp_send_json_error(['message' => __('No services selected.', 'whmin')]);
    }

    $branding = whmin_get_branding_settings();
    $texts = whmin_get_notification_texts();

    $display_from_name = get_bloginfo('name');
    $display_from_link = !empty($branding['footer_link']) ? $branding['footer_link'] : home_url();

    $accounts = whmin_get_whm_accounts(); 
    $client_site_url = '';
    if(!is_wp_error($accounts)) {
        foreach($accounts as $acc) {
            if($acc['user'] === $user) {
                $client_site_url = 'http://' . $acc['domain'];
                break;
            }
        }
    }

    $custom_names = get_option('whmin_custom_site_names', []);
    $site_friendly_name = !empty($custom_names[$user]) ? $custom_names[$user] : $user;

    $subject = $texts['email_subject']; 
    $subject = str_replace('%site%', $site_friendly_name, $subject);

    ob_start();
    
    $recipient_name = 'Customer'; 
    $site_name      = $site_friendly_name;
    $site_url       = $client_site_url;
    $list_expired   = $all_services_to_notify['expired'];
    $list_soon      = $all_services_to_notify['soon'];
    $text_config    = $texts;
    $brand_link     = $display_from_link;
    
    include WHMIN_PLUGIN_DIR . 'templates/email-service-expiration.php'; 
    $message = ob_get_clean();

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    if ($cc_email && is_email($cc_email)) {
        $headers[] = 'Cc: ' . $cc_email;
    }

    $sent = wp_mail($recipient_email, $subject, $message, $headers);

    if ($sent) {
        // Store that we sent notification for these services
        whmin_mark_services_notified($user, array_merge($all_services_to_notify['expired'], $all_services_to_notify['soon']));
        
        wp_send_json_success(['message' => __('Email sent successfully.', 'whmin')]);
    } else {
        wp_send_json_error(['message' => __('Failed to send email.', 'whmin')]);
    }
}
add_action('wp_ajax_whmin_send_service_email', 'whmin_ajax_send_service_email');

/**
 * NEW: AJAX handler to renew services and send email notification
 */
function whmin_ajax_renew_site_services() {
    check_ajax_referer('whmin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'whmin')]);
    }

    $user = sanitize_text_field($_POST['user']);
    $renewed_services_data = isset($_POST['renewed_services']) ? $_POST['renewed_services'] : [];

    if (empty($user) || empty($renewed_services_data)) {
        wp_send_json_error(['message' => __('Invalid data.', 'whmin')]);
    }

    $all_data = get_option('whmin_site_services_data', []);
    if (!isset($all_data[$user])) {
        wp_send_json_error(['message' => __('Site not found.', 'whmin')]);
    }

    $site_data = $all_data[$user];
    $recipient_email = $site_data['emails']['primary'];
    $cc_email = $site_data['emails']['secondary'];

    if (!is_email($recipient_email)) {
        wp_send_json_error(['message' => __('Primary email address is missing.', 'whmin')]);
    }

    // Update expiration dates for renewed services
    $renewed_services = [];
    foreach ($renewed_services_data as $renewal) {
        $index = intval($renewal['index']);
        $new_expiration = sanitize_text_field($renewal['new_expiration']);
        
        if (isset($site_data['items'][$index])) {
            $site_data['items'][$index]['expiration_date'] = $new_expiration;
            $renewed_services[] = $site_data['items'][$index];
        }
    }

    // Save updated data
    $all_data[$user] = $site_data;
    update_option('whmin_site_services_data', $all_data);

    // Send renewal confirmation email
    $branding = whmin_get_branding_settings();
    $texts = whmin_get_notification_texts();

    $display_from_link = !empty($branding['footer_link']) ? $branding['footer_link'] : home_url();

    $accounts = whmin_get_whm_accounts(); 
    $client_site_url = '';
    if(!is_wp_error($accounts)) {
        foreach($accounts as $acc) {
            if($acc['user'] === $user) {
                $client_site_url = 'http://' . $acc['domain'];
                break;
            }
        }
    }

    $custom_names = get_option('whmin_custom_site_names', []);
    $site_friendly_name = !empty($custom_names[$user]) ? $custom_names[$user] : $user;

    $subject = $texts['renewal_subject'];

    ob_start();
    
    $recipient_name = 'Customer';
    $site_name = $site_friendly_name;
    $site_url = $client_site_url;
    $renewed_items = $renewed_services;
    $text_config = $texts;
    $brand_link = $display_from_link;
    
    include WHMIN_PLUGIN_DIR . 'templates/email-service-renewed.php';
    $message = ob_get_clean();

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    if ($cc_email && is_email($cc_email)) {
        $headers[] = 'Cc: ' . $cc_email;
    }

    $sent = wp_mail($recipient_email, $subject, $message, $headers);

    if ($sent) {
        wp_send_json_success(['message' => __('Services renewed and email sent successfully.', 'whmin')]);
    } else {
        wp_send_json_success(['message' => __('Services renewed but failed to send email.', 'whmin')]);
    }
}
add_action('wp_ajax_whmin_renew_site_services', 'whmin_ajax_renew_site_services');

/**
 * Helper: Get all services needing notification (expired or expiring soon)
 */
function whmin_get_services_needing_notification($user, $all_services, $selected_indices = []) {
    $expired_services = [];
    $soon_services = [];
    $now = time();
    
    // Get days before threshold from settings
    $auto_settings = whmin_get_auto_expiration_settings();
    $days_threshold = $auto_settings['days_before'];
    $threshold_time = strtotime("+{$days_threshold} days", $now);

    foreach ($all_services as $index => $item) {
        // Check if this service should be included
        $should_include = empty($selected_indices) || in_array($index, $selected_indices);
        
        if ($should_include && !$item['unlimited'] && !empty($item['expiration_date'])) {
            $exp_ts = strtotime($item['expiration_date']);
            
            if ($exp_ts < $now) {
                // Already expired
                $expired_services[] = $item;
            } elseif ($exp_ts < $threshold_time) {
                // Expiring soon
                $soon_services[] = $item;
            }
        }
    }

    return [
        'expired' => $expired_services,
        'soon' => $soon_services
    ];
}

/**
 * Helper: Mark services as notified
 */
function whmin_mark_services_notified($user, $services) {
    $notified_services = get_option('whmin_notified_services', []);
    
    if (!isset($notified_services[$user])) {
        $notified_services[$user] = [];
    }
    
    foreach ($services as $service) {
        $service_key = md5(serialize($service));
        $notified_services[$user][$service_key] = time();
    }
    
    update_option('whmin_notified_services', $notified_services);
}

/**
 * Cron job to check and send automatic expiration emails
 */
function whmin_check_automatic_expiration_emails() {
    $auto_settings = whmin_get_auto_expiration_settings();
    
    if (!$auto_settings['enable_auto_emails']) {
        return; // Feature disabled
    }
    
    $enabled_sites = $auto_settings['enabled_sites'];
    if (empty($enabled_sites)) {
        return; // No sites enabled
    }
    
    $all_services_data = get_option('whmin_site_services_data', []);
    
    foreach ($enabled_sites as $user) {
        if (!isset($all_services_data[$user])) {
            continue;
        }
        
        $site_data = $all_services_data[$user];
        $services_to_notify = whmin_get_services_needing_notification($user, $site_data['items']);
        
        if (empty($services_to_notify['expired']) && empty($services_to_notify['soon'])) {
            continue; // Nothing to notify
        }
        
        // Check if we already notified recently
        $notified_services = get_option('whmin_notified_services', []);
        $should_send = false;
        
        foreach (array_merge($services_to_notify['expired'], $services_to_notify['soon']) as $service) {
            $service_key = md5(serialize($service));
            if (!isset($notified_services[$user][$service_key]) || 
                (time() - $notified_services[$user][$service_key]) > (24 * HOUR_IN_SECONDS)) {
                $should_send = true;
                break;
            }
        }
        
        if (!$should_send) {
            continue; // Already notified recently
        }
        
        // Send email
        $recipient_email = $site_data['emails']['primary'];
        $cc_email = $site_data['emails']['secondary'];
        
        if (!is_email($recipient_email)) {
            continue;
        }
        
        $branding = whmin_get_branding_settings();
        $texts = whmin_get_notification_texts();
        
        $display_from_link = !empty($branding['footer_link']) ? $branding['footer_link'] : home_url();
        
        $accounts = whmin_get_whm_accounts();
        $client_site_url = '';
        if (!is_wp_error($accounts)) {
            foreach ($accounts as $acc) {
                if ($acc['user'] === $user) {
                    $client_site_url = 'http://' . $acc['domain'];
                    break;
                }
            }
        }
        
        $custom_names = get_option('whmin_custom_site_names', []);
        $site_friendly_name = !empty($custom_names[$user]) ? $custom_names[$user] : $user;
        
        $subject = $texts['email_subject'];
        $subject = str_replace('%site%', $site_friendly_name, $subject);
        
        ob_start();
        
        $recipient_name = 'Customer';
        $site_name = $site_friendly_name;
        $site_url = $client_site_url;
        $list_expired = $services_to_notify['expired'];
        $list_soon = $services_to_notify['soon'];
        $text_config = $texts;
        $brand_link = $display_from_link;
        
        include WHMIN_PLUGIN_DIR . 'templates/email-service-expiration.php';
        $message = ob_get_clean();
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        if ($cc_email && is_email($cc_email)) {
            $headers[] = 'Cc: ' . $cc_email;
        }
        
        $sent = wp_mail($recipient_email, $subject, $message, $headers);
        
        if ($sent) {
            whmin_mark_services_notified($user, array_merge($services_to_notify['expired'], $services_to_notify['soon']));
        }
    }
}

// Schedule cron job if not already scheduled
if (!wp_next_scheduled('whmin_automatic_expiration_check')) {
    wp_schedule_event(time(), 'daily', 'whmin_automatic_expiration_check');
}
add_action('whmin_automatic_expiration_check', 'whmin_check_automatic_expiration_emails');

function whmin_format_bytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= (pow(1024, $pow) > 0 ? pow(1024, $pow) : 1);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}