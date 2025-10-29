<?php
/**
 * Functions for the Personal Branding settings tab.
 */
if (!defined('ABSPATH')) exit;

// Register the settings
add_action('admin_init', 'whmin_register_branding_settings');

function whmin_register_branding_settings() {
    register_setting('whmin_branding_settings', 'whmin_branding_settings', 'whmin_sanitize_branding_settings');
}

/**
 * Helper function to get all branding settings with defaults.
 */
function whmin_get_branding_settings() {
    $defaults = [
        'logo_id' => 0,
        'favicon_id' => 0,
        'footer_note' => sprintf(
            __('&copy; %s. All rights reserved. Powered by Your Company Name.', 'whmin'),
            date('Y')
        ),
        'footer_link' => 'https://www.agenziamagma.it', // New default link
    ];

    $settings = get_option('whmin_branding_settings', []);
    $settings = wp_parse_args($settings, $defaults);
    
    // Dynamically create the footer if the note contains the placeholder
    if (strpos($settings['footer_note'], 'Your Company Name') !== false && !empty($settings['footer_link'])) {
         $linked_name = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url($settings['footer_link']),
            __('Agenzia magma marketing', 'whmin')
        );
        $settings['footer_note'] = str_replace('Your Company Name', $linked_name, $settings['footer_note']);
    }

    return $settings;
}

/**
 * Sanitization callback for the branding settings array.
 */
function whmin_sanitize_branding_settings($input) {
    $sanitized = [];

    $sanitized['logo_id'] = isset($input['logo_id']) ? absint($input['logo_id']) : 0;
    $sanitized['favicon_id'] = isset($input['favicon_id']) ? absint($input['favicon_id']) : 0;
    
    // Sanitize the new footer link field
    $sanitized['footer_link'] = isset($input['footer_link']) ? esc_url_raw($input['footer_link']) : '';
    
    if (isset($input['footer_note'])) {
        $allowed_html = [
            'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
            'br' => [], 'em' => [], 'strong' => [], 'p' => []
        ];
        $sanitized['footer_note'] = wp_kses($input['footer_note'], $allowed_html);
    }

    return $sanitized;
}

/**
 * Hook into wp_head to add the custom favicon if it's set.
 */
function whmin_add_custom_favicon() {
    $settings = get_option('whmin_branding_settings', []); // Get raw settings
    if (!empty($settings['favicon_id'])) {
        $favicon_url = wp_get_attachment_image_url($settings['favicon_id'], 'full');
        if ($favicon_url) {
            echo '<link rel="icon" href="' . esc_url($favicon_url) . '" />';
        }
    }
}