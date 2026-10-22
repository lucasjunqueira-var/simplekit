<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitregistration_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitregistration'); ?></h1>

        <div style="margin-top:20px;background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Simple Kit Registrarion', 'simplekitregistration'); ?></h2>
            <p><?php esc_html_e('This plugin aims to provide a simplified way to register applications on your website. With it, you can create forms that can be filled with information about the applications and also receive files, such as PDFs. The plugin also provides a tool to forward applications to independent reviewers, who do not need access to your website\'s editing panel.', 'simplekitregistration'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Highlights', 'simplekitregistration'); ?></h3>
            <ul>
                <li><?php esc_html_e('Create forms to receive applications.', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Customize with various types of information, including file uploads.', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Allow evaluation of responses even by selected individuals outside your team, with password access.', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Maintain as many simultaneous applications as needed.', 'simplekitregistration'); ?></li>
            </ul>

            <h3 style="color:#1d2327;"><?php esc_html_e('Available fields', 'simplekitregistration'); ?></h3>
            <p><?php esc_html_e('The forms created can contain various types of information:', 'simplekitregistration'); ?></p>
            <ul>
                <li><?php esc_html_e('Text', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('E-mail', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Password', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('URL', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Number', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Phone number', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Tetx area', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Checkboxes', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Radio buttons', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('Dropdown selectors', 'simplekitregistration'); ?></li>
                <li><?php esc_html_e('File uploads', 'simplekitregistration'); ?></li>
            </ul>

            <h3 style="color:#1d2327;"><?php esc_html_e('How the plugin works', 'simplekitregistration'); ?></h3>
            <p><?php esc_html_e('Start by configuring the interface text displayed for both applications and external reviewers.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('Next, create your registration form. You can maintain as many forms as you want simultaneously.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('Create your page or post and include your signup form using the corresponding block.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('Register reviewers, indicating which forms they will have access to and what their access passwords will be.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('Create your access page or post for reviewers and include the corresponding block. When they access it, they will be asked for the email and password you created so they can access the entries. Reviewers can leave as many comments as they want on each entry for a more detailed evaluation. You can, through the WordPress dashboard, track entries, reviewer comments, and even leave your own impressions, all recorded with date and time for validation.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('And you can even export all your forms or just a selected one to keep a backup copy of all registrations with their information and files.', 'simplekitregistration'); ?></p>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Page: Donate
// ---------------------------------------------------------------------------
function simplekitregistration_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Donate', 'simplekitregistration'); ?></h1>

        <div class="notice notice-info">
            <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitregistration'); ?></p>
        </div>

        <div style="margin-top:30px;max-width:400px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com">
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira">
                <input type="hidden" name="currency_code" value="USD">
                <input type="hidden" name="no_note" value="0">
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button">
            </form>
        </div>

        <hr>
        <p><?php esc_html_e('Thank you for your support!', 'simplekitregistration'); ?></p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Dismissible donation notice on the main plugin page
// ---------------------------------------------------------------------------
add_action('admin_notices', 'simplekitregistration_donation_notice');

function simplekitregistration_donation_notice() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'simplekitregistration') === false) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'simplekitregistration') {
        return;
    }

    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'simplekitregistration_dismiss_donation', true)) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible" id="simplekitregistration-donation-notice">
        <p>
            <?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, Please access the Donate sub menu.', 'simplekitregistration'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitregistration-donate')); ?>" class="button button-small" style="margin-left:10px;"><?php esc_html_e('Donate', 'simplekitregistration'); ?></a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitregistration-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitregistration_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitregistration_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_simplekitregistration_dismiss_donation', 'simplekitregistration_ajax_dismiss_donation');

function simplekitregistration_ajax_dismiss_donation() {
    check_ajax_referer('simplekitregistration_dismiss_donation');
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    update_user_meta(get_current_user_id(), 'simplekitregistration_dismiss_donation', 1);
    wp_send_json_success();
}
