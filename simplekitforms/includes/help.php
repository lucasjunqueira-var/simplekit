<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitforms_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }
    ?>
    <div class="wrap">
        <h1>Help & Documentation</h1>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;">The Simple Kit Forms Plugin</h2>
            <p>Simple Kit Forms is a streamlined plugin for creating simple forms and managing responses directly from your WordPress installation. Its purpose is to serve small contact forms, surveys, and data collection needs without relying on external form services.</p>
            <p>Unlike dedicated form platforms (Google Forms, Typeform, etc.), Simple Kit Forms operates entirely within your own WordPress environment. This makes it ideal for website owners who need a straightforward, self-hosted solution for collecting data from their users.</p>
            <p>The plugin manages its own database tables for forms and entries. All data stays on your server, giving you full control and privacy.</p>

            <h3 style="color:#1d2327;">How to use</h3>
            <p>
                <strong>Creating a form</strong><br />
                Go to <em>SK Forms &rarr; Create form</em>, give your form a title, and start adding fields from the palette. You can add text fields, email, textarea, checkboxes, radio buttons, and dropdowns. Drag to reorder fields as needed.
            </p>
            <p>
                <strong>Displaying a form</strong><br />
                Use the <code>[simplekitforms id="X"]</code> shortcode in any post or page, replacing X with the form ID. Alternatively, use the Gutenberg block "Simple Kit Form" to select and display a form visually.
            </p>
            <p>
                <strong>Viewing responses</strong><br />
                Go to <em>SK Forms &rarr; Forms</em> and click the number under "Responses" for any form. You can view individual entries in detail or export all responses as a CSV file.
            </p>
        </div></div>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;">Style</h2>
            <p>To adjust the appearance of the forms displayed on your site, use the following CSS styles as a reference.</p>
            <textarea id="simplekitforms-cssreference" style="width:100%;height:250px;" readonly>/* ===================================================
   Simple Kit Forms &mdash; Default Frontend Styles
   Use these styles as a starting point to customize
   the appearance of forms in your theme.
   =================================================== */

.simplekitforms-form-block {
    max-width: 600px;
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.simplekitforms-form-block h3 {
    margin-top: 0;
}

.simplekitforms-form-block .sf-field {
    margin-bottom: 16px;
}

.simplekitforms-form-block .sf-field label {
    display: block;
    margin-bottom: 4px;
    font-weight: 600;
}

.simplekitforms-form-block .sf-field input[type="text"],
.simplekitforms-form-block .sf-field input[type="email"],
.simplekitforms-form-block .sf-field input[type="password"],
.simplekitforms-form-block .sf-field input[type="url"],
.simplekitforms-form-block .sf-field input[type="number"],
.simplekitforms-form-block .sf-field input[type="tel"],
.simplekitforms-form-block .sf-field textarea,
.simplekitforms-form-block .sf-field select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}

.simplekitforms-form-block .sf-submit {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.simplekitforms-form-block .sf-submit:hover {
    background: #005a87;
}

.simplekitforms-form-block .sf-message {
    margin-top: 12px;
    padding: 10px 14px;
    border-radius: 4px;
}

.simplekitforms-form-block .sf-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.simplekitforms-form-block .sf-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}</textarea>
        </div></div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Page: Donate
// ---------------------------------------------------------------------------
function simplekitforms_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }
    ?>
    <div class="wrap">
        <h1>Donate</h1>

        <div class="notice notice-info">
            <p>This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.</p>
        </div>

        <div style="margin-top:30px;max-width:400px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com" />
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="no_note" value="0" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
            </form>
        </div>

        <hr />

        <p>Thank you for your support!</p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Donation dismissible notice (shown on main plugin page after activation)
// ---------------------------------------------------------------------------
add_action('admin_notices', 'simplekitforms_donation_notice');
function simplekitforms_donation_notice() {
    // Only show on Simple Kit Forms admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'simplekitforms') === false) {
        return;
    }

    // Only show on the main page (not sub-pages)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'simplekitforms') {
        return;
    }

    // Check if user has dismissed the notice
    $user_id = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'simplekitforms_dismiss_donation', true);
    if ($dismissed) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" id="simplekitforms-donation-notice">
        <p>
            This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, please access the Donate sub menu.
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitforms-donate')); ?>" class="button button-small" style="margin-left:10px;">
                Donate
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitforms-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitforms_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitforms_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// AJAX: dismiss donation notice permanently
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitforms_dismiss_donation', 'simplekitforms_ajax_dismiss_donation');
function simplekitforms_ajax_dismiss_donation() {
    check_ajax_referer('simplekitforms_dismiss_donation');
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'simplekitforms_dismiss_donation', 1);
    wp_send_json_success();
}
