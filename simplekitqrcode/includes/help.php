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
            <p><?php esc_html_e('Simple Kit QRCode generates QR codes for your WordPress posts and pages, making it easy to share links via print materials, posters, business cards, or any offline medium. Each QR code includes a tracking URL that lets you monitor how many times it has been accessed.', 'simplekitqrcode'); ?></p>
            <p><?php esc_html_e('The plugin generates QR codes directly within your WordPress admin panel. You can download them as high-resolution PNG images (2048x2048 pixels) suitable for both digital and print use.', 'simplekitqrcode'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('How It Works', 'simplekitqrcode'); ?></h3>
            <p>
                <strong><?php esc_html_e('1. Generate a QR Code', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('Go to SK QRCode and select a post or page from the list. Click "Generate QR Code" to create it instantly. The QR code encodes a special tracking URL that redirects to the original post.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('2. Download', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('After generation, click "Download QR Code PNG (2048x2048px)" to save the image to your computer. The high resolution ensures crisp results even in printed materials.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('3. Track Accesses', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('The Statistics page shows how many times each QR code has been scanned. Each access is counted when a visitor follows the tracking URL, giving you insight into your QR code performance.', 'simplekitqrcode'); ?>
            </p>

            <h3 style="color:#1d2327;"><?php esc_html_e('QR Code Generation', 'simplekitqrcode'); ?></h3>
            <p><?php esc_html_e('Simple Kit QRCode uses the QRServer API to generate accurate, standards-compliant QR codes. If the API is unavailable, the plugin falls back to a built-in matrix generator that produces functional QR codes.', 'simplekitqrcode'); ?></p>
            <p><?php esc_html_e('Each QR code encodes a tracking URL (not the direct post URL) so that every scan is counted. The visitor is then redirected to the original post or page.', 'simplekitqrcode'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Tips for Best Results', 'simplekitqrcode'); ?></h3>
            <p>
                <strong><?php esc_html_e('Print Size', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('For print materials, the downloaded 2048x2048px PNG can be used at sizes up to approximately 15x15 cm (6x6 inches) while maintaining sharpness.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Testing', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('Always test your QR codes with multiple scanner apps before printing in large quantities.', 'simplekitqrcode'); ?>
            </p>
        </div></div>
    </div>
    <?php
}