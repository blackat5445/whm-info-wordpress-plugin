<?php
if (!defined('ABSPATH')) {
    exit;
}

function whmin_add_uninstall_confirmation() {
    $screen = get_current_screen();
    if ($screen->id !== 'plugins') return;

    if (isset($_GET['whmin_uninstall_confirm'])) {
        ?>
        <div class="notice notice-info">
            <form method="post" action="<?php echo admin_url('plugins.php'); ?>">
                <h3><?php _e('AI Product Content Generator - Uninstall Options', 'whmin'); ?></h3>
                <p>
                    <label>
                        <input type="radio" name="whmin_full_uninstall" value="1">
                        <?php _e('Remove all plugin data (settings and queues)', 'whmin'); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="radio" name="whmin_full_uninstall" value="0" checked>
                        <?php _e('Keep plugin data (can reuse if reinstalling)', 'whmin'); ?>
                    </label>
                </p>
                <?php wp_nonce_field('whmin_uninstall_nonce', 'whmin_uninstall_nonce'); ?>
                <p>
                    <input type="submit" class="button button-primary" value="<?php _e('Confirm Uninstall', 'whmin'); ?>">
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="button">
                        <?php _e('Cancel', 'whmin'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    if ($message = get_transient('whmin_uninstall_message')) {
        ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
        delete_transient('whmin_uninstall_message');
    }
}
add_action('admin_notices', 'whmin_add_uninstall_confirmation');

function whmin_modify_uninstall_link($links, $plugin_file) {
    if (plugin_basename(__FILE__) === $plugin_file) {
        $links['uninstall'] = '<a href="' . admin_url('plugins.php?whmin_uninstall_confirm=1') . '">' . __('Uninstall', 'whmin') . '</a>';
    }
    return $links;
}
add_filter('plugin_action_links', 'whmin_modify_uninstall_link', 10, 2);