<?php
if (!defined('ABSPATH')) exit;

$sites_data = whmin_get_indirect_sites_data();
$hosting_providers = whmin_get_hosting_providers();
?>
<!-- Section Header Card -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1">
            <i class="mdi mdi-transit-connection-variant text-primary me-2"></i>
            <?php _e('In-direct Connected Websites', 'whmin'); ?>
        </h3>
        <p class="text-muted">
            <?php _e('Manually add and manage websites that are not connected via the WHM API.', 'whmin'); ?>
        </p>
    </div>
</div>

<!-- Main Content Card -->
<div class="card whmin-card shadow-lg border-0 animate__animated animate__fadeInUp">
    <div class="card-body p-4">
        <!-- Controls: Search and Add Button -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" id="indirect-site-search-input" class="form-control border-0" placeholder="<?php _e('Search websites...', 'whmin'); ?>">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button id="add-new-indirect-site-btn" class="btn btn-primary">
                    <i class="mdi mdi-plus me-2"></i><?php _e('Add New Website', 'whmin'); ?>
                </button>
            </div>
        </div>

        <?php if (empty($sites_data)): ?>
            <div id="no-sites-placeholder" class="text-center p-5">
                <i class="mdi mdi-web-off mdi-4x text-muted mb-3"></i>
                <h4 class="mb-3"><?php _e('No Websites Added Yet', 'whmin'); ?></h4>
                <p class="text-muted"><?php _e('Click the "Add New Website" button to get started.', 'whmin'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Data Table (initially hidden if no data) -->
        <div class="whmin-data-table-container" id="indirect-sites-table-container" <?php echo empty($sites_data) ? 'style="display: none;"' : ''; ?>>
            <div class="table-responsive">
                <table class="table table-hover align-middle whmin-data-table" id="indirect-sites-table">
                    <thead>
                        <tr>
                            <th scope="col" class="sortable-header" data-sort="number">#</th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Website Name', 'whmin'); ?></th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('URL', 'whmin'); ?></th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Hosting', 'whmin'); ?></th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Connection', 'whmin'); ?></th>
                            <th scope="col" class="text-center"><?php _e('Status', 'whmin'); ?></th>
                            <th scope="col" class="text-center"><?php _e('Actions', 'whmin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites_data as $site): ?>
                            <tr id="<?php echo esc_attr($site['uid']); ?>" data-site-data="<?php echo esc_attr(json_encode($site)); ?>">
                                <th scope="row"><?php echo $site['id']; ?></th>
                                <td class="site-name"><?php echo esc_html($site['name']); ?></td>
                                <td><a href="<?php echo esc_url($site['url']); ?>" target="_blank" class="text-decoration-none"><?php echo esc_html($site['url']); ?></a></td>
                                <td class="site-hosting"><?php echo esc_html($site['hosting']); ?></td>
                                <td class="site-connection"><?php echo esc_html($site['connection']); ?></td>
                                <td class="text-center">
                                    <?php
                                        $site_status = $site['status'] ?? 'not_activated';
                                        if ($site_status === 'activated') {
                                            $status = ['text' => __('Activated', 'whmin'), 'class' => 'success'];
                                        } elseif ($site_status === 'activated_manual') {
                                            $status = ['text' => __('Active (Manual)', 'whmin'), 'class' => 'info'];
                                        } else {
                                            $status = ['text' => __('Not Activated', 'whmin'), 'class' => 'secondary'];
                                        }
                                    ?>
                                    <span class="badge bg-<?php echo esc_attr($status['class']); ?>-light text-<?php echo esc_attr($status['class']); ?> rounded-pill">
                                        <?php echo esc_html($status['text']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary modify-indirect-site-btn" data-bs-toggle="tooltip" title="<?php _e('Modify', 'whmin'); ?>"><i class="mdi mdi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger remove-indirect-site-btn" data-bs-toggle="tooltip" title="<?php _e('Remove', 'whmin'); ?>"><i class="mdi mdi-delete-outline"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination / Load More -->
        <div id="pagination-controls" class="text-center mt-4" <?php echo empty($sites_data) ? 'style="display: none;"' : ''; ?>>
            <button id="load-more-btn" class="btn btn-primary"><?php _e('Load More', 'whmin'); ?></button>
        </div>
        <div id="no-results-message" class="text-center p-5" style="display: none;">
             <i class="mdi mdi-magnify-remove-outline mdi-4x text-muted mb-3"></i>
             <h4 class="mb-3"><?php _e('No Matching Websites', 'whmin'); ?></h4>
             <p class="text-muted"><?php _e('Try a different search term.', 'whmin'); ?></p>
        </div>
        
        <!-- Hidden select options for JS -->
        <div id="hosting-options" style="display:none;">
            <?php foreach ($hosting_providers as $group => $providers): ?>
                <optgroup label="<?php echo esc_attr($group); ?>">
                    <?php foreach ($providers as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </div>
    </div>
</div>