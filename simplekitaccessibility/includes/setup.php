<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Field helpers
// ---------------------------------------------------------------------------
function simplekitaccessibility_field_name($key) {
    return 'simplekitaccessibility_settings[' . $key . ']';
}

function simplekitaccessibility_text_field($settings, $key, $label, $description = '') {
    ?>
    <tr>
        <th scope="row">
            <label for="skacc-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
        </th>
        <td>
            <input type="text" class="regular-text" id="skacc-<?php echo esc_attr($key); ?>"
                   name="<?php echo esc_attr(simplekitaccessibility_field_name($key)); ?>"
                   value="<?php echo esc_attr($settings[$key]); ?>" />
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

function simplekitaccessibility_number_field($settings, $key, $label, $min, $max, $step = 1, $description = '') {
    ?>
    <tr>
        <th scope="row">
            <label for="skacc-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
        </th>
        <td>
            <input type="number" class="small-text" id="skacc-<?php echo esc_attr($key); ?>"
                   name="<?php echo esc_attr(simplekitaccessibility_field_name($key)); ?>"
                   value="<?php echo esc_attr($settings[$key]); ?>"
                   min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" step="<?php echo esc_attr($step); ?>" />
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

function simplekitaccessibility_color_field($settings, $key, $label, $description = '') {
    ?>
    <tr>
        <th scope="row">
            <label for="skacc-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
        </th>
        <td>
            <input type="text" class="skacc-color" id="skacc-<?php echo esc_attr($key); ?>"
                   name="<?php echo esc_attr(simplekitaccessibility_field_name($key)); ?>"
                   value="<?php echo esc_attr($settings[$key]); ?>"
                   data-default-color="<?php echo esc_attr($settings[$key]); ?>" />
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

function simplekitaccessibility_select_field($settings, $key, $label, $options, $description = '') {
    ?>
    <tr>
        <th scope="row">
            <label for="skacc-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
        </th>
        <td>
            <select id="skacc-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(simplekitaccessibility_field_name($key)); ?>">
                <?php foreach ($options as $value => $text) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings[$key], $value); ?>>
                        <?php echo esc_html($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

// ---------------------------------------------------------------------------
// Page: Setup
// ---------------------------------------------------------------------------
function simplekitaccessibility_page_setup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitaccessibility'));
    }

    $settings = simplekitaccessibility_get_settings();
    $steps    = simplekitaccessibility_steps();
    $baseline = simplekitaccessibility_baseline();

    $font_percent = isset($steps['font'][$baseline['font']]) ? (int) round((float) $steps['font'][$baseline['font']] * 100) : 100;

    $preview_style = sprintf(
        '--skacc-btn-bg:%1$s;--skacc-btn-fg:%2$s;--skacc-btn-hover:%3$s;--skacc-btn-size:%4$dpx;--skacc-btn-radius:%5$s;--skacc-panel-bg:%6$s;--skacc-panel-fg:%7$s;--skacc-panel-border:%8$s;--skacc-panel-font-size:%9$dpx;--skacc-panel-width:%10$dpx;--skacc-panel-radius:%11$dpx;',
        $settings['button_bg'],
        $settings['button_fg'],
        $settings['button_bg_hover'],
        (int) $settings['button_size'],
        simplekitaccessibility_button_radius($settings),
        $settings['panel_bg'],
        $settings['panel_fg'],
        $settings['panel_border'],
        (int) $settings['panel_font_size'],
        (int) $settings['panel_width'],
        (int) $settings['panel_radius']
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Setup', 'simplekitaccessibility'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields('simplekitaccessibility'); ?>

            <h2><?php esc_html_e('Display', 'simplekitaccessibility'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Floating button', 'simplekitaccessibility'); ?></th>
                    <td>
                        <input type="hidden" name="<?php echo esc_attr(simplekitaccessibility_field_name('enabled')); ?>" value="0" />
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(simplekitaccessibility_field_name('enabled')); ?>" value="1" <?php checked((int) $settings['enabled'], 1); ?> />
                            <?php esc_html_e('Show the accessibility button on the site', 'simplekitaccessibility'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When the plugin is enabled the JavaScript API is loaded on every page, even if the button is hidden here or on a specific page.', 'simplekitaccessibility'); ?>
                        </p>
                    </td>
                </tr>
                <?php
                simplekitaccessibility_select_field(
                    $settings,
                    'scope',
                    __('Where to show the button', 'simplekitaccessibility'),
                    [
                        'singular' => __('Posts, pages and public custom post types', 'simplekitaccessibility'),
                        'site'     => __('Entire site', 'simplekitaccessibility'),
                    ],
                    __('The per-content option in the editor only applies to single views.', 'simplekitaccessibility')
                );

                simplekitaccessibility_select_field(
                    $settings,
                    'position',
                    __('Position', 'simplekitaccessibility'),
                    simplekitaccessibility_position_labels()
                );

                simplekitaccessibility_number_field($settings, 'offset_x', __('Horizontal edge distance', 'simplekitaccessibility'), 0, 200, 1, __('Distance from the left or right edge, in pixels. Used by the positions anchored to a side.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'shift_x', __('Horizontal center shift', 'simplekitaccessibility'), -200, 200, 1, __('Moves the button left or right of the horizontal center. Negative values move it left. Used by the top center and bottom center positions.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'offset_y', __('Vertical edge distance', 'simplekitaccessibility'), 0, 200, 1, __('Distance from the top or bottom edge, in pixels. Used by the top and bottom positions.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'shift_y', __('Vertical center shift', 'simplekitaccessibility'), -200, 200, 1, __('Moves the button up or down from the vertical center. Negative values move it up. Used by the middle left and middle right positions.', 'simplekitaccessibility'));
                ?>
            </table>

            <h2><?php esc_html_e('Button', 'simplekitaccessibility'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                simplekitaccessibility_text_field($settings, 'button_text', __('Button text', 'simplekitaccessibility'), __('Optional. Leave empty to show only the image or the default icon.', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'button_aria_label', __('Accessible name', 'simplekitaccessibility'), __('Read by screen readers. Always required, even when the button has a visible text.', 'simplekitaccessibility'));
                ?>
                <tr>
                    <th scope="row">
                        <label for="skacc-button_image"><?php esc_html_e('Button image', 'simplekitaccessibility'); ?></label>
                    </th>
                    <td>
                        <div class="skacc-media-field">
                            <input type="text" class="regular-text" id="skacc-button_image"
                                   name="<?php echo esc_attr(simplekitaccessibility_field_name('button_image')); ?>"
                                   value="<?php echo esc_attr($settings['button_image']); ?>" />
                            <button type="button" class="button skacc-media-button">
                                <?php esc_html_e('Select image', 'simplekitaccessibility'); ?>
                            </button>
                            <button type="button" class="button-link skacc-media-clear">
                                <?php esc_html_e('Remove', 'simplekitaccessibility'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Optional. When empty, the default universal access icon is used.', 'simplekitaccessibility'); ?>
                        </p>
                        <?php if ($settings['button_image'] !== '') : ?>
                            <p><img class="skacc-media-preview" src="<?php echo esc_url($settings['button_image']); ?>" alt="" /></p>
                        <?php else : ?>
                            <p><img class="skacc-media-preview" src="" alt="" style="display:none" /></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                simplekitaccessibility_text_field($settings, 'button_image_alt', __('Image alternative text', 'simplekitaccessibility'), __('Optional. The accessible name above is used by the button; keep this empty for a decorative image.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'button_size', __('Button size', 'simplekitaccessibility'), 32, 96, 1, __('Minimum width and height in pixels.', 'simplekitaccessibility'));
                simplekitaccessibility_select_field(
                    $settings,
                    'button_shape',
                    __('Shape', 'simplekitaccessibility'),
                    [
                        'circle'  => __('Circle', 'simplekitaccessibility'),
                        'rounded' => __('Rounded square', 'simplekitaccessibility'),
                        'square'  => __('Square', 'simplekitaccessibility'),
                    ]
                );
                simplekitaccessibility_number_field($settings, 'button_radius', __('Corner radius', 'simplekitaccessibility'), 0, 48, 1, __('Used by the rounded square shape.', 'simplekitaccessibility'));
                simplekitaccessibility_color_field($settings, 'button_bg', __('Background color', 'simplekitaccessibility'));
                simplekitaccessibility_color_field($settings, 'button_fg', __('Text color', 'simplekitaccessibility'));
                simplekitaccessibility_color_field($settings, 'button_bg_hover', __('Background color on hover', 'simplekitaccessibility'));
                ?>
            </table>

            <h2><?php esc_html_e('Panel', 'simplekitaccessibility'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                simplekitaccessibility_color_field($settings, 'panel_bg', __('Background color', 'simplekitaccessibility'));
                simplekitaccessibility_color_field($settings, 'panel_fg', __('Text color', 'simplekitaccessibility'));
                simplekitaccessibility_color_field($settings, 'panel_border', __('Border color', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'panel_font_size', __('Font size', 'simplekitaccessibility'), 11, 24, 1, __('In pixels.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'panel_width', __('Panel width', 'simplekitaccessibility'), 220, 420, 10, __('In pixels. On small screens the panel uses the available width.', 'simplekitaccessibility'));
                simplekitaccessibility_number_field($settings, 'panel_radius', __('Corner radius', 'simplekitaccessibility'), 0, 32);
                ?>
            </table>

            <h2><?php esc_html_e('Texts', 'simplekitaccessibility'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                simplekitaccessibility_text_field($settings, 'panel_title', __('Panel title', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'label_font', __('Text size label', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'label_line', __('Line spacing label', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'label_letter', __('Letter spacing label', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'label_contrast', __('High contrast label', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_increase', __('Increase action', 'simplekitaccessibility'), __('Also used in the accessible name of the plus buttons.', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_decrease', __('Decrease action', 'simplekitaccessibility'), __('Also used in the accessible name of the minus buttons.', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_reset', __('Reset action', 'simplekitaccessibility'), __('Used by the per-group reset buttons.', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_reset_all', __('Reset all action', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_close', __('Close action', 'simplekitaccessibility'), __('Accessible name of the close button.', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_on', __('Contrast state: on', 'simplekitaccessibility'));
                simplekitaccessibility_text_field($settings, 'text_off', __('Contrast state: off', 'simplekitaccessibility'));
                ?>
            </table>

            <h2><?php esc_html_e('Advanced', 'simplekitaccessibility'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                simplekitaccessibility_select_field(
                    $settings,
                    'scale_method',
                    __('Text size method', 'simplekitaccessibility'),
                    [
                        'selectors' => __('CSS selectors (default)', 'simplekitaccessibility'),
                        'measured'  => __('Measured per element (best for themes using pixels)', 'simplekitaccessibility'),
                    ],
                    __('The measured method reads the size of each text element before scaling, preserving the typographic hierarchy of the theme. It only runs in the browser and needs a little more processing when the page changes.', 'simplekitaccessibility')
                );

                simplekitaccessibility_select_field(
                    $settings,
                    'contrast_method',
                    __('High contrast method', 'simplekitaccessibility'),
                    [
                        'palette' => __('Color palette overrides', 'simplekitaccessibility'),
                        'filter'  => __('CSS contrast filter', 'simplekitaccessibility'),
                    ],
                    __('The palette never changes layout. The filter affects the whole page and can break fixed or sticky elements of the theme.', 'simplekitaccessibility')
                );

                simplekitaccessibility_select_field(
                    $settings,
                    'persistence',
                    __('Remember choices', 'simplekitaccessibility'),
                    [
                        'none'    => __('Do not remember (default)', 'simplekitaccessibility'),
                        'local'   => __('Remember in this browser', 'simplekitaccessibility'),
                        'session' => __('Remember only during the visit', 'simplekitaccessibility'),
                    ],
                    __('When remembering is disabled, every page load starts from the original state. Remembering uses browser storage; no data is sent to the server.', 'simplekitaccessibility')
                );
                ?>
                <tr>
                    <th scope="row">
                        <label for="skacc-custom_css"><?php esc_html_e('Custom CSS', 'simplekitaccessibility'); ?></label>
                    </th>
                    <td>
                        <textarea id="skacc-custom_css" name="<?php echo esc_attr(simplekitaccessibility_field_name('custom_css')); ?>" rows="8" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Printed after the plugin styles. Use it to adapt the reading adjustments to a specific theme.', 'simplekitaccessibility'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="skacc-high_contrast_css"><?php esc_html_e('High contrast CSS', 'simplekitaccessibility'); ?></label>
                    </th>
                    <td>
                        <textarea id="skacc-high_contrast_css" name="<?php echo esc_attr(simplekitaccessibility_field_name('high_contrast_css')); ?>" rows="8" class="large-text code"><?php echo esc_textarea($settings['high_contrast_css']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Printed only while high contrast is active, after the default palette rules.', 'simplekitaccessibility'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <h2><?php esc_html_e('Preview', 'simplekitaccessibility'); ?></h2>
        <p class="description">
            <?php esc_html_e('Approximate preview of the button and the panel. The final result depends on the theme.', 'simplekitaccessibility'); ?>
        </p>

        <div class="skacc-preview-area">
            <div id="skacc-preview" class="skacc-preview-widget" style="<?php echo esc_attr($preview_style); ?>">
                <button type="button" class="skacc-preview-toggle" aria-hidden="true" tabindex="-1">
                    <span id="skacc-preview-svg-icon" class="skacc-preview-icon">
                        <?php echo simplekitaccessibility_default_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
                    </span>
                    <img id="skacc-preview-image" class="skacc-preview-icon" src="<?php echo esc_url($settings['button_image']); ?>" alt="" <?php echo $settings['button_image'] === '' ? 'style="display:none"' : ''; ?> />
                    <span id="skacc-preview-toggle-text" class="skacc-preview-toggle-text" <?php echo $settings['button_text'] === '' ? 'style="display:none"' : ''; ?>><?php echo esc_html($settings['button_text']); ?></span>
                </button>

                <div class="skacc-preview-panel">
                    <div class="skacc-preview-title" id="skacc-preview-title"><?php echo esc_html($settings['panel_title']); ?></div>

                    <div class="skacc-preview-row">
                        <div class="skacc-preview-label" id="skacc-preview-label-font"><?php echo esc_html($settings['label_font']); ?></div>
                        <div class="skacc-preview-controls">
                            <span class="skacc-preview-action">&minus;</span>
                            <span class="skacc-preview-action skacc-preview-value" id="skacc-preview-font-value"><?php echo esc_html($font_percent . '%'); ?></span>
                            <span class="skacc-preview-action">+</span>
                        </div>
                    </div>

                    <div class="skacc-preview-row">
                        <div class="skacc-preview-label" id="skacc-preview-label-line"><?php echo esc_html($settings['label_line']); ?></div>
                        <div class="skacc-preview-controls">
                            <span class="skacc-preview-action">&minus;</span>
                            <span class="skacc-preview-action skacc-preview-value"><?php echo esc_html($settings['text_reset']); ?></span>
                            <span class="skacc-preview-action">+</span>
                        </div>
                    </div>

                    <div class="skacc-preview-row">
                        <div class="skacc-preview-label" id="skacc-preview-label-letter"><?php echo esc_html($settings['label_letter']); ?></div>
                        <div class="skacc-preview-controls">
                            <span class="skacc-preview-action">&minus;</span>
                            <span class="skacc-preview-action skacc-preview-value"><?php echo esc_html($settings['text_reset']); ?></span>
                            <span class="skacc-preview-action">+</span>
                        </div>
                    </div>

                    <div class="skacc-preview-row">
                        <div class="skacc-preview-label" id="skacc-preview-label-contrast"><?php echo esc_html($settings['label_contrast']); ?></div>
                        <div class="skacc-preview-controls">
                            <span class="skacc-preview-action skacc-preview-wide" id="skacc-preview-contrast"><?php echo esc_html($settings['text_off']); ?></span>
                        </div>
                    </div>

                    <div class="skacc-preview-footer">
                        <span class="skacc-preview-action skacc-preview-wide" id="skacc-preview-reset-all"><?php echo esc_html($settings['text_reset_all']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="skacc-card">
            <h2><?php esc_html_e('Custom triggers', 'simplekitaccessibility'); ?></h2>
            <p><?php esc_html_e('Any element can drive the adjustments:', 'simplekitaccessibility'); ?></p>
            <p><code><button data-skacc-action="font-up">A+</button></code></p>
            <p><?php esc_html_e('Or use the shortcode:', 'simplekitaccessibility'); ?></p>
            <p><code>[simplekitaccessibility action="contrast" label="High contrast"]</code></p>
            <p><?php esc_html_e('See the Help page for the complete list of actions and the JavaScript API.', 'simplekitaccessibility'); ?></p>
        </div>
    </div>
    <?php
}
