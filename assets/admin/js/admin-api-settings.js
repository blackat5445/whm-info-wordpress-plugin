/**
 * WHM Info API Settings JavaScript
 * 
 * Handles all interactions on the API Settings page.
 * 
 * @package WHM_Info
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initializeApiSettings();
    });

    /**
     * Attaches all event listeners for the API settings page.
     */
    function initializeApiSettings() {
        $('#whm-api-form').on('submit', handleFormSubmit);
        $('#test-connection').on('click', testWhmConnection);
        $('#toggle-whm-token').on('click', () => togglePasswordVisibility('whm_api_token', $('#toggle-whm-token')));
        $('#toggle-binoculars-token').on('click', () => togglePasswordVisibility('binoculars_token_display', $('#toggle-binoculars-token')));
        $('#copy-token').on('click', copyTokenToClipboard);
        $('#generate-token').on('click', generateBinocularsToken);
        $('#regenerate-token').on('click', confirmAndRegenerateToken);
        $('#revoke-token').on('click', confirmAndRevokeToken);
    }

    /**
     * Handles the WHM API form submission.
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        const $button = $(this).find('button[type="submit"]');
        const originalText = $button.html();
        setButtonLoading($button, 'Saving...');

        $.post(WHMIN_Admin.ajaxurl, {
            action: 'whmin_save_whm_api',
            nonce: WHMIN_Admin.nonce,
            server_url: $('#whm_server_url').val(),
            username: $('#whm_username').val(),
            api_token: $('#whm_api_token').val()
        }).done(response => {
            if (response.success) {
                toastr.success(response.data.message);
                updateConnectionStatus('connected');
            } else {
                toastr.error(response.data.message || 'Failed to save settings');
            }
        }).fail(() => {
            toastr.error('An error occurred while saving settings.');
        }).always(() => {
            resetButtonLoading($button, originalText);
        });
    }

    /**
     * Tests the connection to the WHM server.
     */
    function testWhmConnection() {
        const $button = $('#test-connection');
        const originalText = $button.html();
        setButtonLoading($button, 'Testing...');

        $.post(WHMIN_Admin.ajaxurl, { action: 'whmin_test_whm_connection', nonce: WHMIN_Admin.nonce })
            .done(response => {
                if (response.success) {
                    toastr.success(response.data.message);
                    updateConnectionStatus('connected');
                } else {
                    toastr.error(response.data.message || 'Connection failed');
                    updateConnectionStatus('disconnected');
                }
            })
            .fail(() => {
                toastr.error('Failed to test connection.');
                updateConnectionStatus('error');
            })
            .always(() => {
                resetButtonLoading($button, originalText);
            });
    }
    
    /**
     * Shows a SweetAlert confirmation before regenerating the token.
     */
    function confirmAndRegenerateToken() {
        Swal.fire({
            title: 'Are you sure?',
            text: "Regenerating the token will invalidate the current one. External sites will need the new token to connect.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Yes, Regenerate It!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performTokenAction('regenerate');
            }
        });
    }

    /**
     * Shows a SweetAlert confirmation before revoking the token.
     */
    function confirmAndRevokeToken() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the token. External connections will stop working until a new one is generated.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Revoke It!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performTokenAction('revoke');
            }
        });
    }

    /**
     * Generates the initial Binoculars token.
     */
     function generateBinocularsToken() {
        performTokenAction('generate');
     }

    /**
     * Performs a token action (generate, regenerate, revoke) via AJAX.
     * @param {string} type - The type of action ('generate', 'regenerate', 'revoke').
     */
    function performTokenAction(type) {
        const $button = $(`#${type}-token`);
        const originalText = $button.html();
        const loadingText = type.charAt(0).toUpperCase() + type.slice(1) + 'ing...';
        setButtonLoading($button, loadingText);

        $.post(WHMIN_Admin.ajaxurl, {
            action: `whmin_${type}_api_token`,
            nonce: WHMIN_Admin.nonce
        }).done(response => {
            if (response.success) {
                toastr.success(response.data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(response.data.message || `Failed to ${type} token.`);
            }
        }).fail(() => {
            toastr.error(`An error occurred while trying to ${type} the token.`);
        }).always(() => {
            resetButtonLoading($button, originalText);
        });
    }
    
    /**
     * Copies the Binoculars API token to the clipboard.
     */
    function copyTokenToClipboard() {
        const token = $('#binoculars_token_display').val();
        navigator.clipboard.writeText(token).then(() => {
            toastr.success('Token copied to clipboard!');
        }, () => {
            toastr.error('Failed to copy token.');
        });
    }

    /**
     * Toggles the visibility of a password input field.
     */
    function togglePasswordVisibility(inputId, $button) {
        const $input = $('#' + inputId);
        const $icon = $button.find('i');
        const isPassword = $input.attr('type') === 'password';
        
        $input.attr('type', isPassword ? 'text' : 'password');
        $icon.toggleClass('mdi-eye mdi-eye-off');
    }

    /**
     * Updates the connection status indicator badge.
     */
    function updateConnectionStatus(status) {
        const $statusElement = $('#connection-status');
        const $statusBadge = $statusElement.closest('.badge');
        const statuses = {
            connected: { text: 'Connected', class: 'bg-success' },
            disconnected: { text: 'Disconnected', class: 'bg-danger' },
            error: { text: 'Connection Error', class: 'bg-warning' },
            default: { text: 'Ready to Connect', class: 'bg-primary' }
        };
        const newStatus = statuses[status] || statuses.default;
        
        $statusBadge.removeClass('bg-primary bg-success bg-danger bg-warning').addClass(newStatus.class);
        $statusElement.text(newStatus.text);
    }

    // --- HELPER FUNCTIONS ---
    function setButtonLoading($button, text) {
        $button.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ${text}`);
    }

    function resetButtonLoading($button, originalText) {
        $button.prop('disabled', false).html(originalText);
    }

})(jQuery);