<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------
add_action('wp_enqueue_scripts', 'simplekitaccessibility_enqueue_assets');

function simplekitaccessibility_enqueue_assets() {
    $settings = simplekitaccessibility_get_settings();

    if (empty($settings['enabled'])) {
        return;
    }

    wp_enqueue_style(
        'simplekitaccessibility',
        SIMPLEKITACCESSIBILITY_PLUGIN_URL . 'assets/frontend.css',
        [],
        SIMPLEKITACCESSIBILITY_VERSION
    );

    wp_enqueue_script(
        'simplekitaccessibility',
        SIMPLEKITACCESSIBILITY_PLUGIN_URL . 'assets/frontend.js',
        [],
        SIMPLEKITACCESSIBILITY_VERSION,
        true
    );

    wp_localize_script('simplekitaccessibility', 'simplekitaccessibility_config', simplekitaccessibility_js_config());

    $inline  = simplekitaccessibility_widget_css($settings);
    $inline .= simplekitaccessibility_scale_css($settings);
    $inline .= simplekitaccessibility_contrast_css($settings);
    $inline .= $settings['custom_css'] !== '' ? $settings['custom_css'] . "\n" : '';

    wp_add_inline_style('simplekitaccessibility', $inline);
}

/**
 * Configuration handed to assets/frontend.js.
 *
 * @return array
 */
function simplekitaccessibility_js_config() {
    $settings = simplekitaccessibility_get_settings();
    $steps    = simplekitaccessibility_steps();
    $counts   = [];

    foreach ($steps as $group => $table) {
        $counts[$group] = count($table);
    }

    return [
        'storage'     => $settings['persistence'],
        'key'         => 'simplekitaccessibility',
        'scaleMethod' => $settings['scale_method'],
        // Only the measured method needs the selector list at runtime.
        'fontSelectors' => $settings['scale_method'] === 'measured' ? simplekitaccessibility_text_selectors() : '',
        'steps'    => [
            'font'   => array_map('floatval', $steps['font']),
            'line'   => array_map(function ($value) {
                return $value === null ? null : (float) $value;
            }, $steps['line']),
            'letter' => array_map('floatval', $steps['letter']),
        ],
        'baseline' => simplekitaccessibility_baseline(),
        'counts'   => $counts,
        'labels'   => [
            'font'      => $settings['label_font'],
            'line'      => $settings['label_line'],
            'letter'    => $settings['label_letter'],
            'contrast'  => $settings['label_contrast'],
            'increase'  => $settings['text_increase'],
            'decrease'  => $settings['text_decrease'],
            'reset'     => $settings['text_reset'],
            'resetAll'  => $settings['text_reset_all'],
            'close'     => $settings['text_close'],
            'on'        => $settings['text_on'],
            'off'       => $settings['text_off'],
        ],
    ];
}

// ---------------------------------------------------------------------------
// CSS generators
// ---------------------------------------------------------------------------
/**
 * Text containers affected by the reading adjustments.
 *
 * @return string Comma separated selector list.
 */
function simplekitaccessibility_text_selectors() {
    // Headings are deliberately excluded: the multiplier is relative to the
    // parent font size, so scaling them would flatten the type hierarchy.
    $selectors = [
        'p',
        'li',
        'blockquote',
        'dd',
        'dt',
        'figcaption',
        'td',
        'th',
        'address',
        'cite',
        'pre',
        'code',
        'label',
        'legend',
        'button:not(.skacc-toggle):not(.skacc-action):not(.skacc-close)',
        'input:not([type="checkbox"]):not([type="radio"])',
        'select',
        'textarea',
    ];

    $selectors = apply_filters('simplekitaccessibility_text_selectors', $selectors);

    return implode(', ', array_filter(array_map('trim', (array) $selectors)));
}

/**
 * Containers that receive letter spacing. Unlike font size, letter spacing is
 * an absolute length per element, so headings can be included safely.
 *
 * @return string Comma separated selector list.
 */
function simplekitaccessibility_letter_selectors() {
    $selectors = array_filter(array_map('trim', explode(',', simplekitaccessibility_text_selectors())));
    $selectors = array_merge($selectors, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);

    $selectors = apply_filters('simplekitaccessibility_letter_selectors', $selectors);

    return implode(', ', array_filter(array_map('trim', (array) $selectors)));
}

/**
 * Block level text containers used for line height.
 *
 * @return string Comma separated selector list.
 */
function simplekitaccessibility_block_selectors() {
    $selectors = [
        'p',
        'li',
        'blockquote',
        'dd',
        'dt',
        'figcaption',
        'td',
        'th',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'address',
        'cite',
        'pre',
        'label',
        'legend',
    ];

    $selectors = apply_filters('simplekitaccessibility_block_selectors', $selectors);

    return implode(', ', array_filter(array_map('trim', (array) $selectors)));
}

/**
 * Format a multiplier for CSS output.
 */
function simplekitaccessibility_css_number($value) {
    $value = (float) $value;
    $text  = rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    return $text === '' || $text === '-' ? '0' : $text;
}

/**
 * CSS variables of the widget, built only from validated colors and integers.
 */
function simplekitaccessibility_widget_css($settings) {
    $radius = simplekitaccessibility_button_radius($settings);

    return sprintf(
        '#skacc-widget{--skacc-btn-bg:%1$s;--skacc-btn-fg:%2$s;--skacc-btn-hover:%3$s;--skacc-btn-size:%4$dpx;--skacc-btn-radius:%5$s;--skacc-panel-bg:%6$s;--skacc-panel-fg:%7$s;--skacc-panel-border:%8$s;--skacc-panel-font-size:%9$dpx;--skacc-panel-width:%10$dpx;--skacc-panel-radius:%11$dpx;--skacc-offset-x:%12$dpx;--skacc-offset-y:%13$dpx;--skacc-shift-x:%14$dpx;--skacc-shift-y:%15$dpx;}',
        $settings['button_bg'],
        $settings['button_fg'],
        $settings['button_bg_hover'],
        (int) $settings['button_size'],
        $radius,
        $settings['panel_bg'],
        $settings['panel_fg'],
        $settings['panel_border'],
        (int) $settings['panel_font_size'],
        (int) $settings['panel_width'],
        (int) $settings['panel_radius'],
        (int) $settings['offset_x'],
        (int) $settings['offset_y'],
        (int) $settings['shift_x'],
        (int) $settings['shift_y']
    ) . "\n";
}

/**
 * Border radius of the floating button.
 */
function simplekitaccessibility_button_radius($settings) {
    if ($settings['button_shape'] === 'circle') {
        return '50%';
    }
    if ($settings['button_shape'] === 'square') {
        return '0';
    }
    return (int) $settings['button_radius'] . 'px';
}

/**
 * Scale, line height and letter spacing rules.
 *
 * The multiplier is applied to a curated list of text containers. Nested
 * containers are reset to 1em so the factor is never applied twice.
 */
function simplekitaccessibility_scale_css($settings = null) {
    if (!is_array($settings)) {
        $settings = simplekitaccessibility_get_settings();
    }

    $steps    = simplekitaccessibility_steps();
    $baseline = simplekitaccessibility_baseline();
    $text     = simplekitaccessibility_text_selectors();
    $letter   = simplekitaccessibility_letter_selectors();
    $block    = simplekitaccessibility_block_selectors();
    $css      = '';

    // The measured method applies inline sizes in JavaScript, so the font
    // rules would only fight with the measured values.
    $font_steps = $settings['scale_method'] === 'measured' ? [] : $steps['font'];

    foreach ($font_steps as $index => $factor) {
        if ($index === (int) $baseline['font'] || $factor === null) {
            continue;
        }
        $factor = (float) $factor;
        if ($factor <= 0) {
            continue;
        }
        $css .= sprintf(
            "html.skacc-fs-%1\$d :where(%2\$s){font-size:calc(1em * %3\$s) !important;}\n",
            (int) $index,
            $text,
            simplekitaccessibility_css_number($factor)
        );
        $css .= sprintf(
            "html.skacc-fs-%1\$d :where(li,td,dd,blockquote) :where(p,li,td,th,dd,dt,blockquote){font-size:1em !important;}\n",
            (int) $index
        );
    }

    foreach ($steps['line'] as $index => $value) {
        if ($index === (int) $baseline['line'] || $value === null) {
            continue;
        }
        $value = (float) $value;
        if ($value <= 0) {
            continue;
        }
        $css .= sprintf(
            "html.skacc-lh-%1\$d :where(%2\$s){line-height:%3\$s !important;}\n",
            (int) $index,
            $block,
            simplekitaccessibility_css_number($value)
        );
    }

    $css .= "html[class*=\"skacc-ls-\"] :where(.dashicons,[class^=\"dashicons-\"],[class*=\" dashicons-\"],.fa,.fas,.far,.fab,[class^=\"fa-\"],[class*=\" fa-\"]){letter-spacing:0 !important;}\n";

    foreach ($steps['letter'] as $index => $value) {
        if ($index === (int) $baseline['letter'] || $value === null) {
            continue;
        }
        $value = (float) $value;
        if ($value === 0.0) {
            continue;
        }
        $css .= sprintf(
            "html.skacc-ls-%1\$d :where(%2\$s){letter-spacing:%3\$sem !important;}\n",
            (int) $index,
            $letter,
            simplekitaccessibility_css_number($value)
        );
    }

    return $css;
}

/**
 * High contrast rules. The palette method never changes layout; the filter
 * method is opt-in because filters create a containing block for fixed
 * positioned descendants of body.
 */
function simplekitaccessibility_contrast_css($settings) {
    $css = '';

    if ($settings['contrast_method'] === 'filter') {
        $css .= "html.skacc-contrast body{background:#ffffff !important;color:#000000 !important;}\n";
        $css .= "html.skacc-contrast body > *:not(#skacc-widget){filter:contrast(1.5) saturate(0.9);}\n";
    } else {
        $css .= "html.skacc-contrast body{background:#ffffff !important;color:#000000 !important;}\n";
        $css .= "html.skacc-contrast :where(p,li,blockquote,dd,dt,figcaption,td,th,h1,h2,h3,h4,h5,h6,address,cite,label,legend,strong,em,small,code,pre){color:#000000 !important;}\n";
        $css .= "html.skacc-contrast :where(a,a:visited){color:#0b3d91 !important;text-decoration:underline !important;}\n";
        $css .= "html.skacc-contrast :where(a:hover,a:focus){color:#000000 !important;}\n";
        $css .= "html.skacc-contrast :where(button:not(.skacc-toggle):not(.skacc-action):not(.skacc-close),input[type=\"submit\"],input[type=\"button\"],.wp-block-button__link){background:#000000 !important;color:#ffffff !important;border:2px solid #000000 !important;}\n";
        $css .= "html.skacc-contrast :where(input:not([type=\"submit\"]):not([type=\"button\"]):not([type=\"checkbox\"]):not([type=\"radio\"]),select,textarea){background:#ffffff !important;color:#000000 !important;border:2px solid #000000 !important;}\n";
        $css .= "html.skacc-contrast :where(table,td,th){border-color:#000000 !important;}\n";
        $css .= "html.skacc-contrast :where(mark){background:#ffe600 !important;color:#000000 !important;}\n";
        $css .= "html.skacc-contrast :focus-visible{outline:3px solid #000000 !important;outline-offset:2px !important;}\n";
    }

    if ($settings['high_contrast_css'] !== '') {
        $css .= $settings['high_contrast_css'] . "\n";
    }

    return $css;
}

// ---------------------------------------------------------------------------
// Persistence bootstrap (runs before paint, avoids a flash of default state)
// ---------------------------------------------------------------------------
add_action('wp_head', 'simplekitaccessibility_print_bootstrap', 1);

function simplekitaccessibility_print_bootstrap() {
    $settings = simplekitaccessibility_get_settings();

    if (empty($settings['enabled']) || $settings['persistence'] === 'none') {
        return;
    }

    $steps    = simplekitaccessibility_steps();
    $baseline = simplekitaccessibility_baseline();
    $counts   = [];

    foreach ($steps as $group => $table) {
        $counts[$group] = count($table);
    }

    $config = [
        'key'         => 'simplekitaccessibility',
        'store'       => $settings['persistence'] === 'local' ? 'localStorage' : 'sessionStorage',
        'baseline'    => $baseline,
        'counts'      => $counts,
        'scaleMethod' => $settings['scale_method'],
    ];

    $script = '(function(){"use strict";var cfg=' . wp_json_encode($config) . ';'
        . 'var store=null;try{store=window[cfg.store];}catch(e){store=null;}'
        . 'if(!store){return;}'
        . 'var state=null;try{state=JSON.parse(store.getItem(cfg.key)||"null");}catch(e){state=null;}'
        . 'if(!state||state.v!==1){state=null;}'
        . 'var groups=["font","line","letter"];var prefixes={font:"skacc-fs-",line:"skacc-lh-",letter:"skacc-ls-"};'
        . 'var html=document.documentElement;html.classList.add("skacc-on");'
        . 'for(var g=0;g<groups.length;g++){'
        . 'var group=groups[g],prefix=prefixes[group],base=cfg.baseline[group],max=cfg.counts[group];'
        . 'if(group==="font"&&cfg.scaleMethod==="measured"){continue;}'
        . 'var value=state&&typeof state[group]==="number"?state[group]:base;'
        . 'if(typeof base!=="number"){continue;}'
        . 'if(value!==base&&value>=0&&value<max){html.classList.add(prefix+value);}'
        . '}'
        . 'if(state&&state.contrast){html.classList.add("skacc-contrast");}'
        . '})();';

    wp_print_inline_script_tag($script, ['id' => 'simplekitaccessibility-bootstrap']);
}

// ---------------------------------------------------------------------------
// Widget
// ---------------------------------------------------------------------------
add_action('wp_body_open', 'simplekitaccessibility_render_widget', 5);
add_action('wp_footer', 'simplekitaccessibility_render_widget_fallback', 5);

function simplekitaccessibility_render_widget_fallback() {
    simplekitaccessibility_render_widget();
}

/**
 * Whether the floating button must be printed on the current request.
 *
 * @return bool
 */
function simplekitaccessibility_should_display() {
    $settings = simplekitaccessibility_get_settings();

    if (empty($settings['enabled'])) {
        return false;
    }

    if ($settings['scope'] === 'singular' && !is_singular()) {
        return false;
    }

    if (is_singular()) {
        $post_id = get_queried_object_id();
        if ($post_id && get_post_meta($post_id, '_simplekitaccessibility_disabled', true) === '1') {
            return false;
        }
    }

    return (bool) apply_filters('simplekitaccessibility_should_display', true);
}

function simplekitaccessibility_render_widget() {
    static $rendered = false;

    if ($rendered || !simplekitaccessibility_should_display()) {
        return;
    }

    $rendered = true;

    // The markup is escaped inside the builder.
    echo simplekitaccessibility_widget_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Default button icon (universal access).
 */
function simplekitaccessibility_default_icon() {
    return '<svg class="skacc-toggle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<circle cx="12" cy="4" r="2" fill="currentColor"></circle>'
        . '<path d="M12 7c-2.8 0-5.2-.8-7-1.6l-.8 1.8c1.6.7 3.5 1.3 5.3 1.5L9 14l-2.4 6.2 1.9.7L11 15h2l2.5 5.9 1.9-.7L15 14l-.5-5.3c1.8-.2 3.7-.8 5.3-1.5L19 5.4C17.2 6.2 14.8 7 12 7z" fill="currentColor"></path>'
        . '</svg>';
}

/**
 * One control row: label plus decrease, reset and increase buttons.
 */
function simplekitaccessibility_row_html($settings, $group, $label, $middle_text) {
    $decrease = $settings['text_decrease'] !== '' ? $settings['text_decrease'] : __('Decrease', 'simplekitaccessibility');
    $increase = $settings['text_increase'] !== '' ? $settings['text_increase'] : __('Increase', 'simplekitaccessibility');
    $reset    = $settings['text_reset'] !== '' ? $settings['text_reset'] : __('Reset', 'simplekitaccessibility');

    $html  = '<div class="skacc-row">';
    $html .= '<div class="skacc-row-label" id="skacc-label-' . esc_attr($group) . '">' . esc_html($label) . '</div>';
    $html .= '<div class="skacc-row-controls" role="group" aria-labelledby="skacc-label-' . esc_attr($group) . '">';
    $html .= '<button type="button" class="skacc-action" data-skacc-action="' . esc_attr($group) . '-down" aria-label="' . esc_attr($decrease . ': ' . $label) . '">&minus;</button>';
    $html .= '<button type="button" class="skacc-action skacc-value" data-skacc-action="' . esc_attr($group) . '-reset" aria-label="' . esc_attr($reset . ': ' . $label) . '">' . esc_html($middle_text) . '</button>';
    $html .= '<button type="button" class="skacc-action" data-skacc-action="' . esc_attr($group) . '-up" aria-label="' . esc_attr($increase . ': ' . $label) . '">+</button>';
    $html .= '</div></div>';

    return $html;
}

/**
 * Widget markup.
 *
 * @return string
 */
function simplekitaccessibility_widget_html() {
    $settings = simplekitaccessibility_get_settings();
    $steps    = simplekitaccessibility_steps();
    $baseline = simplekitaccessibility_baseline();

    $classes = ['skacc-widget', 'skacc-pos-' . $settings['position']];
    if (is_admin_bar_showing()) {
        $classes[] = 'skacc-has-adminbar';
    }

    $label = $settings['button_aria_label'] !== ''
        ? $settings['button_aria_label']
        : __('Accessibility tools', 'simplekitaccessibility');

    $inner = '';
    if ($settings['button_image'] !== '') {
        $inner .= '<img class="skacc-toggle-icon" src="' . esc_url($settings['button_image']) . '" alt="' . esc_attr($settings['button_image_alt']) . '" />';
    } elseif ($settings['button_text'] === '') {
        $inner .= simplekitaccessibility_default_icon();
    }
    if ($settings['button_text'] !== '') {
        $inner .= '<span class="skacc-toggle-text">' . esc_html($settings['button_text']) . '</span>';
    }
    if ($inner === '') {
        $inner = simplekitaccessibility_default_icon();
    }

    $font_factor = isset($steps['font'][$baseline['font']]) ? (float) $steps['font'][$baseline['font']] : 1.0;
    $font_text   = (int) round($font_factor * 100) . '%';
    $reset_text  = $settings['text_reset'] !== '' ? $settings['text_reset'] : __('Reset', 'simplekitaccessibility');
    $off_text    = $settings['text_off'] !== '' ? $settings['text_off'] : __('Off', 'simplekitaccessibility');

    $html  = '<div id="skacc-widget" class="' . esc_attr(implode(' ', $classes)) . '">';
    $html .= '<button type="button" id="skacc-toggle" class="skacc-toggle" aria-expanded="false" aria-controls="skacc-panel" aria-label="' . esc_attr($label) . '">' . $inner . '</button>';
    $html .= '<div id="skacc-panel" class="skacc-panel" role="group" aria-labelledby="skacc-panel-title" tabindex="-1" hidden>';
    $html .= '<div class="skacc-panel-header">';
    $html .= '<div class="skacc-title" id="skacc-panel-title">' . esc_html($settings['panel_title']) . '</div>';
    $html .= '<button type="button" class="skacc-close" data-skacc-action="panel" aria-label="' . esc_attr($settings['text_close']) . '"><span aria-hidden="true">&times;</span></button>';
    $html .= '</div>';

    $html .= simplekitaccessibility_row_html($settings, 'font', $settings['label_font'], $font_text);
    $html .= simplekitaccessibility_row_html($settings, 'line', $settings['label_line'], $reset_text);
    $html .= simplekitaccessibility_row_html($settings, 'letter', $settings['label_letter'], $reset_text);

    $html .= '<div class="skacc-row">';
    $html .= '<div class="skacc-row-label" id="skacc-label-contrast">' . esc_html($settings['label_contrast']) . '</div>';
    $html .= '<div class="skacc-row-controls" role="group" aria-labelledby="skacc-label-contrast">';
    $html .= '<button type="button" class="skacc-action skacc-wide" data-skacc-action="contrast" aria-pressed="false">' . esc_html($off_text) . '</button>';
    $html .= '</div></div>';

    $html .= '<div class="skacc-panel-footer">';
    $html .= '<button type="button" class="skacc-action skacc-wide skacc-reset-all" data-skacc-action="reset-all">' . esc_html($settings['text_reset_all']) . '</button>';
    $html .= '</div>';

    $html .= '</div></div>';
    $html .= '<div id="skacc-live" class="skacc-visually-hidden" aria-live="polite" aria-atomic="true"></div>';

    return $html;
}
