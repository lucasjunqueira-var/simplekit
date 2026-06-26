<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitqrcode_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitqrcode'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitqrcode'); ?></h1>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('The Simple Kit QRCode Plugin', 'simplekitqrcode'); ?></h2>
            <p><?php esc_html_e('This plugin allows for the quick creation of QR codes that link to posts or pages on your website. Furthermore, it tracks the usage of these codes, keeping an updated list of how many times each one has been used.', 'simplekitqrcode'); ?></p>

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Highlights', 'simplekitqrcode'); ?></h2>
            <ul>
                <li><?php esc_html_e('Simple and quick generation of QR codes.', 'simplekitqrcode'); ?></li>
                <li><?php esc_html_e('Monitoring code usage statistics.', 'simplekitqrcode'); ?></li>
            </ul>

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('How the plugin works', 'simplekitqrcode'); ?></h2>
            <p><?php esc_html_e('After activating the plugin, access the "generate QR code" page from the "SK QRCode" menu. Here, you will see a list of posts and pages published on your website. Simply select one and click the "generate QR code" button.', 'simplekitqrcode'); ?></p>
            <img style="width:100%;" src="<?php echo esc_url(str_replace('/includes', '', plugins_url( 'assets/screenshot-1.jpg', __FILE__ ))) ?>" />

            <p><?php esc_html_e('The code will be generated, and you can download the image for printing.', 'simplekitqrcode'); ?></p>
            <img style="width:100%;" src="<?php echo esc_url(str_replace('/includes', '', plugins_url( 'assets/screenshot-2.jpg', __FILE__ ))) ?>" />

            <p><?php esc_html_e('On the statistics page you can see all the generated chords, the number of times they have been scanned, and the last time someone used them, as well as delete the data for any of them.', 'simplekitqrcode'); ?></p>
            <img style="width:100%;" src="<?php echo esc_url(str_replace('/includes', '', plugins_url( 'assets/screenshot-3.jpg', __FILE__ ))) ?>" />

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Frequently asked questions', 'simplekitqrcode'); ?></h2>
            <p>
                <strong><?php esc_html_e('Does QR code generation use any external service or API?', 'simplekitqrcode'); ?></strong><br />
                <?php esc_html_e('No, all image generation is done by the plugin itself. For this, it´s important that your web server has the PHP GD2 extension enabled (which is common).', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('I lost the QR code image for a page, how do I download it again?', 'simplekitqrcode'); ?></strong><br />
                <?php esc_html_e('Simply generate the code for the same page again. The usage statistics for this new code will be added to those you already have recorded for it.', 'simplekitqrcode'); ?>
            </p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Library Credits', 'simplekitqrcode'); ?></h3>
            <p>
                <?php esc_html_e('This plugin uses the psyon/php-qrcode library by Donald Becker, based on Kreative Software\'s QR code generator.', 'simplekitqrcode'); ?>
                <br />
                <?php esc_html_e('Repository:', 'simplekitqrcode'); ?>
                <a href="https://github.com/psyon/php-qrcode" target="_blank" rel="noopener noreferrer">https://github.com/psyon/php-qrcode</a>
                <br />
                <?php esc_html_e('License:', 'simplekitqrcode'); ?>
                <a href="https://opensource.org/licenses/MIT" target="_blank" rel="noopener noreferrer">MIT License</a>
            </p>
        </div></div>
    </div>
    <?php
}
