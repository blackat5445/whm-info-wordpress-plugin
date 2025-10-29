<?php
if (!defined('ABSPATH')) exit;
$settings = whmin_get_public_settings();
$timeframe_options = [
    '24h' => '24 Hours', '7d' => '7 Days', '1m' => '1 Month', '3m' => '3 Months',
    '6m' => '6 Months', '12m' => '1 Year', '60m' => '5 Years'
];
?>
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1"><i class="mdi mdi-earth text-primary me-2"></i> <?php _e('Public Page Settings', 'whmin'); ?></h3>
        <p class="text-muted"><?php _e('Customize the appearance and content of your public status page.', 'whmin'); ?></p>
    </div>
</div>

<div class="card whmin-card shadow-lg border-0 mt-4">
    <div class="card-body p-4">
        <h4 class="card-title mb-3"><?php _e('Manual Data Refresh', 'whmin'); ?></h4>
        <p class="text-muted"><?php _e('The plugin automatically checks site statuses every 15 minutes. Click the button below to force an immediate refresh.', 'whmin'); ?></p>
        <button id="manual-refresh-btn" class="btn btn-primary"><i class="mdi mdi-refresh me-2"></i> <?php _e('Refresh Status Data Now', 'whmin'); ?></button>
    </div>
</div>

<form method="post" action="options.php" class="whmin-settings-form">
    <?php settings_fields('whmin_public_page_settings'); ?>

    <!-- WHM Server Uptime Graph Settings -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="enable_server_graph" name="whmin_public_settings[enable_server_graph]" value="1" <?php checked(true, $settings['enable_server_graph']); ?>>
                <label class="form-check-label" for="enable_server_graph"><h4 class="card-title d-inline"><?php _e('Hosted Servers Uptime Graph', 'whmin'); ?></h4></label>
            </div>
            <div class="whmin-settings-group" data-dependency="enable_server_graph">
                <hr>
                <h6><?php _e('Visible Timeframes', 'whmin'); ?></h6>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <?php foreach ($timeframe_options as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="<?php echo esc_attr($key); ?>" id="server_tf_<?php echo esc_attr($key); ?>" name="whmin_public_settings[server_graph_timeframes][]" <?php checked(in_array($key, $settings['server_graph_timeframes'])); ?>>
                            <label class="form-check-label" for="server_tf_<?php echo esc_attr($key); ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <h6><?php _e('Color Options', 'whmin'); ?></h6>
                <div class="row">
                    <div class="col-md-4"><label class="form-label">Graph Bar Color:</label><input type="color" class="form-control" name="whmin_public_settings[server_graph_bar_color]" value="<?php echo esc_attr($settings['server_graph_bar_color']); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Button Background:</label><input type="color" class="form-control" name="whmin_public_settings[server_graph_button_bg]" value="<?php echo esc_attr($settings['server_graph_button_bg']); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Button Text Color:</label><input type="color" class="form-control" name="whmin_public_settings[server_graph_button_text]" value="<?php echo esc_attr($settings['server_graph_button_text']); ?>"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Managed Servers Uptime Graph Settings -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="enable_managed_graph" name="whmin_public_settings[enable_managed_graph]" value="1" <?php checked(true, $settings['enable_managed_graph']); ?>>
                <label class="form-check-label" for="enable_managed_graph"><h4 class="card-title d-inline"><?php _e('Managed Servers Uptime Graph', 'whmin'); ?></h4></label>
            </div>
            <div class="whmin-settings-group" data-dependency="enable_managed_graph">
                <hr>
                <h6><?php _e('Visible Timeframes', 'whmin'); ?></h6>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <?php foreach ($timeframe_options as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="<?php echo esc_attr($key); ?>" id="managed_tf_<?php echo esc_attr($key); ?>" name="whmin_public_settings[managed_graph_timeframes][]" <?php checked(in_array($key, $settings['managed_graph_timeframes'])); ?>>
                            <label class="form-check-label" for="managed_tf_<?php echo esc_attr($key); ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <h6><?php _e('Color Options', 'whmin'); ?></h6>
                <div class="row">
                    <div class="col-md-4"><label class="form-label">Graph Bar Color:</label><input type="color" class="form-control" name="whmin_public_settings[managed_graph_bar_color]" value="<?php echo esc_attr($settings['managed_graph_bar_color']); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Button Background:</label><input type="color" class="form-control" name="whmin_public_settings[managed_graph_button_bg]" value="<?php echo esc_attr($settings['managed_graph_button_bg']); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Button Text Color:</label><input type="color" class="form-control" name="whmin_public_settings[managed_graph_button_text]" value="<?php echo esc_attr($settings['managed_graph_button_text']); ?>"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Site Counter Section -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
             <h4 class="card-title mb-3"><?php _e('Site Counter Section', 'whmin'); ?></h4>
             <div class="row align-items-center">
                 <div class="col-md-8">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="enable_hosted_counter" name="whmin_public_settings[enable_hosted_counter]" value="1" <?php checked(true, $settings['enable_hosted_counter']); ?>><label class="form-check-label" for="enable_hosted_counter">Enable "Hosted on Our Servers"</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="enable_managed_counter" name="whmin_public_settings[enable_managed_counter]" value="1" <?php checked(true, $settings['enable_managed_counter']); ?>><label class="form-check-label" for="enable_managed_counter">Enable "Managed on Other Servers"</label></div>
                    <div class="form-check form-switch mb-2 ps-5" data-dependency="enable_managed_counter"><input class="form-check-input" type="checkbox" id="show_hosting_breakdown" name="whmin_public_settings[show_hosting_breakdown]" value="1" <?php checked(true, $settings['show_hosting_breakdown']); ?>><label class="form-check-label" for="show_hosting_breakdown">Show hosting provider breakdown</label></div>
                 </div>
                 <div class="col-md-4"><label class="form-label">Counter Number Color:</label><input type="color" class="form-control" name="whmin_public_settings[counter_color]" value="<?php echo esc_attr($settings['counter_color']); ?>"></div>
             </div>
        </div>
    </div>
    
    <!-- Maintenance Section -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="enable_maintenance_news" name="whmin_public_settings[enable_maintenance_news]" value="1" <?php checked(true, $settings['enable_maintenance_news']); ?>>
                <label class="form-check-label" for="enable_maintenance_news"><h4 class="card-title d-inline"><?php _e('Maintenance & News Section', 'whmin'); ?></h4></label>
            </div>
        </div>
    </div>

    <div class="whmin-save-button-container">
        <?php submit_button(); ?>
    </div>
</form>

