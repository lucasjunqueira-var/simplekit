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
            <h2 style="margin-top:0;color:#1d2327;">Simple Kit Forms</h2>
            <p>This plugin allows for the quick creation of forms that can be added to pages and posts using the block editor. All responses are recorded in its own database and can be exported at any time.</p>

            <h3 style="color:#1d2327;">Highlights</h3>
            <ul>
                <li>Simple form creation.</li>
                <li>Quick embedding into pages and posts.</li>
                <li>Review and manage responses directly on the dashboard.</li>
                <li>Export responses as spreadsheets.</li>
                <li>Spam protection using reCaptch or Akismet.</li>
            </ul>

            <h3 style="color:#1d2327;">Available fields</h3>
            <p>The forms created can contain various types of information:</p>
            <ul>
                <li>Text</li>
                <li>E-mail</li>
                <li>Password</li>
                <li>URL</li>
                <li>Number</li>
                <li>Phone number</li>
                <li>Tetx area</li>
                <li>Checkboxes</li>
                <li>Radio buttons</li>
                <li>Dropdown selectors</li>
                <li>File uploads</li>
            </ul>

            <h3 style="color:#1d2327;">How the plugin works</h3>
            <p>After activating the plugin, first access the "settings" page in the SK Forms menu of your dashboard. The first adjustment to make is to define the spam protection for your forms. The recommended method is reCaptcha. To do this, you need to provide the keys supplied by the service, which can be created for free on the service's website at <a href="https://www.google.com/recaptcha/" target="_blank" rel="noopener noreferrer">https://www.google.com/recaptcha/</a>. Another form of spam protection is the use of Akismet. You can also proceed without protection, but this is not recommended. Next, define whether or not you want to log the IP address of the visitor who responds to your forms.</p>
            <p>You can also choose to receive your form responses via email. To do this, in the form settings, fill in the delivery details and the mailbox to which you want to send the messages (which will also be recorded in your WordPress site's dashboard).</p>
            <p>Next, go to the "create form" page. Here, you must give your form a title, specify the thank-you message after it is completed, and enter the text for the submit button.</p>
            <p>After that, in "field palette", add as many fields as you want to your form and click "create form".</p>
            <p>Your form will be available along with the others you create on the "forms" page of the menu. From this list, you can edit, check the responses received, or even remove the form.</p>
            <p>When editing a page or post where you want to include the form, simply add a "Simple Form Kit" block and, in that block's settings, specify which of your forms should appear. You can also adjust the form's rendering using the custom CSS field.</p>
            <p>When reviewing form responses, you'll immediately see the most recent ones in a list, allowing you to directly check details about each one. You can also remove them individually or in groups, and even export them all as a CSV spreadsheet.</p>
            <p>Additionally, on the "backup" page you can make a backup copy of all your forms and received responses, which can even be used to import them into another WordPress installation.</p>

            <h3 style="color:#1d2327;">Frequently asked questions</h3>
            <p>
                <strong>How many forms can I register? How many responses can I receive for each one?</strong><br />
                There are no limits to the number of forms and responses received.
            </p>
            <p>
                <strong>How do I adapt the form display to my page layout?</strong><br />
                When adding a form block to your posts or pages, one of the possible settings is the CSS styles used for rendering. You can adjust this CSS individually per block to suit your needs.
            </p>
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
