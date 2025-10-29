<?php
if (!defined('ABSPATH')) exit;

$data = whmin_get_public_dashboard_data();
$server_status = $data['overall_status']['status'] ?? 'unknown'; 
?>
<div class="whmin-public-status-page">
    <header class="whmin-header">
        <img src="<?php echo esc_url(plugins_url('assets/img/logo.png', WHMIN_PLUGIN_BASENAME)); ?>" alt="Logo" class="whmin-logo">
        <div class="whmin-overall-status whmin-status-<?php echo esc_attr($server_status); ?>">
            <i class="mdi"></i>
            <span><?php echo esc_html($data['overall_status']['text']); ?></span>
        </div>
    </header>

    <main class="whmin-main-content">
        <!-- Graph Section for Hosted Servers -->
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h3><?php _e('WHM Server Uptime', 'whmin'); ?></h3>
                <p><?php _e('Historical uptime data for websites hosted on our infrastructure.', 'whmin'); ?></p>
            </div>
            <div class="whmin-card-body">
                <div class="whmin-graph-controls" data-chart-target="serverHistoryChart">
                    <button class="whmin-range-btn active" data-range="1"><?php _e('1M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="3"><?php _e('3M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="6"><?php _e('6M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="12"><?php _e('1Y', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="60"><?php _e('5Y', 'whmin'); ?></button>
                </div>
                <div class="whmin-chart-container">
                    <canvas id="serverHistoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Graph Section for Managed Servers -->
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h3><?php _e('Managed Servers Uptime', 'whmin'); ?></h3>
                <p><?php _e('Historical uptime data for websites we manage on other hosting.', 'whmin'); ?></p>
            </div>
            <div class="whmin-card-body">
                 <div class="whmin-graph-controls" data-chart-target="externalHistoryChart">
                    <button class="whmin-range-btn active" data-range="1"><?php _e('1M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="3"><?php _e('3M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="6"><?php _e('6M', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="12"><?php _e('1Y', 'whmin'); ?></button>
                    <button class="whmin-range-btn" data-range="60"><?php _e('5Y', 'whmin'); ?></button>
                </div>
                <div class="whmin-chart-container">
                    <canvas id="externalHistoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="whmin-grid">
            <div class="whmin-card">
                <div class="whmin-card-header">
                    <h4><?php _e('Hosted on Our Servers', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <div class="whmin-stat-big"><?php echo esc_html($data['stats']['direct_count']); ?></div>
                    <p><?php _e('Websites', 'whmin'); ?></p>
                </div>
            </div>
            <div class="whmin-card">
                 <div class="whmin-card-header">
                    <h4><?php _e('Managed on Other Servers', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <div class="whmin-stat-big"><?php echo esc_html($data['stats']['indirect_count']); ?></div>
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
                </div>
            </div>
        </div>

        <!-- Maintenance News Section -->
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
    </main>
</div>