<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Página de Configurações
// ---------------------------------------------------------------------------
function simplekitforms_page_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    $msg  = '';
    $type = 'success';

    // Process form submission
    if (isset($_POST['sf_save_settings']) && isset($_POST['_sf_settings_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_sf_settings_nonce'])), 'sf_settings_action')
    ) {
        update_option('simplekitforms_protection', in_array(sanitize_text_field(wp_unslash($_POST['protection'] ?? '')), array('none', 'akismet', 'recaptcha')) ? sanitize_text_field(wp_unslash($_POST['protection'])) : 'none');
        update_option('simplekitforms_recaptcha_site_key', sanitize_text_field(wp_unslash($_POST['recaptcha_site_key'] ?? '')));
        update_option('simplekitforms_recaptcha_secret_key', sanitize_text_field(wp_unslash($_POST['recaptcha_secret_key'] ?? '')));
        update_option('simplekitforms_collect_ip', isset($_POST['collect_ip']) ? '1' : '0');
        $msg  = 'Settings saved successfully.';
    }

    $protection      = get_option('simplekitforms_protection', 'none');
    $recaptcha_site  = get_option('simplekitforms_recaptcha_site_key', '');
    $recaptcha_secret = get_option('simplekitforms_recaptcha_secret_key', '');
    $collect_ip      = (bool) get_option('simplekitforms_collect_ip', true);

    ?>
    <div class="wrap">
        <h1>Simple Kit Forms Settings</h1>
        <hr class="wp-header-end">

        <?php if ($msg) : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($msg); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('sf_settings_action', '_sf_settings_nonce'); ?>
            <input type="hidden" name="sf_save_settings" value="1" />

            <h2>Submission protection</h2>
            <p class="description">Choose how to protect the forms against spam and automated submissions.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="protection">Protection type</label></th>
                    <td>
                        <select id="protection" name="protection" onchange="toggleRecaptchaFields(this.value);">
                            <option value="none" <?php selected($protection, 'none'); ?>>No protection</option>
                            <option value="akismet" <?php selected($protection, 'akismet'); ?>>Akismet</option>
                            <option value="recaptcha" <?php selected($protection, 'recaptcha'); ?>>reCAPTCHA</option>
                        </select>
                        <p class="description">Akismet requires the Akismet plugin to be installed and active. reCAPTCHA requires valid site and secret keys from Google.</p>
                    </td>
                </tr>
            </table>

            <div id="recaptcha-settings" style="<?php echo $protection === 'recaptcha' ? '' : 'display:none;'; ?>">
                <h3>reCAPTCHA Keys</h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="recaptcha_site_key">Site key</label></th>
                        <td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($recaptcha_site); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recaptcha_secret_key">Secret key</label></th>
                        <td>
                            <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($recaptcha_secret); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Leave empty to keep the current key. Fill in to change it.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <h2>Data collection</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Collect IP address</th>
                    <td>
                        <label for="collect_ip">
                            <input type="checkbox" id="collect_ip" name="collect_ip" value="1" <?php checked($collect_ip, true); ?> />
                            Record the visitor's IP address when saving form submissions
                        </label>
                    </td>
                </tr>
            </table>

            <script>
            function toggleRecaptchaFields(value) {
                var div = document.getElementById('recaptcha-settings');
                if (div) {
                    div.style.display = (value === 'recaptcha') ? '' : 'none';
                }
            }
            </script>

            <?php submit_button('Save settings'); ?>
        </form>
    </div>
    <?php
}
