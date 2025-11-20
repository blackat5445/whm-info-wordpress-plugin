<?php
if (!defined('ABSPATH')) exit;

$recipients_data        = whmin_get_notification_recipients();
$notification_settings  = whmin_get_notification_settings();
$current_interval       = $notification_settings['interval'];
$text_settings          = whmin_get_notification_texts();
$auto_settings          = whmin_get_auto_expiration_settings();

// Get all sites for the multi-select
$sites_data = whmin_get_direct_connected_sites_data();
$enabled_sites = $auto_settings['enabled_sites'];
?>
<!-- Section Header Card -->
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1">
            <i class="mdi mdi-bell-outline text-primary me-2"></i>
            <?php _e('Notification Settings', 'whmin'); ?>
        </h3>
        <p class="text-muted">
            <?php _e('Add and manage recipients who will receive notifications for service status events.', 'whmin'); ?>
        </p>
    </div>
</div>

<!-- Main Content Card (Recipients Table) -->
<div class="card whmin-card shadow-lg border-0 animate__animated animate__fadeInUp">
    <div class="card-body p-4">
        <!-- Controls: Search and Add Button -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0">
                        <i class="mdi mdi-magnify"></i>
                    </span>
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
                            <th scope="col" class="sortable-header" data-sort="string"><?php _e('Channels', 'whmin'); ?></th>
                            <th scope="col" class="text-center"><?php _e('Actions', 'whmin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients_data as $recipient): ?>
                            <tr id="<?php echo esc_attr($recipient['uid']); ?>"
                                data-recipient-data="<?php echo esc_attr(json_encode($recipient)); ?>">
                                <th scope="row"><?php echo (int) $recipient['id']; ?></th>
                                <td class="recipient-name"><?php echo esc_html($recipient['name']); ?></td>
                                <td class="recipient-email"><?php echo esc_html($recipient['email']); ?></td>
                                <td class="recipient-telephone"><?php echo esc_html($recipient['telephone']); ?></td>
                                <td class="recipient-channels">
                                    <?php if (!empty($recipient['notify_email'])): ?>
                                        <span class="badge bg-primary">
                                            <i class="mdi mdi-email-outline me-1"></i><?php _e('Email', 'whmin'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small me-2">
                                            <?php _e('Email disabled', 'whmin'); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="badge bg-light text-muted border ms-1">
                                        <i class="mdi mdi-telegram me-1"></i>
                                        <?php _e('Telegram (coming soon)', 'whmin'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary modify-recipient-btn" data-bs-toggle="tooltip" title="<?php _e('Modify', 'whmin'); ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger remove-recipient-btn" data-bs-toggle="tooltip" title="<?php _e('Remove', 'whmin'); ?>">
                                        <i class="mdi mdi-delete-outline"></i>
                                    </button>
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

<!-- Global Notification Behaviour + Customizable Texts + Auto Emails + Save/Test Buttons -->
<form method="post" action="options.php" class="whmin-settings-form">
    <?php settings_fields('whmin_notification_settings'); ?>
    <?php settings_fields('whmin_notification_texts'); ?>
    <?php settings_fields('whmin_auto_expiration_settings'); ?>

    <!-- 1. Global Interval Settings -->
    <div class="card whmin-card shadow-lg border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-tune-variant text-primary me-2"></i>
                <?php _e('Global Notification Behaviour', 'whmin'); ?>
            </h4>
            <p class="text-muted">
                <?php _e('These settings control how often all recipients are notified when uptime drops below 100%.', 'whmin'); ?>
            </p>

            <div class="row align-items-center mt-3">
                <div class="col-md-4">
                    <label for="whmin_notification_interval" class="form-label mb-0">
                        <?php _e('Notification frequency', 'whmin'); ?>
                    </label>
                </div>
                <div class="col-md-8">
                    <select id="whmin_notification_interval"
                            name="whmin_notification_settings[interval]"
                            class="form-select">
                        <option value="server_refresh" <?php selected('server_refresh', $current_interval); ?>>
                            <?php _e('Based on server refresh schedule', 'whmin'); ?>
                        </option>
                        <option value="30min" <?php selected('30min', $current_interval); ?>>
                            <?php _e('Every 30 minutes (if still down)', 'whmin'); ?>
                        </option>
                        <option value="1h" <?php selected('1h', $current_interval); ?>>
                            <?php _e('Every 1 hour (if still down)', 'whmin'); ?>
                        </option>
                        <option value="3h" <?php selected('3h', $current_interval); ?>>
                            <?php _e('Every 3 hours (if still down)', 'whmin'); ?>
                        </option>
                        <option value="12h" <?php selected('12h', $current_interval); ?>>
                            <?php _e('Every 12 hours (if still down)', 'whmin'); ?>
                        </option>
                        <option value="24h" <?php selected('24h', $current_interval); ?>>
                            <?php _e('Every 24 hours (if still down)', 'whmin'); ?>
                        </option>
                    </select>
                    <div class="form-text">
                        <?php _e('Notifications are sent immediately when uptime drops below 100% and again when everything is back to normal. The interval only controls extra reminders while issues persist.', 'whmin'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Automatic Expiration Emails -->
    <div class="card whmin-card shadow-lg border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-email-sync text-warning me-2"></i>
                <?php _e('Automatic Expiration Emails', 'whmin'); ?>
            </h4>
            <p class="text-muted small mb-4">
                <?php _e('Automatically send service expiration notifications to site owners based on expiration dates.', 'whmin'); ?>
            </p>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="whmin_auto_expiration_settings[enable_auto_emails]" 
                       id="enable_auto_emails" value="1" <?php checked($auto_settings['enable_auto_emails'], 1); ?>>
                <label class="form-check-label fw-bold" for="enable_auto_emails">
                    <?php _e('Enable automatic expiration emails', 'whmin'); ?>
                </label>
                <div class="form-text"><?php _e('When enabled, emails will be sent daily to site owners whose services are expiring soon or already expired.', 'whmin'); ?></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold"><?php _e('Days before expiration to notify', 'whmin'); ?></label>
                <input type="number" name="whmin_auto_expiration_settings[days_before]" class="form-control" 
                       value="<?php echo esc_attr($auto_settings['days_before']); ?>" min="1" max="90">
                <div class="form-text"><?php _e('Send notifications when services are expiring within this many days (default: 21)', 'whmin'); ?></div>
            </div>

            <div class="mb-0">
                <label class="form-label fw-bold"><?php _e('Enable automatic emails for these sites', 'whmin'); ?></label>
                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                    <?php if (!empty($sites_data) && !is_wp_error($sites_data)): ?>
                        <?php foreach ($sites_data as $site): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" 
                                       name="whmin_auto_expiration_settings[enabled_sites][]" 
                                       value="<?php echo esc_attr($site['user']); ?>" 
                                       id="site_<?php echo esc_attr($site['user']); ?>"
                                       <?php checked(in_array($site['user'], $enabled_sites)); ?>>
                                <label class="form-check-label" for="site_<?php echo esc_attr($site['user']); ?>">
                                    <strong><?php echo esc_html($site['name']); ?></strong>
                                    <small class="text-muted">(<?php echo esc_html($site['user']); ?>)</small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php _e('No sites available. Please add sites in Direct Connected Websites.', 'whmin'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-text"><?php _e('Only selected sites will receive automatic expiration notifications.', 'whmin'); ?></div>
            </div>
        </div>
    </div>

    <!-- 3. Customizable Expiration Email Texts -->
    <div class="card whmin-card shadow-lg border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-email-edit-outline text-primary me-2"></i>
                <?php _e('Expiration Email Customization', 'whmin'); ?>
            </h4>
            <p class="text-muted small mb-4">
                <?php _e('Customize the content of service expiration emails.', 'whmin'); ?>
            </p>

            <!-- Subject -->
            <div class="mb-4">
                <label class="form-label fw-bold"><?php _e('Email Subject', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[email_subject]" class="form-control" value="<?php echo esc_attr($text_settings['email_subject']); ?>">
                <div class="form-text"><?php _e('Use %site% to dynamically insert the website name.', 'whmin'); ?></div>
            </div>

            <!-- Greeting -->
            <div class="mb-4">
                <label class="form-label fw-bold"><?php _e('Greeting Text', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[greeting_text]" class="form-control" value="<?php echo esc_attr($text_settings['greeting_text']); ?>">
                <div class="form-text"><?php _e('The greeting at the beginning of the email (e.g., "Dear Client,").', 'whmin'); ?></div>
            </div>

            <!-- Table Headers -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: Service', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[table_header_service]" class="form-control" value="<?php echo esc_attr($text_settings['table_header_service']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: Price', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[table_header_price]" class="form-control" value="<?php echo esc_attr($text_settings['table_header_price']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: Expiration', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[table_header_expiration]" class="form-control" value="<?php echo esc_attr($text_settings['table_header_expiration']); ?>">
                </div>
            </div>

            <!-- Split View Texts -->
            <div class="row g-4">
                <!-- Already Expired Section -->
                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light h-100 border-danger" style="border-left-width: 4px !important;">
                        <h6 class="text-danger mb-3">
                            <i class="mdi mdi-alert-circle me-1"></i><?php _e('Section: Already Expired', 'whmin'); ?>
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?php _e('Header Title', 'whmin'); ?></label>
                            <input type="text" name="whmin_notification_texts[header_expired]" class="form-control form-control-sm" value="<?php echo esc_attr($text_settings['header_expired']); ?>">
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label small fw-bold"><?php _e('Body Text', 'whmin'); ?></label>
                            <textarea name="whmin_notification_texts[body_expired]" class="form-control form-control-sm" rows="4"><?php echo esc_textarea($text_settings['body_expired']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Expiring Soon Section -->
                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light h-100 border-warning" style="border-left-width: 4px !important;">
                        <h6 class="text-warning mb-3" style="color: #b58900 !important;">
                            <i class="mdi mdi-clock-alert-outline me-1"></i><?php _e('Section: Expiring Soon', 'whmin'); ?>
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?php _e('Header Title', 'whmin'); ?></label>
                            <input type="text" name="whmin_notification_texts[header_soon]" class="form-control form-control-sm" value="<?php echo esc_attr($text_settings['header_soon']); ?>">
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label small fw-bold"><?php _e('Body Text', 'whmin'); ?></label>
                            <textarea name="whmin_notification_texts[body_soon]" class="form-control form-control-sm" rows="4"><?php echo esc_textarea($text_settings['body_soon']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Text -->
            <div class="mt-4">
                <label class="form-label fw-bold"><?php _e('Email Footer Text', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[footer_text]" class="form-control" value="<?php echo esc_attr($text_settings['footer_text']); ?>">
            </div>
        </div>
    </div>

    <!-- 4. Renewal Email Customization -->
    <div class="card whmin-card shadow-lg border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-check-circle-outline text-success me-2"></i>
                <?php _e('Renewal Email Customization', 'whmin'); ?>
            </h4>
            <p class="text-muted small mb-4">
                <?php _e('Customize the content of service renewal confirmation emails.', 'whmin'); ?>
            </p>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Subject', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[renewal_subject]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_subject']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Greeting', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[renewal_greeting]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_greeting']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Header', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[renewal_header]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_header']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Body Text', 'whmin'); ?></label>
                <textarea name="whmin_notification_texts[renewal_body]" class="form-control" rows="3"><?php echo esc_textarea($text_settings['renewal_body']); ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: Service', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[renewal_table_header_service]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_table_header_service']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: Price', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[renewal_table_header_price]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_table_header_price']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><?php _e('Table Header: New Expiration', 'whmin'); ?></label>
                    <input type="text" name="whmin_notification_texts[renewal_table_header_new_expiration]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_table_header_new_expiration']); ?>">
                </div>
            </div>

            <div class="mb-0">
                <label class="form-label fw-bold"><?php _e('Footer Text', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[renewal_footer]" class="form-control" value="<?php echo esc_attr($text_settings['renewal_footer']); ?>">
            </div>
        </div>
    </div>

    <!-- 5. News Email Customization -->
    <div class="card whmin-card shadow-lg border-0 mt-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">
                <i class="mdi mdi-newspaper-variant-outline text-info me-2"></i>
                <?php _e('News/Blog Email Customization', 'whmin'); ?>
            </h4>
            <p class="text-muted small mb-4">
                <?php _e('Customize the content of news notification emails sent when new blog posts are published.', 'whmin'); ?>
            </p>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Subject', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[news_subject]" class="form-control" value="<?php echo esc_attr($text_settings['news_subject']); ?>">
                <div class="form-text"><?php _e('Use %title% for the post title.', 'whmin'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Greeting', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[news_greeting]" class="form-control" value="<?php echo esc_attr($text_settings['news_greeting']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Header', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[news_header]" class="form-control" value="<?php echo esc_attr($text_settings['news_header']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?php _e('Body Text', 'whmin'); ?></label>
                <textarea name="whmin_notification_texts[news_body]" class="form-control" rows="3"><?php echo esc_textarea($text_settings['news_body']); ?></textarea>
            </div>

            <div class="mb-0">
                <label class="form-label fw-bold"><?php _e('Footer Text', 'whmin'); ?></label>
                <input type="text" name="whmin_notification_texts[news_footer]" class="form-control" value="<?php echo esc_attr($text_settings['news_footer']); ?>">
            </div>
        </div>
    </div>

    <div class="whmin-save-button-container" style="margin-top: 20px;">
        <?php submit_button(__('Save Notification Settings', 'whmin'), 'primary', 'submit', false); ?>

        <button type="button"
                id="whmin-send-test-notification"
                class="btn btn-outline-secondary ms-2" style="padding: 0.75rem 2rem;">
            <i class="mdi mdi-email-fast-outline me-1" ></i>
            <?php _e('Send Test Notification to All Recipients', 'whmin'); ?>
        </button>

        <p class="form-text mt-2 text-muted">
            <?php _e('The test notification will be sent to every recipient with at least one channel enabled (Email and/or Telegram).', 'whmin'); ?>
        </p>
    </div>
</form>