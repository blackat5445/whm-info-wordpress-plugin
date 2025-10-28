<?php
/**
 * Main Settings Template
 * 
 * @package WHM_Info
 * @subpackage Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Include the API settings template
require_once WHMIN_PLUGIN_DIR . 'templates/admin/api-settings.php';


