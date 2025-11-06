<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns all impact options (including "normal").
 *
 * Keys are stored as meta value; label/color are for display.
 */
function whmin_get_news_impact_options() {
    return array(
        'normal' => array(
            'label' => __('Normal post (not shown on status page)', 'whmin'),
            'color' => '',
        ),
        'no_impact' => array(
            'label' => __('No impact – informational', 'whmin'),
            'color' => '#6B7280', // gray
        ),
        'low_impact' => array(
            'label' => __('Low impact', 'whmin'),
            'color' => '#16A34A', // green
        ),
        'medium_impact' => array(
            'label' => __('Medium impact', 'whmin'),
            'color' => '#F97316', // orange
        ),
        'high_impact' => array(
            'label' => __('High impact', 'whmin'),
            'color' => '#DC2626', // red
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
    <?php
}

/**
 * Save meta box value.
 */
function whmin_save_maintenance_news_meta_box($post_id) {
    // Autosave / revisions / nonce / capability checks
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

    // Store as meta; keep "normal" explicitly, or delete to be safe.
    if ($impact === 'normal') {
        delete_post_meta($post_id, '_whmin_news_impact');
    } else {
        update_post_meta($post_id, '_whmin_news_impact', $impact);
    }
}
add_action('save_post', 'whmin_save_maintenance_news_meta_box');

/**
 * Fetch recent posts that should appear in Maintenance & News.
 *
 * @param int $limit Number of posts to return.
 * @param array $args Additional WP_Query arguments (e.g., 'offset').
 * @return array[]
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
    
    // Merge provided arguments (like 'offset') with defaults
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

        // Prefer explicit excerpt, fallback to content.
        $content = has_excerpt($post_id) ? get_the_excerpt() : get_the_content(null, false, $post_id);
        $text    = wp_strip_all_tags($content);

        // Trim to 350 chars with ellipsis
        $excerpt = $text;
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text, 'UTF-8') > 350) {
                $excerpt = mb_substr($text, 0, 350, 'UTF-8') . '…';
            }
            // else: no need for else, excerpt is already $text
        } else {
            if (strlen($text) > 350) {
                $excerpt = substr($text, 0, 350) . '…';
            }
            // else: no need for else, excerpt is already $text
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
 * @param array $item The news item data array.
 */
function whmin_render_single_news_item($item) {
    // This function is only called for NON-featured items from the template/AJAX, 
    // so we don't need the 'is-featured' class here.
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
 * Render the full Maintenance & News card (used by both public & private dashboards).
 *
 * @param int $limit Number of posts to render initially.
 */
function whmin_render_maintenance_news_section($limit = 7) {
    // Get one more than the limit to determine if a "Load More" button is needed.
    $all_items = whmin_get_maintenance_news_items($limit + 1);
    
    $has_more = count($all_items) > $limit;
    $items    = array_slice($all_items, 0, $limit);

    // WHMIN_PLUGIN_DIR is assumed to be defined in the main plugin file.
    if (!defined('WHMIN_PLUGIN_DIR')) {
        return;
    }

    $template_path = WHMIN_PLUGIN_DIR . 'templates/maintenance-news-section.php';

    if (file_exists($template_path)) {
        // Pass variables to the template by including it
        include $template_path;
    } else {
        echo '<p>' . esc_html__('Maintenance & News template not found.', 'whmin') . '</p>';
    }
}

/**
 * AJAX handler for loading more maintenance news items.
 */
function whmin_ajax_load_more_news() {
    // Using the same nonce as localized in WHMIN::enqueue_frontend_assets
    check_ajax_referer('whmin_public_nonce', 'nonce'); 
    
    $offset = intval($_POST['offset'] ?? 0);
    $limit  = 7; // Fixed batch size for loading more

    // Get a batch + 1 to check for more
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