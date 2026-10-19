<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Gutenberg blocks
//   - simplekitregistration/form   : display a form to visitors
//   - simplekitregistration/access : restricted area for evaluators
// ---------------------------------------------------------------------------
add_action('init', 'simplekitregistration_register_blocks');

function simplekitregistration_register_blocks() {
    wp_register_script(
        'simplekitregistration-block',
        SIMPLEKITREGISTRATION_PLUGIN_URL . 'assets/block.js',
        ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
        SIMPLEKITREGISTRATION_VERSION,
        true
    );

    $forms_data = [];
    foreach (simplekitregistration_get_all_forms() as $form) {
        $forms_data[] = [
            'id'    => (int) $form->id,
            'title' => esc_html($form->title),
            'code'  => esc_html($form->code),
        ];
    }

    wp_localize_script('simplekitregistration-block', 'simplekitregistration_block_data', [
        'forms'    => $forms_data,
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => esc_url_raw(rest_url('simplekitregistration/v1/submit')),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'nonce'      => wp_create_nonce('simplekitregistration_submit_nonce'),
    ]);

    register_block_type('simplekitregistration/form', [
        'editor_script'   => 'simplekitregistration-block',
        'render_callback' => 'simplekitregistration_render_form_by_attributes',
        'attributes'      => [
            'form_id' => [
                'type'    => 'number',
                'default' => 0,
            ],
            'code' => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);

    register_block_type('simplekitregistration/access', [
        'editor_script'   => 'simplekitregistration-block',
        'render_callback' => 'simplekitregistration_render_access_block',
        'attributes'      => [],
    ]);
}

// ---------------------------------------------------------------------------
// Shortcode fallback for the public form
// ---------------------------------------------------------------------------
add_shortcode('simplekitregistration', 'simplekitregistration_shortcode');

function simplekitregistration_shortcode($atts) {
    $atts = shortcode_atts([
        'id'   => 0,
        'code' => '',
    ], $atts);

    return simplekitregistration_render_form_by_attributes([
        'form_id' => (int) $atts['id'],
        'code'    => sanitize_text_field($atts['code']),
    ]);
}

// ---------------------------------------------------------------------------
// Frontend assets
// ---------------------------------------------------------------------------
add_action('wp_enqueue_scripts', 'simplekitregistration_enqueue_frontend_assets');

function simplekitregistration_enqueue_frontend_assets() {
    wp_enqueue_style(
        'simplekitregistration-frontend',
        SIMPLEKITREGISTRATION_PLUGIN_URL . 'assets/frontend.css',
        [],
        SIMPLEKITREGISTRATION_VERSION
    );

    wp_enqueue_script(
        'simplekitregistration-frontend',
        SIMPLEKITREGISTRATION_PLUGIN_URL . 'assets/frontend.js',
        [],
        SIMPLEKITREGISTRATION_VERSION,
        true
    );

    wp_localize_script('simplekitregistration-frontend', 'simplekitregistration_frontend', [
        'strings' => [
            'sending'         => __('Sending...', 'simplekitregistration'),
            'error'           => __('Error submitting. Please try again.', 'simplekitregistration'),
            'connectionError' => __('Connection error. Please try again.', 'simplekitregistration'),
        ],
    ]);
}
