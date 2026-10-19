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
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('The Simple Kit Registration Plugin', 'simplekitregistration'); ?></h2>
            <p><?php esc_html_e('Simple Kit Registration collects registration data through forms that may include file uploads and lets selected evaluators review the received responses on restricted pages of your site, without accessing the WordPress dashboard.', 'simplekitregistration'); ?></p>
            <p><?php esc_html_e('All data and uploaded files stay on your own server.', 'simplekitregistration'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('How to use', 'simplekitregistration'); ?></h3>
            <p>
                <strong><?php esc_html_e('Creating a form', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Go to SK Registration &rarr; Create form, give your form a title and a form code, then add fields from the palette. Besides text, textarea, checkboxes, radio buttons and dropdowns, you can add one or more File fields.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('The form code', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('The form code is an alphanumeric identifier without spaces or special characters. It is used as the name of the folder that stores the uploaded files, and cannot be changed after the form receives its first response.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('File fields', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Each file field can define instructions, a maximum size in MB and a list of allowed extensions. Leave the extensions empty to accept any file type. During a submission, files larger than the configured limit or with a disallowed extension are rejected.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Displaying a form', 'simplekitregistration'); ?></strong><br>
                <?php
                /* translators: %s: shortcode example. */
                echo esc_html(sprintf(__('Use the block "Simple Kit Registration Form" in the editor, or the shortcode %s in any post or page.', 'simplekitregistration'), '[simplekitregistration code="my-form"]'));
                ?>
            </p>
            <p>
                <strong><?php esc_html_e('Viewing responses in the dashboard', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Go to SK Registration &rarr; Forms and click the number under "Responses". You can open a response in detail, download its files and export the responses as CSV.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Messages', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('In the response details, both in the dashboard and on the restricted page, anyone allowed to view the response can add comment messages. Each message records the author (WordPress user name/e-mail or the evaluator e-mail) and the date/time, and is shown below the response in the order it was received. Messages are permanent: they cannot be edited or deleted.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Settings', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Go to SK Registration &rarr; Settings to customize every text displayed to the evaluators on the restricted pages (login, form list, responses list, response details and messages) and every text written to the export spreadsheets (the column headers of the responses CSV and the column headers and row labels of the form backup CSV). The configuration is global (it applies to all forms). Leave a field empty to restore its default text.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Evaluators', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Go to SK Registration &rarr; Evaluators to register the e-mail and password of each evaluator and select which forms each one may review. Passwords are stored encrypted (hashed) in the plugin table.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Restricted pages', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('Add the block "Simple Kit Registration Access" to any page. Visitors see a login form; after a successful login a cookie is set for one week and the block lists the forms that the evaluator may access. Clicking a form shows its responses and clicking a response opens the full content in a new tab.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Backup', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('The Backup page generates a ZIP file with all the plugin data and uploaded files, and can also import a previously generated backup. The same ZIP is generated automatically when the plugin is deactivated and restored when it is activated again.', 'simplekitregistration'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Individual form backup', 'simplekitregistration'); ?></strong><br>
                <?php esc_html_e('The Backup page also exports a single form as a ZIP file containing one CSV spreadsheet with all the responses and all the messages of that form (ready to open in Excel) plus the complete upload folder with the files of every response. This individual export is export only: it cannot be imported back.', 'simplekitregistration'); ?>
            </p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Upload folder', 'simplekitregistration'); ?></h3>
            <p>
                <?php esc_html_e('Uploaded files are stored under the WordPress uploads directory, following the layout below:', 'simplekitregistration'); ?><br>
                <code>uploads/simplekitregistration/<form-code>/<response-id>/<file></code>
            </p>
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
