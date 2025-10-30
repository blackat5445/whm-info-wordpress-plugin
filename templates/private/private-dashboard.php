<?php
/**
 * Enhanced Private Dashboard Template
 * Fixed: Graphs first, bandwidth error fixed, removed shell access, uses hosted count
 */
if (!defined('ABSPATH')) exit;

$data = whmin_get_private_dashboard_data();
$settings = whmin_get_public_settings();
$branding = whmin_get_branding_settings();
$server_status = $data['overall_status']['status'] ?? 'unknown';

// Determine logo URL
$logo_url = $branding['logo_id']
    ? wp_get_attachment_image_url($branding['logo_id'], 'full')
    : plugins_url('assets/img/logo.png', WHMIN_PLUGIN_BASENAME);

// Extract server details
$account_summary = $data['server_details']['account_summary'] ?? [];
$system_info     = $data['server_details']['system_info'] ?? [];
$disk_usage      = $data['server_details']['disk_usage'] ?? [];
$mysql_info      = $data['server_details']['mysql_info'] ?? [];
$ssl_info        = $data['server_details']['ssl_info'] ?? [];
$apache_status   = $data['server_details']['apache_status'] ?? [];

// Grid layout logic
$is_hosted_visible  = $settings['enable_hosted_counter'];
$is_managed_visible = $settings['enable_managed_counter'];
$grid_class = ($is_hosted_visible && $is_managed_visible) ? 'whmin-grid' : 'whmin-grid-single';
?>
<style>
/* Gray highlight for deactivated rows */
.whmin-status-row.whmin-status-deactivated {
    background: #f3f4f6;
    color: #6b7280;
    opacity: .95;
}
.whmin-status-row.whmin-status-deactivated a { color: inherit; opacity: .9; }
</style>

<div class="whmin-public-status-page whmin-private-dashboard-page"> 
    <header class="whmin-header">
        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="whmin-logo">
        <div class="whmin-overall-status whmin-status-<?php echo esc_attr($server_status); ?>">
            <i class="mdi"></i>
            <span>
                <?php 
                echo esc_html($data['overall_status']['text']); 
                if (isset($data['overall_status']['percent'])) {
                    echo ' — ' . esc_html(number_format((float)$data['overall_status']['percent'], 2)) . '%';
                }
                ?>
            </span>
        </div>
    </header>

    <main class="whmin-main-content">
        <!-- GRAPHS FIRST - Hosted Servers Uptime -->
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

        <!-- GRAPHS FIRST - Managed Servers Uptime -->
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

        <!-- Server Overview Cards -->
        <div class="whmin-grid whmin-overview-grid">
            <!-- Account Summary Card -->
            <div class="whmin-card whmin-overview-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-account-multiple-outline"></i> <?php _e('Accounts Overview', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($account_summary['summary'])): ?>
                        <div class="whmin-stats-grid">
                            <?php foreach ($account_summary['summary'] as $label => $value): ?>
                                <div class="whmin-stat-item">
                                    <div class="whmin-stat-value"><?php echo esc_html($value); ?></div>
                                    <div class="whmin-stat-label"><?php echo esc_html($label); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($account_summary['error'] ?? __('No data available', 'whmin')); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Services Status Card -->
            <div class="whmin-card whmin-overview-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-server-network"></i> <?php _e('Services Status', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($apache_status['summary'])): ?>
                        <div class="whmin-services-summary">
                            <div class="whmin-service-stat whmin-service-running">
                                <span class="whmin-service-count"><?php echo esc_html($apache_status['summary']['running']); ?></span>
                                <span class="whmin-service-label"><?php _e('Running', 'whmin'); ?></span>
                            </div>
                            <div class="whmin-service-stat whmin-service-stopped">
                                <span class="whmin-service-count"><?php echo esc_html($apache_status['summary']['stopped']); ?></span>
                                <span class="whmin-service-label"><?php _e('Stopped', 'whmin'); ?></span>
                            </div>
                        </div>
                        <div class="whmin-services-list">
                            <?php foreach ($apache_status['services'] as $service_key => $service): ?>
                                <div class="whmin-service-item whmin-service-<?php echo esc_attr($service['status']); ?>">
                                    <span class="whmin-service-name"><?php echo esc_html($service['display_name']); ?></span>
                                    <span class="whmin-service-badge"><?php echo esc_html($service['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($apache_status['error'] ?? __('No service data available', 'whmin')); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resource Usage Section -->
        <div class="whmin-grid">
            <!-- Disk Usage Card -->
            <div class="whmin-card whmin-resource-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-harddisk"></i> <?php _e('Disk Usage', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($account_summary['disk']) && !isset($account_summary['error'])): ?>
                        <div class="whmin-resource-summary">
                            <div class="whmin-resource-values">
                                <span class="whmin-resource-used"><?php echo esc_html($account_summary['disk']['used']); ?> <?php echo esc_html($account_summary['disk']['unit']); ?></span>
                                <span class="whmin-resource-separator">/</span>
                                <span class="whmin-resource-total"><?php echo esc_html($account_summary['disk']['limit']); ?> <?php echo esc_html($account_summary['disk']['unit']); ?></span>
                            </div>
                            <?php if ($account_summary['disk']['percentage'] > 0): ?>
                                <div class="whmin-resource-percentage"><?php echo esc_html($account_summary['disk']['percentage']); ?>%</div>
                            <?php endif; ?>
                        </div>
                        <?php if ($account_summary['disk']['percentage'] > 0): ?>
                            <div class="whmin-progress-bar">
                                <div class="whmin-progress-fill" 
                                     style="width: <?php echo esc_attr(min($account_summary['disk']['percentage'], 100)); ?>%"
                                     data-percentage="<?php echo esc_attr($account_summary['disk']['percentage']); ?>">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Top Disk Users -->
                        <?php if (isset($disk_usage['top_users']) && !empty($disk_usage['top_users'])): ?>
                            <div class="whmin-top-users">
                                <h5><?php _e('Top Disk Users', 'whmin'); ?></h5>
                                <div class="whmin-top-users-list">
                                    <?php foreach (array_slice($disk_usage['top_users'], 0, 5) as $user): ?>
                                        <div class="whmin-user-item">
                                            <span class="whmin-user-domain"><?php echo esc_html($user['domain']); ?></span>
                                            <span class="whmin-user-usage"><?php echo esc_html(round($user['used'], 2)); ?> MB</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($account_summary['error'] ?? __('No disk data available', 'whmin')); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bandwidth Usage Card -->
            <div class="whmin-card whmin-resource-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-swap-horizontal"></i> <?php _e('Bandwidth Usage', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($account_summary['bandwidth']) && !isset($account_summary['error'])): ?>
                        <div class="whmin-resource-summary">
                            <div class="whmin-resource-values">
                                <span class="whmin-resource-used"><?php echo esc_html($account_summary['bandwidth']['used']); ?> <?php echo esc_html($account_summary['bandwidth']['unit']); ?></span>
                                <span class="whmin-resource-separator">/</span>
                                <span class="whmin-resource-total"><?php echo esc_html($account_summary['bandwidth']['limit']); ?> <?php echo esc_html($account_summary['bandwidth']['unit']); ?></span>
                            </div>
                            <?php if ($account_summary['bandwidth']['percentage'] > 0): ?>
                                <div class="whmin-resource-percentage"><?php echo esc_html($account_summary['bandwidth']['percentage']); ?>%</div>
                            <?php endif; ?>
                        </div>
                        <?php if ($account_summary['bandwidth']['percentage'] > 0): ?>
                            <div class="whmin-progress-bar">
                                <div class="whmin-progress-fill" 
                                     style="width: <?php echo esc_attr(min($account_summary['bandwidth']['percentage'], 100)); ?>%"
                                     data-percentage="<?php echo esc_attr($account_summary['bandwidth']['percentage']); ?>">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Top Bandwidth Users -->
                        <?php if (isset($system_info['bandwidth']['top_users']) && !empty($system_info['bandwidth']['top_users'])): ?>
                            <div class="whmin-top-users">
                                <h5><?php _e('Top Bandwidth Users', 'whmin'); ?></h5>
                                <div class="whmin-top-users-list">
                                    <?php foreach (array_slice($system_info['bandwidth']['top_users'], 0, 5) as $user): ?>
                                        <div class="whmin-user-item">
                                            <span class="whmin-user-domain"><?php echo esc_html($user['acct'] ?? $user['domain'] ?? $user['user'] ?? 'unknown'); ?></span>
                                            <span class="whmin-user-usage"><?php echo esc_html(round(floatval($user['totalbytes'] ?? 0) / 1024 / 1024, 2)); ?> MB</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($account_summary['error'] ?? __('No bandwidth data available', 'whmin')); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Load Chart -->
        <?php if (isset($system_info['load']) && !empty($system_info['load'])): ?>
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h4><i class="mdi mdi-chart-line"></i> <?php _e('System Load Average', 'whmin'); ?></h4>
            </div>
            <div class="whmin-card-body">
                <canvas id="systemLoadChart" height="80"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Information Grid -->
        <div class="whmin-grid whmin-details-grid">
            <!-- Hosted Accounts -->
            <div class="whmin-card whmin-detail-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-server"></i> <?php _e('Hosted Accounts', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($account_summary['total_accounts'])): ?>
                        <div class="whmin-hosted-accounts-display">
                            <div class="whmin-stat-big-inline" style="color: <?php echo esc_attr($settings['counter_color'] ?? '#075b63'); ?>;">
                                <?php echo esc_html($account_summary['total_accounts']); ?>
                            </div>
                            <p class="whmin-stat-label-inline"><?php _e('Total Hosted Accounts', 'whmin'); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="whmin-error"><?php _e('No hosted account data available', 'whmin'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MySQL Information -->
            <div class="whmin-card whmin-detail-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-database"></i> <?php _e('MySQL Information', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (!isset($mysql_info['error'])): ?>
                        <ul class="whmin-detail-list">
                            <li>
                                <span><?php _e('Status', 'whmin'); ?></span>
                                <strong class="whmin-status-badge whmin-status-<?php echo esc_attr($mysql_info['status']); ?>">
                                    <?php echo esc_html(ucfirst($mysql_info['status'])); ?>
                                </strong>
                            </li>
                            <?php if (isset($mysql_info['version'])): ?>
                                <li>
                                    <span><?php _e('Version', 'whmin'); ?></span>
                                    <strong><?php echo esc_html($mysql_info['version']); ?></strong>
                                </li>
                            <?php endif; ?>
                            <li>
                                <span><?php _e('Accounts with DB Access', 'whmin'); ?></span>
                                <strong><?php echo esc_html($mysql_info['accounts_with_db']); ?></strong>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($mysql_info['error']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SSL Information -->
            <div class="whmin-card whmin-detail-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-lock-check-outline"></i> <?php _e('SSL Information', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <?php if (isset($ssl_info['summary']) && !isset($ssl_info['error'])): ?>
                        <ul class="whmin-detail-list">
                            <?php foreach ($ssl_info['summary'] as $label => $value): ?>
                                <li>
                                    <span><?php echo esc_html($label); ?></span>
                                    <strong><?php echo esc_html($value); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="whmin-error"><?php echo esc_html($ssl_info['error'] ?? __('No SSL data available', 'whmin')); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Version Info -->
            <?php if (isset($system_info['version']) && !empty($system_info['version'])): ?>
            <div class="whmin-card whmin-detail-card">
                <div class="whmin-card-header">
                    <h4><i class="mdi mdi-information-outline"></i> <?php _e('System Version', 'whmin'); ?></h4>
                </div>
                <div class="whmin-card-body">
                    <ul class="whmin-detail-list">
                        <?php foreach ($system_info['version'] as $label => $value): ?>
                            <li>
                                <span><?php echo esc_html($label); ?></span>
                                <strong><?php echo esc_html($value); ?></strong>
                            </li>
                        <?php endforeach; ?>
                        <?php if (isset($system_info['packages_available'])): ?>
                            <li>
                                <span><?php _e('Available Packages', 'whmin'); ?></span>
                                <strong><?php echo esc_html($system_info['packages_available']); ?></strong>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Package Distribution -->
        <?php if (isset($account_summary['packages']) && !empty($account_summary['packages'])): ?>
        <div class="whmin-card">
            <div class="whmin-card-header">
                <h4><i class="mdi mdi-package-variant"></i> <?php _e('Package Distribution', 'whmin'); ?></h4>
            </div>
            <div class="whmin-card-body">
                <div class="whmin-packages-grid">
                    <div class="whmin-packages-chart">
                        <canvas id="packagesChart"></canvas>
                    </div>
                    <div class="whmin-packages-list">
                        <ul class="whmin-package-items">
                            <?php 
                            $package_colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336', '#00BCD4', '#FFC107', '#E91E63'];
                            $color_index = 0;
                            foreach ($account_summary['packages'] as $package => $count): 
                            ?>
                                <li class="whmin-package-item">
                                    <span class="whmin-package-color" style="background-color: <?php echo esc_attr($package_colors[$color_index % count($package_colors)]); ?>"></span>
                                    <span class="whmin-package-name"><?php echo esc_html($package); ?></span>
                                    <span class="whmin-package-count"><?php echo esc_html($count); ?></span>
                                </li>
                            <?php 
                                $color_index++;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hosted Servers Status Table -->
        <?php 
        $direct_status = whmin_get_direct_sites_detailed_status();
        $direct_stats  = whmin_get_status_statistics($direct_status);

        if (!empty($direct_status)): 
        ?>
        <div class="whmin-card whmin-status-table-card">
            <div class="whmin-card-header">
                <div class="whmin-status-table-header">
                    <div>
                        <h3><i class="mdi mdi-server"></i> <?php _e('Hosted on Our Servers - Detailed Status', 'whmin'); ?></h3>
                        <p><?php _e('Real-time status monitoring for all hosted websites', 'whmin'); ?></p>
                    </div>
                    <div class="whmin-status-overview">
                        <span class="whmin-status-pill whmin-pill-success"><?php echo esc_html($direct_stats['operational']); ?> <?php _e('Up', 'whmin'); ?></span>
                        <span class="whmin-status-pill whmin-pill-warning"><?php echo esc_html($direct_stats['degraded']); ?> <?php _e('Degraded', 'whmin'); ?></span>
                        <span class="whmin-status-pill whmin-pill-danger"><?php echo esc_html($direct_stats['down']); ?> <?php _e('Down', 'whmin'); ?></span>
                        <?php if ($direct_stats['deactivated'] > 0): ?>
                        <span class="whmin-status-pill whmin-pill-muted"><?php echo esc_html($direct_stats['deactivated']); ?> <?php _e('Disabled', 'whmin'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="whmin-card-body whmin-table-container">
                <table class="whmin-status-table">
                    <thead>
                        <tr>
                            <th><?php _e('Website Name', 'whmin'); ?></th>
                            <th><?php _e('URL', 'whmin'); ?></th>
                            <th><?php _e('Status', 'whmin'); ?></th>
                            <th><?php _e('Response Time', 'whmin'); ?></th>
                            <th><?php _e('Last Checked', 'whmin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($direct_status as $site): ?>
                        <tr class="whmin-status-row whmin-status-<?php echo esc_attr($site['status']); ?>">
                            <td class="whmin-site-name" data-label="<?php _e('Website', 'whmin'); ?>">
                                <strong><?php echo esc_html($site['name']); ?></strong>
                                <span class="whmin-site-user"><?php echo esc_html($site['user']); ?></span>
                            </td>
                            <td class="whmin-site-url" data-label="<?php _e('URL', 'whmin'); ?>">
                                <a href="<?php echo esc_url($site['url']); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html(parse_url($site['url'], PHP_URL_HOST)); ?>
                                    <i class="mdi mdi-open-in-new"></i>
                                </a>
                            </td>
                            <td class="whmin-site-status" data-label="<?php _e('Status', 'whmin'); ?>">
                                <?php echo whmin_get_status_badge_html($site['status'], $site['status_code']); ?>
                            </td>
                            <td class="whmin-site-response" data-label="<?php _e('Response', 'whmin'); ?>">
                                <?php echo whmin_format_response_time($site['response_time']); ?>
                            </td>
                            <td class="whmin-site-lastcheck" data-label="<?php _e('Last Check', 'whmin'); ?>">
                                <?php 
                                if ($site['last_check'] > 0) {
                                    echo esc_html(human_time_diff($site['last_check'], current_time('timestamp'))) . ' ' . __('ago', 'whmin');
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Managed Servers Status Table (unchanged) -->
        <?php 
        $indirect_status = whmin_get_indirect_sites_detailed_status();
        $indirect_stats  = whmin_get_status_statistics($indirect_status);
        if (!empty($indirect_status)): 
        ?>
        <div class="whmin-card whmin-status-table-card">
            <div class="whmin-card-header">
                <div class="whmin-status-table-header">
                    <div>
                        <h3><i class="mdi mdi-cloud-check"></i> <?php _e('Managed on Other Servers - Detailed Status', 'whmin'); ?></h3>
                        <p><?php _e('Real-time status monitoring for managed websites on external hosting', 'whmin'); ?></p>
                    </div>
                    <div class="whmin-status-overview">
                        <span class="whmin-status-pill whmin-pill-success"><?php echo esc_html($indirect_stats['operational']); ?> <?php _e('Up', 'whmin'); ?></span>
                        <span class="whmin-status-pill whmin-pill-warning"><?php echo esc_html($indirect_stats['degraded']); ?> <?php _e('Degraded', 'whmin'); ?></span>
                        <span class="whmin-status-pill whmin-pill-danger"><?php echo esc_html($indirect_stats['down']); ?> <?php _e('Down', 'whmin'); ?></span>
                    </div>
                </div>
            </div>
            <div class="whmin-card-body whmin-table-container">
                <table class="whmin-status-table">
                    <thead>
                        <tr>
                            <th><?php _e('Website Name', 'whmin'); ?></th>
                            <th><?php _e('URL', 'whmin'); ?></th>
                            <th><?php _e('Hosting Provider', 'whmin'); ?></th>
                            <th><?php _e('Status', 'whmin'); ?></th>
                            <th><?php _e('Response Time', 'whmin'); ?></th>
                            <th><?php _e('Last Checked', 'whmin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indirect_status as $site): ?>
                        <tr class="whmin-status-row whmin-status-<?php echo esc_attr($site['status']); ?>">
                            <td class="whmin-site-name">
                                <strong><?php echo esc_html($site['name']); ?></strong>
                            </td>
                            <td class="whmin-site-url">
                                <a href="<?php echo esc_url($site['url']); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html(parse_url($site['url'], PHP_URL_HOST)); ?>
                                    <i class="mdi mdi-open-in-new"></i>
                                </a>
                            </td>
                            <td class="whmin-site-hosting">
                                <?php echo esc_html($site['hosting']); ?>
                            </td>
                            <td class="whmin-site-status">
                                <?php echo whmin_get_status_badge_html($site['status'], $site['status_code']); ?>
                            </td>
                            <td class="whmin-site-response">
                                <?php echo whmin_format_response_time($site['response_time']); ?>
                            </td>
                            <td class="whmin-site-lastcheck">
                                <?php 
                                if ($site['last_check'] > 0) {
                                    echo esc_html(human_time_diff($site['last_check'], current_time('timestamp'))) . ' ' . __('ago', 'whmin');
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section (if enabled) -->
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

        <!-- Maintenance News Section (if enabled) -->
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

<?php
// Build list of hosted (direct) site keys that are deactivated to FILTER OUT from hosted charts only
$direct_status_for_filter = whmin_get_direct_sites_detailed_status();
$direct_deactivated_keys = array_values(array_map(
    function($s){ return $s['user']; },
    array_filter($direct_status_for_filter, function($s){ return ($s['status'] ?? '') === 'deactivated'; })
));
?>

<script>
// Pass data to JavaScript (private charts)
window.whminServerData = {
    systemLoad: <?php echo json_encode($system_info['load'] ?? []); ?>,
    packages:   <?php echo json_encode($account_summary['packages'] ?? []); ?>
};

// Hosted graph filter: strip deactivated DIRECT sites from WHMIN_Public_Data.history.direct
window.WHMIN_Filter = window.WHMIN_Filter || {};
window.WHMIN_Filter.excludeDirectKeys = <?php echo json_encode($direct_deactivated_keys); ?>;

(function waitAndFilterDirectHistory(){
    var tries = 0, maxTries = 200; // ~2s @ 10ms
    var iv = setInterval(function(){
        tries++;
        if (window.WHMIN_Public_Data && window.WHMIN_Public_Data.history && window.WHMIN_Public_Data.history.direct) {
            (window.WHMIN_Filter.excludeDirectKeys || []).forEach(function(k){
                if (k && window.WHMIN_Public_Data.history.direct[k]) {
                    delete window.WHMIN_Public_Data.history.direct[k];
                }
            });
            clearInterval(iv);
        } else if (tries >= maxTries) {
            clearInterval(iv);
        }
    }, 10);
})();
</script>
