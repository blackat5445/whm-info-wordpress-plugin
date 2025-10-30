<?php
if (!defined('ABSPATH')) exit;
?>
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1">
            <i class="mdi mdi-shield-lock-outline text-primary me-2"></i>
            <?php _e('Private Settings', 'whmin'); ?>
        </h3>
        <p class="text-muted">
            <?php _e('Configure private or internal settings for the plugin that are not exposed publicly.', 'whmin'); ?>
        </p>
    </div>
</div>

<!-- Manual Data Refresh Card -->
<div class="card whmin-card shadow-lg border-0 mt-4">
    <div class="card-body p-4">
        <h4 class="card-title mb-3">
            <i class="mdi mdi-refresh text-primary me-2"></i>
            <?php _e('Manual Data Refresh', 'whmin'); ?>
        </h4>
        
        <div class="row">
            <!-- Status Data Refresh -->
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <h5 class="mb-2">
                        <i class="mdi mdi-web-check me-2 text-success"></i>
                        <?php _e('Site Status Data', 'whmin'); ?>
                    </h5>
                    <p class="text-muted small mb-3">
                        <?php _e('The plugin automatically checks site statuses every 15 minutes. Click below to force an immediate refresh of all site status checks.', 'whmin'); ?>
                    </p>
                    <button id="manual-status-refresh-btn" class="btn btn-success w-100">
                        <i class="mdi mdi-web-refresh me-2"></i>
                        <?php _e('Refresh Site Statuses', 'whmin'); ?>
                    </button>
                    <div id="last-status-check" class="mt-2 text-center small text-muted">
                        <?php 
                        $last_check = get_option('whmin_last_status_check', 0);
                        if ($last_check > 0) {
                            printf(
                                __('Last checked: %s ago', 'whmin'),
                                human_time_diff($last_check, current_time('timestamp'))
                            );
                        } else {
                            _e('Never checked', 'whmin');
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Server Data Refresh -->
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <h5 class="mb-2">
                        <i class="mdi mdi-server me-2 text-primary"></i>
                        <?php _e('Server & WHM Data', 'whmin'); ?>
                    </h5>
                    <p class="text-muted small mb-3">
                        <?php _e('Fetch fresh data from your WHM server including disk usage, bandwidth, accounts, and system information.', 'whmin'); ?>
                    </p>
                    <button id="manual-server-refresh-btn" class="btn btn-primary w-100">
                        <i class="mdi mdi-server-network me-2"></i>
                        <?php _e('Refresh Server Data', 'whmin'); ?>
                    </button>
                    <div class="mt-2 text-center small text-muted">
                        <?php _e('Updates dashboard charts and statistics', 'whmin'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Next Scheduled Check Info -->
        <div class="alert alert-info mt-3 mb-0">
            <i class="mdi mdi-information-outline me-2"></i>
            <strong><?php _e('Next Automatic Check:', 'whmin'); ?></strong>
            <?php echo whmin_get_next_cron_time(); ?>
        </div>
    </div>
</div>

<!-- Future Settings Placeholder -->
<div class="card whmin-card shadow-lg border-0 mt-4">
    <div class="card-body p-4">
        <h4 class="card-title mb-3">
            <i class="mdi mdi-cog-outline text-primary me-2"></i>
            <?php _e('Advanced Settings', 'whmin'); ?>
        </h4>
        <p class="text-muted">
            <?php _e('Additional private configuration options will appear here in future updates.', 'whmin'); ?>
        </p>
    </div>
</div>