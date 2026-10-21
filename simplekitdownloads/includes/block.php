<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Frontend assets
// ---------------------------------------------------------------------------
add_action('wp_enqueue_scripts', 'simplekitdownloads_register_frontend_assets');

function simplekitdownloads_register_frontend_assets() {
    wp_register_style(
        'simplekitdownloads-frontend',
        SIMPLEKITDOWNLOADS_PLUGIN_URL . 'assets/frontend.css',
        [],
        SIMPLEKITDOWNLOADS_VERSION
    );
}

function simplekitdownloads_enqueue_frontend_assets() {
    static $inline_added = false;

    if (!wp_style_is('simplekitdownloads-frontend', 'registered')) {
        simplekitdownloads_register_frontend_assets();
    }
    wp_enqueue_style('simplekitdownloads-frontend');

    $css = simplekitdownloads_get_button_css();
    if (!$inline_added && $css !== '') {
        wp_add_inline_style('simplekitdownloads-frontend', $css);
        $inline_added = true;
    }
}

// ---------------------------------------------------------------------------
// Gutenberg block registration
// ---------------------------------------------------------------------------
add_action('init', 'simplekitdownloads_register_block');

function simplekitdownloads_register_block() {
    wp_register_script(
        'simplekitdownloads-block',
        SIMPLEKITDOWNLOADS_PLUGIN_URL . 'assets/block.js',
        ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
        SIMPLEKITDOWNLOADS_VERSION,
        true
    );

    if (is_admin()) {
        $downloads_data = [];
        foreach (simplekitdownloads_get_downloads() as $download) {
            $versions = [
                [
                    'label' => __('Current (latest)', 'simplekitdownloads'),
                    'value' => 'current',
                ],
            ];
            foreach (simplekitdownloads_get_versions($download->id) as $version) {
                $versions[] = [
                    'label' => $version->version,
                    'value' => $version->version,
                ];
            }
            $downloads_data[] = [
                'id'       => (int) $download->id,
                'name'     => $download->name,
                'versions' => $versions,
            ];
        }

        wp_localize_script('simplekitdownloads-block', 'simplekitdownloads_block_data', [
            'downloads'            => $downloads_data,
            'default_text'         => simplekitdownloads_get_button_text(),
            'default_text_version' => simplekitdownloads_get_button_text_version(),
        ]);
    }

    register_block_type('simplekitdownloads/download', [
        'editor_script'   => 'simplekitdownloads-block',
        'render_callback' => 'simplekitdownloads_render_block',
        'attributes'      => [
            'download_id' => [
                'type'    => 'number',
                'default' => 0,
            ],
            'version'     => [
                'type'    => 'string',
                'default' => 'current',
            ],
            'text'        => [
                'type'    => 'string',
                'default' => '',
            ],
            'custom_css'  => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);
}

/**
 * Resolve the requested version of a download.
 */
function simplekitdownloads_resolve_version($download_id, $requested) {
    if ($requested === '' || $requested === 'current') {
        return simplekitdownloads_get_latest_version($download_id);
    }
    return simplekitdownloads_get_version_by_label($download_id, $requested);
}

/**
 * Render the download button.
 */
function simplekitdownloads_render_block($attributes) {
    $download_id = isset($attributes['download_id']) ? (int) $attributes['download_id'] : 0;
    if ($download_id <= 0) {
        return '';
    }

    $requested = isset($attributes['version']) ? (string) $attributes['version'] : 'current';
    $version   = simplekitdownloads_resolve_version($download_id, $requested);

    // Q2: a download without versions does not render a button at all.
    if (!$version) {
        return '';
    }

    $custom_text = isset($attributes['text']) ? (string) $attributes['text'] : '';
    $is_current  = ($requested === '' || $requested === 'current');
    $label       = simplekitdownloads_button_label($is_current ? null : $version->version, $custom_text);
    $url         = simplekitdownloads_get_download_url($download_id, $requested === '' ? 'current' : $requested);

    simplekitdownloads_enqueue_frontend_assets();

    $custom_css = isset($attributes['custom_css']) ? simplekitdownloads_sanitize_css($attributes['custom_css']) : '';

    $html  = '<div class="simplekitdownloads-block">';
    if ($custom_css !== '') {
        $html .= '<style>' . $custom_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized CSS.
    }
    $html .= '<a class="simplekitdownloads-button" href="' . esc_url($url) . '" rel="nofollow">';
    $html .= esc_html($label);
    $html .= '</a></div>';

    return $html;
}

// ---------------------------------------------------------------------------
// Shortcode (same behavior as the block)
// ---------------------------------------------------------------------------
add_shortcode('simplekitdownloads', 'simplekitdownloads_shortcode');

function simplekitdownloads_shortcode($atts) {
    $atts = shortcode_atts([
        'id'      => 0,
        'version' => 'current',
        'text'    => '',
        'css'     => '',
    ], $atts, 'simplekitdownloads');

    return simplekitdownloads_render_block([
        'download_id' => (int) $atts['id'],
        'version'     => (string) $atts['version'],
        'text'        => (string) $atts['text'],
        'custom_css'  => (string) $atts['css'],
    ]);
}
