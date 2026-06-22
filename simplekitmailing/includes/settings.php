<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Helper: form fields configuration (per list, with global fallback)
// ---------------------------------------------------------------------------
function simplekitmailing_get_collect_name($list_id = 0) {
    return (bool) simplekitmailing_get_list_setting($list_id, 'collect_name', 'simplekitmailing_collect_name', false);
}

function simplekitmailing_get_collect_phone($list_id = 0) {
    return (bool) simplekitmailing_get_list_setting($list_id, 'collect_phone', 'simplekitmailing_collect_phone', false);
}

function simplekitmailing_get_collect_ip($list_id = 0) {
    return (bool) simplekitmailing_get_list_setting($list_id, 'collect_ip', 'simplekitmailing_collect_ip', true);
}

function simplekitmailing_get_block_text($list_id, $key, $default) {
    return simplekitmailing_get_list_setting($list_id, $key, '', $default);
}

function simplekitmailing_get_smtp_settings($list_id = 0) {
    return [
        'host'       => simplekitmailing_get_list_setting($list_id, 'smtp_host', 'simplekitmailing_smtp_host', ''),
        'port'       => simplekitmailing_get_list_setting($list_id, 'smtp_port', 'simplekitmailing_smtp_port', '587'),
        'username'   => simplekitmailing_get_list_setting($list_id, 'smtp_username', 'simplekitmailing_smtp_username', ''),
        'password'   => simplekitmailing_get_list_setting($list_id, 'smtp_password', 'simplekitmailing_smtp_password', ''),
        'encryption' => simplekitmailing_get_list_setting($list_id, 'smtp_encryption', 'simplekitmailing_smtp_encryption', 'tls'),
    ];
}

function simplekitmailing_get_sender($list_id = 0) {
    return [
        'name'  => simplekitmailing_get_list_setting($list_id, 'sender_name', 'simplekitmailing_sender_name', get_bloginfo('name')),
        'email' => simplekitmailing_get_list_setting($list_id, 'sender_email', 'simplekitmailing_sender_email', get_bloginfo('admin_email')),
    ];
}

function simplekitmailing_get_reply_to($list_id = 0) {
    return [
        'name'  => simplekitmailing_get_list_setting($list_id, 'replyto_name', 'simplekitmailing_replyto_name', get_bloginfo('name')),
        'email' => simplekitmailing_get_list_setting($list_id, 'replyto_email', 'simplekitmailing_replyto_email', get_bloginfo('admin_email')),
    ];
}

function simplekitmailing_get_redirect_page_id($list_id = 0) {
    return (int) simplekitmailing_get_list_setting($list_id, 'redirect_page_id', 'simplekitmailing_redirect_page_id', 0);
}

function simplekitmailing_get_unsubscribe_page_id($list_id = 0) {
    return (int) simplekitmailing_get_list_setting($list_id, 'unsubscribe_page_id', 'simplekitmailing_unsubscribe_page_id', 0);
}

function simplekitmailing_get_email_header_bg_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'header_bg_color', 'simplekitmailing_header_bg_color', '#0073aa');
}

function simplekitmailing_get_email_header_text($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'header_text', 'simplekitmailing_header_text', get_bloginfo('name'));
}

function simplekitmailing_get_email_header_text_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'header_text_color', 'simplekitmailing_header_text_color', '#ffffff');
}

function simplekitmailing_get_email_header_html($list_id = 0) {
    $default_header = '<td style="padding:20px 30px;background-color:' . esc_attr(simplekitmailing_get_email_header_bg_color($list_id)) . ';color:' . esc_attr(simplekitmailing_get_email_header_text_color($list_id)) . ';font-size:20px;font-weight:bold;">'
        . esc_html(simplekitmailing_get_email_header_text($list_id)) . '</td>';
    return simplekitmailing_get_list_setting($list_id, 'header_html', 'simplekitmailing_header_html', $default_header);
}

function simplekitmailing_get_email_footer_html($list_id = 0) {
    $default_footer = '&copy; ' . gmdate('Y') . ' ' . esc_html(get_bloginfo('name')) . '<br><a href="' . esc_url(home_url()) . '">' . esc_html(home_url()) . '</a>';
    return simplekitmailing_get_list_setting($list_id, 'footer_html', 'simplekitmailing_footer_html', $default_footer);
}

function simplekitmailing_get_email_bg_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_bg_color', '', '#ffffff');
}

function simplekitmailing_get_email_text_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_text_color', '', '#333333');
}

function simplekitmailing_get_email_link_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_link_color', '', '#0073aa');
}

function simplekitmailing_get_email_h1_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_h1_color', '', '#000000');
}

function simplekitmailing_get_email_h2_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_h2_color', '', '#222222');
}

function simplekitmailing_get_email_h3_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_h3_color', '', '#333333');
}

function simplekitmailing_get_email_h4_color($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'email_h4_color', '', '#444444');
}

/**
 * Generate editor content_style reflecting the current color settings.
 */
function simplekitmailing_editor_color_style($list_id) {
    $bg    = simplekitmailing_get_email_bg_color($list_id);
    $text  = simplekitmailing_get_email_text_color($list_id);
    $link  = simplekitmailing_get_email_link_color($list_id);
    $h1    = simplekitmailing_get_email_h1_color($list_id);
    $h2    = simplekitmailing_get_email_h2_color($list_id);
    $h3    = simplekitmailing_get_email_h3_color($list_id);
    $h4    = simplekitmailing_get_email_h4_color($list_id);
    return "body { background-color: {$bg}; color: {$text}; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.6; padding: 20px; }"
        . " a { color: {$link}; }"
        . " h1 { color: {$h1}; }"
        . " h2 { color: {$h2}; }"
        . " h3 { color: {$h3}; }"
        . " h4 { color: {$h4}; }";
}

/**
 * Build tinymce config array with color-aware content_style.
 */
function simplekitmailing_editor_config($list_id) {
    return array(
        'textarea_name' => '', // must be overridden per editor
        'teeny'         => false,
        'media_buttons' => true,
        'textarea_rows' => 15,
        'tinymce'       => array(
            'content_style' => simplekitmailing_editor_color_style($list_id),
        ),
    );
}

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------
function simplekitmailing_page_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';

    // --- Process form submission ---
    $saved_msg = '';
    $saved_type = 'success';

    $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
    if ($request_method === 'POST' && isset($_POST['sm_save_settings'])) {
        // Verify nonce
        if (isset($_POST['_sm_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_sm_settings_nonce'])), 'sm_settings_action')) {
            $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;
            if ($list_id > 0) {
                // Get existing settings to merge with
                $current = simplekitmailing_get_list_settings($list_id);

                // Build settings from POST
                $data = array(
                    'smtp_host'           => sanitize_text_field(wp_unslash($_POST['smtp_host'] ?? '')),
                    'smtp_port'           => sanitize_text_field(wp_unslash($_POST['smtp_port'] ?? '587')),
                    'smtp_username'       => sanitize_text_field(wp_unslash($_POST['smtp_username'] ?? '')),
                    'smtp_password'       => sanitize_text_field(wp_unslash($_POST['smtp_password'] ?? '')),
                    'smtp_encryption'     => in_array(wp_unslash($_POST['smtp_encryption'] ?? ''), array('tls', 'ssl', 'none')) ? sanitize_text_field(wp_unslash($_POST['smtp_encryption'])) : 'tls',
                    'sender_name'         => sanitize_text_field(wp_unslash($_POST['sender_name'] ?? '')),
                    'sender_email'        => sanitize_email(wp_unslash($_POST['sender_email'] ?? '')),
                    'replyto_name'        => sanitize_text_field(wp_unslash($_POST['replyto_name'] ?? '')),
                    'replyto_email'       => sanitize_email(wp_unslash($_POST['replyto_email'] ?? '')),
                    'redirect_page_id'    => absint(wp_unslash($_POST['redirect_page_id'] ?? 0)),
                    'unsubscribe_page_id' => absint(wp_unslash($_POST['unsubscribe_page_id'] ?? 0)),
                    'collect_name'        => isset($_POST['collect_name']) ? '1' : '0',
                    'collect_phone'       => isset($_POST['collect_phone']) ? '1' : '0',
                    'collect_ip'          => isset($_POST['collect_ip']) ? '1' : '0',
                    'header_html'         => wp_kses_post(wp_unslash($_POST['header_html'] ?? '')),
                    'footer_html'         => wp_kses_post(wp_unslash($_POST['footer_html'] ?? '')),
                    'email_bg_color'      => sanitize_hex_color(wp_unslash($_POST['email_bg_color'] ?? '#ffffff')),
                    'email_text_color'    => sanitize_hex_color(wp_unslash($_POST['email_text_color'] ?? '#333333')),
                    'email_link_color'    => sanitize_hex_color(wp_unslash($_POST['email_link_color'] ?? '#0073aa')),
                    'email_h1_color'      => sanitize_hex_color(wp_unslash($_POST['email_h1_color'] ?? '#000000')),
                    'email_h2_color'      => sanitize_hex_color(wp_unslash($_POST['email_h2_color'] ?? '#222222')),
                    'email_h3_color'      => sanitize_hex_color(wp_unslash($_POST['email_h3_color'] ?? '#333333')),
                    'email_h4_color'      => sanitize_hex_color(wp_unslash($_POST['email_h4_color'] ?? '#444444')),
                    'block_title'         => sanitize_text_field(wp_unslash($_POST['block_title'] ?? '')),
                    'email_placeholder'   => sanitize_text_field(wp_unslash($_POST['email_placeholder'] ?? '')),
                    'name_placeholder'    => sanitize_text_field(wp_unslash($_POST['name_placeholder'] ?? '')),
                    'phone_placeholder'   => sanitize_text_field(wp_unslash($_POST['phone_placeholder'] ?? '')),
                    'agree_text'          => sanitize_text_field(wp_unslash($_POST['agree_text'] ?? '')),
                    'submit_text'         => sanitize_text_field(wp_unslash($_POST['submit_text'] ?? '')),
                    'sending_text'        => sanitize_text_field(wp_unslash($_POST['sending_text'] ?? '')),
                    'success_message'     => sanitize_text_field(wp_unslash($_POST['success_message'] ?? '')),
                    'unsub_title'          => sanitize_text_field(wp_unslash($_POST['unsub_title'] ?? '')),
                    'unsub_message'        => sanitize_text_field(wp_unslash($_POST['unsub_message'] ?? '')),
                    'unsub_error_message'  => sanitize_text_field(wp_unslash($_POST['unsub_error_message'] ?? '')),
                    'unsub_no_email'       => sanitize_text_field(wp_unslash($_POST['unsub_no_email'] ?? '')),
                    'unsub_instructions'   => sanitize_text_field(wp_unslash($_POST['unsub_instructions'] ?? '')),
                    'double_optin'        => isset($_POST['double_optin']) ? '1' : '0',
                    'confirm_page_id'     => absint(wp_unslash($_POST['confirm_page_id'] ?? 0)),
                    'confirm_email_subject' => sanitize_text_field(wp_unslash($_POST['confirm_email_subject'] ?? '')),
                    'confirm_email_content' => wp_kses_post(wp_unslash($_POST['confirm_email_content'] ?? '')),
                    'protection'          => in_array(wp_unslash($_POST['protection'] ?? ''), array('none', 'recaptcha')) ? sanitize_text_field(wp_unslash($_POST['protection'])) : 'none',
                    'recaptcha_site_key'  => sanitize_text_field(wp_unslash($_POST['recaptcha_site_key'] ?? '')),
                    'recaptcha_secret_key' => sanitize_text_field(wp_unslash($_POST['recaptcha_secret_key'] ?? '')),
                );

                // Merge new data over existing settings (preserves keys not in POST)
                foreach ($data as $k => $v) {
                    $current[$k] = $v;
                }

                simplekitmailing_save_list_settings($list_id, $current);
                $saved_msg = __('Settings saved successfully for this list.', 'simplekitmailing');
            }
        }
    }

    $lists = simplekitmailing_get_lists();
    $current_list_id = isset($_GET['list_id']) ? absint($_GET['list_id']) : (!empty($lists) ? (int) $lists[0]->id : 0);
    $settings = $current_list_id ? simplekitmailing_get_list_settings($current_list_id) : array();

    // Helper value reader
    $v = function($key, $global, $default) use ($settings) {
        if (isset($settings[$key]) && $settings[$key] !== '' && $settings[$key] !== null) {
            return $settings[$key];
        }
        if ($global) {
            $g = get_option($global, null);
            if ($g !== null) {
                return $g;
            }
        }
        return $default;
    };

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Simple Kit Mailing Settings', 'simplekitmailing'); ?></h1>

        <?php if ($saved_msg) : ?>
            <div class="notice notice-<?php echo esc_attr($saved_type); ?> is-dismissible">
                <p><?php echo esc_html($saved_msg); ?></p>
            </div>
        <?php endif; ?>

        <form method="get" action="" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="simplekitmailing-settings" />
            <label for="settings-list-select"><?php esc_html_e('Configure list:', 'simplekitmailing'); ?></label>
            <select id="settings-list-select" name="list_id" onchange="this.form.submit();">
                <?php foreach ($lists as $list) : ?>
                    <option value="<?php echo (int) $list->id; ?>" <?php selected($current_list_id, $list->id); ?>>
                        <?php echo esc_html($list->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><input type="submit" class="button" value="<?php esc_attr_e('Select', 'simplekitmailing'); ?>" /></noscript>
        </form>

        <?php if ($current_list_id) : ?>
            <form method="post" action="">
                <?php wp_nonce_field('sm_settings_action', '_sm_settings_nonce'); ?>
                <input type="hidden" name="sm_save_settings" value="1" />
                <input type="hidden" name="list_id" value="<?php echo (int) $current_list_id; ?>" />

                <h2><?php esc_html_e('Sender', 'simplekitmailing'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="sender_name"><?php esc_html_e('Sender name', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="sender_name" name="sender_name" value="<?php echo esc_attr($v('sender_name', 'simplekitmailing_sender_name', get_bloginfo('name'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sender_email"><?php esc_html_e('Sender email', 'simplekitmailing'); ?></label></th>
                        <td><input type="email" id="sender_email" name="sender_email" value="<?php echo esc_attr($v('sender_email', 'simplekitmailing_sender_email', get_bloginfo('admin_email'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="replyto_name"><?php esc_html_e('Reply-to name', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="replyto_name" name="replyto_name" value="<?php echo esc_attr($v('replyto_name', 'simplekitmailing_replyto_name', get_bloginfo('name'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="replyto_email"><?php esc_html_e('Reply-to email', 'simplekitmailing'); ?></label></th>
                        <td><input type="email" id="replyto_email" name="replyto_email" value="<?php echo esc_attr($v('replyto_email', 'simplekitmailing_replyto_email', get_bloginfo('admin_email'))); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('SMTP Settings', 'simplekitmailing'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="smtp_host"><?php esc_html_e('SMTP server', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr($v('smtp_host', 'simplekitmailing_smtp_host', '')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_port"><?php esc_html_e('SMTP port', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="smtp_port" name="smtp_port" value="<?php echo esc_attr($v('smtp_port', 'simplekitmailing_smtp_port', '587')); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_username"><?php esc_html_e('SMTP username', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="smtp_username" name="smtp_username" value="<?php echo esc_attr($v('smtp_username', 'simplekitmailing_smtp_username', '')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_password"><?php esc_html_e('SMTP password', 'simplekitmailing'); ?></label></th>
                        <td>
                            <input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr($v('smtp_password', 'simplekitmailing_smtp_password', '')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Leave blank to keep the current password.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_encryption"><?php esc_html_e('Encryption', 'simplekitmailing'); ?></label></th>
                        <td>
                            <select id="smtp_encryption" name="smtp_encryption">
                                <option value="tls" <?php selected($v('smtp_encryption', 'simplekitmailing_smtp_encryption', 'tls'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected($v('smtp_encryption', 'simplekitmailing_smtp_encryption', 'tls'), 'ssl'); ?>>SSL</option>
                                <option value="none" <?php selected($v('smtp_encryption', 'simplekitmailing_smtp_encryption', 'tls'), 'none'); ?>><?php esc_html_e('None', 'simplekitmailing'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Redirection', 'simplekitmailing'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="redirect_page_id"><?php esc_html_e('Page after signup', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'id'       => 'redirect_page_id',
                                'name'     => 'redirect_page_id',
                                'selected' => (int) $v('redirect_page_id', 'simplekitmailing_redirect_page_id', 0),
                                'show_option_none' => esc_html__('— Select —', 'simplekitmailing'),
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Page to redirect visitors to after signing up for this list.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Unsubscribe', 'simplekitmailing'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="unsubscribe_page_id"><?php esc_html_e('Unsubscribe page', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'id'       => 'unsubscribe_page_id',
                                'name'     => 'unsubscribe_page_id',
                                'selected' => (int) $v('unsubscribe_page_id', 'simplekitmailing_unsubscribe_page_id', 0),
                                'show_option_none' => esc_html__('— Select —', 'simplekitmailing'),
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Page containing the "Simple Kit Mailing Unsubscribe" block.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Signup form', 'simplekitmailing'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Additional fields', 'simplekitmailing'); ?></th>
                        <td>
                            <fieldset>
                                <label for="collect_name">
                                    <input type="checkbox" id="collect_name" name="collect_name" value="1" <?php checked((bool) $v('collect_name', 'simplekitmailing_collect_name', false)); ?> />
                                    <?php esc_html_e('Request name', 'simplekitmailing'); ?>
                                </label>
                                <br />
                                <label for="collect_phone">
                                    <input type="checkbox" id="collect_phone" name="collect_phone" value="1" <?php checked((bool) $v('collect_phone', 'simplekitmailing_collect_phone', false)); ?> />
                                    <?php esc_html_e('Request phone / WhatsApp', 'simplekitmailing'); ?>
                                </label>
                                <br />
                                <label for="collect_ip">
                                    <input type="checkbox" id="collect_ip" name="collect_ip" value="1" <?php checked((bool) $v('collect_ip', 'simplekitmailing_collect_ip', true)); ?> />
                                    <?php esc_html_e('Collect IP address', 'simplekitmailing'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Email template', 'simplekitmailing'); ?></h2>
                <p class="description"><?php esc_html_e('Customize the colors used in email messages and confirmation emails for this list.', 'simplekitmailing'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Email colors', 'simplekitmailing'); ?></th>
                        <td>
                            <table class="simplekitmailing-color-table">
                                <tr>
                                    <td class="sm-color-label"><label for="email_bg_color"><?php esc_html_e('Background:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_bg_color" name="email_bg_color" value="<?php echo esc_attr($v('email_bg_color', '', '#ffffff')); ?>" class="simplekitmailing-color-picker" data-default-color="#ffffff" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_text_color"><?php esc_html_e('Default text:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_text_color" name="email_text_color" value="<?php echo esc_attr($v('email_text_color', '', '#333333')); ?>" class="simplekitmailing-color-picker" data-default-color="#333333" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_link_color"><?php esc_html_e('Links:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_link_color" name="email_link_color" value="<?php echo esc_attr($v('email_link_color', '', '#0073aa')); ?>" class="simplekitmailing-color-picker" data-default-color="#0073aa" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_h1_color"><?php esc_html_e('Heading H1:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_h1_color" name="email_h1_color" value="<?php echo esc_attr($v('email_h1_color', '', '#000000')); ?>" class="simplekitmailing-color-picker" data-default-color="#000000" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_h2_color"><?php esc_html_e('Heading H2:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_h2_color" name="email_h2_color" value="<?php echo esc_attr($v('email_h2_color', '', '#222222')); ?>" class="simplekitmailing-color-picker" data-default-color="#222222" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_h3_color"><?php esc_html_e('Heading H3:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_h3_color" name="email_h3_color" value="<?php echo esc_attr($v('email_h3_color', '', '#333333')); ?>" class="simplekitmailing-color-picker" data-default-color="#333333" /></td>
                                </tr>
                                <tr>
                                    <td class="sm-color-label"><label for="email_h4_color"><?php esc_html_e('Heading H4:', 'simplekitmailing'); ?></label></td>
                                    <td><input type="text" id="email_h4_color" name="email_h4_color" value="<?php echo esc_attr($v('email_h4_color', '', '#444444')); ?>" class="simplekitmailing-color-picker" data-default-color="#444444" /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_html"><?php esc_html_e('Header (HTML)', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            $header_config = simplekitmailing_editor_config($current_list_id);
                            $header_config['textarea_name'] = 'header_html';
                            wp_editor(
                                $v('header_html', '', '<td style="padding:20px 30px;background-color:#0073aa;color:#ffffff;font-size:20px;font-weight:bold;">' . esc_html(get_bloginfo('name')) . '</td>'),
                                'header_html',
                                $header_config
                            );
                            ?>
                            <p class="description"><?php esc_html_e('Full HTML for the email header row. Use table-based markup for best email client compatibility.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_html"><?php esc_html_e('Footer (HTML)', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            $footer_config = simplekitmailing_editor_config($current_list_id);
                            $footer_config['textarea_name'] = 'footer_html';
                            wp_editor(
                                $v('footer_html', 'simplekitmailing_footer_html', '&copy; ' . gmdate('Y') . ' ' . esc_html(get_bloginfo('name')) . '<br><a href="' . esc_url(home_url()) . '">' . esc_html(home_url()) . '</a>'),
                                'footer_html',
                                $footer_config
                            );
                            ?>
                            <p class="description"><?php esc_html_e('Footer HTML displayed at the end of each email.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Signup block texts', 'simplekitmailing'); ?></h2>
                <p class="description"><?php esc_html_e('Customize all texts displayed in the email collection form for this list.', 'simplekitmailing'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="block_title"><?php esc_html_e('Block title', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="block_title" name="block_title" value="<?php echo esc_attr($v('block_title', '', __('Receive our news', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_placeholder"><?php esc_html_e('Email placeholder', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="email_placeholder" name="email_placeholder" value="<?php echo esc_attr($v('email_placeholder', '', __('Enter your email', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="name_placeholder"><?php esc_html_e('Name placeholder', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="name_placeholder" name="name_placeholder" value="<?php echo esc_attr($v('name_placeholder', '', __('Enter your name', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone_placeholder"><?php esc_html_e('Phone placeholder', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="phone_placeholder" name="phone_placeholder" value="<?php echo esc_attr($v('phone_placeholder', '', __('Enter your phone / WhatsApp', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="agree_text"><?php esc_html_e('Agreement text', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="agree_text" name="agree_text" value="<?php echo esc_attr($v('agree_text', '', __('I agree with the terms of registration', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="submit_text"><?php esc_html_e('Button text', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="submit_text" name="submit_text" value="<?php echo esc_attr($v('submit_text', '', __('Subscribe', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sending_text"><?php esc_html_e('"Sending" text', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="sending_text" name="sending_text" value="<?php echo esc_attr($v('sending_text', '', __('Sending...', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="success_message"><?php esc_html_e('Success message', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="success_message" name="success_message" value="<?php echo esc_attr($v('success_message', '', __('Registration successful!', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Unsubscribe block texts', 'simplekitmailing'); ?></h2>
                <p class="description"><?php esc_html_e('Customize all texts displayed in the unsubscribe block for this list.', 'simplekitmailing'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="unsub_title"><?php esc_html_e('Block title', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="unsub_title" name="unsub_title" value="<?php echo esc_attr($v('unsub_title', '', __('Unsubscribe email', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="unsub_message"><?php esc_html_e('Confirmation message', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="unsub_message" name="unsub_message" value="<?php echo esc_attr($v('unsub_message', '', __('Your email has been removed from our mailing list.', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="unsub_error_message"><?php esc_html_e('Error message', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="unsub_error_message" name="unsub_error_message" value="<?php echo esc_attr($v('unsub_error_message', '', __('The email address was not found in our mailing list.', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="unsub_no_email"><?php esc_html_e('"No email" message', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="unsub_no_email" name="unsub_no_email" value="<?php echo esc_attr($v('unsub_no_email', '', __('No email provided for unsubscription.', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="unsub_instructions"><?php esc_html_e('Instructions', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="unsub_instructions" name="unsub_instructions" value="<?php echo esc_attr($v('unsub_instructions', '', __('Use the unsubscribe link sent in the email to remove your registration.', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Submission protection', 'simplekitmailing'); ?></h2>
                <p class="description"><?php esc_html_e('Choose how to protect the signup form against spam and automated submissions.', 'simplekitmailing'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="protection"><?php esc_html_e('Protection type', 'simplekitmailing'); ?></label></th>
                        <td>
                            <select id="protection" name="protection" onchange="toggleRecaptchaFields(this.value);">
                                <option value="none" <?php selected($v('protection', '', 'none'), 'none'); ?>><?php esc_html_e('No protection', 'simplekitmailing'); ?></option>
                                <option value="recaptcha" <?php selected($v('protection', '', 'none'), 'recaptcha'); ?>><?php esc_html_e('reCAPTCHA', 'simplekitmailing'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('reCAPTCHA requires valid site and secret keys from Google.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                </table>

                <div id="recaptcha-settings" style="<?php echo $v('protection', '', 'none') === 'recaptcha' ? '' : 'display:none;'; ?>">
                    <h3><?php esc_html_e('reCAPTCHA Keys', 'simplekitmailing'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="recaptcha_site_key"><?php esc_html_e('Site key', 'simplekitmailing'); ?></label></th>
                            <td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($v('recaptcha_site_key', 'simplekitmailing_recaptcha_site_key', '')); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="recaptcha_secret_key"><?php esc_html_e('Secret key', 'simplekitmailing'); ?></label></th>
                            <td><input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($v('recaptcha_secret_key', 'simplekitmailing_recaptcha_secret_key', '')); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <script>
                function toggleRecaptchaFields(value) {
                    var div = document.getElementById('recaptcha-settings');
                    if (div) {
                        div.style.display = (value === 'recaptcha') ? '' : 'none';
                    }
                }
                </script>

                <h2><?php esc_html_e('Double opt-in', 'simplekitmailing'); ?></h2>
                <p class="description"><?php esc_html_e('When enabled, new subscribers must confirm their email address by clicking a link sent via email before being added to this list.', 'simplekitmailing'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable double opt-in', 'simplekitmailing'); ?></th>
                        <td>
                            <label for="double_optin">
                                <input type="checkbox" id="double_optin" name="double_optin" value="1" <?php checked((bool) $v('double_optin', '', false)); ?> />
                                <?php esc_html_e('Require email confirmation for new subscriptions', 'simplekitmailing'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="confirm_page_id"><?php esc_html_e('Confirmation page', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'id'       => 'confirm_page_id',
                                'name'     => 'confirm_page_id',
                                'selected' => (int) $v('confirm_page_id', '', 0),
                                'show_option_none' => esc_html__('— Select —', 'simplekitmailing'),
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Page containing the "Simple Kit Mailing Confirm" block.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="confirm_email_subject"><?php esc_html_e('Confirmation email subject', 'simplekitmailing'); ?></label></th>
                        <td><input type="text" id="confirm_email_subject" name="confirm_email_subject" value="<?php echo esc_attr($v('confirm_email_subject', '', __('Please confirm your subscription', 'simplekitmailing'))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="confirm_email_content"><?php esc_html_e('Confirmation email content', 'simplekitmailing'); ?></label></th>
                        <td>
                            <?php
                            $default_confirm = __("Hello, this email address has just been added to our mailing list. To complete the registration process, please access the link below.<br><br>{confirm_link}<br><br><em>If you did not request to be added to our list, you can ignore this message or, if you prefer, indicate that you do not want your email to be used in future subscription attempts by accessing the link {unsubscribe_link}</em>", 'simplekitmailing');
                            $confirm_config = simplekitmailing_editor_config($current_list_id);
                            $confirm_config['textarea_name'] = 'confirm_email_content';
                            wp_editor(
                                $v('confirm_email_content', '', $default_confirm),
                                'confirm_email_content',
                                $confirm_config
                            );
                            ?>
                            <p class="description"><?php esc_html_e('Use {confirm_link} as a placeholder for the confirmation link, and {unsubscribe_link} as a placeholder for the unsubscribe link. These will be replaced with the actual URLs when the email is sent.', 'simplekitmailing'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save settings for this list', 'simplekitmailing')); ?>
            </form>
        <?php else : ?>
            <p><?php esc_html_e('No mailing lists found. Create a list first.', 'simplekitmailing'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
