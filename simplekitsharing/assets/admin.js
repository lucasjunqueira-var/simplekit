/**
 * Simple Kit Sharing - Admin JavaScript
 * Media uploader support for image fields
 */
(function($) {
    'use strict';

    /**
     * Open the WordPress media library for a specific input field.
     * A new frame is created each time to ensure the correct targetId
     * and button are captured in the closure, preventing field mix-ups.
     */
    $(document).on('click', '.simplesharing-upload-btn', function(e) {
        e.preventDefault();

        var button    = $(this);
        var targetId  = button.data('target');
        var targetEl  = $('#' + targetId);

        var frame = wp.media({
            title:    simplekitsharing_admin.media_title || 'Select or Upload Image',
            button:   { text: simplekitsharing_admin.media_button || 'Use this image' },
            multiple: false,
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            targetEl.val(attachment.url).trigger('change');

            // Update image preview inside a .simplesharing-image-wrapper (settings page)
            var wrapper = button.closest('.simplesharing-image-wrapper');
            if (wrapper.length) {
                wrapper.find('img').remove();
                if (attachment.url) {
                    var img = $('<img>').attr('src', attachment.url).css({
                        'max-width':    '200px',
                        'margin-top':   '8px',
                        'border':       '1px solid #ddd',
                        'border-radius':'4px',
                        'display':      'block',
                    });
                    wrapper.append(img);
                }
                return;
            }

            // Fallback for meta box (no .simplesharing-image-wrapper)
            var metaImg = button.siblings('img');
            if (metaImg.length) {
                metaImg.attr('src', attachment.url);
            } else if (attachment.url) {
                var img = $('<br><img>').attr('src', attachment.url).css({
                    'max-width':    '180px',
                    'margin-top':   '6px',
                    'border':       '1px solid #ddd',
                    'border-radius':'4px',
                });
                button.after(img);
            }
        });

        frame.open();
    });

})(jQuery);
