<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Shortcode and declarative triggers
//
// Every element of the page can drive the adjustments through
// data-skacc-action. The shortcode prints such an element for authors that
// want their own controls, independent of the floating button.
// ---------------------------------------------------------------------------
function simplekitaccessibility_actions() {
    return [
        'font-up',
        'font-down',
        'font-reset',
        'line-up',
        'line-down',
        'line-reset',
        'letter-up',
        'letter-down',
        'letter-reset',
        'contrast',
        'reset-all',
        'panel',
    ];
}

function simplekitaccessibility_is_action($action) {
    return in_array($action, simplekitaccessibility_actions(), true);
}

/**
 * [simplekitaccessibility action="font-up" label="A+" class="my-class"]
 */
add_shortcode('simplekitaccessibility', 'simplekitaccessibility_shortcode');

function simplekitaccessibility_shortcode($atts) {
    $atts = shortcode_atts([
        'action' => '',
        'label'  => '',
        'class'  => '',
    ], $atts, 'simplekitaccessibility');

    $action = sanitize_key($atts['action']);
    if (!simplekitaccessibility_is_action($action)) {
        return '';
    }

    $settings = simplekitaccessibility_get_settings();
    $label    = $atts['label'] !== '' ? $atts['label'] : $settings['button_aria_label'];

    $classes = ['skacc-trigger'];
    if ($atts['class'] !== '') {
        $classes[] = $atts['class'];
    }

    return '<button type="button" class="' . esc_attr(implode(' ', $classes)) . '" data-skacc-action="' . esc_attr($action) . '">' . esc_html($label) . '</button>';
}
