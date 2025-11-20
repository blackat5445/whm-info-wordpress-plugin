<?php
if (!defined('ABSPATH')) exit;

// Fetch the website data
$sites_data = whmin_get_direct_connected_sites_data();
$whm_url = get_option('whmin_whm_server_url');
?>

<!-- Section Header Card -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1">
            <i class="mdi mdi-lan-connect text-primary me-2"></i>
            <?php _e('Direct Connected Websites', 'whmin'); ?>
        </h3>
        <p class="text-muted">
            <?php _e('Manage websites connected directly via the WHM API.', 'whmin'); ?>
        </p>
    </div>
</div>

<!-- Main Content Card -->
<div class="card whmin-card shadow-lg border-0 animate__animated animate__fadeInUp">
    <div class="card-body p-4">
        <?php if (is_wp_error($sites_data) || empty($sites_data)): ?>
            <div class="text-center p-5">
                <i class="mdi mdi-alert-circle-outline mdi-4x text-warning mb-3"></i>
                <h4 class="mb-3"><?php _e('No Websites Found', 'whmin'); ?></h4>
                <?php if (is_wp_error($sites_data)): ?>
                    <p class="text-danger"><?php echo esc_html($sites_data->get_error_message()); ?></p>
                <?php endif; ?>
                <p class="text-muted">
                    <?php _e('This could be because your API settings are incorrect, or no cPanel accounts exist for your user.', 'whmin'); ?>
                </p>
                <div class="mt-4">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=whmin-settings&tab=api_settings')); ?>" class="btn btn-primary btn-lg me-2">
                        <i class="mdi mdi-key-variant me-2"></i>
                        <?php _e('Check API Settings', 'whmin'); ?>
                    </a>
                    <?php if (!empty($whm_url)): ?>
                    <a href="<?php echo esc_url(rtrim($whm_url, '/')); ?>" target="_blank" class="btn btn-outline-secondary btn-lg">
                        <i class="mdi mdi-plus-circle-outline me-2"></i>
                        <?php _e('Add Account in WHM', 'whmin'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Search Bar -->
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" id="site-search-input" class="form-control border-0" placeholder="<?php _e('Search websites...', 'whmin'); ?>">
                </div>
            </div>

            <!-- Data Table Container -->
            <div class="whmin-data-table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle whmin-data-table" id="direct-sites-table">
                        <thead>
                            <tr>
                                <th scope="col" class="sortable-header" data-sort="number">#</th>
                                <th scope="col" class="sortable-header" data-sort="string"><?php _e('Website', 'whmin'); ?></th>
                                <th scope="col"><?php _e('Services & Expiration', 'whmin'); ?></th> <!-- NEW -->
                                <th scope="col" class="sortable-header" data-sort="number"><?php _e('Disk', 'whmin'); ?></th>
                                <th scope="col" class="text-center sortable-header" data-sort="string"><?php _e('Status', 'whmin'); ?></th>
                                <th scope="col" class="text-center"><?php _e('Action', 'whmin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sites_data as $site): ?>
                            <tr id="site-<?php echo esc_attr($site['user']); ?>" data-json="<?php echo esc_attr(json_encode($site['services'])); ?>">
                                <th scope="row"><?php echo $site['id']; ?></th>
                                
                                <td>
                                    <strong class="site-name"><?php echo $site['name']; ?></strong><br>
                                    <a href="<?php echo $site['url']; ?>" target="_blank" class="small text-muted text-decoration-none"><?php echo $site['url']; ?></a>
                                    <div class="small text-muted mt-1">
                                        <?php printf(__('Setup: %s', 'whmin'), $site['setup_date']); ?>
                                        <!-- Connection Status -->
                                        <?php if($site['connection_status'] === 'activated'): ?>
                                            <span class="badge bg-success-light text-success ms-1" style="font-size:0.65em;">Agent Active</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- NEW: Services Column -->
                                <td class="small">
                                    <?php 
                                    if (!empty($site['services']['items'])) {
                                        $count = 0;
                                        foreach ($site['services']['items'] as $item) {
                                            if ($count >= 2) {
                                                echo '<div class="text-muted"><em>+' . (count($site['services']['items']) - 2) . ' ' . __('more', 'whmin') . '...</em></div>';
                                                break;
                                            }
                                            
                                            $exp_date = $item['unlimited'] ? __('Unlimited', 'whmin') : $item['expiration_date'];
                                            $price = $item['price'] ? $item['price'] . '€' : '-';
                                            $is_expired = (!$item['unlimited'] && strtotime($item['expiration_date']) < time());
                                            $style = $is_expired ? 'text-danger fw-bold' : 'text-dark';
                                            
                                            echo '<div class="'.$style.'">';
                                            echo esc_html($item['name']) . ': ' . esc_html($price) . ' <span class="text-muted">(' . esc_html($exp_date) . ')</span>';
                                            if ($is_expired) echo ' <i class="mdi mdi-alert-circle" title="Expired"></i>';
                                            echo '</div>';
                                            $count++;
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>

                                <td data-value="<?php echo esc_attr($site['disk_used_bytes']); ?>"><?php echo $site['disk_used']; ?></td>

                                <!-- Status -->
                                <td class="text-center" data-value="<?php echo esc_attr($site['status']['text']); ?>">
                                    <span class="badge bg-<?php echo esc_attr($site['status']['class']); ?>-light text-<?php echo esc_attr($site['status']['class']); ?> rounded-pill">
                                        <?php echo esc_html($site['status']['text']); ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <!-- Edit Services -->
                                    <button
                                        class="btn btn-sm btn-outline-primary edit-site-btn"
                                        data-user="<?php echo esc_attr($site['user']); ?>"
                                        data-name="<?php echo esc_attr($site['name']); ?>"
                                        data-bs-toggle="tooltip"
                                        title="<?php _e('Edit Services & Info', 'whmin'); ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>

                                    <!-- Send Email -->
                                    <button
                                        class="btn btn-sm btn-outline-warning send-email-btn"
                                        data-user="<?php echo esc_attr($site['user']); ?>"
                                        data-bs-toggle="tooltip"
                                        title="<?php _e('Send Expiration Email', 'whmin'); ?>">
                                        <i class="mdi mdi-email-alert"></i>
                                    </button>
                                    
                                    <!-- Toggle Monitor -->
                                    <button
                                        class="btn btn-sm <?php echo $site['monitoring_enabled'] ? 'btn-success' : 'btn-outline-secondary'; ?> toggle-monitoring-btn"
                                        data-user="<?php echo esc_attr($site['user']); ?>"
                                        data-enabled="<?php echo $site['monitoring_enabled'] ? '1' : '0'; ?>"
                                        data-bs-toggle="tooltip"
                                        title="<?php _e('Toggle Monitoring', 'whmin'); ?>">
                                        <i class="mdi mdi-<?php echo $site['monitoring_enabled'] ? 'eye' : 'eye-off'; ?>"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="pagination-controls" class="text-center mt-4">
                <button id="load-more-btn" class="btn btn-primary"><?php _e('Load More', 'whmin'); ?></button>
            </div>
            <div id="no-results-message" class="text-center p-5" style="display: none;">
                <i class="mdi mdi-magnify-remove-outline mdi-4x text-muted mb-3"></i>
                <h4 class="mb-3"><?php _e('No Matching Websites', 'whmin'); ?></h4>
                <p class="text-muted"><?php _e('Try a different search term.', 'whmin'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>