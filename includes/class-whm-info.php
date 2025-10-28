<?php
if (!defined('ABSPATH')) {
    exit;
}

class WHMIN {
    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    public static function activate() {
        // Placeholder for future setup (capabilities/options/etc.)
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    private function includes() {
        // Settings
        require_once WHMIN_PLUGIN_DIR . 'includes/settings/api-settings.php';
        require_once WHMIN_PLUGIN_DIR . 'includes/settings/direct-connected-websites.php';
        require_once WHMIN_PLUGIN_DIR . 'includes/settings/sites-settings.php';

        // Shortcodes
        require_once WHMIN_PLUGIN_DIR . 'includes/shortcodes/dashboard.php';

        // API
        require_once WHMIN_PLUGIN_DIR . 'includes/api/whm-api.php';

        // Functions
        require_once WHMIN_PLUGIN_DIR . 'includes/functions/server-status.php';
        require_once WHMIN_PLUGIN_DIR . 'includes/functions/sites-status.php';

        // Utilities
        require_once WHMIN_PLUGIN_DIR . 'includes/util/uninstall-confirm.php';
    }

    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('whmin', false, dirname(WHMIN_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Admin assets
     * - local bootstrap (css + js)
     * - MDI Icons (CDN)
     * - animate.css (CDN)
     * - Toastr (CDN)
     * - main admin css
     * - main admin js
     */
    public function enqueue_admin_assets($hook) {
        // Keep wp-admin lean; only load on our pages.
        $page = $_GET['page'] ?? '';
        $tab = $_GET['tab'] ?? 'api_settings';
        
        if (strpos($page, 'whmin') !== 0 && strpos($hook, 'whmin') === false) {
            return;
        }

        $ver = defined('WHMIN_VERSION') ? WHMIN_VERSION : '1.0.0';

        // Styles
        wp_enqueue_style(
            'whmin-bootstrap',
            WHMIN_PLUGIN_URL . 'assets/vendor/bootstrap/css/bootstrap.min.css',
            array(),
            $ver
        );

        wp_enqueue_style(
            'whmin-mdi-icons',
            'https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css',
            array(),
            '7.2.96'
        );

        wp_enqueue_style(
            'whmin-animate',
            'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
            array(),
            '4.1.1'
        );

        wp_enqueue_style(
            'whmin-toastr',
            'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
            array(),
            '2.1.4'
        );

        wp_enqueue_style(
            'whmin-admin',
            WHMIN_PLUGIN_URL . 'assets/admin/css/admin.css',
            array('whmin-bootstrap', 'whmin-mdi-icons', 'whmin-animate'),
            $ver
        );

        // Scripts
        wp_enqueue_script(
            'whmin-bootstrap',
            WHMIN_PLUGIN_URL . 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
            array('jquery'),
            $ver,
            true
        );

        wp_enqueue_script(
            'whmin-toastr',
            'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
            array('jquery'),
            '2.1.4',
            true
        );

        wp_enqueue_script(
            'whmin-admin',
            WHMIN_PLUGIN_URL . 'assets/admin/js/admin.js',
            array('jquery', 'whmin-bootstrap', 'whmin-toastr'),
            $ver,
            true
        );

        wp_enqueue_script(
            'sweetalert2', 
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', 
            [], 
            '11.7.20', 
            true
        );

        if ($page === 'whmin-settings') {
            wp_enqueue_script(
                'whmin-api-settings', 
                WHMIN_PLUGIN_URL . 'assets/admin/js/admin-api-settings.js', 
                ['jquery', 'sweetalert2', 'whmin-admin'], // Depends on the general admin script
                $ver, 
                true
            );

            if ($tab === 'direct_connected') {
                wp_enqueue_script(
                    'whmin-direct-connected', 
                    WHMIN_PLUGIN_URL . 'assets/admin/js/admin-direct-connected.js', 
                    ['jquery', 'sweetalert2', 'whmin-admin'],
                    $ver, 
                    true
                );
            }
        }

        wp_localize_script('whmin-admin', 'WHMIN_Admin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('whmin_admin_nonce'),
        ));
    }

    /**
     * Public assets
     * - styles.css
     * - local bootstrap (CSS)
     */
    public function enqueue_public_assets() {
        if (is_admin()) return;

        $ver = defined('WHMIN_VERSION') ? WHMIN_VERSION : '1.0.0';

        wp_enqueue_style(
            'whmin-bootstrap',
            WHMIN_PLUGIN_URL . 'assets/vendor/bootstrap/css/bootstrap.min.css',
            array(),
            $ver
        );

        wp_enqueue_style(
            'whmin-styles',
            WHMIN_PLUGIN_URL . 'assets/public/css/styles.css',
            array('whmin-bootstrap'),
            $ver
        );
    }

    /**
     * Admin Menu:
     * - Top: WHM INFO (opens Settings)
     * - Sub: Settings
     * - Sub: About
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WHM INFO', 'whmin'),
            __('WHM INFO', 'whmin'),
            'manage_options',
            'whmin-settings',
            array($this, 'render_settings_page'),
            'dashicons-admin-generic',
            65
        );

        add_submenu_page(
            'whmin-settings',
            __('Settings', 'whmin'),
            __('Settings', 'whmin'),
            'manage_options',
            'whmin-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'whmin-settings',
            __('About', 'whmin'),
            __('About', 'whmin'),
            'manage_options',
            'whmin-about',
            array($this, 'render_about_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        // If your settings files add settings sections/fields, include a template:
        $settings_tpl = WHMIN_PLUGIN_DIR . 'templates/settings.php';
        if (file_exists($settings_tpl)) {
            include $settings_tpl;
            return;
        }

        echo '<div class="wrap whmin-admin-page">';
        echo '<h1>' . esc_html__('WHM Info — Settings', 'whmin') . '</h1>';
        do_action('whmin_render_settings');
        echo '</div>';
    }

    public function render_about_page() {
        if (!current_user_can('manage_options')) return;

        // Per your note: render from templates/about.php
        $about_tpl = WHMIN_PLUGIN_DIR . 'templates/about.php';
        if (file_exists($about_tpl)) {
            include $about_tpl;
            return;
        }

        // Fallback if template is missing (dev-friendly)
        echo '<div class="wrap whmin-admin-page">';
        echo '<h1>' . esc_html__('About WHM Info', 'whmin') . '</h1>';
        echo '<p>' . esc_html__('Create templates/about.php to customize this page.', 'whmin') . '</p>';
        echo '</div>';
    }
}