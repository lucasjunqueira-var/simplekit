<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Defaults
// ---------------------------------------------------------------------------
function simplekitaccessibility_default_settings() {
    return [
        // Display
        'enabled'           => 1,
        'scope'             => 'singular',
        'position'          => 'middle-right',
        'offset_x'          => 20,
        'offset_y'          => 20,
        'shift_x'           => 0,
        'shift_y'           => 0,
        // Button
        'button_text'       => '',
        'button_aria_label' => __('Accessibility tools', 'simplekitaccessibility'),
        'button_image'      => '',
        'button_image_alt'  => '',
        'button_size'       => 48,
        'button_shape'      => 'circle',
        'button_radius'     => 8,
        'button_bg'         => '#1d2327',
        'button_fg'         => '#ffffff',
        'button_bg_hover'   => '#135e96',
        // Panel
        'panel_bg'          => '#ffffff',
        'panel_fg'          => '#1d2327',
        'panel_border'      => '#1d2327',
        'panel_font_size'   => 15,
        'panel_width'       => 280,
        'panel_radius'      => 8,
        // Texts
        'panel_title'       => __('Accessibility', 'simplekitaccessibility'),
        'label_font'        => __('Text size', 'simplekitaccessibility'),
        'label_line'        => __('Line spacing', 'simplekitaccessibility'),
        'label_letter'      => __('Letter spacing', 'simplekitaccessibility'),
        'label_contrast'    => __('High contrast', 'simplekitaccessibility'),
        'text_increase'     => __('Increase', 'simplekitaccessibility'),
        'text_decrease'     => __('Decrease', 'simplekitaccessibility'),
        'text_reset'        => __('Reset', 'simplekitaccessibility'),
        'text_reset_all'    => __('Reset all', 'simplekitaccessibility'),
        'text_close'        => __('Close', 'simplekitaccessibility'),
        'text_on'           => __('On', 'simplekitaccessibility'),
        'text_off'          => __('Off', 'simplekitaccessibility'),
        // Advanced
        'scale_method'      => 'selectors',
        'contrast_method'   => 'palette',
        'persistence'       => 'none',
        'custom_css'        => '',
        'high_contrast_css' => '',
    ];
}

/**
 * Allowed values for the enumerated settings.
 *
 * @return array
 */
function simplekitaccessibility_choices() {
    return [
        'scope'           => ['singular', 'site'],
        'position'        => [
            'top-left',
            'top-center',
            'top-right',
            'middle-left',
            'middle-right',
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ],
        'button_shape'    => ['circle', 'rounded', 'square'],
        'scale_method'    => ['selectors', 'measured'],
        'contrast_method' => ['palette', 'filter'],
        'persistence'     => ['none', 'local', 'session'],
    ];
}

/**
 * Position labels used by the Setup page.
 *
 * @return array
 */
function simplekitaccessibility_position_labels() {
    return [
        'top-left'      => __('Top left', 'simplekitaccessibility'),
        'top-center'    => __('Top center', 'simplekitaccessibility'),
        'top-right'     => __('Top right', 'simplekitaccessibility'),
        'middle-left'   => __('Middle left', 'simplekitaccessibility'),
        'middle-right'  => __('Middle right', 'simplekitaccessibility'),
        'bottom-left'   => __('Bottom left', 'simplekitaccessibility'),
        'bottom-center' => __('Bottom center', 'simplekitaccessibility'),
        'bottom-right'  => __('Bottom right', 'simplekitaccessibility'),
    ];
}

// ---------------------------------------------------------------------------
// Read helpers
// ---------------------------------------------------------------------------
function simplekitaccessibility_get_settings() {
    $saved = get_option('simplekitaccessibility_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    return wp_parse_args($saved, simplekitaccessibility_default_settings());
}

function simplekitaccessibility_get($key, $fallback = '') {
    $settings = simplekitaccessibility_get_settings();
    return array_key_exists($key, $settings) ? $settings[$key] : $fallback;
}

/**
 * Step tables used by the frontend. Values are multipliers; `null` marks the
 * baseline entry of a table (the moment where no CSS class is applied).
 *
 * @return array
 */
function simplekitaccessibility_steps() {
    $steps = [
        'font'   => [0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.6, 1.8, 2.0],
        'line'   => [1.0, 1.2, 1.4, null, 1.7, 1.9, 2.1, 2.3],
        'letter' => [-0.02, -0.01, 0.0, 0.02, 0.04, 0.06, 0.08, 0.10],
    ];

    $steps = apply_filters('simplekitaccessibility_steps', $steps);

    foreach (['font', 'line', 'letter'] as $group) {
        if (!is_array($steps[$group]) || count($steps[$group]) < 2) {
            $steps[$group] = [1.0];
        }
        $steps[$group] = array_values($steps[$group]);
    }

    return $steps;
}

/**
 * Baseline (neutral) index of each step table.
 *
 * @return array
 */
function simplekitaccessibility_baseline() {
    $baseline = [
        'font'   => 2,
        'line'   => 3,
        'letter' => 2,
    ];

    return apply_filters('simplekitaccessibility_baseline', $baseline);
}

// ---------------------------------------------------------------------------
// Sanitization
// ---------------------------------------------------------------------------
function simplekitaccessibility_sanitize_checkbox($value) {
    return (string) $value === '1' ? 1 : 0;
}

/**
 * Coerces a submitted value to a string, ignoring arrays and objects.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function simplekitaccessibility_scalar_value($value) {
    return is_scalar($value) ? (string) $value : '';
}

/**
 * CSS is printed inside a <style> element. Tags are stripped and external
 * imports are removed so the field cannot break out of the element.
 */
function simplekitaccessibility_sanitize_css($css) {
    $css = sanitize_textarea_field(simplekitaccessibility_scalar_value($css));
    $css = preg_replace('/@import[^;]*;?/i', '', $css);
    $css = preg_replace('/expression\s*\(/i', '', $css);
    $css = str_replace(['</style', '<'], '', (string) $css);
    return trim((string) $css);
}

function simplekitaccessibility_sanitize_settings($input) {
    $defaults = simplekitaccessibility_default_settings();
    $input    = is_array($input) ? $input : [];
    $out      = [];

    $out['enabled'] = empty($input['enabled']) ? 0 : 1;

    foreach (simplekitaccessibility_choices() as $key => $allowed) {
        $raw       = isset($input[$key]) ? simplekitaccessibility_scalar_value(wp_unslash($input[$key])) : '';
        $value     = sanitize_key($raw);
        $out[$key] = in_array($value, $allowed, true) ? $value : $defaults[$key];
    }

    $ranges = [
        'offset_x'        => [0, 200],
        'offset_y'        => [0, 200],
        'button_size'     => [32, 96],
        'button_radius'   => [0, 48],
        'panel_font_size' => [11, 24],
        'panel_width'     => [220, 420],
        'panel_radius'    => [0, 32],
    ];
    foreach ($ranges as $key => $range) {
        $raw       = isset($input[$key]) ? simplekitaccessibility_scalar_value(wp_unslash($input[$key])) : '';
        $value     = $raw === '' ? (int) $defaults[$key] : absint($raw);
        $out[$key] = max($range[0], min($range[1], $value));
    }

    // Shifts are signed: they move the button away from the center of an axis.
    $signed_ranges = [
        'shift_x' => [-200, 200],
        'shift_y' => [-200, 200],
    ];
    foreach ($signed_ranges as $key => $range) {
        $raw       = isset($input[$key]) ? simplekitaccessibility_scalar_value(wp_unslash($input[$key])) : '';
        $value     = $raw === '' ? (int) $defaults[$key] : (int) $raw;
        $out[$key] = max($range[0], min($range[1], $value));
    }

    $colors = ['button_bg', 'button_fg', 'button_bg_hover', 'panel_bg', 'panel_fg', 'panel_border'];
    foreach ($colors as $key) {
        $raw       = isset($input[$key]) ? simplekitaccessibility_scalar_value(wp_unslash($input[$key])) : '';
        $value     = sanitize_hex_color($raw);
        $out[$key] = $value ? $value : $defaults[$key];
    }

    $texts = [
        'button_text',
        'button_aria_label',
        'button_image_alt',
        'panel_title',
        'label_font',
        'label_line',
        'label_letter',
        'label_contrast',
        'text_increase',
        'text_decrease',
        'text_reset',
        'text_reset_all',
        'text_close',
        'text_on',
        'text_off',
    ];
    foreach ($texts as $key) {
        $raw       = isset($input[$key]) ? simplekitaccessibility_scalar_value(wp_unslash($input[$key])) : '';
        $out[$key] = isset($input[$key]) ? sanitize_text_field($raw) : $defaults[$key];
    }

    $out['button_image'] = isset($input['button_image'])
        ? esc_url_raw(trim(simplekitaccessibility_scalar_value(wp_unslash($input['button_image']))))
        : '';

    $out['custom_css'] = simplekitaccessibility_sanitize_css(
        isset($input['custom_css']) ? wp_unslash($input['custom_css']) : ''
    );

    $out['high_contrast_css'] = simplekitaccessibility_sanitize_css(
        isset($input['high_contrast_css']) ? wp_unslash($input['high_contrast_css']) : ''
    );

    return $out;
}

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitaccessibility_register_settings');

function simplekitaccessibility_register_settings() {
    register_setting('simplekitaccessibility', 'simplekitaccessibility_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'simplekitaccessibility_sanitize_settings',
        'default'           => simplekitaccessibility_default_settings(),
        'show_in_rest'      => false,
    ]);
}
