/**
 * Simple Kit Mailing - Admin JavaScript
 */
(function($) {
    'use strict';

    // Seletor "Selecionar todos" na tabela de cadastros
    $(document).on('click', '#cb-select-all-1', function() {
        var checked = $(this).prop('checked');
        $('input[name="subscriber_ids[]"]').prop('checked', checked);
    });

    // Color picker para as configurações de template de email
    $(document).ready(function() {
        $('.simplekitmailing-color-picker').wpColorPicker();
    });

})(jQuery);
