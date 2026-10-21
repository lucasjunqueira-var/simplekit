/**
 * Simple Kit Downloads - admin script
 *
 * Shows either the upload field or the URL field according to the selected
 * source. The file field is required only when a new version is being added.
 */
(function () {
    'use strict';

    function updateSource(form) {
        if (!form) {
            return;
        }

        var select = form.querySelector('.skd-source');
        if (!select) {
            return;
        }

        var isUrl = select.value === 'url';

        var uploadRow = form.querySelector('.skd-upload-row');
        var urlRow = form.querySelector('.skd-url-row');

        if (uploadRow) {
            uploadRow.style.display = isUrl ? 'none' : '';
        }
        if (urlRow) {
            urlRow.style.display = isUrl ? '' : 'none';
        }

        var fileInput = form.querySelector('input[type="file"]');
        var urlInput = form.querySelector('input[name="url"]');
        var mode = form.getAttribute('data-mode') || 'edit';

        if (fileInput) {
            fileInput.required = (!isUrl && mode === 'add');
        }
        if (urlInput) {
            urlInput.required = isUrl;
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.classList && event.target.classList.contains('skd-source')) {
            updateSource(event.target.closest('form'));
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form.skd-version-form');
        for (var i = 0; i < forms.length; i++) {
            updateSource(forms[i]);
        }
    });
})();
