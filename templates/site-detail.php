<?php
if (!defined('ABSPATH')) exit;

/**
 * This template is STRICTLY for admins.
 * Double-check capability here, even though we also gate it in the template loader.
 */
if (!current_user_can('manage_options')) {
    wp_die(
        esc_html__('You do not have permission to view this page.', 'whmin'),
        esc_html__('Access denied', 'whmin'),
        ['response' => 403]
    );
}

$type = get_query_var('whmin_site_type');
$key  = get_query_var('whmin_site_key');

$type = ($type === 'direct') ? 'direct' : 'remote';

$meta = whmin_get_site_meta($type, $key);

get_header();

?>
<div class="whmin-public-status-page whmin-private-dashboard-page whmin-site-detail-page">
    <?php if (!$meta): ?>
        <main class="whmin-main-content">
            <div class="whmin-card">
                <div class="whmin-card-header">
                    <h3><?php _e('Site information not available', 'whmin'); ?></h3>
                </div>
                <div class="whmin-card-body">
                    <p><?php _e('No metadata has been received from the agent for this site yet. Make sure the WHM Info Connect plugin is installed and activated, and that the connection is configured correctly.', 'whmin'); ?></p>
                </div>
            </div>
        </main>
    <?php else: ?>
        <?php
        $name            = $meta['name'] ?? $meta['url'] ?? $key;
        $url             = $meta['url'] ?? '';
        $hosting         = $meta['hosting'] ?? ($type === 'direct' ? 'WHM' : '');
        $connection_type = $meta['connection'] ?? ($type === 'direct' ? __('Direct (WHM)', 'whmin') : __('Remote', 'whmin'));
        $agent_connected = !empty($meta['agent_connected']) && !empty($meta['data']['agent']);
        $whm_disk        = $meta['data']['whm_disk'] ?? null;
        $agent           = $meta['data']['agent'] ?? null;
        $php_info        = $agent['php']       ?? [];
        $wp_info         = $agent['wordpress'] ?? [];
        $theme_info      = $agent['theme']     ?? [];
        $plugins_info    = $agent['plugins']['active'] ?? [];
        $server_info     = $agent['server']    ?? [];
        $generated_at    = !empty($agent['generated_at']) ? (int)$agent['generated_at'] : 0;
        ?>
        <header class="whmin-header whmin-site-detail-header">
            <div class="whmin-site-header-main">
                <h1><?php echo esc_html($name); ?></h1>
                <?php if ($url): ?>
                    <p>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($url); ?>
                            <i class="mdi mdi-open-in-new"></i>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="whmin-site-header-badges">
                <span class="whmin-status-pill <?php echo $agent_connected ? 'whmin-pill-success' : 'whmin-pill-muted'; ?>">
                    <i class="mdi <?php echo $agent_connected ? 'mdi-check-circle-outline' : 'mdi-link-off'; ?>"></i>
                    <?php echo $agent_connected ? esc_html__('Agent connected', 'whmin') : esc_html__('Agent not connected', 'whmin'); ?>
                </span>

                <?php if ($hosting): ?>
                    <span class="whmin-status-pill whmin-pill-info">
                        <i class="mdi mdi-server"></i>
                        <?php echo esc_html($hosting); ?>
                    </span>
                <?php endif; ?>

                <?php if ($connection_type): ?>
                    <span class="whmin-status-pill whmin-pill-muted">
                        <i class="mdi mdi-connection"></i>
                        <?php echo esc_html($connection_type); ?>
                    </span>
                <?php endif; ?>

                <?php if ($generated_at): ?>
                    <span class="whmin-status-pill whmin-pill-muted">
                        <i class="mdi mdi-clock-outline"></i>
                        <?php
                        printf(
                            /* translators: %s = human time diff */
                            esc_html__('Refreshed %s ago', 'whmin'),
                            esc_html(human_time_diff($generated_at, current_time('timestamp')))
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <main class="whmin-main-content">
            <!-- Overview card -->
            <div class="whmin-card">
                <div class="whmin-card-header">
                    <h3><i class="mdi mdi-information-outline"></i> <?php _e('Overview', 'whmin'); ?></h3>
                </div>
                <div class="whmin-card-body">
                    <div class="whmin-grid whmin-details-grid">
                        <div>
                            <ul class="whmin-detail-list">
                                <li>
                                    <span><?php _e('Type', 'whmin'); ?></span>
                                    <strong><?php echo $type === 'direct' ? esc_html__('Hosted on our WHM', 'whmin') : esc_html__('Remote / Managed', 'whmin'); ?></strong>
                                </li>
                                <?php if ($hosting): ?>
                                    <li>
                                        <span><?php _e('Hosting', 'whmin'); ?></span>
                                        <strong><?php echo esc_html($hosting); ?></strong>
                                    </li>
                                <?php endif; ?>
                                <?php if ($connection_type): ?>
                                    <li>
                                        <span><?php _e('Connection', 'whmin'); ?></span>
                                        <strong><?php echo esc_html($connection_type); ?></strong>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php if ($type === 'direct' && $whm_disk): ?>
                            <div>
                                <h5 class="whmin-subtitle"><i class="mdi mdi-harddisk"></i> <?php _e('WHM Disk Usage', 'whmin'); ?></h5>
                                <p>
                                    <strong><?php echo esc_html($whm_disk['label']); ?></strong>
                                    <br>
                                    <small><?php printf(esc_html__('%s bytes', 'whmin'), esc_html($whm_disk['bytes'])); ?></small>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($agent_connected && $agent): ?>
                <!-- PHP + WordPress cards -->
                <div class="whmin-grid whmin-details-grid">
                    <!-- PHP Info -->
                    <div class="whmin-card whmin-detail-card">
                        <div class="whmin-card-header">
                            <h4><i class="mdi mdi-language-php"></i> <?php _e('PHP Configuration', 'whmin'); ?></h4>
                        </div>
                        <div class="whmin-card-body">
                            <ul class="whmin-detail-list">
                                <?php if (!empty($php_info['version'])): ?>
                                <li>
                                    <span><?php _e('Version', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($php_info['version']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($php_info['memory_limit'])): ?>
                                <li>
                                    <span><?php _e('Memory limit', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($php_info['memory_limit']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($php_info['upload_max_filesize'])): ?>
                                <li>
                                    <span><?php _e('Upload max filesize', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($php_info['upload_max_filesize']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($php_info['post_max_size'])): ?>
                                <li>
                                    <span><?php _e('Post max size', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($php_info['post_max_size']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($php_info['max_execution_time'])): ?>
                                <li>
                                    <span><?php _e('Max execution time', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($php_info['max_execution_time']); ?>s</strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($php_info['display_errors'])): ?>
                                <li>
                                    <span><?php _e('Display errors', 'whmin'); ?></span>
                                    <strong><?php echo $php_info['display_errors'] ? esc_html__('On', 'whmin') : esc_html__('Off', 'whmin'); ?></strong>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- WordPress Info -->
                    <div class="whmin-card whmin-detail-card">
                        <div class="whmin-card-header">
                            <h4><i class="mdi mdi-wordpress"></i> <?php _e('WordPress', 'whmin'); ?></h4>
                        </div>
                        <div class="whmin-card-body">
                            <ul class="whmin-detail-list">
                                <?php if (!empty($wp_info['version'])): ?>
                                <li>
                                    <span><?php _e('Version', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($wp_info['version']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($wp_info['multisite'])): ?>
                                <li>
                                    <span><?php _e('Multisite', 'whmin'); ?></span>
                                    <strong><?php echo $wp_info['multisite'] ? esc_html__('Yes', 'whmin') : esc_html__('No', 'whmin'); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($wp_info['language'])): ?>
                                <li>
                                    <span><?php _e('Language', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($wp_info['language']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($wp_info['debug'])): ?>
                                <li>
                                    <span><?php _e('Debug mode', 'whmin'); ?></span>
                                    <strong><?php echo $wp_info['debug'] ? esc_html__('Enabled', 'whmin') : esc_html__('Disabled', 'whmin'); ?></strong>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Theme + Server -->
                <div class="whmin-grid whmin-details-grid">
                    <!-- Theme -->
                    <div class="whmin-card whmin-detail-card">
                        <div class="whmin-card-header">
                            <h4><i class="mdi mdi-palette-outline"></i> <?php _e('Theme', 'whmin'); ?></h4>
                        </div>
                        <div class="whmin-card-body">
                            <ul class="whmin-detail-list">
                                <?php if (!empty($theme_info['name'])): ?>
                                <li>
                                    <span><?php _e('Active theme', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($theme_info['name']); ?><?php echo !empty($theme_info['version']) ? ' ' . esc_html($theme_info['version']) : ''; ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($theme_info['template'])): ?>
                                <li>
                                    <span><?php _e('Template', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($theme_info['template']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($theme_info['is_child'])): ?>
                                <li>
                                    <span><?php _e('Child theme of', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($theme_info['parent_name']); ?></strong>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Server -->
                    <div class="whmin-card whmin-detail-card">
                        <div class="whmin-card-header">
                            <h4><i class="mdi mdi-server-network"></i> <?php _e('Server', 'whmin'); ?></h4>
                        </div>
                        <div class="whmin-card-body">
                            <ul class="whmin-detail-list">
                                <?php if (!empty($server_info['software'])): ?>
                                <li>
                                    <span><?php _e('Software', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($server_info['software']); ?></strong>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($server_info['php_uname'])): ?>
                                <li>
                                    <span><?php _e('OS', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($server_info['php_uname']); ?></strong>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Plugins list -->
                <div class="whmin-card">
                    <div class="whmin-card-header">
                        <h3><i class="mdi mdi-puzzle-outline"></i> <?php _e('Active Plugins', 'whmin'); ?></h3>
                        <?php if (isset($agent['plugins']['total_active'])): ?>
                            <p><?php printf(esc_html__('%d active plugins', 'whmin'), (int)$agent['plugins']['total_active']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="whmin-card-body">
                        <?php if (!empty($plugins_info)): ?>
                            <div class="whmin-table-container">
                                <table class="whmin-status-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Plugin', 'whmin'); ?></th>
                                            <th><?php _e('Version', 'whmin'); ?></th>
                                            <th><?php _e('Author', 'whmin'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plugins_info as $plugin): ?>
                                            <tr>
                                                <td><?php echo esc_html($plugin['name']); ?></td>
                                                <td><?php echo esc_html($plugin['version']); ?></td>
                                                <td><?php echo esc_html($plugin['author']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="whmin-error"><?php _e('No active plugins reported by the agent.', 'whmin'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="whmin-card">
                    <div class="whmin-card-header">
                        <h3><i class="mdi mdi-link-off"></i> <?php _e('Agent data not available', 'whmin'); ?></h3>
                    </div>
                    <div class="whmin-card-body">
                        <p><?php _e('The agent has not sent any metadata for this site yet. Check that the WHM Info Connect plugin is installed, configured with the correct API URL and key, and that the connection is activated.', 'whmin'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
