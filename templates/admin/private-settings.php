<?php
if (!defined('ABSPATH')) exit;

// Get private settings 
$private_settings = whmin_get_private_settings();

// Current cron schedule slug
$current_cron_slug = isset($private_settings['status_cron_schedule'])
    ? $private_settings['status_cron_schedule']
    : 'whmin_15_minutes';

// Available cron options (slug => label)
$cron_options = array(
    'whmin_5_minutes'   => __('Every 5 Minutes', 'whmin'),
    'whmin_10_minutes'  => __('Every 10 Minutes', 'whmin'),
    'whmin_15_minutes'  => __('Every 15 Minutes', 'whmin'),
    'whmin_30_minutes'  => __('Every 30 Minutes', 'whmin'),
    'whmin_60_minutes'  => __('Every 60 Minutes', 'whmin'),
);

// Server data cache interval (minutes)
$server_cache_minutes = isset($private_settings['server_data_cache_minutes'])
    ? (int) $private_settings['server_data_cache_minutes']
    : 5;

// Site status HTTP timeout (seconds)
$site_timeout = isset($private_settings['site_status_timeout'])
    ? (int) $private_settings['site_status_timeout']
    : 8;

$last_check = (int) get_option('whmin_last_status_check', 0);
?>
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1">
            <i class="mdi mdi-shield-lock-outline text-primary me-2"></i>
            <?php _e('Private Settings', 'whmin'); ?>
        </h3>
        <p class="text-muted mb-0">
            <?php _e('Configure internal monitoring, refresh schedules and timeouts used by the private dashboard.', 'whmin'); ?>
        </p>
    </div>
</div>

<!-- Manual Site Status Refresh (full-width card) -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h4 class="card-title mb-3">
            <i class="mdi mdi-web-refresh text-primary me-2"></i>
            <?php _e('Manual Site Status Refresh', 'whmin'); ?>
        </h4>
        <p class="text-muted">
            <?php _e('The plugin automatically checks website statuses on a schedule. Use this button to force an immediate refresh of all monitored sites.', 'whmin'); ?>
        </p>
        <button id="manual-status-refresh-btn" class="btn btn-success">
            <i class="mdi mdi-web-refresh me-2"></i>
            <?php _e('Refresh Site Statuses Now', 'whmin'); ?>
        </button>

        <div class="mt-3 small text-muted">
            <?php if ($last_check > 0): ?>
                <div id="last-status-check">
                    <?php
                    printf(
                        __('Last automatic check: %s ago', 'whmin'),
                        human_time_diff($last_check, current_time('timestamp'))
                    );
                    ?>
                </div>
            <?php else: ?>
                <div id="last-status-check">
                    <?php _e('Last automatic check: never run', 'whmin'); ?>
                </div>
            <?php endif; ?>
            <div class="mt-1">
                <strong><?php _e('Next scheduled check:', 'whmin'); ?></strong>
                <?php echo whmin_get_next_cron_time(); ?>
            </div>
        </div>
    </div>
</div>

<!-- Manual Server / WHM Data Refresh (full-width card) -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h4 class="card-title mb-3">
            <i class="mdi mdi-server-network text-primary me-2"></i>
            <?php _e('Manual Server & WHM Data Refresh', 'whmin'); ?>
        </h4>
        <p class="text-muted">
            <?php _e('Refresh cached data fetched from your WHM server such as disk usage, bandwidth, accounts and system information used on the private dashboard.', 'whmin'); ?>
        </p>
        <button id="manual-server-refresh-btn" class="btn btn-primary">
            <i class="mdi mdi-server-refresh me-2"></i>
            <?php _e('Refresh Server Data Now', 'whmin'); ?>
        </button>
        <div class="mt-2 small text-muted">
            <?php _e('This will clear and rebuild the cached server data used for charts and statistics.', 'whmin'); ?>
        </div>
    </div>
</div>

<!-- Automation & Performance Settings -->
<form method="post" action="options.php" class="whmin-settings-form">
    <?php settings_fields('whmin_private_settings'); ?>

    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-timer-cog-outline text-primary me-2"></i>
                <?php _e('Automation & Performance', 'whmin'); ?>
            </h4>

            <!-- Cron interval for site status checks -->
            <div class="mb-4">
                <label for="whmin_status_cron_schedule" class="form-label">
                    <?php _e('Site Status Check Interval (Cron)', 'whmin'); ?>
                </label>
                <select
                    id="whmin_status_cron_schedule"
                    name="whmin_private_settings[status_cron_schedule]"
                    class="form-select"
                >
                    <?php foreach ($cron_options as $slug => $label): ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $current_cron_slug); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    <?php _e('Controls how often the background cron job checks all sites (affects both public and private dashboards).', 'whmin'); ?>
                </div>
            </div>

            <!-- Server data cache TTL -->
            <div class="mb-4">
                <label for="whmin_server_data_cache_minutes" class="form-label">
                    <?php _e('Server Data Refresh Interval (Private Dashboard Cache)', 'whmin'); ?>
                </label>
                <select
                    id="whmin_server_data_cache_minutes"
                    name="whmin_private_settings[server_data_cache_minutes]"
                    class="form-select"
                >
                    <?php
                    $server_ttl_choices = array(2, 5, 10, 30, 60);
                    foreach ($server_ttl_choices as $minutes):
                    ?>
                        <option value="<?php echo esc_attr($minutes); ?>" <?php selected($minutes, $server_cache_minutes); ?>>
                            <?php
                            printf(
                                _n('%d minute', '%d minutes', $minutes, 'whmin'),
                                $minutes
                            );
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    <?php _e('Controls how long WHM server statistics are cached before being refetched for the private dashboard.', 'whmin'); ?>
                </div>
            </div>

            <!-- Website status HTTP timeout -->
            <div class="mb-0">
                <label for="whmin_site_status_timeout" class="form-label">
                    <?php _e('Website Status Timeout (per site, in seconds)', 'whmin'); ?>
                </label>
                <input
                    type="number"
                    min="1"
                    max="60"
                    step="1"
                    class="form-control"
                    id="whmin_site_status_timeout"
                    name="whmin_private_settings[site_status_timeout]"
                    value="<?php echo esc_attr($site_timeout); ?>"
                >
                <div class="form-text">
                    <?php _e('Maximum time to wait for each website when checking status. Lower values make pages fail fast if a site is very slow.', 'whmin'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="whmin-save-button-container">
        <?php submit_button(); ?>
    </div>
</form>
