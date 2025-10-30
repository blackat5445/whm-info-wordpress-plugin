<?php
if (!defined('ABSPATH')) exit;
$recipients_data = whmin_get_notification_recipients();
?>
<!-- Section Header Card -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1"><i class="mdi mdi-bell-outline text-primary me-2"></i> <?php _e('Notification Settings', 'whmin'); ?></h3>
        <p class="text-muted"><?php _e('Add and manage recipients who will receive email notifications for service status events.', 'whmin'); ?></p>
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
                    <input type="text" id="recipient-search-input" class="form-control border-0" placeholder="<?php _e('Search recipients...', 'whmin'); ?>">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button id="add-new-recipient-btn" class="btn btn-primary">
                    <i class="mdi mdi-plus me-2"></i><?php _e('Add New Recipient', 'whmin'); ?>
                </button>
            </div>
        </div>

        <?php if (empty($recipients_data)): ?>
            <div id="no-recipients-placeholder" class="text-center p-5">
                <i class="mdi mdi-account-off-outline mdi-4x text-muted mb-3"></i>
                <h4 class="mb-3"><?php _e('No Recipients Added Yet', 'whmin'); ?></h4>
                <p class="text-muted"><?php _e('Click the "Add New Recipient" button to get started.', 'whmin'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="whmin-data-table-container" id="recipients-table-container" <?php echo empty($recipients_data) ? 'style="display: none;"' : ''; ?>>
            <div class="table-responsive">
                <table class="table table-hover align-middle whmin-data-table" id="recipients-table">
                    <thead>
                        <tr>
                            <th scope="col" class="sortable-header" data-sort="number">#</th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Name', 'whmin'); ?></th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Email', 'whmin'); ?></th>
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Telephone', 'whmin'); ?></th>
                            <th scope="col" class="text-center"><?php _e('Actions', 'whmin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients_data as $recipient): ?>
                            <tr id="<?php echo esc_attr($recipient['uid']); ?>" data-recipient-data="<?php echo esc_attr(json_encode($recipient)); ?>">
                                <th scope="row"><?php echo $recipient['id']; ?></th>
                                <td class="recipient-name"><?php echo esc_html($recipient['name']); ?></td>
                                <td class="recipient-email"><?php echo esc_html($recipient['email']); ?></td>
                                <td class="recipient-telephone"><?php echo esc_html($recipient['telephone']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary modify-recipient-btn" data-bs-toggle="tooltip" title="<?php _e('Modify', 'whmin'); ?>"><i class="mdi mdi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger remove-recipient-btn" data-bs-toggle="tooltip" title="<?php _e('Remove', 'whmin'); ?>"><i class="mdi mdi-delete-outline"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="no-results-message" class="text-center p-5" style="display: none;">
             <i class="mdi mdi-magnify-remove-outline mdi-4x text-muted mb-3"></i>
             <h4 class="mb-3"><?php _e('No Matching Recipients', 'whmin'); ?></h4>
             <p class="text-muted"><?php _e('Try a different search term.', 'whmin'); ?></p>
        </div>
    </div>
</div>