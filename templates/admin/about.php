<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap whmin-admin-page whmin-about-page">

    <!-- Main Title Card (Keep this as is - dark text on white) -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <!-- NOTE: Titles are set to white but have no background, making them invisible.
                 Assuming this is intentional based on your final request. -->
            <h1 class="card-title mb-1" style="color: #ffffff !important;">
                <i class="mdi mdi-server text-primary me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('WHM Info (Servers OverWatch)', 'whmin'); ?>
            </h1>
            <p class="text-muted mb-0">
                <?php esc_html_e('A comprehensive WordPress plugin that connects to your WHM server and turns raw server data into beautiful dashboards, public status pages, and smart notifications.', 'whmin'); ?>
            </p>
        </div>
    </div>

    <!-- Overview Card -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-0" style="color: #ffffff !important;">
                <i class="mdi mdi-eye-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('What is WHM Info?', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <p class="mb-3">
                <?php esc_html_e('WHM Info (Servers OverWatch) connects your WordPress site to your WHM server via the WHM API, giving you real-time visibility into server health, hosted websites, and historical uptime.', 'whmin'); ?>
            </p>

            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-muted mb-2"><?php esc_html_e('Key capabilities', 'whmin'); ?></h5>
                    <ul class="list-unstyled mb-0">
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Real-time server status and resource usage', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Direct and indirect website monitoring', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Historical uptime charts using Chart.js', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Public & private dashboards with shortcodes', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Email notifications for incidents and recovery', 'whmin'); ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5 class="text-muted mb-2"><?php esc_html_e('Technology & UX', 'whmin'); ?></h5>
                    <ul class="list-unstyled mb-0">
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Bootstrap 5 based UI with responsive layouts', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Material Design Icons & smooth animations', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Conditional asset loading for performance', 'whmin'); ?></li>
                        <li><span style="font-family: 'Material Design Icons'; color: #2ecc71; margin-right: 5px;">&#xF012C;</span> <?php esc_html_e('Branding options to match your identity', 'whmin'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<!-- Dashboards & Shortcodes -->
<div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-0" style="color: #ffffff !important;">
                <i class="mdi mdi-view-dashboard-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Dashboards & Shortcodes', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="mb-2"><?php esc_html_e('Public Status Page', 'whmin'); ?></h5>
                    <p class="text-muted mb-1">
                        <?php esc_html_e('Create a client-facing page that shows overall server status, hosted sites status, history charts, and your branding.', 'whmin'); ?>
                    </p>
                    <!-- START: COPYABLE SHORTCODE WRAPPER - Shortcode 1 -->
                    <div class="whmin-shortcode-wrapper d-flex align-items-center mb-2">
                        <pre id="shortcode-1" class="whmin-code-snippet mb-0 me-2" style="flex-grow: 1;"><code>[whmin_public_dashboard]</code></pre>
                        <i class="mdi mdi-content-copy whmin-copy-icon" 
                           onclick="whmin_copy_shortcode('shortcode-1', this)"
                           style="cursor: pointer; color: #0d6efd; font-size: 1.5em; transition: color 0.2s, transform 0.2s;"></i>
                    </div>
                    <!-- END: COPYABLE SHORTCODE WRAPPER -->
                    <p class="small text-muted mb-0">
                        <?php esc_html_e('Add this shortcode to any page (for example: "Server Status") to publish a public status dashboard.', 'whmin'); ?>
                    </p>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-2"><?php esc_html_e('Private Admin Dashboard', 'whmin'); ?></h5>
                    <p class="text-muted mb-1">
                        <?php esc_html_e('Provides a rich, internal overview for admins: uptime, services, disk/bandwidth usage, package distribution, and more.', 'whmin'); ?>
                    </p>
                    <!-- START: COPYABLE SHORTCODE WRAPPER - Shortcode 2 -->
                    <div class="whmin-shortcode-wrapper d-flex align-items-center mb-2">
                        <pre id="shortcode-2" class="whmin-code-snippet mb-0 me-2" style="flex-grow: 1;"><code>[whmin_private_dashboard]</code></pre>
                        <i class="mdi mdi-content-copy whmin-copy-icon" 
                           onclick="whmin_copy_shortcode('shortcode-2', this)"
                           style="cursor: pointer; color: #0d6efd; font-size: 1.5em; transition: color 0.2s, transform 0.2s;"></i>
                    </div>
                    <!-- END: COPYABLE SHORTCODE WRAPPER -->
                    <p class="small text-muted mb-0">
                        <?php esc_html_e('Use this shortcode on a private page and restrict it to logged-in admins only.', 'whmin'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Flow -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-cog-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Basic Setup Flow', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <ol class="whmin-ordered-steps">
                <li>
                    <strong><?php esc_html_e('Configure WHM API access', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Go to WHM INFO → Settings → API Settings, enter your WHM hostname and API credentials, test the connection, and save.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Add websites to monitor', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Direct sites are discovered from WHM; indirect sites can be added manually under the Indirect Connected Websites tab.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Configure dashboards & notifications', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('Create pages with the shortcodes above, customize public/private settings, and set up email notifications for incidents.', 'whmin'); ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('(Optional) Install WHM Info Connect agent', 'whmin'); ?></strong><br>
                    <span class="text-muted">
                        <?php esc_html_e('For deep WordPress-level metadata (PHP, theme, plugins) per site, install the WHM Info Connect child plugin on each WordPress site and connect it using the API URL and key from this plugin.', 'whmin'); ?>
                    </p>
                </li>
            </ol>
        </div>
    </div>

    <!-- Meta / Credits -->
    <div class="card whmin-card shadow-lg border-0 mb-4">
        <!-- SIMULATED CARD HEADER (White text on white background) -->
        <div class="p-4">
            <h3 class="card-title mb-3" style="color: #ffffff !important;">
                <i class="mdi mdi-information-outline me-2" style="color: #0d6efd !important;"></i>
                <?php esc_html_e('Project & Credits', 'whmin'); ?>
            </h3>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="mb-2"><?php esc_html_e('Project Links', 'whmin'); ?></h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="https://github.com/blackat5445/whm-info-wordpress-plugin" target="_blank" rel="noopener">
                                <i class="mdi mdi-github"></i>
                                <?php esc_html_e('GitHub repository', 'whmin'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.kasra.eu" target="_blank" rel="noopener">
                                <i class="mdi mdi-account-circle-outline"></i>
                                <?php esc_html_e('Maintainer: Kasra Falahati', 'whmin'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.agenziamagma.it" target="_blank" rel="noopener">
                                <i class="mdi mdi-briefcase-outline"></i>
                                <?php esc_html_e('Sponsored by Agenzia Magma', 'whmin'); ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-2"><?php esc_html_e('License & Requirements', 'whmin'); ?></h5>
                    <p class="text-muted mb-1">
                        <?php esc_html_e('Licensed under GPL v2 or later. Designed for WordPress 5.8+ and PHP 7.4+ (PHP 8+ recommended).', 'whmin'); ?>
                    </p>
                    <p class="text-muted mb-0">
                        <?php esc_html_e('For best results, run on HTTPS with a WHM server that has API access enabled.', 'whmin'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- The JavaScript goes here as requested -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Copies the text content of a shortcode element to the clipboard
     * and provides visual feedback on the icon.
     * @param {string} elementId The ID of the <pre><code> element to copy.
     * @param {HTMLElement} iconElement The MDI icon element that was clicked.
     */
    window.whmin_copy_shortcode = function(elementId, iconElement) {
        var shortcodeElement = document.getElementById(elementId);
        if (!shortcodeElement) {
            console.error('Shortcode element not found:', elementId);
            return;
        }

        var codeElement = shortcodeElement.querySelector('code');
        var textToCopy = codeElement ? codeElement.textContent : shortcodeElement.textContent;

        // Save original icon and color
        var originalIcon = iconElement.className;
        var originalColor = iconElement.style.color;

        function setIconSuccess() {
            iconElement.className = 'mdi mdi-check whmin-copy-icon';
            iconElement.style.color = '#2ecc71'; // Green
            setTimeout(function() {
                iconElement.className = originalIcon;
                iconElement.style.color = originalColor;
            }, 1500);
        }

        function setIconError() {
            iconElement.className = 'mdi mdi-close whmin-copy-icon';
            iconElement.style.color = '#e74c3c'; // Red
            setTimeout(function() {
                iconElement.className = originalIcon;
                iconElement.style.color = originalColor;
            }, 1500);
        }

        // Fallback using a temporary textarea + execCommand('copy')
        function fallbackCopy() {
            var textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    setIconSuccess();
                } else {
                    console.error('Fallback: copy command unsuccessful');
                    setIconError();
                }
            } catch (err) {
                console.error('Fallback: unable to copy', err);
                setIconError();
            }

            document.body.removeChild(textarea);
        }

        // Use modern Clipboard API when available and allowed
        if (navigator && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(textToCopy).then(function() {
                setIconSuccess();
            }).catch(function(err) {
                console.error('Could not copy text via navigator.clipboard: ', err);
                // Try fallback
                fallbackCopy();
            });
        } else {
            // No Clipboard API — use fallback
            fallbackCopy();
        }
    };

    // Optional: Add a hover effect for better UX
    document.querySelectorAll('.whmin-copy-icon').forEach(function(icon) {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1.0)';
        });
    });
});
</script>