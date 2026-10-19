/**
 * Simple Kit Registration - Admin JavaScript
 * Form builder with drag-and-drop field management.
 */
(function($) {
    'use strict';

    var fieldIndex = 0;
    var strings = window.simplekitregistration_admin ? window.simplekitregistration_admin.strings : {};

    function t(key, fallback) {
        return strings[key] || fallback;
    }

    function initFieldIndices() {
        $('#sf-fields-container .sf-field-row').each(function() {
            var idx = $(this).data('index');
            if (idx >= fieldIndex) {
                fieldIndex = idx + 1;
            }
        });
    }

    function generateFieldName(label) {
        var name = String(label)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '')
            .substring(0, 50);
        if (!name) {
            name = 'field_' + fieldIndex;
        }
        return name;
    }

    function getFieldTypeLabel(type, subtype) {
        if (type === 'text' && subtype && subtype !== 'text') {
            var subtypeLabels = {
                'email':    t('email', 'E-mail'),
                'password': t('password', 'Password'),
                'url':      t('url', 'URL'),
                'number':   t('number', 'Number'),
                'tel':      t('tel', 'Phone')
            };
            return subtypeLabels[subtype] || t('text', 'Text');
        }
        var labels = {
            'text':       t('text', 'Text'),
            'textarea':   t('textarea', 'Textarea'),
            'checkboxes': t('checkboxes', 'Checkboxes'),
            'radio':      t('radio', 'Radio buttons'),
            'select':     t('select', 'Dropdown'),
            'file':       t('file', 'File')
        };
        return labels[type] || t('text', 'Text');
    }

    function addField(type, subtype) {
        var idx = fieldIndex++;
        var typeLabel = getFieldTypeLabel(type, subtype);
        var name = 'field_' + idx;

        var html = '<div class="sf-field-row" data-index="' + idx + '">';
        html += '<div class="sf-field-header">';
        html += '<span class="sf-drag-handle dashicons dashicons-menu"></span>';
        html += '<strong class="sf-field-type-label">' + typeLabel + '</strong>';
        html += '<span class="sf-field-label-preview">(' + t('noLabel', '(no label)') + ')</span>';
        html += '<button type="button" class="button button-small sf-toggle-config">' + t('configure', 'Configure') + '</button>';
        html += '<button type="button" class="button button-small sf-remove-field button-link-delete">' + t('remove', 'Remove') + '</button>';
        html += '</div>';

        html += '<div class="sf-field-config" style="display:none;">';
        html += '<input type="hidden" name="skr_fields[' + idx + '][type]" value="' + type + '">';
        html += '<input type="hidden" name="skr_fields[' + idx + '][subtype]" value="' + subtype + '">';
        html += '<input type="hidden" name="skr_fields[' + idx + '][name]" value="' + name + '" class="sf-field-name">';

        html += '<label>' + t('fieldLabel', 'Label') + ': ';
        html += '<input type="text" name="skr_fields[' + idx + '][label]" value="" class="sf-field-label regular-text">';
        html += '</label>';

        if (type === 'text' || type === 'textarea') {
            html += '<label>' + t('fieldPlaceholder', 'Placeholder') + ': ';
            html += '<input type="text" name="skr_fields[' + idx + '][placeholder]" value="" class="regular-text">';
            html += '</label>';
        }

        if (type === 'file') {
            html += '<label style="display:block;margin-top:8px;">' + t('fileDescription', 'Description / instructions') + ': ';
            html += '<textarea name="skr_fields[' + idx + '][description]" rows="2" class="large-text"></textarea>';
            html += '</label>';
            html += '<label>' + t('fileMaxSize', 'Maximum size (MB)') + ': ';
            html += '<input type="number" min="0" step="0.1" name="skr_fields[' + idx + '][max_size_mb]" value="" class="small-text">';
            html += '</label>';
            html += '<label style="display:block;margin-top:4px;">' + t('fileAllowedExt', 'Allowed extensions') + ': ';
            html += '<input type="text" name="skr_fields[' + idx + '][allowed_ext]" value="" class="regular-text" placeholder="pdf, jpg, png">';
            html += '<br><span class="description">' + t('fileAllowedHint', 'Comma separated list. Leave empty to accept any file type.') + '</span>';
            html += '</label>';
        }

        html += '<label>';
        html += '<input type="checkbox" name="skr_fields[' + idx + '][required]" value="1">';
        html += ' ' + t('fieldRequired', 'Required');
        html += '</label>';

        if (type === 'checkboxes' || type === 'radio' || type === 'select') {
            html += '<div class="sf-options-group">';
            html += '<p><strong>' + t('fieldOptions', 'Options') + ':</strong></p>';
            html += '<div class="sf-options-list"></div>';
            html += '<button type="button" class="button sf-add-option">+ ' + t('addOption', 'Add option') + '</button>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        var $row = $(html);
        $('#sf-fields-container').append($row);
        $('#sf-no-fields').hide();
        $row.find('.sf-field-config').show();

        initOptionsSortable();
    }

    function updateLabelPreview($row) {
        var label = $row.find('.sf-field-label').val() || '(' + t('noLabel', '(no label)') + ')';
        $row.find('.sf-field-label-preview').text(label);
        $row.find('.sf-field-name').val(generateFieldName(label));
    }

    $(document).on('click', '.sf-add-field', function() {
        addField($(this).data('type'), $(this).data('subtype') || '');
    });

    $(document).on('click', '.sf-toggle-config', function() {
        $(this).closest('.sf-field-row').find('.sf-field-config').slideToggle();
    });

    $(document).on('click', '.sf-remove-field', function() {
        var $row = $(this).closest('.sf-field-row');
        if (confirm(t('confirmDelete', 'Remove this field?'))) {
            $row.remove();
            if ($('#sf-fields-container .sf-field-row').length === 0) {
                $('#sf-no-fields').show();
            }
        }
    });

    $(document).on('input', '.sf-field-label', function() {
        updateLabelPreview($(this).closest('.sf-field-row'));
    });

    function initOptionsSortable() {
        $('.sf-options-list').not('.ui-sortable').sortable({
            handle: '.sf-option-drag-handle',
            placeholder: 'sf-option-placeholder',
            tolerance: 'pointer',
            axis: 'y',
            items: '.sf-option-row'
        });
    }

    $(document).on('click', '.sf-add-option', function() {
        var $list = $(this).closest('.sf-options-group').find('.sf-options-list');
        var idx = $(this).closest('.sf-field-row').data('index');
        $list.append(
            '<div class="sf-option-row">' +
            '<span class="sf-option-drag-handle dashicons dashicons-menu"></span>' +
            '<input type="text" name="skr_fields[' + idx + '][options][]" value="" class="regular-text">' +
            '<button type="button" class="button button-small sf-remove-option">' + t('removeOption', 'Remove') + '</button>' +
            '</div>'
        );
    });

    $(document).on('click', '.sf-remove-option', function() {
        $(this).closest('.sf-option-row').remove();
    });

    $('#sf-fields-container').sortable({
        handle: '.sf-drag-handle',
        placeholder: 'sf-field-row ui-sortable-placeholder',
        tolerance: 'pointer',
        axis: 'y'
    });

    initFieldIndices();
    initOptionsSortable();

})(jQuery);
