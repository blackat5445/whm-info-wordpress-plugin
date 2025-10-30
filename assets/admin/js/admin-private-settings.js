/**
 * WHM Info Private Settings JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        
        // --- Manual Site Status Refresh Button ---
        $('#manual-status-refresh-btn').on('click', function() {
            const $button = $(this);
            const originalText = $button.html();

            // Set loading state
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Refreshing Statuses...');

            $.post(WHMIN_Admin.ajaxurl, {
                action: 'whmin_manual_status_check', // This checks all sites
                nonce: WHMIN_Admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.data.message);
                    // Update the "last checked" timestamp
                    $('#last-status-check').html('Last checked: just now');
                    // Reload page after 2 seconds to show new data
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.data.message || 'An error occurred.');
                }
            })
            .fail(function() {
                toastr.error('A server error occurred. Please try again.');
            })
            .always(function() {
                // Restore button state after a delay
                setTimeout(function() {
                    $button.prop('disabled', false).html(originalText);
                }, 1000);
            });
        });

        // --- Manual Server Data Refresh Button ---
        $('#manual-server-refresh-btn').on('click', function() {
            const $button = $(this);
            const originalText = $button.html();

            // Set loading state
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Refreshing Server Data...');

            $.post(WHMIN_Admin.ajaxurl, {
                action: 'whmin_refresh_server_data',
                nonce: WHMIN_Admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.data.message);
                    // Reload page after 2 seconds to show new data
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.data.message || 'An error occurred.');
                }
            })
            .fail(function() {
                toastr.error('A server error occurred. Please try again.');
            })
            .always(function() {
                // Restore button state after a delay
                setTimeout(function() {
                    $button.prop('disabled', false).html(originalText);
                }, 1000);
            });
        });
    });

})(jQuery);