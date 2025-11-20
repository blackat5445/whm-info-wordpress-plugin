<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns all impact options (including "normal").
 */
function whmin_get_news_impact_options() {
    return array(
        'normal' => array(
            'label' => __('Normal post (not shown on status page)', 'whmin'),
            'color' => '',
        ),
        'no_impact' => array(
            'label' => __('No impact – informational', 'whmin'),
            'color' => '#6B7280',
        ),
        'low_impact' => array(
            'label' => __('Low impact', 'whmin'),
            'color' => '#16A34A',
        ),
        'medium_impact' => array(
            'label' => __('Medium impact', 'whmin'),
            'color' => '#F97316',
        ),
        'high_impact' => array(
            'label' => __('High impact', 'whmin'),
            'color' => '#DC2626',
        ),
    );
}

/**
 * Register meta box on post edit screen.
 */
function whmin_register_maintenance_news_meta_box() {
    add_meta_box(
        'whmin_maintenance_news',
        __('Status Page / Maintenance & News', 'whmin'),
        'whmin_render_maintenance_news_meta_box',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'whmin_register_maintenance_news_meta_box');

/**
 * Render the meta box dropdown.
 */
function whmin_render_maintenance_news_meta_box($post) {
    wp_nonce_field('whmin_maintenance_news_meta_box', 'whmin_maintenance_news_nonce');

    $current = get_post_meta($post->ID, '_whmin_news_impact', true);
    if ($current === '') {
        $current = 'normal';
    }

    $send_email = get_post_meta($post->ID, '_whmin_send_news_email', true);

    $options = whmin_get_news_impact_options();
    ?>
    <p><?php esc_html_e('How should this post appear on your WHM Info status page?', 'whmin'); ?></p>
    <p>
        <label for="whmin_news_impact" class="screen-reader-text">
            <?php esc_html_e('Impact level', 'whmin'); ?>
        </label>
        <select name="whmin_news_impact" id="whmin_news_impact" class="widefat">
            <?php foreach ($options as $value => $opt): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                    <?php echo esc_html($opt['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        <?php esc_html_e('Select "Normal post" to hide it from the Maintenance & News section.', 'whmin'); ?>
    </p>
    
    <hr style="margin: 15px 0;">
    
    <p>
        <label for="whmin_send_news_email">
            <input type="checkbox" name="whmin_send_news_email" id="whmin_send_news_email" value="1" <?php checked($send_email, '1'); ?>>
            <?php esc_html_e('Send email notification to site owners', 'whmin'); ?>
        </label>
    </p>
    <p class="description">
        <?php esc_html_e('When published, this post will be emailed to all enabled site owners in Direct Connected Websites.', 'whmin'); ?>
    </p>
    <?php
}

/**
 * Save meta box value.
 */
function whmin_save_maintenance_news_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['whmin_maintenance_news_nonce']) ||
        !wp_verify_nonce($_POST['whmin_maintenance_news_nonce'], 'whmin_maintenance_news_meta_box')) {
        return;
    }
    if (isset($_POST['post_type']) && $_POST['post_type'] === 'post') {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (!isset($_POST['whmin_news_impact'])) {
        return;
    }

    $impact = sanitize_text_field($_POST['whmin_news_impact']);
    $options = whmin_get_news_impact_options();

    if (!array_key_exists($impact, $options)) {
        $impact = 'normal';
    }

    if ($impact === 'normal') {
        delete_post_meta($post_id, '_whmin_news_impact');
    } else {
        update_post_meta($post_id, '_whmin_news_impact', $impact);
    }

    // Save email notification checkbox
    $send_email = isset($_POST['whmin_send_news_email']) ? '1' : '0';
    update_post_meta($post_id, '_whmin_send_news_email', $send_email);
}
add_action('save_post', 'whmin_save_maintenance_news_meta_box');

/**
 * Hook into post publish to send news email notifications
 */
function whmin_send_news_email_on_publish($post_id, $post) {
    // Check if this is an autosave or revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Only for published posts
    if ($post->post_status !== 'publish' || $post->post_type !== 'post') {
        return;
    }
    
    // Check if email notification is enabled for this post
    $send_email = get_post_meta($post_id, '_whmin_send_news_email', true);
    if ($send_email !== '1') {
        return;
    }
    
    // Check if we already sent email for this post
    $already_sent = get_post_meta($post_id, '_whmin_news_email_sent', true);
    if ($already_sent === '1') {
        return; // Already sent
    }
    
    // Get automatic expiration settings to see which sites have notifications enabled
    $auto_settings = whmin_get_auto_expiration_settings();
    $enabled_sites = $auto_settings['enabled_sites'];
    
    if (empty($enabled_sites)) {
        return; // No sites enabled for notifications
    }
    
    // Get all site service data
    $all_services_data = get_option('whmin_site_services_data', []);
    
    // Get branding and text settings
    $branding = whmin_get_branding_settings();
    $texts = whmin_get_notification_texts();
    $brand_link = !empty($branding['footer_link']) ? $branding['footer_link'] : home_url();
    
    // Prepare post data
    $post_title = get_the_title($post_id);
    $post_url = get_permalink($post_id);
    $post_date = get_the_date('', $post_id);
    $post_content = apply_filters('the_content', $post->post_content);
    
    // Send email to each enabled site owner
    foreach ($enabled_sites as $user) {
        if (!isset($all_services_data[$user])) {
            continue;
        }
        
        $site_data = $all_services_data[$user];
        $recipient_email = $site_data['emails']['primary'];
        $cc_email = $site_data['emails']['secondary'];
        
        if (!is_email($recipient_email)) {
            continue;
        }
        
        // Prepare subject
        $subject = $texts['news_subject'];
        $subject = str_replace('%title%', $post_title, $subject);
        
        // Prepare template variables
        ob_start();
        
        $recipient_name = 'Customer';
        $text_config = $texts;
        
        include WHMIN_PLUGIN_DIR . 'templates/email-news-notification.php';
        $message = ob_get_clean();
        
        // Prepare headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if ($cc_email && is_email($cc_email)) {
            $headers[] = 'Cc: ' . $cc_email;
        }
        
        // Send email
        wp_mail($recipient_email, $subject, $message, $headers);
    }
    
    // Mark as sent
    update_post_meta($post_id, '_whmin_news_email_sent', '1');
}
add_action('publish_post', 'whmin_send_news_email_on_publish', 10, 2);

/**
 * Fetch recent posts that should appear in Maintenance & News.
 */
function whmin_get_maintenance_news_items($limit = 5, $args = array()) {
    $options      = whmin_get_news_impact_options();
    $impact_slugs = array_diff(array_keys($options), array('normal'));

    if (empty($impact_slugs)) {
        return array();
    }

    $default_query_args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'ignore_sticky_posts' => true,
        'meta_query'          => array(
            array(
                'key'     => '_whmin_news_impact',
                'value'   => $impact_slugs,
                'compare' => 'IN',
            ),
        ),
    );
    
    $query_args = array_merge($default_query_args, $args); 

    $query = new WP_Query($query_args);

    if (!$query->have_posts()) {
        return array();
    }

    $items = array();

    while ($query->have_posts()) {
        $query->the_post();

        $post_id      = get_the_ID();
        $impact_slug  = get_post_meta($post_id, '_whmin_news_impact', true);
        if (!$impact_slug || !isset($options[$impact_slug])) {
            continue;
        }

        $impact_label = $options[$impact_slug]['label'];
        $impact_color = $options[$impact_slug]['color'];

        $content = has_excerpt($post_id) ? get_the_excerpt() : get_the_content(null, false, $post_id);
        $text    = wp_strip_all_tags($content);

        $excerpt = $text;
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text, 'UTF-8') > 350) {
                $excerpt = mb_substr($text, 0, 350, 'UTF-8') . '…';
            }
        } else {
            if (strlen($text) > 350) {
                $excerpt = substr($text, 0, 350) . '…';
            }
        }

        $items[] = array(
            'id'           => $post_id,
            'title'        => get_the_title($post_id),
            'excerpt'      => $excerpt,
            'permalink'    => get_permalink($post_id),
            'impact_slug'  => $impact_slug,
            'impact_label' => $impact_label,
            'impact_color' => $impact_color,
            'date'         => get_the_date('', $post_id),
        );
    }

    wp_reset_postdata();

    return $items;
}

/**
 * Renders the HTML for a single news item.
 */
function whmin_render_single_news_item($item) {
    ?>
    <div class="whmin-news-item">
        <a class="whmin-news-link" href="<?php echo esc_url($item['permalink']); ?>">
            <div class="whmin-news-header">
                <span class="whmin-news-impact-badge"
                        style="background-color: <?php echo esc_attr($item['impact_color']); ?>;">
                    <?php echo esc_html($item['impact_label']); ?>
                </span>
                <h4 class="whmin-news-title">
                    <?php echo esc_html($item['title']); ?>
                </h4>
            </div>
            <div class="whmin-news-meta">
                <?php echo esc_html($item['date']); ?>
            </div>
            <div class="whmin-news-body">
                <p><?php echo esc_html($item['excerpt']); ?></p>
            </div>
        </a>
    </div>
    <?php
}

/**
 * Render the full Maintenance & News card.
 */
function whmin_render_maintenance_news_section($limit = 7) {
    $all_items = whmin_get_maintenance_news_items($limit + 1);
    
    $has_more = count($all_items) > $limit;
    $items    = array_slice($all_items, 0, $limit);

    if (!defined('WHMIN_PLUGIN_DIR')) {
        return;
    }

    $template_path = WHMIN_PLUGIN_DIR . 'templates/maintenance-news-section.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>' . esc_html__('Maintenance & News template not found.', 'whmin') . '</p>';
    }
}

/**
 * AJAX handler for loading more maintenance news items.
 */
function whmin_ajax_load_more_news() {
    check_ajax_referer('whmin_public_nonce', 'nonce'); 
    
    $offset = intval($_POST['offset'] ?? 0);
    $limit  = 7;

    $query_args = array(
        'offset'         => $offset,
        'posts_per_page' => $limit + 1,
    );
    
    $all_items = whmin_get_maintenance_news_items($limit + 1, $query_args);

    $has_more = count($all_items) > $limit;
    $items    = array_slice($all_items, 0, $limit);
    
    ob_start();
    foreach ($items as $item):
        whmin_render_single_news_item($item);
    endforeach;
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html'     => $html,
        'new_offset' => $offset + count($items),
        'has_more' => $has_more,
    ));
}

add_action('wp_ajax_whmin_load_more_news', 'whmin_ajax_load_more_news');
add_action('wp_ajax_nopriv_whmin_load_more_news', 'whmin_ajax_load_more_news');