/**
 * Simple Kit Accessibility - frontend runtime.
 *
 * Exposes window.simplekitaccessibility so any element of the page can drive
 * the reading adjustments, and renders the floating panel when present.
 */
(function (window, document) {
    'use strict';

    if (window.simplekitaccessibility) {
        return;
    }

    var cfg        = window.simplekitaccessibility_config || {};
    var groups     = ['font', 'line', 'letter'];
    var prefixes   = { font: 'skacc-fs-', line: 'skacc-lh-', letter: 'skacc-ls-' };
    var steps      = cfg.steps || {};
    var baseline   = cfg.baseline || { font: 2, line: 3, letter: 2 };
    var labels     = cfg.labels || {};
    var storageKey = cfg.key || 'simplekitaccessibility';

    var state = {
        font: typeof baseline.font === 'number' ? baseline.font : 0,
        line: typeof baseline.line === 'number' ? baseline.line : 0,
        letter: typeof baseline.letter === 'number' ? baseline.letter : 0,
        contrast: false
    };

    var subscribers = [];
    var widget = null;
    var toggle = null;
    var panel = null;
    var live = null;

    // -----------------------------------------------------------------
    // Storage
    // -----------------------------------------------------------------
    function store() {
        try {
            if (cfg.storage === 'local') {
                return window.localStorage;
            }
            if (cfg.storage === 'session') {
                return window.sessionStorage;
            }
        } catch (e) {
            return null;
        }
        return null;
    }

    function isIndex(group, value) {
        var table = steps[group] || [];
        return typeof value === 'number' && value >= 0 && value < table.length;
    }

    function load() {
        var target = store();
        if (!target) {
            return;
        }

        var raw = null;
        try {
            raw = target.getItem(storageKey);
        } catch (e) {
            return;
        }
        if (!raw) {
            return;
        }

        var data = null;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (!data || data.v !== 1) {
            return;
        }

        for (var i = 0; i < groups.length; i++) {
            var group = groups[i];
            if (isIndex(group, data[group])) {
                state[group] = data[group];
            }
        }
        state.contrast = !!data.contrast;
    }

    function save() {
        var target = store();
        if (!target) {
            return;
        }
        try {
            target.setItem(storageKey, JSON.stringify({
                v: 1,
                font: state.font,
                line: state.line,
                letter: state.letter,
                contrast: state.contrast
            }));
        } catch (e) {
            // Quota or private mode: state simply stays in memory.
        }
    }

    function clearStorage() {
        var target = store();
        if (!target) {
            return;
        }
        try {
            target.removeItem(storageKey);
        } catch (e) {
            // Ignore.
        }
    }

    // -----------------------------------------------------------------
    // State
    // -----------------------------------------------------------------
    function getState() {
        return {
            font: state.font,
            line: state.line,
            letter: state.letter,
            contrast: !!state.contrast
        };
    }

    function apply() {
        var html = document.documentElement;
        html.classList.add('skacc-on');

        for (var i = 0; i < groups.length; i++) {
            var group = groups[i];
            var table = steps[group] || [];
            var prefix = prefixes[group];
            var index;

            for (index = 0; index < table.length; index++) {
                html.classList.remove(prefix + index);
            }

            if (typeof baseline[group] === 'number' && state[group] !== baseline[group] && isIndex(group, state[group])) {
                html.classList.add(prefix + state[group]);
            }
        }

        if (state.contrast) {
            html.classList.add('skacc-contrast');
        } else {
            html.classList.remove('skacc-contrast');
        }

        syncMeasured();
    }

    // -----------------------------------------------------------------
    // Measured text scaling (optional engine)
    //
    // Instead of multiplying the parent size, every text element is measured
    // once and receives its own size times the factor. This preserves the
    // typographic hierarchy of themes that declare sizes in pixels, including
    // inline styles produced by page builders.
    // -----------------------------------------------------------------
    var measuredReady = false;
    var measuredDirty = false;
    var measuredApplied = false;
    var measuredNodes = [];
    var measuredBase = [];
    var measuredOriginal = [];
    var measuredPriority = [];
    var measuredObserver = null;
    var measuredTimer = 0;

    function measuredEnabled() {
        return cfg.scaleMethod === 'measured'
            && typeof window.getComputedStyle === 'function'
            && typeof document.querySelectorAll === 'function';
    }

    function restoreMeasuredStyles() {
        if (!measuredApplied) {
            return;
        }

        for (var i = 0; i < measuredNodes.length; i++) {
            var node = measuredNodes[i];
            var original = measuredOriginal[i] || '';

            if (original !== '') {
                node.style.setProperty('font-size', original, measuredPriority[i] || '');
            } else {
                node.style.removeProperty('font-size');
            }
        }

        measuredApplied = false;
    }

    function applyMeasuredStyles() {
        if (state.font === baseline.font) {
            restoreMeasuredStyles();
            return;
        }

        var factor = steps.font[state.font] || 1;

        for (var i = 0; i < measuredNodes.length; i++) {
            var base = measuredBase[i];
            if (!base) {
                continue;
            }
            measuredNodes[i].style.setProperty('font-size', (base * factor) + 'px', 'important');
        }

        measuredApplied = true;
    }

    function measureTextNodes() {
        var nodes = [];
        var bases = [];
        var originals = [];
        var priorities = [];
        var found = [];

        try {
            found = document.querySelectorAll(cfg.fontSelectors || '');
        } catch (e) {
            found = [];
        }

        for (var i = 0; i < found.length; i++) {
            var node = found[i];

            if (node.closest && node.closest('#skacc-widget')) {
                continue;
            }

            var original = node.style.fontSize || '';
            var size = parseFloat(window.getComputedStyle(node).fontSize);

            if (!size || isNaN(size)) {
                continue;
            }

            nodes.push(node);
            bases.push(size);
            originals.push(original);
            priorities.push(original !== '' ? node.style.getPropertyPriority('font-size') : '');
        }

        measuredNodes = nodes;
        measuredBase = bases;
        measuredOriginal = originals;
        measuredPriority = priorities;
    }

    function refreshMeasured() {
        restoreMeasuredStyles();
        measureTextNodes();
        applyMeasuredStyles();

        measuredReady = true;
        measuredDirty = false;
    }

    function ensureMeasuredObserver() {
        if (measuredObserver || typeof window.MutationObserver !== 'function' || !document.body) {
            return;
        }
        measuredObserver = new window.MutationObserver(scheduleMeasuredRefresh);
        measuredObserver.observe(document.body, { childList: true, subtree: true });
    }

    function scheduleMeasuredRefresh() {
        if (!measuredEnabled() || state.font === baseline.font) {
            return;
        }

        measuredDirty = true;
        window.clearTimeout(measuredTimer);
        measuredTimer = window.setTimeout(function () {
            if (state.font === baseline.font) {
                return;
            }
            refreshMeasured();
        }, 250);
    }

    function syncMeasured() {
        if (!measuredEnabled()) {
            return;
        }

        if (state.font === baseline.font) {
            if (measuredObserver) {
                measuredObserver.disconnect();
                measuredObserver = null;
            }
            restoreMeasuredStyles();
            measuredReady = false;
            measuredDirty = false;
            return;
        }

        if (!measuredReady || measuredDirty) {
            refreshMeasured();
        } else {
            applyMeasuredStyles();
        }

        ensureMeasuredObserver();
    }

    function formatValue(group, value) {
        var table = steps[group] || [];

        if (group === 'font') {
            return Math.round((table[value] || 1) * 100) + '%';
        }
        if (group === 'line') {
            var line = table[value];
            return line === null || typeof line === 'undefined' ? String(labels.reset || '') : String(line);
        }
        if (group === 'letter') {
            var letter = table[value];
            return letter === null || typeof letter === 'undefined' ? String(labels.reset || '') : Number(letter).toFixed(2) + 'em';
        }
        return '';
    }

    function describe(group) {
        if (group === 'contrast') {
            return (labels.contrast || 'Contrast') + ': ' + (state.contrast ? (labels.on || 'On') : (labels.off || 'Off'));
        }
        return (labels[group] || group) + ': ' + formatValue(group, state[group]);
    }

    function announce(message) {
        if (!live) {
            return;
        }
        live.textContent = '';
        window.setTimeout(function () {
            live.textContent = message;
        }, 40);
    }

    function emit(name) {
        var event;
        try {
            event = new CustomEvent(name, { detail: getState() });
        } catch (e) {
            return;
        }
        document.dispatchEvent(event);
    }

    function notify() {
        var snapshot = getState();
        var list = subscribers.slice();
        for (var i = 0; i < list.length; i++) {
            try {
                list[i](snapshot);
            } catch (e) {
                // A broken subscriber must not stop the runtime.
            }
        }
    }

    function syncUi() {
        if (!panel) {
            return;
        }

        var fontButton = panel.querySelector('[data-skacc-action="font-reset"]');
        if (fontButton) {
            fontButton.textContent = formatValue('font', state.font);
        }

        var lineButton = panel.querySelector('[data-skacc-action="line-reset"]');
        if (lineButton) {
            lineButton.textContent = state.line === baseline.line
                ? (labels.reset || 'Reset')
                : formatValue('line', state.line);
        }

        var letterButton = panel.querySelector('[data-skacc-action="letter-reset"]');
        if (letterButton) {
            letterButton.textContent = state.letter === baseline.letter
                ? (labels.reset || 'Reset')
                : formatValue('letter', state.letter);
        }

        var contrastButton = panel.querySelector('[data-skacc-action="contrast"]');
        if (contrastButton) {
            contrastButton.textContent = state.contrast ? (labels.on || 'On') : (labels.off || 'Off');
            contrastButton.setAttribute('aria-pressed', state.contrast ? 'true' : 'false');
        }

        var allContrast = document.querySelectorAll('[data-skacc-action="contrast"]');
        for (var i = 0; i < allContrast.length; i++) {
            if (allContrast[i] !== contrastButton) {
                allContrast[i].setAttribute('aria-pressed', state.contrast ? 'true' : 'false');
            }
        }
    }

    function commit(group) {
        apply();
        save();
        syncUi();
        notify();
        emit('simplekitaccessibility:change');
        announce(describe(group));
    }

    function step(group, delta) {
        var table = steps[group] || [];
        if (!table.length) {
            return;
        }

        var next = state[group] + delta;
        if (next < 0) {
            next = 0;
        }
        if (next > table.length - 1) {
            next = table.length - 1;
        }
        if (next === state[group]) {
            announce(describe(group));
            return;
        }

        state[group] = next;
        commit(group);
    }

    function resetGroup(group) {
        if (state[group] === baseline[group]) {
            announce(describe(group));
            return;
        }
        state[group] = baseline[group];
        commit(group);
    }

    function setContrast(value) {
        value = !!value;
        if (state.contrast === value) {
            return;
        }
        state.contrast = value;
        commit('contrast');
    }

    function resetAll() {
        state.font = baseline.font;
        state.line = baseline.line;
        state.letter = baseline.letter;
        state.contrast = false;
        clearStorage();
        commit('font');
        announce(labels.resetAll || 'Reset');
    }

    // -----------------------------------------------------------------
    // Panel
    // -----------------------------------------------------------------
    function openPanel() {
        if (!panel || !toggle) {
            return;
        }
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        panel.focus();
        emit('simplekitaccessibility:panel-open');
    }

    function closePanel(returnFocus) {
        if (!panel || !toggle) {
            return;
        }
        if (panel.hidden) {
            return;
        }
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus !== false) {
            toggle.focus();
        }
        emit('simplekitaccessibility:panel-close');
    }

    function togglePanel() {
        if (!panel) {
            return;
        }
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------
    var actions = {
        'font-up': function () { step('font', 1); },
        'font-down': function () { step('font', -1); },
        'font-reset': function () { resetGroup('font'); },
        'line-up': function () { step('line', 1); },
        'line-down': function () { step('line', -1); },
        'line-reset': function () { resetGroup('line'); },
        'letter-up': function () { step('letter', 1); },
        'letter-down': function () { step('letter', -1); },
        'letter-reset': function () { resetGroup('letter'); },
        'contrast': function () { setContrast(!state.contrast); },
        'reset-all': function () { resetAll(); },
        'panel': function () { togglePanel(); }
    };

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || target.nodeType !== 1 || !target.closest) {
            return;
        }

        var trigger = target.closest('[data-skacc-action]');
        if (!trigger) {
            return;
        }

        var action = trigger.getAttribute('data-skacc-action');
        if (!action || !actions[action]) {
            return;
        }

        event.preventDefault();
        actions[action]();
    });

    // Clicks outside the widget close the panel without stealing focus.
    document.addEventListener('click', function (event) {
        if (!panel || panel.hidden || !widget) {
            return;
        }
        if (widget.contains(event.target)) {
            return;
        }
        closePanel(false);
    }, true);

    document.addEventListener('keydown', function (event) {
        var key = event.key || '';
        if (key !== 'Escape' && key !== 'Esc' && event.keyCode !== 27) {
            return;
        }
        if (panel && !panel.hidden) {
            closePanel();
        }
    });

    // Late resources (images, web fonts, breakpoints) change the measured
    // sizes, so the measurements are refreshed once they settle.
    window.addEventListener('resize', scheduleMeasuredRefresh);
    window.addEventListener('load', scheduleMeasuredRefresh);

    if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
        document.fonts.ready.then(function () {
            scheduleMeasuredRefresh();
        });
    }

    // -----------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------
    window.simplekitaccessibility = {
        version: '1.0.0',
        getState: getState,
        increaseFont: function () { step('font', 1); },
        decreaseFont: function () { step('font', -1); },
        resetFont: function () { resetGroup('font'); },
        increaseLineHeight: function () { step('line', 1); },
        decreaseLineHeight: function () { step('line', -1); },
        resetLineHeight: function () { resetGroup('line'); },
        increaseLetterSpacing: function () { step('letter', 1); },
        decreaseLetterSpacing: function () { step('letter', -1); },
        resetLetterSpacing: function () { resetGroup('letter'); },
        setContrast: setContrast,
        toggleContrast: function () { setContrast(!state.contrast); },
        resetAll: resetAll,
        openPanel: openPanel,
        closePanel: function () { closePanel(); },
        togglePanel: togglePanel,
        subscribe: function (callback) {
            if (typeof callback !== 'function') {
                return function () {};
            }
            subscribers.push(callback);
            return function () {
                var index = subscribers.indexOf(callback);
                if (index !== -1) {
                    subscribers.splice(index, 1);
                }
            };
        }
    };

    // -----------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------
    function init() {
        widget = document.getElementById('skacc-widget');
        live = document.getElementById('skacc-live');

        if (widget) {
            toggle = document.getElementById('skacc-toggle');
            panel = document.getElementById('skacc-panel');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    togglePanel();
                });
            }
        }

        load();
        apply();
        syncUi();
        emit('simplekitaccessibility:ready');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
