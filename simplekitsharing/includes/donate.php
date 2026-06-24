<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Donate page
// ---------------------------------------------------------------------------
function simplekitsharing_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitsharing'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Donate', 'simplekitsharing'); ?></h1>

        <div class="notice notice-info">
            <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitsharing'); ?></p>
        </div>

        <div style="margin-top:30px;max-width:400px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com" />
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="no_note" value="0" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="<?php esc_attr_e('Donate with PayPal button', 'simplekitsharing'); ?>" />
            </form>
        </div>

        <hr />

        <p><?php esc_html_e('Thank you for your support!', 'simplekitsharing'); ?></p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Donation dismissible notice (shown on main plugin page after activation)
// ---------------------------------------------------------------------------
add_action('admin_notices', 'simplekitsharing_donation_notice');
function simplekitsharing_donation_notice() {
    // Only show on Simple Kit Sharing admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'simplekitsharing') === false) {
        return;
    }

    // Only show on the main page (not sub-pages)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page URL detection, not a state-changing action.
    if (!isset($_GET['page']) || $_GET['page'] !== 'simplekitsharing') {
        return;
    }

    // Check if user has dismissed the notice
    $user_id = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'simplekitsharing_dismiss_donation', true);
    if ($dismissed) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" id="simplekitsharing-donation-notice">
        <p>
            <?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, Please access the Donate sub menu.', 'simplekitsharing'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitsharing-donate')); ?>" class="button button-small" style="margin-left:10px;">
                <?php esc_html_e('Donate', 'simplekitsharing'); ?>
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitsharing-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitsharing_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitsharing_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// AJAX handler for dismissing the donation notice
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitsharing_dismiss_donation', 'simplekitsharing_ajax_dismiss_donation');
function simplekitsharing_ajax_dismiss_donation() {
    check_ajax_referer('simplekitsharing_dismiss_donation');
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'simplekitsharing_dismiss_donation', 1);
    wp_send_json_success();
}
