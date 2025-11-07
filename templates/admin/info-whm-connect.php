<?php
if (!defined('ABSPATH')) {
    exit;
}

// Direct download URL for the child plugin zip
$whmin_connect_zip_url = WHMIN_PLUGIN_URL . 'Child-plugin/whm-info-connect.zip';
?>

<div class="wrap whmin-admin-page whmin-about-page">

    <!-- Main Title Card (Keep this as is - dark text on white) -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <h1 class="card-title mb-1" style="color: #ffffff !important;">
                <i class="mdi mdi-link-variant text-primary me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('WHM Info Connect — Site Agent', 'whmin'); ?>
            </h1>
            <p class="text-muted mb-0">
                <?php esc_html_e('Use the WHM Info Connect child plugin to send detailed WordPress metadata (PHP, theme, plugins, server info) from each site back to WHM Info.', 'whmin'); ?>
            </p>
        </div>
    </div>
    
    <!-- Download WHM Info Connect -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4 d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <h3 class="card-title mb-2" style="color: #ffffff !important;">
                    <i class="mdi mdi-download-circle-outline me-2" style="color: #0d6efd !important;"></i>
                    <?php esc_html_e('Download WHM Info Connect Plugin', 'whmin'); ?>
                </h3>
                <p class="mb-0 text-muted">
                    <?php esc_html_e('Download the child plugin ZIP file and upload it to each WordPress site you want to connect to WHM Info.', 'whmin'); ?>
                </p>
            </div>
            <div>
                <a href="<?php echo esc_url($whmin_connect_zip_url); ?>"
                   class="btn btn-primary btn-lg px-4"
                   download>
                    <i class="mdi mdi-download me-2"></i>
                    <?php esc_html_e('Download whm-info-connect.zip', 'whmin'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- What it does -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-information-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('What is WHM Info Connect?', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <p class="mb-3">
                <?php esc_html_e('WHM Info Connect is a lightweight child plugin that you install on each WordPress site you want to inspect in depth. It exposes a secure REST endpoint that WHM Info calls to retrieve PHP settings, WordPress information, active theme and plugins, and some server details.', 'whmin'); ?>
            </p>
            <ul class="list-unstyled mb-0">
                <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Works for both WHM-hosted sites and external (indirect) sites', 'whmin'); ?></li>
                <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Uses a shared API URL and API key configured in WHM Info', 'whmin'); ?></li>
                <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Data is pulled over the endpoint /wp-json/whmin-connect/v1/site-meta', 'whmin'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Step 1: Get API URL + Key -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-key-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Step 1 — Get the API URL & Key from WHM Info', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <ol class="whmin-ordered-steps">
                <li>
                    <strong><?php esc_html_e('Open WHM Info settings', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Go to WHM INFO → Settings → API Settings.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Locate the Connect / Agent section', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Copy the generated API URL and API key that will be used by the WHM Info Connect plugin.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Keep them safe', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Treat the API key like a password. Only paste it into trusted sites you control.', 'whmin'); ?>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Step 2: Install on a WordPress site -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-wordpress me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Step 2 — Install WHM Info Connect on a WordPress site', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <ol class="whmin-ordered-steps">
                <li>
                    <strong><?php esc_html_e('Upload the ZIP', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('On the target WordPress site, go to Plugins → Add New → Upload Plugin and select whm-info-connect.zip from your Child-plugin folder.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Activate the plugin', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Click “Activate” once the upload completes.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Open the WHM Info Connect settings', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('A new menu item (for example “WHM Info Connect” under Settings or in the sidebar) will appear. Open it.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Paste the API URL & key', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Paste the API URL and API key you copied from WHM Info and save the settings.', 'whmin'); ?>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Step 3: Direct (WHM) sites -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-server me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Step 3 — Connect Direct (WHM-hosted) Websites', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <p class="mb-3 text-muted">
                <?php esc_html_e('For sites that are hosted on the same WHM server you connected in the API settings, WHM Info already knows about the accounts. The Connect plugin simply adds deep WordPress metadata.', 'whmin'); ?>
            </p>
            <ol class="whmin-ordered-steps">
                <li>
                    <strong><?php esc_html_e('Install WHM Info Connect on each WordPress site', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Repeat Step 2 for every WHM account that runs WordPress.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Use the same API URL & key everywhere', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('All direct sites use the same endpoint and key that you configured in WHM Info.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Verify the connection', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('In the Private Dashboard tables, the “More Information” column will switch from “Agent not connected” to a clickable link when the site starts sending data.', 'whmin'); ?>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Step 4: Indirect (external) sites -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-cloud-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Step 4 — Connect Indirect (External) Websites', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <p class="mb-3 text-muted">
                <?php esc_html_e('For websites hosted on other providers, you add them first as indirect sites in WHM Info, then you connect their WordPress via WHM Info Connect.', 'whmin'); ?>
            </p>

            <ol class="whmin-ordered-steps">
                <li>
                    <strong><?php esc_html_e('Add the site in WHM Info', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Go to WHM INFO → Settings → Indirect Connected Websites and add the external site (URL, label, provider, etc.).', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Install WHM Info Connect on that WordPress site', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('On the external WordPress site, upload and activate whm-info-connect.zip, then paste the same API URL & key you use for direct sites.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Match URL and monitoring settings', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Make sure the URL in the Indirect Connected Websites tab matches the site’s real URL and that monitoring is enabled.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Confirm “Agent connected” state', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Once WHM Info successfully calls the /wp-json/whmin-connect/v1/site-meta endpoint, the site will show as agent-connected and the “More information” link will be available.', 'whmin'); ?>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-alert-circle-outline text-warning me-2" style="color: #ffa700 !important;"></i>
                <?php esc_html_e('Troubleshooting', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <ul class="list-unstyled mb-0">
                <li>• <?php esc_html_e('Ensure the API URL and key in WHM Info Connect exactly match those in WHM Info → API Settings.', 'whmin'); ?></li>
                <li>• <?php esc_html_e('Verify that the site is publicly reachable over HTTPS and that /wp-json/ is not blocked by security plugins or firewalls.', 'whmin'); ?></li>
                <li>• <?php esc_html_e('Check that monitoring is enabled for the site in Direct or Indirect Connected Websites.', 'whmin'); ?></li>
                <li>• <?php esc_html_e('Use the manual “Refresh Site Metadata” / “Refresh Agent Data” button in the Private Settings tab if you added the agent recently.', 'whmin'); ?></li>
            </ul>
        </div>
    </div>
</div>