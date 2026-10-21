/**
 * Simple Kit PDF - pdf.js viewer wrapper (ES module).
 *
 * Loaded on demand by assets/frontend.js.
 *
 * IMPORTANT: the import order below matters. Both files are self-contained
 * bundles; `pdf.mjs` publishes the API on `globalThis.pdfjsLib` when it is
 * evaluated, and `pdf_viewer.mjs` destructures that global at evaluation time.
 * Importing pdf.mjs first guarantees the global exists before the viewer
 * components module runs.
 */
import * as pdfjsLib from './pdfjs/build/pdf.mjs';
import { PDFViewer, EventBus, PDFLinkService } from './pdfjs/web/pdf_viewer.mjs';

var instances = new WeakMap();

/**
 * Initializes one viewer inside a block container. Repeated calls for the same
 * container return the same promise.
 *
 * @param {Element} container Block container.
 * @param {Object}  config    Localized configuration.
 * @return {Promise<Object|null>}
 */
export function initViewer(container, config) {
    config = config || {};

    if (instances.has(container)) {
        return instances.get(container);
    }

    var promise = createViewer(container, config);
    instances.set(container, promise);
    return promise;
}

function strings(config) {
    return (config && config.strings) || {};
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

async function createViewer(container, config) {
    var url = container.getAttribute('data-pdf-url') || '';
    var scrollEl = container.querySelector('.simplekitpdf-scroll');
    var viewerEl = container.querySelector('.simplekitpdf-viewer');
    var messages = strings(config);

    if (!url || !scrollEl || !viewerEl) {
        setStatus(container, messages.pdfError || 'The PDF file could not be loaded.');
        return null;
    }

    if (config.workerUrl) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerUrl;
    }

    var eventBus = new EventBus();
    var linkService = new PDFLinkService({ eventBus: eventBus });
    var viewer = new PDFViewer({
        container: scrollEl,
        viewer: viewerEl,
        eventBus: eventBus,
        linkService: linkService,
        textLayerMode: 1,
        annotationMode: pdfjsLib.AnnotationMode ? pdfjsLib.AnnotationMode.ENABLE : 1
    });

    linkService.setViewer(viewer);

    var state = { fitWidth: true };
    var heightMode = container.getAttribute('data-height-mode') || 'fixed';
    var heightFrame = 0;

    function scheduleAutoHeight() {
        if (heightMode === 'fixed') {
            return;
        }
        if (heightFrame) {
            cancelAnimationFrame(heightFrame);
        }
        heightFrame = requestAnimationFrame(function () {
            heightFrame = 0;
            applyAutoHeight(container, viewer, heightMode);
        });
    }

    setupToolbar(container, viewer, state);
    setupViewerEvents(container, viewer, eventBus, messages, scheduleAutoHeight);
    setupResizeHandling(viewer, scrollEl, state);

    try {
        var loadingTask = pdfjsLib.getDocument({
            url: url,
            isEvalSupported: false
        });
        var pdfDocument = await loadingTask.promise;

        viewer.setDocument(pdfDocument);
        linkService.setDocument(pdfDocument, null);
        container.classList.add('simplekitpdf-ready');

        return {
            viewer: viewer,
            linkService: linkService,
            pdfDocument: pdfDocument
        };
    } catch (error) {
        if (window.console && window.console.error) {
            window.console.error('[simplekitpdf]', error);
        }
        setStatus(container, messages.pdfError || 'The PDF file could not be loaded.');
        return null;
    }
}

/**
 * Wires the toolbar controls. Every control is optional, so a block rendered
 * without a toolbar simply skips this step.
 */
function setupToolbar(container, viewer, state) {
    var prev = container.querySelector('.simplekitpdf-prev');
    var next = container.querySelector('.simplekitpdf-next');
    var pageInput = container.querySelector('.simplekitpdf-page-input');
    var zoomIn = container.querySelector('.simplekitpdf-zoom-in');
    var zoomOut = container.querySelector('.simplekitpdf-zoom-out');
    var fitWidth = container.querySelector('.simplekitpdf-fit-width');

    if (prev) {
        prev.addEventListener('click', function () {
            viewer.previousPage();
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            viewer.nextPage();
        });
    }

    if (pageInput) {
        pageInput.addEventListener('change', function () {
            var requested = parseInt(pageInput.value, 10);
            var total = viewer.pagesCount || 0;

            if (!requested || requested < 1) {
                pageInput.value = String(viewer.currentPageNumber || 1);
                return;
            }

            var page = total > 0 ? Math.min(requested, total) : requested;
            pageInput.value = String(page);
            viewer.currentPageNumber = page;
        });
    }

    if (zoomIn) {
        zoomIn.addEventListener('click', function () {
            state.fitWidth = false;
            viewer.increaseScale();
        });
    }

    if (zoomOut) {
        zoomOut.addEventListener('click', function () {
            state.fitWidth = false;
            viewer.decreaseScale();
        });
    }

    if (fitWidth) {
        fitWidth.addEventListener('click', function () {
            state.fitWidth = true;
            viewer.currentScaleValue = 'page-width';
        });
    }
}

function setupViewerEvents(container, viewer, eventBus, messages, onLayoutChange) {
    eventBus.on('pagesinit', function () {
        // Pages exist now: fit them to the block width and hide the overlay.
        viewer.currentScaleValue = 'page-width';
        updatePageCounter(container, viewer);
        setStatus(container, '');
        if (onLayoutChange) {
            onLayoutChange();
        }
    });

    eventBus.on('pagechanging', function (evt) {
        var pageNumber = evt.pageNumber || 1;
        var pageInput = container.querySelector('.simplekitpdf-page-input');
        var prev = container.querySelector('.simplekitpdf-prev');
        var next = container.querySelector('.simplekitpdf-next');

        if (pageInput) {
            pageInput.value = String(pageNumber);
        }
        if (prev) {
            prev.disabled = pageNumber <= 1;
        }
        if (next) {
            next.disabled = viewer.pagesCount > 0 && pageNumber >= viewer.pagesCount;
        }
    });

    eventBus.on('scalechanging', function (evt) {
        var label = container.querySelector('.simplekitpdf-zoom-value');
        var scale = evt.scale || viewer.currentScale || 1;

        if (label) {
            label.textContent = Math.round(scale * 100) + '%';
        }
        if (onLayoutChange) {
            onLayoutChange();
        }
    });

    eventBus.on('documentloaded', function () {
        updatePageCounter(container, viewer);
        if (onLayoutChange) {
            onLayoutChange();
        }
    });

    // Keep the loading overlay meaningful if rendering never starts.
    if (messages.loading) {
        var status = container.querySelector('.simplekitpdf-status');
        if (status && !status.textContent) {
            status.textContent = messages.loading;
        }
    }
}

function updatePageCounter(container, viewer) {
    var count = container.querySelector('.simplekitpdf-page-count');
    var pageInput = container.querySelector('.simplekitpdf-page-input');
    var total = viewer.pagesCount || 0;

    if (count) {
        count.textContent = '/ ' + total;
    }
    if (pageInput) {
        pageInput.max = total > 0 ? String(total) : '';
        pageInput.value = String(viewer.currentPageNumber || 1);
    }
}

/**
 * Computes the block height from the rendered layout.
 *
 * - "first-page": top padding plus the first page plus its bottom gap, at the
 *   current scale, so exactly one page is visible.
 * - "full-document": the real height of the viewer content, which already
 *   includes every page, the gaps between them and the viewer padding.
 *
 * The toolbar height is added when the toolbar is visible. The inline height
 * written by PHP is only a placeholder until this runs.
 *
 * @param {Element} container  Block container.
 * @param {Object}  viewer     PDFViewer instance.
 * @param {string}  heightMode "first-page" or "full-document".
 */
function applyAutoHeight(container, viewer, heightMode) {
    if (!viewer.pagesCount) {
        return;
    }

    var viewerEl = container.querySelector('.simplekitpdf-viewer');
    if (!viewerEl) {
        return;
    }

    var contentHeight;

    if (heightMode === 'first-page') {
        var firstPage = viewerEl.querySelector('.page');
        if (!firstPage) {
            return;
        }

        var viewerTop = viewerEl.getBoundingClientRect().top;
        var pageRect = firstPage.getBoundingClientRect();
        var marginBottom = parseFloat(window.getComputedStyle(firstPage).marginBottom);

        contentHeight = pageRect.bottom - viewerTop + (isNaN(marginBottom) ? 0 : marginBottom);
    } else {
        contentHeight = Math.max(viewerEl.offsetHeight, viewerEl.scrollHeight);
    }

    var toolbarEl = container.querySelector('.simplekitpdf-toolbar');
    var toolbarHeight = toolbarEl ? toolbarEl.offsetHeight : 0;
    var target = Math.ceil(toolbarHeight + contentHeight);
    var current = parseFloat(container.style.height) || 0;

    if (target > 0 && Math.abs(target - current) >= 1) {
        container.style.height = target + 'px';
    }
}

/**
 * Re-applies "page-width" while the block is resized, matching the behavior of
 * the official viewer application.
 */
function setupResizeHandling(viewer, scrollEl, state) {
    if (typeof ResizeObserver === 'undefined') {
        return;
    }

    var frame = 0;

    var observer = new ResizeObserver(function () {
        if (!state.fitWidth) {
            return;
        }
        if (frame) {
            cancelAnimationFrame(frame);
        }
        frame = requestAnimationFrame(function () {
            frame = 0;
            var value = viewer.currentScaleValue;
            if (value === 'page-width' || value === 'auto') {
                viewer.currentScaleValue = value;
            }
        });
    });

    observer.observe(scrollEl);
}
