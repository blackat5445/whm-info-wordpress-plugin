/**
 * WHM Info Public Settings JavaScript
 */
(function($) {
    'use strict';

    // Function to toggle dependent sections based on a checkbox
    function handleDependencyToggle() {
        const targetId = $(this).attr('id');
        const isChecked = $(this).is(':checked');
        $(`[data-dependency="${targetId}"]`).toggle(isChecked);
    }

    $(document).ready(function() {
        // --- Manual Refresh Button ---
        $('#manual-refresh-btn').on('click', function() {
            const $button = $(this);
            const originalText = $button.html();

            // Set loading state
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Refreshing...');

            $.post(WHMIN_Admin.ajaxurl, {
                action: 'whmin_manual_status_refresh',
                nonce: WHMIN_Admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.data.message);
                } else {
                    toastr.error(response.data.message || 'An error occurred.');
                }
            })
            .fail(function() {
                toastr.error('A server error occurred. Please try again.');
            })
            .always(function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            });
        });

        // --- Settings Dependency Logic ---
        // Find all checkboxes that control other sections
        $('input[type="checkbox"]').each(function() {
            const targetId = $(this).attr('id');
            if ($(`[data-dependency="${targetId}"]`).length) {
                // Attach event listener
                $(this).on('change', handleDependencyToggle);
                // Trigger on page load to set initial state
                $(this).trigger('change');
            }
        });
    });

})(jQuery);

