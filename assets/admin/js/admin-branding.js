/**
 * WHM Info Personal Branding JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        let mediaFrame;

        $('.whmin-image-uploader').on('click', '.whmin-upload-btn', function(e) {
            e.preventDefault();

            const $uploader = $(this).closest('.whmin-image-uploader');
            const inputId = $uploader.data('input-id');
            const previewId = $uploader.data('preview-id');

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Select or Upload Media',
                button: { text: 'Use this media' },
                multiple: false
            });

            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                $(`#${inputId}`).val(attachment.id);
                $(`#${previewId} img`).attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
                $(`#${previewId}`).show();
                $uploader.find('.whmin-upload-btn').hide();
                $uploader.find('.whmin-remove-btn').show();
            });

            mediaFrame.open();
        });

        $('.whmin-image-uploader').on('click', '.whmin-remove-btn', function(e) {
            e.preventDefault();
            
            const $uploader = $(this).closest('.whmin-image-uploader');
            const inputId = $uploader.data('input-id');
            const previewId = $uploader.data('preview-id');

            $(`#${inputId}`).val('');
            $(`#${previewId} img`).attr('src', '');
            $(`#${previewId}`).hide();
            $uploader.find('.whmin-upload-btn').show();
            $uploader.find('.whmin-remove-btn').hide();
        });
    });

})(jQuery);