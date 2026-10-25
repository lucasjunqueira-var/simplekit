/**
 * Simple Kit Accessibility - admin JavaScript.
 * Color pickers, media library and the live preview of the Setup page.
 */
(function ($) {
    'use strict';

    var formSelector = 'form[action="options.php"]';
    var settingsField = 'simplekitaccessibility_settings[';

    function fields(key) {
        var name = settingsField + key + ']';
        return $(formSelector).find('[name="' + name + '"]');
    }

    function number(key, fallback) {
        var current = value(key, fallback);
        return current === '' ? fallback : current;
    }

    function value(key, fallback) {
        var $fields = fields(key);

        if (!$fields.length) {
            return fallback;
        }

        if ($fields.filter('[type="checkbox"]').length) {
            return $fields.filter('[type="checkbox"]').is(':checked') ? '1' : '0';
        }

        if ($fields.filter('[type="radio"]').length) {
            var checked = $fields.filter(':checked').val();
            return typeof checked === 'undefined' ? fallback : checked;
        }

        var current = $fields.first().val();
        return typeof current === 'undefined' || current === null ? fallback : current;
    }

    function setVar(element, name, value) {
        if (element && element.style && element.style.setProperty) {
            element.style.setProperty(name, value);
        }
    }

    /**
     * Shows only the offset that applies to the selected position.
     */
    function updatePositionFields() {
        var position = value('position', 'middle-right');
        var onSide = position.indexOf('left') !== -1 || position.indexOf('right') !== -1;
        var onEdge = position.indexOf('top') !== -1 || position.indexOf('bottom') !== -1;
        var centeredX = position === 'top-center' || position === 'bottom-center';
        var centeredY = position === 'middle-left' || position === 'middle-right';

        $('#skacc-offset_x').closest('tr').toggle(onSide);
        $('#skacc-offset_y').closest('tr').toggle(onEdge);
        $('#skacc-shift_x').closest('tr').toggle(centeredX);
        $('#skacc-shift_y').closest('tr').toggle(centeredY);
    }

    function updatePreview() {
        var preview = document.getElementById('skacc-preview');

        if (!preview) {
            return;
        }

        var shape = value('button_shape', 'circle');
        var radius = shape === 'circle' ? '50%' : (shape === 'square' ? '0' : number('button_radius', '8') + 'px');

        setVar(preview, '--skacc-btn-bg', value('button_bg', '#1d2327') || '#1d2327');
        setVar(preview, '--skacc-btn-fg', value('button_fg', '#ffffff') || '#ffffff');
        setVar(preview, '--skacc-btn-hover', value('button_bg_hover', '#135e96') || '#135e96');
        setVar(preview, '--skacc-btn-size', number('button_size', '48') + 'px');
        setVar(preview, '--skacc-btn-radius', radius);
        setVar(preview, '--skacc-panel-bg', value('panel_bg', '#ffffff') || '#ffffff');
        setVar(preview, '--skacc-panel-fg', value('panel_fg', '#1d2327') || '#1d2327');
        setVar(preview, '--skacc-panel-border', value('panel_border', '#1d2327') || '#1d2327');
        setVar(preview, '--skacc-panel-font-size', number('panel_font_size', '15') + 'px');
        setVar(preview, '--skacc-panel-width', number('panel_width', '280') + 'px');
        setVar(preview, '--skacc-panel-radius', number('panel_radius', '8') + 'px');

        var toggleText = value('button_text', '');
        var image = value('button_image', '');

        $('#skacc-preview-toggle-text').text(toggleText).toggle(toggleText !== '');
        $('#skacc-preview-image').toggle(image !== '');
        $('#skacc-preview-svg-icon').toggle(image === '' && toggleText === '');

        $('#skacc-preview-title').text(value('panel_title', ''));
        $('#skacc-preview-label-font').text(value('label_font', ''));
        $('#skacc-preview-label-line').text(value('label_line', ''));
        $('#skacc-preview-label-letter').text(value('label_letter', ''));
        $('#skacc-preview-label-contrast').text(value('label_contrast', ''));
        $('#skacc-preview-contrast').text(value('text_off', ''));
        $('#skacc-preview-reset-all').text(value('text_reset_all', ''));

        $('#skacc-button_radius').closest('tr').toggle(shape === 'rounded');

        updatePositionFields();
    }

    function initColorPickers() {
        if (!$.fn.wpColorPicker) {
            return;
        }

        $('.skacc-color').wpColorPicker({
            change: function () {
                window.setTimeout(updatePreview, 0);
            },
            clear: function () {
                window.setTimeout(updatePreview, 0);
            }
        });
    }

    function initMediaField() {
        $(document).on('click', '.skacc-media-button', function (event) {
            event.preventDefault();

            if (!window.wp || !window.wp.media) {
                return;
            }

            var labels = window.simplekitaccessibility_admin || {};
            var frame = window.wp.media({
                title: labels.mediaTitle || 'Select an image',
                button: { text: labels.mediaButton || 'Use this image' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#skacc-button_image').val(attachment.url).trigger('change');
                $('.skacc-media-preview').attr('src', attachment.url).show();
                updatePreview();
            });

            frame.open();
        });

        $(document).on('click', '.skacc-media-clear', function (event) {
            event.preventDefault();
            $('#skacc-button_image').val('').trigger('change');
            $('.skacc-media-preview').attr('src', '').hide();
            updatePreview();
        });
    }

    $(function () {
        if (!$('#skacc-preview').length) {
            return;
        }

        initColorPickers();
        initMediaField();

        $(document).on('input change', formSelector + ' input, ' + formSelector + ' select, ' + formSelector + ' textarea', updatePreview);

        updatePreview();
    });
})(jQuery);
