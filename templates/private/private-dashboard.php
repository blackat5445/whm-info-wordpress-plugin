<?php
/**
 * Final Public Dashboard Template
 *
 * This template is fully dynamic and respects all settings configured
 * in the "Public Page Settings" admin tab.
 */
if (!defined('ABSPATH')) exit;

$data = whmin_get_public_dashboard_data();
$settings = whmin_get_public_settings();
$branding = whmin_get_branding_settings();
$server_status = $data['overall_status']['status'] ?? 'unknown';

// Determine logo URL
$logo_url = $branding['logo_id']
    ? wp_get_attachment_image_url($branding['logo_id'], 'full')
    : plugins_url('assets/img/logo.png', WHMIN_PLUGIN_BASENAME);


// Logic for grid layout
$is_hosted_visible = $settings['enable_hosted_counter'];
$is_managed_visible = $settings['enable_managed_counter'];
$grid_class = ($is_hosted_visible && $is_managed_visible) ? 'whmin-grid' : 'whmin-grid-single';
?>
<div class="whmin-public-status-page whmin-private-dashboard-page"> 
    <header class="whmin-header">
        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="whmin-logo">
        <div class="whmin-overall-status whmin-status-<?php echo esc_attr($server_status); ?>">
            <i class="mdi"></i>
            <span><?php echo esc_html($data['overall_status']['text']); ?></span>
        </div>
    </header>

    <main class="whmin-main-content">
        <!-- Graph Section for Hosted Servers (Conditionally Rendered) -->
        <?php if ($settings['enable_server_graph']): ?>
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h3><?php _e('Hosted Servers Uptime', 'whmin'); ?></h3>
                <p><?php _e('Historical uptime data for websites hosted on our infrastructure.', 'whmin'); ?></p>
            </div>
            <div class="whmin-card-body">
                <div class="whmin-graph-controls" data-chart-target="serverHistoryChart">
                    <?php
                    $timeframes = ['24h' => '24H', '7d' => '7D', '1m' => '1M', '3m' => '3M', '6m' => '6M', '12m' => '1Y', '60m' => '5Y'];
                    foreach ($timeframes as $key => $label):
                        if (in_array($key, $settings['server_graph_timeframes'])): ?>
                            <button class="whmin-range-btn <?php echo $key === '1m' ? 'active' : ''; ?>" data-range="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                        <?php endif;
                    endforeach;
                    ?>
                </div>
                <div class="whmin-chart-container">
                    <canvas id="serverHistoryChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Graph Section for Managed Servers (Conditionally Rendered) -->
        <?php if ($settings['enable_managed_graph']): ?>
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h3><?php _e('Managed Servers Uptime', 'whmin'); ?></h3>
                <p><?php _e('Historical uptime data for websites we manage on other hosting.', 'whmin'); ?></p>
            </div>
            <div class="whmin-card-body">
                 <div class="whmin-graph-controls" data-chart-target="externalHistoryChart">
                    <?php foreach ($timeframes as $key => $label):
                        if (in_array($key, $settings['managed_graph_timeframes'])): ?>
                            <button class="whmin-range-btn <?php echo $key === '1m' ? 'active' : ''; ?>" data-range="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                        <?php endif;
                    endforeach;
                    ?>
                </div>
                <div class="whmin-chart-container">
                    <canvas id="externalHistoryChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section (Conditionally Rendered) -->
        <?php if ($is_hosted_visible || $is_managed_visible): ?>
        <div class="<?php echo esc_attr($grid_class); ?>">
            
            <?php if ($is_hosted_visible): ?>
            <div class="whmin-card">
                <div class="whmin-card-header">
                    <h4><?php _e('Hosted on Our Servers', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <div class="whmin-stat-big animated-counter" data-target="<?php echo esc_attr($data['stats']['direct_count']); ?>" style="color: <?php echo esc_attr($settings['counter_color']); ?>;">0</div>
                    <p><?php _e('Websites', 'whmin'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_managed_visible): ?>
            <div class="whmin-card">
                 <div class="whmin-card-header">
                    <h4><?php _e('Managed on Other Servers', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <div class="whmin-stat-big animated-counter" data-target="<?php echo esc_attr($data['stats']['indirect_count']); ?>" style="color: <?php echo esc_attr($settings['counter_color']); ?>;">0</div>
                    
                    <?php if ($settings['show_hosting_breakdown']): ?>
                        <p><?php _e('Websites distributed across various providers:', 'whmin'); ?></p>
                        <ul class="whmin-host-list">
                            <?php if (!empty($data['stats']['hosting_groups'])): ?>
                                <?php foreach($data['stats']['hosting_groups'] as $host => $count): ?>
                                    <li><?php echo esc_html($host); ?> <span>(<?php echo esc_html($count); ?>)</span></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><?php _e('No providers listed.', 'whmin'); ?></li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('Websites', 'whmin'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Maintenance News Section (Conditionally Rendered) -->
        <?php if ($settings['enable_maintenance_news']): ?>
        <div class="whmin-card whmin-maintenance-news">
             <div class="whmin-card-header">
                <h3><?php _e('Maintenance & News', 'whmin'); ?></h3>
            </div>
            <div class="whmin-card-body">
                <div class="whmin-no-news">
                    <i class="mdi mdi-information-outline"></i>
                    <p><?php _e('No recent news or scheduled maintenance to report.', 'whmin'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer Section -->
    <?php if (!empty($branding['footer_note'])): ?>
    <footer class="whmin-footer">
        <?php echo wp_kses_post($branding['footer_note']); ?>
    </footer>
    <?php endif; ?>
</div>