<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Donate page
// ---------------------------------------------------------------------------
function simplekitpdf_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitpdf'));
    }
    ?>
    <div class="wrap simplekitpdf-wrap">
        <h1><?php esc_html_e('Donate', 'simplekitpdf'); ?></h1>

        <div class="notice notice-info">
            <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitpdf'); ?></p>
        </div>

        <div class="simplekitpdf-donate-form">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com" />
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="no_note" value="0" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="<?php esc_attr_e('Donate with PayPal button', 'simplekitpdf'); ?>" />
            </form>
        </div>

        <hr />

        <p><?php esc_html_e('Thank you for your support!', 'simplekitpdf'); ?></p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Dismissible donation notice (shown on the main plugin page after activation)
// ---------------------------------------------------------------------------
add_action('admin_notices', 'simplekitpdf_donation_notice');

function simplekitpdf_donation_notice() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'simplekitpdf') === false) {
        return;
    }

    // Only on the main page (Help), not on sub-pages.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page detection.
    if (!isset($_GET['page']) || $_GET['page'] !== 'simplekitpdf') {
        return;
    }

    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'simplekitpdf_dismiss_donation', true)) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible" id="simplekitpdf-donation-notice">
        <p>
            <?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, Please access the Donate sub menu.', 'simplekitpdf'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitpdf-donate')); ?>" class="button button-small" style="margin-left:10px;">
                <?php esc_html_e('Donate', 'simplekitpdf'); ?>
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitpdf-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitpdf_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitpdf_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// AJAX handler for dismissing the donation notice
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitpdf_dismiss_donation', 'simplekitpdf_ajax_dismiss_donation');

function simplekitpdf_ajax_dismiss_donation() {
    check_ajax_referer('simplekitpdf_dismiss_donation');

    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    update_user_meta(get_current_user_id(), 'simplekitpdf_dismiss_donation', 1);
    wp_send_json_success();
}
