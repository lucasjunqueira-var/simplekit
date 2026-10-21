<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Frontend assets
//
// The css/js bootstrap is small; pdf.js itself is a lazy dynamic import made by
// assets/frontend.js only when a block enters the viewport.
// ---------------------------------------------------------------------------
add_action('wp_enqueue_scripts', 'simplekitpdf_maybe_enqueue_assets');

function simplekitpdf_maybe_enqueue_assets() {
    /**
     * Allows forcing the frontend assets on pages where block detection fails
     * (for example when the block is inserted through a reusable block).
     *
     * @param bool $load Whether the assets should be loaded.
     */
    $load = apply_filters('simplekitpdf_enqueue_frontend_assets', simplekitpdf_current_post_has_pdf());
    if (!$load) {
        return;
    }
    simplekitpdf_enqueue_assets();
}

/**
 * Tells whether the current singular post contains the block or the shortcode.
 *
 * @return bool
 */
function simplekitpdf_current_post_has_pdf() {
    if (!is_singular()) {
        return false;
    }

    $post = get_post();
    if (!$post instanceof WP_Post) {
        return false;
    }

    if (has_block('simplekitpdf/pdf', $post)) {
        return true;
    }

    return has_shortcode((string) $post->post_content, 'simplekitpdf');
}

/**
 * Registers and enqueues the frontend styles and bootstrap script.
 *
 * Safe to call more than once, including from the block render callback.
 *
 * @return void
 */
function simplekitpdf_enqueue_assets() {
    if (wp_script_is('simplekitpdf-frontend', 'enqueued') || wp_script_is('simplekitpdf-frontend', 'done')) {
        return;
    }

    wp_enqueue_style(
        'simplekitpdf-pdfjs',
        SIMPLEKITPDF_PDFJS_URL . 'web/pdf_viewer.css',
        array(),
        SIMPLEKITPDF_PDFJS_VERSION
    );

    wp_enqueue_style(
        'simplekitpdf-frontend',
        SIMPLEKITPDF_PLUGIN_URL . 'assets/frontend.css',
        array('simplekitpdf-pdfjs'),
        SIMPLEKITPDF_VERSION
    );

    wp_enqueue_script(
        'simplekitpdf-frontend',
        SIMPLEKITPDF_PLUGIN_URL . 'assets/frontend.js',
        array(),
        SIMPLEKITPDF_VERSION,
        true
    );

    wp_localize_script('simplekitpdf-frontend', 'simplekitpdf_frontend', array(
        'viewerModuleUrl' => add_query_arg('ver', SIMPLEKITPDF_VERSION, SIMPLEKITPDF_PLUGIN_URL . 'assets/viewer.js'),
        'workerUrl'       => SIMPLEKITPDF_PDFJS_URL . 'build/pdf.worker.mjs',
        'assetsUrl'       => SIMPLEKITPDF_PDFJS_URL,
        'strings'         => array(
            'loading'   => __('Loading PDF...', 'simplekitpdf'),
            'loadError' => __('The PDF viewer could not be loaded.', 'simplekitpdf'),
            'pdfError'  => __('The PDF file could not be loaded.', 'simplekitpdf'),
        ),
    ));
}
