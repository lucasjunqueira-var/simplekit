/**
 * Simple Kit PDF - frontend bootstrap.
 *
 * The heavy pdf.js modules are only imported when a block gets close to the
 * viewport, and only once per page no matter how many blocks exist.
 */
(function () {
    'use strict';

    var config = window.simplekitpdf_frontend || {};
    var modulePromise = null;

    function strings() {
        return config.strings || {};
    }

    function setStatus(container, message) {
        var status = container.querySelector('.simplekitpdf-status');
        if (!status) {
            return;
        }
        if (!message) {
            status.hidden = true;
            return;
        }
        status.hidden = false;
        status.textContent = message;
    }

    function loadViewerModule() {
        if (!modulePromise) {
            modulePromise = import(config.viewerModuleUrl).catch(function (error) {
                // Allow a later attempt (for example after a network hiccup).
                modulePromise = null;
                throw error;
            });
        }
        return modulePromise;
    }

    function boot(container) {
        if (container.getAttribute('data-simplekitpdf-ready') === '1') {
            return;
        }
        container.setAttribute('data-simplekitpdf-ready', '1');

        loadViewerModule().then(function (viewerModule) {
            return viewerModule.initViewer(container, config);
        }).catch(function (error) {
            if (window.console && window.console.error) {
                window.console.error('[simplekitpdf]', error);
            }
            setStatus(container, strings().loadError || 'The PDF viewer could not be loaded.');
        });
    }

    function init() {
        var containers = document.querySelectorAll('[data-simplekitpdf]');
        if (!containers.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(containers, boot);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                observer.unobserve(entry.target);
                boot(entry.target);
            });
        }, { rootMargin: '200px 0px' });

        Array.prototype.forEach.call(containers, function (container) {
            observer.observe(container);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
