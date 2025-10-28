/**
 * WHM Info General Admin JavaScript
 * 
 * Contains initializers for common UI elements across the plugin's admin pages.
 * 
 * @package WHM_Info
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Configure Toastr globally for the plugin
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": true,
                "timeOut": "3000",
            };
        }

        initializeAnimations();
        initializeTooltips();
    });

    /**
     * Initialize animations on tab switch
     */
    function initializeAnimations() {
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function() {
            const target = $(this).data('bs-target');
            $(target).addClass('animate__animated animate__fadeIn');

            setTimeout(function() {
                $(target).removeClass('animate__animated animate__fadeIn');
            }, 1000);
        });
    }

    /**
     * Initialize Bootstrap tooltips
     */
    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

})(jQuery);