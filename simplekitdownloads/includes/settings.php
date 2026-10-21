<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Settings (Setup page)
// ---------------------------------------------------------------------------

add_action('admin_init', 'simplekitdownloads_register_settings');

function simplekitdownloads_register_settings() {
    register_setting('simplekitdownloads_settings', 'simplekitdownloads_button_text', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'Download',
    ]);

    register_setting('simplekitdownloads_settings', 'simplekitdownloads_button_text_version', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'Download v%version%',
    ]);

    register_setting('simplekitdownloads_settings', 'simplekitdownloads_button_css', [
        'type'              => 'string',
        'sanitize_callback' => 'simplekitdownloads_sanitize_css',
        'default'           => '',
    ]);

    register_setting('simplekitdownloads_settings', 'simplekitdownloads_record_ip', [
        'type'              => 'string',
        'sanitize_callback' => 'simplekitdownloads_sanitize_checkbox',
        'default'           => '0',
    ]);
}

function simplekitdownloads_sanitize_checkbox($value) {
    return $value === '1' ? '1' : '0';
}

/**
 * CSS is printed inside a <style> element. Removing "<" prevents any tag
 * breakout; "@import" is removed to avoid loading external resources.
 */
function simplekitdownloads_sanitize_css($css) {
    $css = sanitize_textarea_field((string) $css);
    $css = preg_replace('/@import[^;]*;?/i', '', $css);
    return trim((string) $css);
}

function simplekitdownloads_get_button_text() {
    $text = get_option('simplekitdownloads_button_text', 'Download');
    return $text !== '' ? $text : __('Download', 'simplekitdownloads');
}

function simplekitdownloads_get_button_text_version() {
    $text = get_option('simplekitdownloads_button_text_version', 'Download v%version%');
    return $text !== '' ? $text : __('Download v%version%', 'simplekitdownloads');
}

function simplekitdownloads_get_button_css() {
    return (string) get_option('simplekitdownloads_button_css', '');
}

function simplekitdownloads_record_ip() {
    return get_option('simplekitdownloads_record_ip', '0') === '1';
}

/**
 * Build the label of a download button.
 */
function simplekitdownloads_button_label($version_label, $custom_text = '') {
    if (trim((string) $custom_text) !== '') {
        return trim((string) $custom_text);
    }
    if ($version_label === '' || $version_label === null) {
        return simplekitdownloads_get_button_text();
    }
    return str_replace('%version%', $version_label, simplekitdownloads_get_button_text_version());
}

// ---------------------------------------------------------------------------
// Page: Setup
// ---------------------------------------------------------------------------
function simplekitdownloads_page_setup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Setup', 'simplekitdownloads'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields('simplekitdownloads_settings'); ?>

            <h2><?php esc_html_e('Button texts', 'simplekitdownloads'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="simplekitdownloads_button_text"><?php esc_html_e('Default button text', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" id="simplekitdownloads_button_text"
                               name="simplekitdownloads_button_text"
                               value="<?php echo esc_attr(simplekitdownloads_get_button_text()); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simplekitdownloads_button_text_version"><?php esc_html_e('Button text with version', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" id="simplekitdownloads_button_text_version"
                               name="simplekitdownloads_button_text_version"
                               value="<?php echo esc_attr(simplekitdownloads_get_button_text_version()); ?>" />
                        <p class="description">
                            <?php esc_html_e('Use %version% where the version number should appear. Use the default text field when an empty version is requested.', 'simplekitdownloads'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('Button appearance', 'simplekitdownloads'); ?></h2>
            <p class="description">
                <?php esc_html_e('Leave the field below empty to use the built-in styles. The rules are applied globally to every download button.', 'simplekitdownloads'); ?>
            </p>
            <textarea name="simplekitdownloads_button_css" id="simplekitdownloads_button_css" rows="12"
                      class="large-text code"><?php echo esc_textarea(simplekitdownloads_get_button_css()); ?></textarea>
            <p class="description">
                <?php esc_html_e('Default classes: .simplekitdownloads-button (link) and .simplekitdownloads-block (wrapper).', 'simplekitdownloads'); ?>
            </p>

            <h2><?php esc_html_e('Statistics', 'simplekitdownloads'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Visitor IP', 'simplekitdownloads'); ?></th>
                    <td>
                        <input type="hidden" name="simplekitdownloads_record_ip" value="0" />
                        <label>
                            <input type="checkbox" name="simplekitdownloads_record_ip" value="1"
                                <?php checked(simplekitdownloads_record_ip()); ?> />
                            <?php esc_html_e('Record the visitor IP address on each download', 'simplekitdownloads'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('The IP address is stored with the download record and can be exported in the full log CSV. Download records are never deleted automatically.', 'simplekitdownloads'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
