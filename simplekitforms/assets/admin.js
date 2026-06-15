/**
 * Simple Kit Forms - Admin JavaScript
 * Form builder with drag-and-drop field management
 */
(function($) {
    'use strict';

    var fieldIndex = 0;
    var strings = window.simplekitforms_admin ? window.simplekitforms_admin.strings : {};

    function initFieldIndices() {
        $('#sf-fields-container .sf-field-row').each(function() {
            var idx = $(this).data('index');
            if (idx >= fieldIndex) {
                fieldIndex = idx + 1;
            }
        });
    }

    function generateFieldName(label) {
        var name = label
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
                'email':    strings.email || 'E-mail',
                'password': strings.password || 'Senha',
                'url':      strings.url || 'URL',
                'number':   strings.number || 'Número',
                'tel':      strings.tel || 'Telefone',
            };
            return subtypeLabels[subtype] || strings.text || 'Texto';
        }
        var labels = {
            'text':       strings.text || 'Texto',
            'textarea':   strings.textarea || 'Área de texto',
            'checkboxes': strings.checkboxes || 'Caixas de seleção',
            'radio':      strings.radio || 'Botões de opção',
            'select':     strings.select || 'Lista suspensa',
        };
        return labels[type] || strings.text || 'Texto';
    }

    function addField(type, subtype) {
        var idx = fieldIndex++;
        var typeLabel = getFieldTypeLabel(type, subtype);
        var name = 'field_' + idx;

        var html = '<div class="sf-field-row" data-index="' + idx + '">';
        html += '<div class="sf-field-header">';
        html += '<span class="sf-drag-handle dashicons dashicons-menu"></span>';
        html += '<strong class="sf-field-type-label">' + typeLabel + '</strong>';
        html += '<span class="sf-field-label-preview">(' + (strings.fieldLabel || 'Label') + ')</span>';
        html += '<button type="button" class="button button-small sf-toggle-config">Configurar</button>';
        html += '<button type="button" class="button button-small sf-remove-field button-link-delete">Remover</button>';
        html += '</div>';

        html += '<div class="sf-field-config" style="display:none;">';
        html += '<input type="hidden" name="sf_fields[' + idx + '][type]" value="' + type + '">';
        html += '<input type="hidden" name="sf_fields[' + idx + '][subtype]" value="' + subtype + '">';
        html += '<input type="hidden" name="sf_fields[' + idx + '][name]" value="' + name + '" class="sf-field-name">';

        html += '<label>' + (strings.fieldLabel || 'Label') + ': ';
        html += '<input type="text" name="sf_fields[' + idx + '][label]" value="" class="sf-field-label regular-text">';
        html += '</label>';

        if (type === 'text' || type === 'textarea') {
            html += '<label>' + (strings.fieldPlaceholder || 'Placeholder') + ': ';
            html += '<input type="text" name="sf_fields[' + idx + '][placeholder]" value="" class="regular-text">';
            html += '</label>';
        }

        html += '<label>';
        html += '<input type="checkbox" name="sf_fields[' + idx + '][required]" value="1">';
        html += ' ' + (strings.fieldRequired || 'Obrigatório');
        html += '</label>';

        if (type === 'checkboxes' || type === 'radio' || type === 'select') {
            html += '<div class="sf-options-group">';
            html += '<p><strong>' + (strings.fieldOptions || 'Opções') + ':</strong></p>';
            html += '<div class="sf-options-list">';
            html += '</div>';
            html += '<button type="button" class="button sf-add-option">+ ' + (strings.addOption || 'Adicionar opção') + '</button>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        var $row = $(html);
        $('#sf-fields-container').append($row);
        $('#sf-no-fields').hide();
        $row.find('.sf-field-config').show();

        // Inicializar sortable nas opções do novo campo (se houver)
        initOptionsSortable();
    }

    function updateLabelPreview($row) {
        var label = $row.find('.sf-field-label').val() || '(' + (strings.fieldLabel || 'Label') + ')';
        $row.find('.sf-field-label-preview').text(label);
        var name = generateFieldName(label);
        $row.find('.sf-field-name').val(name);
    }

    $(document).on('click', '.sf-add-field', function() {
        var type = $(this).data('type');
        var subtype = $(this).data('subtype') || '';
        addField(type, subtype);
    });

    $(document).on('click', '.sf-toggle-config', function() {
        var $config = $(this).closest('.sf-field-row').find('.sf-field-config');
        $config.slideToggle();
    });

    $(document).on('click', '.sf-remove-field', function() {
        var $row = $(this).closest('.sf-field-row');
        if (confirm('Remover este campo?')) {
            $row.remove();
            if ($('#sf-fields-container .sf-field-row').length === 0) {
                $('#sf-no-fields').show();
            }
        }
    });

    $(document).on('input', '.sf-field-label', function() {
        updateLabelPreview($(this).closest('.sf-field-row'));
    });

    // -----------------------------------------------------------------------
    // Inicializar sortable nas listas de opções
    // -----------------------------------------------------------------------
    function initOptionsSortable() {
        // Apenas inicializa listas que ainda não são sortable
        $('.sf-options-list').not('.ui-sortable').sortable({
            handle: '.sf-option-drag-handle',
            placeholder: 'sf-option-placeholder',
            tolerance: 'pointer',
            axis: 'y',
            items: '.sf-option-row',
        });
    }

    $(document).on('click', '.sf-add-option', function() {
        var $list = $(this).closest('.sf-options-group').find('.sf-options-list');
        var idx = $(this).closest('.sf-field-row').data('index');
        var $option = $('<div class="sf-option-row">' +
            '<span class="sf-option-drag-handle dashicons dashicons-menu"></span>' +
            '<input type="text" name="sf_fields[' + idx + '][options][]" value="" class="regular-text">' +
            '<button type="button" class="button button-small sf-remove-option">Remover</button>' +
            '</div>');
        $list.append($option);
        // A recriação do sortable já cobre a nova opção
    });

    $(document).on('click', '.sf-remove-option', function() {
        $(this).closest('.sf-option-row').remove();
    });

    $('#sf-fields-container').sortable({
        handle: '.sf-drag-handle',
        placeholder: 'sf-field-row ui-sortable-placeholder',
        tolerance: 'pointer',
        axis: 'y',
    });

    initFieldIndices();
    initOptionsSortable();

})(jQuery);
