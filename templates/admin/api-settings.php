<?php
/**
 * API Settings Template
 * 
 * @package WHM_Info
 * @subpackage Templates/Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get saved values
$whm_server_url = get_option('whmin_whm_server_url', '');
$whm_username = get_option('whmin_whm_username', '');
$whm_api_token = get_option('whmin_whm_api_token', '');
$binoculars_token = get_option('whmin_binoculars_api_token', '');
$binoculars_enabled = get_option('whmin_binoculars_api_enabled', false);
$binoculars_metadata = get_option('whmin_binoculars_api_generated', array());
?>

<div class="whmin-settings-container animate__animated animate__fadeIn">
    <div class="container-fluid p-0">
        <!-- Header Section -->
        <div class="whmin-header-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="whmin-page-title animate__animated animate__fadeInLeft">
                        <i class="mdi mdi-key-variant text-primary me-3"></i>
                        <?php _e('API Settings', 'whmin'); ?>
                    </h1>
                    <p class="text-muted animate__animated animate__fadeInLeft animate__delay-1s">
                        <?php _e('Configure WHM API connection and manage Binoculars API tokens', 'whmin'); ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="whmin-status-indicator animate__animated animate__fadeInRight">
                        <span class="badge bg-primary rounded-pill px-3 py-2">
                            <i class="mdi mdi-power-plug me-2"></i>
                            <span id="connection-status"><?php _e('Ready to Connect', 'whmin'); ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-pills whmin-custom-tabs mb-4 animate__animated animate__fadeInUp" id="apiSettingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="whm-api-tab" data-bs-toggle="pill" data-bs-target="#whm-api" type="button" role="tab" aria-controls="whm-api" aria-selected="true">
                    <i class="mdi mdi-server me-2"></i>
                    <?php _e('WHM API', 'whmin'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="binoculars-api-tab" data-bs-toggle="pill" data-bs-target="#binoculars-api" type="button" role="tab" aria-controls="binoculars-api" aria-selected="false">
                    <i class="mdi mdi-binoculars me-2"></i>
                    <?php _e('Binoculars API', 'whmin'); ?>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content animate__animated animate__fadeIn" id="apiSettingsTabContent">
            
            <!-- WHM API Tab -->
            <div class="tab-pane fade show active" id="whm-api" role="tabpanel" aria-labelledby="whm-api-tab">
                <div class="card whmin-card shadow-lg border-0">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-lg-8">
                                <h3 class="card-title mb-4">
                                    <i class="mdi mdi-server-network text-primary me-2"></i>
                                    <?php _e('WHM Server Configuration', 'whmin'); ?>
                                </h3>
                                
                                <form id="whm-api-form" class="whmin-form">
                                    <div class="mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                                        <label for="whm_server_url" class="form-label fw-bold">
                                            <i class="mdi mdi-web me-2 text-muted"></i>
                                            <?php _e('Server URL', 'whmin'); ?>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="mdi mdi-link-variant text-primary"></i>
                                            </span>
                                            <input type="url" 
                                                   class="form-control form-control-lg" 
                                                   id="whm_server_url" 
                                                   name="whm_server_url"
                                                   value="<?php echo esc_url($whm_server_url); ?>"
                                                   placeholder="https://your-server.com:2087"
                                                   required>
                                        </div>
                                        <small class="form-text text-muted">
                                            <?php _e('Enter your WHM server URL with port (usually 2087)', 'whmin'); ?>
                                        </small>
                                    </div>

                                    <div class="mb-4 animate__animated animate__fadeInUp animate__delay-2s">
                                        <label for="whm_username" class="form-label fw-bold">
                                            <i class="mdi mdi-account me-2 text-muted"></i>
                                            <?php _e('Username', 'whmin'); ?>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="mdi mdi-account-star text-primary"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control form-control-lg" 
                                                   id="whm_username" 
                                                   name="whm_username"
                                                   value="<?php echo esc_attr($whm_username); ?>"
                                                   placeholder="root or reseller username"
                                                   required>
                                        </div>
                                        <small class="form-text text-muted">
                                            <?php _e('Your WHM username (usually root)', 'whmin'); ?>
                                        </small>
                                    </div>

                                    <div class="mb-4 animate__animated animate__fadeInUp animate__delay-3s">
                                        <label for="whm_api_token" class="form-label fw-bold">
                                            <i class="mdi mdi-key me-2 text-muted"></i>
                                            <?php _e('API Token', 'whmin'); ?>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="mdi mdi-lock text-primary"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control form-control-lg" 
                                                   id="whm_api_token" 
                                                   name="whm_api_token"
                                                   value="<?php echo esc_attr($whm_api_token); ?>"
                                                   placeholder="Enter your WHM API token"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggle-whm-token">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">
                                            <?php _e('Generate this token in WHM > Manage API Tokens', 'whmin'); ?>
                                        </small>
                                    </div>

                                    <div class="d-flex gap-3 animate__animated animate__fadeInUp animate__delay-4s">
                                        <button type="submit" class="btn btn-primary btn-lg px-4">
                                            <i class="mdi mdi-content-save me-2"></i>
                                            <?php _e('Save Settings', 'whmin'); ?>
                                        </button>
                                        <button type="button" id="test-connection" class="btn btn-outline-success btn-lg px-4">
                                            <i class="mdi mdi-power-plug me-2"></i>
                                            <?php _e('Test Connection', 'whmin'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card bg-light border-0 animate__animated animate__fadeInRight animate__delay-2s">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="mdi mdi-information-outline text-primary me-2"></i>
                                            <?php _e('Quick Guide', 'whmin'); ?>
                                        </h5>
                                        <ol class="small">
                                            <li class="mb-2"><?php _e('Log in to your WHM panel', 'whmin'); ?></li>
                                            <li class="mb-2"><?php _e('Navigate to "Manage API Tokens"', 'whmin'); ?></li>
                                            <li class="mb-2"><?php _e('Create a new API token', 'whmin'); ?></li>
                                            <li class="mb-2"><?php _e('Copy and paste the token here', 'whmin'); ?></li>
                                            <li><?php _e('Test the connection', 'whmin'); ?></li>
                                        </ol>
                                    </div>
                                </div>
                                
                                <div class="card mt-3 border-0 shadow-sm animate__animated animate__fadeInRight animate__delay-3s">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-shield-check-outline mdi-3x text-success mb-3"></i>
                                        <h6><?php _e('Secure Connection', 'whmin'); ?></h6>
                                        <p class="small text-muted mb-0">
                                            <?php _e('Your API credentials are encrypted and stored securely', 'whmin'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Binoculars API Tab -->
            <div class="tab-pane fade" id="binoculars-api" role="tabpanel" aria-labelledby="binoculars-api-tab">
                <div class="card whmin-card shadow-lg border-0">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-lg-8">
                                <h3 class="card-title mb-4">
                                    <i class="mdi mdi-binoculars text-primary me-2"></i>
                                    <?php _e('Binoculars API Management', 'whmin'); ?>
                                </h3>
                                
                                <div class="alert alert-info mb-4 animate__animated animate__fadeInUp">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    <?php _e('Generate an API token to allow external WordPress sites to connect to this plugin.', 'whmin'); ?>
                                </div>

                                <?php if ($binoculars_token): ?>
                                    <div class="binoculars-token-section animate__animated animate__fadeInUp animate__delay-1s">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">
                                                <i class="mdi mdi-key-variant me-2 text-muted"></i>
                                                <?php _e('Current API Token', 'whmin'); ?>
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="binoculars_token_display" 
                                                       value="<?php echo esc_attr($binoculars_token); ?>" 
                                                       readonly>
                                                <button class="btn btn-outline-secondary" type="button" id="toggle-binoculars-token">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button class="btn btn-primary" type="button" id="copy-token">
                                                    <i class="mdi mdi-content-copy me-2"></i>
                                                    <?php _e('Copy', 'whmin'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="token-info mb-4">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-card p-3 bg-light rounded animate__animated animate__fadeInLeft animate__delay-2s">
                                                        <i class="mdi mdi-check-circle-outline text-success me-2"></i>
                                                        <strong><?php _e('Status:', 'whmin'); ?></strong>
                                                        <?php if ($binoculars_enabled): ?>
                                                            <span class="badge bg-success ms-2"><?php _e('Active', 'whmin'); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary ms-2"><?php _e('Inactive', 'whmin'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-card p-3 bg-light rounded animate__animated animate__fadeInRight animate__delay-2s">
                                                        <i class="mdi mdi-calendar-blank-outline text-primary me-2"></i>
                                                        <strong><?php _e('Generated:', 'whmin'); ?></strong>
                                                        <span class="ms-2">
                                                            <?php 
                                                            if (!empty($binoculars_metadata['timestamp'])) {
                                                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($binoculars_metadata['timestamp'])));
                                                            } else {
                                                                _e('Unknown', 'whmin');
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-3 animate__animated animate__fadeInUp animate__delay-3s">
                                            <button type="button" id="regenerate-token" class="btn btn-warning btn-lg px-4">
                                                <i class="mdi mdi-sync me-2"></i>
                                                <?php _e('Regenerate Token', 'whmin'); ?>
                                            </button>
                                            <button type="button" id="revoke-token" class="btn btn-danger btn-lg px-4">
                                                <i class="mdi mdi-close-circle-outline me-2"></i>
                                                <?php _e('Revoke Token', 'whmin'); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="generate-token-section text-center py-5 animate__animated animate__fadeInUp">
                                        <i class="mdi mdi-key-plus mdi-4x text-muted mb-4"></i>
                                        <h4 class="mb-3"><?php _e('No API Token Generated', 'whmin'); ?></h4>
                                        <p class="text-muted mb-4">
                                            <?php _e('Generate your first API token to enable external connections.', 'whmin'); ?>
                                        </p>
                                        <button type="button" id="generate-token" class="btn btn-primary btn-lg px-5 py-3">
                                            <i class="mdi mdi-creation me-2"></i>
                                            <?php _e('Generate API Token', 'whmin'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card bg-light border-0 animate__animated animate__fadeInRight animate__delay-2s">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="mdi mdi-code-tags text-primary me-2"></i>
                                            <?php _e('API Endpoints', 'whmin'); ?>
                                        </h5>
                                        <div class="small">
                                            <p class="mb-2">
                                                <code class="text-dark"><?php echo esc_url(rest_url('whmin/v1/status')); ?></code>
                                            </p>
                                            <p class="mb-2">
                                                <code class="text-dark"><?php echo esc_url(rest_url('whmin/v1/server-info')); ?></code>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mt-3 bg-gradient border-0 text-white animate__animated animate__fadeInRight animate__delay-3s" style="background: linear-gradient(135deg, #075b63, #0a8a96);">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="mdi mdi-power-plug-outline me-2"></i>
                                            <?php _e('Integration', 'whmin'); ?>
                                        </h5>
                                        <p class="small mb-2">
                                            <?php _e('Add this header to your API requests:', 'whmin'); ?>
                                        </p>
                                        <code class="text-white small">X-API-Token: YOUR_TOKEN</code>
                                    </div>
                                </div>
                                
                                <?php if ($binoculars_token): ?>
                                <div class="card mt-3 border-0 shadow-sm animate__animated animate__fadeInRight animate__delay-4s">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="mdi mdi-history text-warning me-2"></i>
                                            <?php _e('Token History', 'whmin'); ?>
                                        </h6>
                                        <?php 
                                        $history = get_option('whmin_binoculars_api_history', array());
                                        if (!empty($history)): ?>
                                            <ul class="list-unstyled small">
                                                <?php foreach(array_slice($history, -3) as $entry): ?>
                                                    <li class="mb-1">
                                                        <i class="mdi mdi-clock-outline text-muted me-1"></i>
                                                        <?php echo esc_html(date_i18n('M j, Y', strtotime($entry['timestamp']))); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="small text-muted mb-0"><?php _e('No regeneration history', 'whmin'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
