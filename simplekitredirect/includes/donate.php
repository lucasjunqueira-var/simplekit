<?php
/**
 * Donate page and donation notice.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

/**
 * Render the Donate page.
 *
 * @return void
 */
function simplekitredirect_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitredirect'));
    }
    ?>
    <div class="wrap simplekitredirect-help">
        <h1><?php esc_html_e('Donate', 'simplekitredirect'); ?></h1>

        <div class="simplekitredirect-card">
            <div class="notice notice-info inline">
                <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitredirect'); ?></p>
            </div>

            <div class="simplekitredirect-donate">
                <form action="https://www.paypal.com/donate" method="post" target="_blank">
                    <input type="hidden" name="business" value="chokito76@gmail.com" />
                    <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                    <input type="hidden" name="currency_code" value="USD" />
                    <input type="hidden" name="no_note" value="0" />
                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="<?php esc_attr_e('Donate with PayPal button', 'simplekitredirect'); ?>" />
                </form>
            </div>

            <p><?php esc_html_e('Thank you for your support!', 'simplekitredirect'); ?></p>
        </div>
    </div>
    <?php
}

add_action('admin_notices', 'simplekitredirect_donation_notice');

/**
 * Show the dismissible donation notice on the main plugin page.
 *
 * @return void
 */
function simplekitredirect_donation_notice() {
    $screen = get_current_screen();

    if (!$screen || false === strpos($screen->id, 'simplekitredirect')) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only detects the current admin page.
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    if ('simplekitredirect' !== $page) {
        return;
    }

    $user_id   = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'simplekitredirect_dismiss_donation', true);

    if ($dismissed) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible" id="simplekitredirect-donation-notice">
        <p>
            <?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, Please access the Donate sub menu.', 'simplekitredirect'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitredirect-donate')); ?>" class="button button-small" style="margin-left:10px;">
                <?php esc_html_e('Donate', 'simplekitredirect'); ?>
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitredirect-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitredirect_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitredirect_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_simplekitredirect_dismiss_donation', 'simplekitredirect_ajax_dismiss_donation');

/**
 * Persist the donation notice dismissal for the current user.
 *
 * @return void
 */
function simplekitredirect_ajax_dismiss_donation() {
    check_ajax_referer('simplekitredirect_dismiss_donation');

    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    update_user_meta(get_current_user_id(), 'simplekitredirect_dismiss_donation', 1);
    wp_send_json_success();
}
