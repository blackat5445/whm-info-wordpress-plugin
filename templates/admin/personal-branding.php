<?php
if (!defined('ABSPATH')) exit;

$settings = whmin_get_branding_settings();

// Get image URLs for previews
$logo_url = $settings['logo_id'] ? wp_get_attachment_image_url($settings['logo_id'], 'medium') : '';
$favicon_url = $settings['favicon_id'] ? wp_get_attachment_image_url($settings['favicon_id'], 'thumbnail') : '';
?>
<div class="card whmin-card shadow-lg border-0 mb-4">
    <div class="card-body p-4">
        <h3 class="card-title mb-1"><i class="mdi mdi-palette-outline text-primary me-2"></i> <?php _e('Personal Branding', 'whmin'); ?></h3>
        <p class="text-muted"><?php _e('Customize the look and feel of the public status page.', 'whmin'); ?></p>
    </div>
</div>

<form method="post" action="options.php" class="whmin-settings-form">
    <?php settings_fields('whmin_branding_settings'); ?>

    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <div class="row">
                <!-- Logo Uploader -->
                <div class="col-md-6">
                    <h4 class="card-title mb-3"><?php _e('Custom Logo', 'whmin'); ?></h4>
                    <p class="text-muted"><?php _e('Upload a logo to replace the default one in the header of the status page. Recommended size: 200x50px.', 'whmin'); ?></p>
                    <div class="whmin-image-uploader" data-input-id="whmin_logo_id" data-preview-id="whmin_logo_preview">
                        <div id="whmin_logo_preview" class="whmin-image-preview mb-3" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                            <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; height: auto;" />
                        </div>
                        <input type="hidden" id="whmin_logo_id" name="whmin_branding_settings[logo_id]" value="<?php echo esc_attr($settings['logo_id']); ?>">
                        <button type="button" class="button whmin-upload-btn" style="<?php echo $logo_url ? 'display:none;' : ''; ?>"><?php _e('Upload Logo', 'whmin'); ?></button>
                        <button type="button" class="button whmin-remove-btn" style="<?php echo $logo_url ? '' : 'display:none;'; ?>"><?php _e('Remove Logo', 'whmin'); ?></button>
                    </div>
                </div>
                
                <!-- Favicon Uploader -->
                <div class="col-md-6">
                    <h4 class="card-title mb-3"><?php _e('Custom Favicon', 'whmin'); ?></h4>
                    <p class="text-muted"><?php _e('Upload a favicon to be used on the public status page. Recommended size: 32x32px or 64x64px.', 'whmin'); ?></p>
                    <div class="whmin-image-uploader" data-input-id="whmin_favicon_id" data-preview-id="whmin_favicon_preview">
                        <div id="whmin_favicon_preview" class="whmin-image-preview mb-3" style="<?php echo $favicon_url ? '' : 'display:none;'; ?>">
                            <img src="<?php echo esc_url($favicon_url); ?>" style="max-width: 64px; height: auto;" />
                        </div>
                        <input type="hidden" id="whmin_favicon_id" name="whmin_branding_settings[favicon_id]" value="<?php echo esc_attr($settings['favicon_id']); ?>">
                        <button type="button" class="button whmin-upload-btn" style="<?php echo $favicon_url ? 'display:none;' : ''; ?>"><?php _e('Upload Favicon', 'whmin'); ?></button>
                        <button type="button" class="button whmin-remove-btn" style="<?php echo $favicon_url ? '' : 'display:none;'; ?>"><?php _e('Remove Favicon', 'whmin'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-3"><?php _e('Footer Note', 'whmin'); ?></h4>
            <p class="text-muted"><?php _e('This content will appear in the footer of the public status page. You can use basic HTML like links and bold text.', 'whmin'); ?></p>
            <?php
            wp_editor($settings['footer_note'], 'whmin_footer_note', [
                'textarea_name' => 'whmin_branding_settings[footer_note]',
                'media_buttons' => false,
                'textarea_rows' => 5,
                'teeny' => true,
            ]);
            ?>
        </div>

        <div>
            <label class="form-label" for="whmin_footer_link"><?php _e('"Powered By" Link', 'whmin'); ?></label>
            <p class="text-muted small"><?php _e('Enter the URL for the "Powered by" link if you are using the default footer note.', 'whmin'); ?></p>
            <input type="url" id="whmin_footer_link" class="form-control" name="whmin_branding_settings[footer_link]" value="<?php echo esc_attr($settings['footer_link']); ?>" placeholder="https://www.your-agency.com">
        </div>
    </div>
    
    <div class="whmin-save-button-container">
        <?php submit_button(); ?>
    </div>
</form>