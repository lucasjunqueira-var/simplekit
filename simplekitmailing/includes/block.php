<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Default CSS for each block type
// ---------------------------------------------------------------------------

/**
 * Returns the default CSS for the Collect block.
 */
function simplekitmailing_get_default_collect_css() {
    return '.simplekitmailing-collect-block {' .
        'max-width: 400px;' .
        'margin: 20px 0;' .
        'padding: 20px;' .
        'border: 1px solid #ddd;' .
        'border-radius: 8px;' .
        'background: #f9f9f9;' .
    '}' .
    '.simplekitmailing-collect-block h3 {' .
        'margin-top: 0;' .
    '}' .
    '.simplekitmailing-collect-block .sm-field {' .
        'margin-bottom: 12px;' .
    '}' .
    '.simplekitmailing-collect-block .sm-field input[type="email"],' .
    '.simplekitmailing-collect-block .sm-field input[type="text"],' .
    '.simplekitmailing-collect-block .sm-field input[type="tel"] {' .
        'width: 100%;' .
        'padding: 10px;' .
        'border: 1px solid #ccc;' .
        'border-radius: 4px;' .
        'box-sizing: border-box;' .
    '}' .
    '.simplekitmailing-collect-block .sm-field label {' .
        'display: flex;' .
        'align-items: flex-start;' .
        'gap: 8px;' .
        'font-size: 14px;' .
        'cursor: pointer;' .
    '}' .
    '.simplekitmailing-collect-block .sm-field label input[type="checkbox"] {' .
        'margin-top: 2px;' .
    '}' .
    '.simplekitmailing-collect-block .sm-submit {' .
        'background: #0073aa;' .
        'color: #fff;' .
        'border: none;' .
        'padding: 10px 20px;' .
        'border-radius: 4px;' .
        'cursor: pointer;' .
        'font-size: 16px;' .
    '}' .
    '.simplekitmailing-collect-block .sm-submit:hover {' .
        'background: #005a87;' .
    '}' .
    '.simplekitmailing-collect-block .sm-submit:disabled {' .
        'opacity: 0.6;' .
        'cursor: not-allowed;' .
    '}' .
    '.simplekitmailing-collect-block .sm-message {' .
        'margin-top: 10px;' .
        'padding: 8px 12px;' .
        'border-radius: 4px;' .
        'display: none;' .
    '}' .
    '.simplekitmailing-collect-block .sm-message.error {' .
        'display: block;' .
        'background: #f8d7da;' .
        'color: #721c24;' .
        'border: 1px solid #f5c6cb;' .
    '}' .
    '.simplekitmailing-collect-block .sm-message.success {' .
        'display: block;' .
        'background: #d4edda;' .
        'color: #155724;' .
        'border: 1px solid #c3e6cb;' .
    '}';
}

/**
 * Returns the default CSS for the Unsubscribe block.
 */
function simplekitmailing_get_default_unsubscribe_css() {
    return '.simplekitmailing-unsubscribe-block {' .
        'max-width: 500px;' .
        'margin: 40px auto;' .
        'padding: 30px;' .
        'border: 1px solid #ddd;' .
        'border-radius: 8px;' .
        'background: #f9f9f9;' .
        'text-align: center;' .
    '}' .
    '.simplekitmailing-unsubscribe-block h2 {' .
        'margin-top: 0;' .
        'color: #333;' .
    '}' .
    '.simplekitmailing-unsubscribe-block .sm-unsubscribed {' .
        'color: #155724;' .
        'background: #d4edda;' .
        'border: 1px solid #c3e6cb;' .
        'padding: 15px;' .
        'border-radius: 4px;' .
        'margin-top: 20px;' .
    '}' .
    '.simplekitmailing-unsubscribe-block .sm-no-email {' .
        'color: #856404;' .
        'background: #fff3cd;' .
        'border: 1px solid #ffeeba;' .
        'padding: 15px;' .
        'border-radius: 4px;' .
        'margin-top: 20px;' .
    '}';
}

/**
 * Returns the default CSS for the Confirm block.
 */
function simplekitmailing_get_default_confirm_css() {
    return '.simplekitmailing-confirm-block {' .
        'max-width: 500px;' .
        'margin: 40px auto;' .
        'padding: 30px;' .
        'border: 1px solid #ddd;' .
        'border-radius: 8px;' .
        'background: #f9f9f9;' .
        'text-align: center;' .
    '}' .
    '.simplekitmailing-confirm-block h2 {' .
        'margin-top: 0;' .
        'color: #333;' .
    '}' .
    '.simplekitmailing-confirm-block .sm-confirm-success {' .
        'color: #155724;' .
        'background: #d4edda;' .
        'border: 1px solid #c3e6cb;' .
        'padding: 15px;' .
        'border-radius: 4px;' .
        'margin-top: 20px;' .
    '}' .
    '.simplekitmailing-confirm-block .sm-confirm-error {' .
        'color: #721c24;' .
        'background: #f8d7da;' .
        'border: 1px solid #f5c6cb;' .
        'padding: 15px;' .
        'border-radius: 4px;' .
        'margin-top: 20px;' .
    '}';
}

// ---------------------------------------------------------------------------
// Register Gutenberg block "Simple Kit Mailing Collect"
// ---------------------------------------------------------------------------
add_action('init', 'simplekitmailing_register_block');

function simplekitmailing_register_block() {
    // Register the block script
    wp_register_script(
        'simplekitmailing-block',
        SIMPLEKITMAILING_PLUGIN_URL . 'assets/block.js',
        ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
        SIMPLEKITMAILING_VERSION,
        true
    );

    // Pass available lists and default CSS to the block editor
    $lists = simplekitmailing_get_lists();
    $list_options = [];
    foreach ($lists as $list) {
        $list_options[] = [
            'value' => (int) $list->id,
            'label' => $list->name,
        ];
    }

    wp_localize_script('simplekitmailing-block', 'simplekitmailing_block_data', [
        'ajax_url'           => admin_url('admin-ajax.php'),
        'nonce'              => wp_create_nonce('simplekitmailing_collect_nonce'),
        'lists'              => $list_options,
        'default_collect_css' => simplekitmailing_get_default_collect_css(),
        'default_unsubscribe_css' => simplekitmailing_get_default_unsubscribe_css(),
        'default_confirm_css' => simplekitmailing_get_default_confirm_css(),
    ]);

    // Register the collect block
    register_block_type('simplekitmailing/collect', [
        'editor_script'   => 'simplekitmailing-block',
        'render_callback' => 'simplekitmailing_render_block',
        'attributes'      => [
            'title' => [
                'type'    => 'string',
                'default' => '',
            ],
            'list_id' => [
                'type'    => 'integer',
                'default' => 0,
            ],
            'custom_css' => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);

    // Register the unsubscribe block
    register_block_type('simplekitmailing/unsubscribe', [
        'editor_script'   => 'simplekitmailing-block',
        'render_callback' => 'simplekitmailing_render_unsubscribe_block',
        'attributes'      => [
            'title' => [
                'type'    => 'string',
                'default' => '',
            ],
            'message' => [
                'type'    => 'string',
                'default' => '',
            ],
            'error_message' => [
                'type'    => 'string',
                'default' => '',
            ],
            'custom_css' => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);

    // Register the confirm block (double opt-in)
    register_block_type('simplekitmailing/confirm', [
        'editor_script'   => 'simplekitmailing-block',
        'render_callback' => 'simplekitmailing_render_confirm_block',
        'attributes'      => [
            'title' => [
                'type'    => 'string',
                'default' => '',
            ],
            'success_message' => [
                'type'    => 'string',
                'default' => '',
            ],
            'error_message' => [
                'type'    => 'string',
                'default' => '',
            ],
            'removed_message' => [
                'type'    => 'string',
                'default' => '',
            ],
            'custom_css' => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);
}

// ---------------------------------------------------------------------------
// Frontend block rendering
// ---------------------------------------------------------------------------
function simplekitmailing_render_block($attributes) {
    $list_id = isset($attributes['list_id']) ? (int) $attributes['list_id'] : 0;

    // If no list_id defined, use the first list
    if (!$list_id) {
        $lists = simplekitmailing_get_lists();
        $list_id = !empty($lists) ? (int) $lists[0]->id : 0;
    }

    // Block texts from list settings (with fallback to block attribute or default translation)
    $block_title       = $attributes['title'] ?? simplekitmailing_get_block_text($list_id, 'block_title', __('Receive our news', 'simplekitmailing'));
    $email_placeholder = simplekitmailing_get_block_text($list_id, 'email_placeholder', __('Enter your email', 'simplekitmailing'));
    $name_placeholder  = simplekitmailing_get_block_text($list_id, 'name_placeholder', __('Enter your name', 'simplekitmailing'));
    $phone_placeholder = simplekitmailing_get_block_text($list_id, 'phone_placeholder', __('Enter your phone / WhatsApp', 'simplekitmailing'));
    $agree_text        = simplekitmailing_get_block_text($list_id, 'agree_text', __('I agree with the terms of registration', 'simplekitmailing'));
    $submit_text       = simplekitmailing_get_block_text($list_id, 'submit_text', __('Subscribe', 'simplekitmailing'));
    $sending_text      = simplekitmailing_get_block_text($list_id, 'sending_text', __('Sending...', 'simplekitmailing'));
    $success_message   = simplekitmailing_get_block_text($list_id, 'success_message', __('Registration successful!', 'simplekitmailing'));

    // Check which fields are enabled (per list)
    $collect_name  = simplekitmailing_get_collect_name($list_id);
    $collect_phone = simplekitmailing_get_collect_phone($list_id);

    // Check protection settings
    $protection = simplekitmailing_get_protection($list_id);
    $use_recaptcha = ($protection === 'recaptcha');

    // Use custom CSS if provided, otherwise use default
    $block_css = !empty($attributes['custom_css']) ? $attributes['custom_css'] : simplekitmailing_get_default_collect_css();

    ob_start();
    ?>
    <div class="simplekitmailing-collect-block">
        <style>
            <?php echo wp_kses_post($block_css); ?>
            <?php if ($use_recaptcha) : ?>
            .simplekitmailing-collect-block .g-recaptcha {
                margin-bottom: 12px;
            }
            <?php endif; ?>
        </style>

        <?php if ($use_recaptcha) :
            $recaptcha_key = simplekitmailing_get_recaptcha_site_key($list_id);
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode($recaptcha_key), array(), SIMPLEKITMAILING_VERSION, true);
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'google-recaptcha') {
                    return str_replace(' src', ' async defer src', $tag);
                }
                return $tag;
            }, 10, 2);
        endif; ?>

        <?php if (!empty($block_title)) : ?>
        <h3><?php echo esc_html($block_title); ?></h3>
        <?php endif; ?>

        <form class="simplekitmailing-form" method="post">
            <div class="sm-field">
                <input type="email" name="sm_email" placeholder="<?php echo esc_attr($email_placeholder); ?>" required />
            </div>
            <?php if ($collect_name) : ?>
            <div class="sm-field">
                <input type="text" name="sm_name" placeholder="<?php echo esc_attr($name_placeholder); ?>" required />
            </div>
            <?php endif; ?>
            <?php if ($collect_phone) : ?>
            <div class="sm-field">
                <input type="tel" name="sm_phone" placeholder="<?php echo esc_attr($phone_placeholder); ?>" required />
            </div>
            <?php endif; ?>
            <div class="sm-field">
                <label>
                    <input type="checkbox" name="sm_agree" value="1" />
                    <span><?php echo esc_html($agree_text); ?></span>
                </label>
            </div>
            <?php if ($use_recaptcha) : ?>
            <input type="hidden" name="sm_recaptcha_token" id="sm_recaptcha_token" value="" />
            <?php endif; ?>
            <div class="sm-field">
                <button type="submit" class="sm-submit"><?php echo esc_html($submit_text); ?></button>
            </div>
            <div class="sm-message"></div>
        </form>

        <script>
        (function() {
            var form = document.querySelector('.simplekitmailing-collect-block .simplekitmailing-form');
            if (!form) return;

            <?php if ($use_recaptcha) : ?>
            // Load reCAPTCHA and generate token on form submit
            var recaptchaSiteKey = '<?php echo esc_js(simplekitmailing_get_recaptcha_site_key($list_id)); ?>';
            var recaptchaTokenField = form.querySelector('input[name="sm_recaptcha_token"]');
            <?php endif; ?>

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                <?php if ($use_recaptcha) : ?>
                // Execute reCAPTCHA v3 and proceed only after token is obtained
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(recaptchaSiteKey, {action: 'submit'}).then(function(token) {
                        recaptchaTokenField.value = token;
                        submitForm();
                    });
                });
                return;
                <?php endif; ?>

                submitForm();
            });

            function submitForm() {
                var email   = form.querySelector('input[name="sm_email"]').value.trim();
                var name    = form.querySelector('input[name="sm_name"]');
                var phone   = form.querySelector('input[name="sm_phone"]');
                var agree   = form.querySelector('input[name="sm_agree"]').checked;
                var message = form.querySelector('.sm-message');
                var button  = form.querySelector('.sm-submit');

                // Local validation
                if (!agree) {
                    message.className = 'sm-message error';
                    message.textContent = '<?php echo esc_js(__('You need to agree to our terms to register your email.', 'simplekitmailing')); ?>';
                    return;
                }

                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    message.className = 'sm-message error';
                    message.textContent = '<?php echo esc_js(__('Please check the email address provided.', 'simplekitmailing')); ?>';
                    return;
                }

                <?php if ($collect_name) : ?>
                if (name && name.value.trim() === '') {
                    message.className = 'sm-message error';
                    message.textContent = '<?php echo esc_js(__('Please fill in your name.', 'simplekitmailing')); ?>';
                    return;
                }
                <?php endif; ?>

                <?php if ($collect_phone) : ?>
                if (phone && phone.value.trim() === '') {
                    message.className = 'sm-message error';
                    message.textContent = '<?php echo esc_js(__('Please fill in your phone / WhatsApp.', 'simplekitmailing')); ?>';
                    return;
                }
                <?php endif; ?>

                button.disabled = true;
                button.textContent = '<?php echo esc_js($sending_text); ?>';
                message.className = 'sm-message';
                message.textContent = '';

                var data = new FormData();
                data.append('action', 'simplekitmailing_collect');
                data.append('email', email);
                data.append('agree', agree ? '1' : '0');
                data.append('nonce', '<?php echo esc_js(wp_create_nonce('simplekitmailing_collect_nonce')); ?>');
                data.append('list_id', '<?php echo (int) $list_id; ?>');
                <?php if ($collect_name) : ?>
                data.append('name', name ? name.value.trim() : '');
                <?php endif; ?>
                <?php if ($collect_phone) : ?>
                data.append('phone', phone ? phone.value.trim() : '');
                <?php endif; ?>
                <?php if ($use_recaptcha) : ?>
                data.append('recaptcha_token', recaptchaTokenField.value);
                <?php endif; ?>

                fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: data,
                })
                .then(function(r) { return r.json(); })
                .then(function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            message.className = 'sm-message success';
                            message.textContent = '<?php echo esc_js($success_message); ?>';
                            form.querySelector('input[name="sm_email"]').value = '';
                            <?php if ($collect_name) : ?>
                            if (name) name.value = '';
                            <?php endif; ?>
                            <?php if ($collect_phone) : ?>
                            if (phone) phone.value = '';
                            <?php endif; ?>
                            form.querySelector('input[name="sm_agree"]').checked = false;
                        }
                    } else {
                        message.className = 'sm-message error';
                        message.textContent = response.data.message || '<?php echo esc_js(__('Error registering. Please try again.', 'simplekitmailing')); ?>';
                    }
                })
                .catch(function() {
                    message.className = 'sm-message error';
                    message.textContent = '<?php echo esc_js(__('Connection error. Please try again.', 'simplekitmailing')); ?>';
                })
                .finally(function() {
                    button.disabled = false;
                    button.textContent = '<?php echo esc_js($submit_text); ?>';
                });
            }
        })();
        </script>
    </div>
        <?php
        return ob_get_clean();
    }

    // ---------------------------------------------------------------------------
    // Frontend rendering of "Simple Kit Mailing Unsubscribe" block
    // ---------------------------------------------------------------------------
    function simplekitmailing_render_unsubscribe_block($attributes) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Front-end block reads URL parameters for display; nonce verified in unsubscribe handler.
        $email   = isset($_GET['em']) ? sanitize_email(wp_unslash($_GET['em'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Front-end block reads URL parameters for display.
        $list_id = isset($_GET['list_id']) ? absint($_GET['list_id']) : 0;

        // Block texts from list settings (with fallback to block attribute or default translation)
        $unsub_title          = $attributes['title'] ?? simplekitmailing_get_block_text($list_id, 'unsub_title', __('Unsubscribe email', 'simplekitmailing'));
        $unsub_message        = !empty($attributes['message']) ? $attributes['message'] : simplekitmailing_get_block_text($list_id, 'unsub_message', __('Your email has been removed from our mailing list.', 'simplekitmailing'));
        $unsub_error_message  = !empty($attributes['error_message']) ? $attributes['error_message'] : simplekitmailing_get_block_text($list_id, 'unsub_error_message', __('The email address was not found in our mailing list.', 'simplekitmailing'));
        if (empty($unsub_error_message)) {
            $unsub_error_message = __('The email address was not found in our mailing list.', 'simplekitmailing');
        }
        $unsub_no_email       = simplekitmailing_get_block_text($list_id, 'unsub_no_email', __('No email provided for unsubscription.', 'simplekitmailing'));
        $unsub_instructions   = simplekitmailing_get_block_text($list_id, 'unsub_instructions', __('Use the unsubscribe link sent in the email to remove your registration.', 'simplekitmailing'));

        $unsub_success = false;

        if (!empty($email) && is_email($email)) {
            global $wpdb;
            $table_subscribers = $wpdb->prefix . 'sm_subscribers';
            $table_removed     = $wpdb->prefix . 'sm_removed';
            $table_pending     = $wpdb->prefix . 'sm_pending';
            $deleted_total     = 0;

            // data is being changed on plugin's custom tables at the lines below. no caching is possible.
            if ($list_id) {
                // Remove only from the specific list
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $deleted = $wpdb->delete($table_subscribers, ['email' => $email, 'list_id' => $list_id]);
                if ($deleted !== false) {
                    $deleted_total += $deleted;
                }
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $deleted = $wpdb->delete($table_pending, ['email' => $email, 'list_id' => $list_id]);
                if ($deleted !== false) {
                    $deleted_total += $deleted;
                }
                if ($deleted_total > 0) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->replace($table_removed, ['list_id' => $list_id, 'email' => $email]);
                    $unsub_success = true;
                }
            } else {
                // Remove from all lists (fallback for compatibility)
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $subs = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT list_id FROM %i WHERE email = %s", $table_subscribers, $email));
                foreach ($subs as $s) {
                    $lid = $s->list_id ?: 0;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $deleted = $wpdb->delete($table_subscribers, ['email' => $email, 'list_id' => $lid]);
                    if ($deleted !== false) {
                        $deleted_total += $deleted;
                    }
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $deleted = $wpdb->delete($table_pending, ['email' => $email, 'list_id' => $lid]);
                    if ($deleted !== false) {
                        $deleted_total += $deleted;
                    }
                }
                if ($deleted_total > 0) {
                    foreach ($subs as $s) {
                        $lid = $s->list_id ?: 0;
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                        $wpdb->replace($table_removed, ['list_id' => $lid, 'email' => $email]);
                    }
                    $unsub_success = true;
                }
            }
        }

        // Use custom CSS if provided, otherwise use default
        $block_css = !empty($attributes['custom_css']) ? $attributes['custom_css'] : simplekitmailing_get_default_unsubscribe_css();

        ob_start();
        ?>
        <div class="simplekitmailing-unsubscribe-block">
            <style>
                <?php echo wp_kses_post($block_css); ?>
            </style>

            <?php if (!empty($unsub_title)) : ?>
            <h2><?php echo esc_html($unsub_title); ?></h2>
            <?php endif; ?>

            <?php if (!empty($email) && is_email($email)) : ?>
                <?php if ($unsub_success) : ?>
                <div class="sm-unsubscribed">
                    <p><?php echo esc_html($unsub_message); ?></p>
                    <p><strong><?php echo esc_html($email); ?></strong></p>
                </div>
                <?php else : ?>
                <div class="sm-no-email" style="color:#721c24;background:#f8d7da;border-color:#f5c6cb;">
                    <p><?php echo esc_html($unsub_error_message); ?></p>
                    <p><strong><?php echo esc_html($email); ?></strong></p>
                </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="sm-no-email" style="color:#721c24;background:#f8d7da;border-color:#f5c6cb;">
                    <p><?php echo esc_html($unsub_error_message); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

// ---------------------------------------------------------------------------
// Frontend rendering of "Simple Kit Mailing Confirm" block (double opt-in)
// ---------------------------------------------------------------------------
function simplekitmailing_render_confirm_block($attributes) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Front-end block reads URL parameters for display; nonce verified in confirm handler.
    $email   = isset($_GET['sm_email']) ? sanitize_email(wp_unslash($_GET['sm_email'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Front-end block reads URL parameters for display.
    $code    = isset($_GET['sm_code']) ? sanitize_text_field(wp_unslash($_GET['sm_code'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Front-end block reads URL parameters for display.
    $list_id = isset($_GET['list_id']) ? absint($_GET['list_id']) : 0;

    // Texts from block attributes or defaults
    $title           = $attributes['title'] ?? __('Confirm your subscription', 'simplekitmailing');
    $success_message = !empty($attributes['success_message']) ? $attributes['success_message'] : __('Your email has been confirmed! You are now subscribed to our mailing list.', 'simplekitmailing');
    $error_message   = !empty($attributes['error_message']) ? $attributes['error_message'] : __('Invalid or expired confirmation link. Please try registering again.', 'simplekitmailing');
    $removed_message = !empty($attributes['removed_message']) ? $attributes['removed_message'] : __('This email is in our removed list and cannot be subscribed.', 'simplekitmailing');

    $result = '';
    $result_type = '';

    if (!empty($email) && !empty($code) && is_email($email)) {
        $confirm_result = simplekitmailing_confirm_subscription($email, $code, $list_id);

        switch ($confirm_result) {
            case 'confirmed':
                $result = $success_message;
                $result_type = 'success';
                break;
            case 'removed':
                $result = $removed_message;
                $result_type = 'error';
                break;
            case 'invalid':
            default:
                $result = $error_message;
                $result_type = 'error';
                break;
        }
    } else {
        $result = $error_message;
        $result_type = 'error';
    }

    // Use custom CSS if provided, otherwise use default
    $block_css = !empty($attributes['custom_css']) ? $attributes['custom_css'] : simplekitmailing_get_default_confirm_css();

    ob_start();
    ?>
    <div class="simplekitmailing-confirm-block">
        <style>
            <?php echo wp_kses_post($block_css); ?>
        </style>

        <?php if (!empty($title)) : ?>
        <h2><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="sm-confirm-<?php echo esc_attr($result_type); ?>">
            <p><?php echo esc_html($result); ?></p>
            <?php if ($result_type === 'success' && !empty($email)) : ?>
                <p><strong><?php echo esc_html($email); ?></strong></p>
            <?php endif; ?>
        </div>

        <p style="margin-top:20px;">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home', 'simplekitmailing'); ?></a>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
