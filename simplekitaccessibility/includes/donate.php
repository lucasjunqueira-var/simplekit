<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Donate page
// ---------------------------------------------------------------------------
function simplekitaccessibility_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitaccessibility'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Donate', 'simplekitaccessibility'); ?></h1>

        <div class="notice notice-info">
            <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitaccessibility'); ?></p>
        </div>

        <div style="margin-top:30px;max-width:400px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com" />
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="no_note" value="0" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="<?php esc_attr_e('Donate with PayPal button', 'simplekitaccessibility'); ?>" />
            </form>
        </div>

        <hr />

        <p><?php esc_html_e('Thank you for your support!', 'simplekitaccessibility'); ?></p>
    </div>
    <?php
}
