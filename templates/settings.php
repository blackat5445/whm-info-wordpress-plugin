<?php
/**
 * Main Settings Page Template
 *
 * This template acts as a router to load the different settings tabs.
 *
 * @package WHM_Info
 * @subpackage Templates/Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

$active_tab = $_GET['tab'] ?? 'api_settings';

?>
<div class="wrap whmin-admin-page">
    <!-- Header Section -->
    <div class="whmin-header-section mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="whmin-page-title animate__animated animate__fadeInLeft">
                    <i class="mdi mdi-cog-outline text-primary me-3"></i>
                    <?php _e('WHM Info Settings', 'whmin'); ?>
                </h1>
                <p class="text-muted animate__animated animate__fadeInLeft animate__delay-1s">
                    <?php _e('Manage all plugin settings from the tabs below.', 'whmin'); ?>
                </p>
            </div>
            <div class="col-md-auto text-end">
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
    <nav class="nav nav-pills whmin-custom-tabs mb-4 animate__animated animate__fadeInUp">
        <a class="nav-link <?php echo $active_tab === 'api_settings' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=api_settings">
            <i class="mdi mdi-key-variant me-2"></i> <?php _e('API Settings', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'direct_connected' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=direct_connected">
            <i class="mdi mdi-lan-connect me-2"></i> <?php _e('Direct Connected Sites', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'indirect_connected' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=indirect_connected">
            <i class="mdi mdi-transit-connection-variant me-2"></i> <?php _e('Indirect Connected Sites', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'public_settings' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=public_settings">
            <i class="mdi mdi-earth me-2"></i> <?php _e('Public Settings', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'private_settings' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=private_settings">
            <i class="mdi mdi-shield-lock-outline me-2"></i> <?php _e('Private Settings', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=notifications">
            <i class="mdi mdi-bell-outline me-2"></i> <?php _e('Notification Settings', 'whmin'); ?>
        </a>
        <a class="nav-link <?php echo $active_tab === 'branding' ? 'active' : ''; ?>" href="?page=whmin-settings&tab=branding">
            <i class="mdi mdi-palette-outline me-2"></i> <?php _e('Personal Branding', 'whmin'); ?>
        </a>
    </nav>

    <!-- Tab Content -->
    <div class="tab-content animate__animated animate__fadeIn" id="whmin-settings-tab-content">
        <?php
        // Load the content of the active tab
        switch ($active_tab) {
            case 'direct_connected':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/direct-connected-websites.php';
                break;
            case 'indirect_connected':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/in-direct-connected-websites.php';
                break;
            case 'public_settings':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/public-settings.php';
                break;
            case 'private_settings':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/private-settings.php';
                break;
            case 'notifications':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/notification-settings.php';
                break;
            case 'branding':
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/personal-branding.php';
                break;
            case 'api_settings':
            default:
                require_once WHMIN_PLUGIN_DIR . 'templates/admin/api-settings.php';
                break;
        }
        ?>
    </div>
</div>