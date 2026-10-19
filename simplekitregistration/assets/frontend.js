/**
 * Simple Kit Registration - Frontend form submission.
 *
 * The submission logic lives in this enqueued file (instead of an inline
 * <script> inside the block output) because inline scripts returned by a
 * dynamic block are processed by the_content filters (wpautop, wptexturize)
 * and can be corrupted, which breaks the page with a JavaScript syntax error.
 */
(function () {
    'use strict';

    var config = window.simplekitregistration_frontend || {};
    var strings = config.strings || {};

    function t(key, fallback) {
        return strings[key] || fallback;
    }

    function handleSubmit(button) {
        var container = button.closest('.skr-form-block');
        if (!container) {
            return;
        }

        var form    = container.querySelector('.skr-form');
        var message = container.querySelector('.skr-message');
        if (!form) {
            return;
        }

        var url         = container.getAttribute('data-rest-url');
        var restNonce   = container.getAttribute('data-rest-nonce');
        var submitNonce = container.getAttribute('data-submit-nonce');
        var formId      = container.getAttribute('data-form-id');
        if (!url) {
            return;
        }

        var data = new FormData(form);
        data.append('form_id', formId);
        data.append('nonce', submitNonce);

        var original = button.textContent;
        button.disabled = true;
        button.textContent = t('sending', 'Sending...');
        if (message) {
            message.className = 'skr-message';
            message.textContent = '';
        }

        fetch(url, {
            method: 'POST',
            headers: { 'X-WP-Nonce': restNonce },
            body: data
        })
        .then(function (response) {
            return response.json().then(function (body) {
                return { status: response.status, body: body };
            });
        })
        .then(function (result) {
            var body = result.body || {};

            if (result.status >= 200 && result.status < 300 && body.success) {
                var thanks = container.querySelector('.skr-thank-you');
                form.innerHTML = '<div class="skr-thank-you-inline">' + (thanks ? thanks.innerHTML : '') + '</div>';
                return;
            }

            var messages = [];
            if (body.data && body.data.messages) {
                messages = body.data.messages;
            } else if (body.message) {
                messages = [body.message];
            }
            if (!messages.length) {
                messages = [t('error', 'Error submitting. Please try again.')];
            }

            if (message) {
                message.className = 'skr-message error';
                message.textContent = messages.join(' ');
            }
        })
        .catch(function () {
            if (message) {
                message.className = 'skr-message error';
                message.textContent = t('connectionError', 'Connection error. Please try again.');
            }
        })
        .finally(function () {
            button.disabled = false;
            button.textContent = original;
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || typeof target.closest !== 'function') {
            return;
        }
        var button = target.closest('.skr-form-block .skr-submit');
        if (!button) {
            return;
        }
        event.preventDefault();
        handleSubmit(button);
    });
})();
