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
            <p><?php esc_html_e('QR codes are generated entirely locally within your server using a pure PHP library. No external API calls are needed — the plugin works offline and does not send any data to third-party services.', 'simplekitqrcode'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('How It Works', 'simplekitqrcode'); ?></h3>
            <p>
                <strong><?php esc_html_e('1. Generate a QR Code', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('Go to SK QRCode and select a post or page from the list. Click "Generate QR Code" to create it instantly. The QR code encodes a special tracking URL that redirects to the original post.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('2. Download', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('After generation, click "Download QR Code PNG (4096x4096px)" to save the image to your computer. The high resolution ensures crisp results even in large format printed materials.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('3. Track Accesses', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('The Statistics page shows how many times each QR code has been scanned. Each access is counted when a visitor follows the tracking URL, giving you insight into your QR code performance.', 'simplekitqrcode'); ?>
            </p>

            <h3 style="color:#1d2327;"><?php esc_html_e('QR Code Generation', 'simplekitqrcode'); ?></h3>
            <p><?php esc_html_e('QR codes are generated locally using the psyon/php-qrcode library, a pure PHP QR code encoder. The library handles data encoding, Reed-Solomon error correction (level M — 15%), module placement, masking, and format information according to the ISO/IEC 18004 standard.', 'simplekitqrcode'); ?></p>
            <p><?php esc_html_e('Each QR code encodes a tracking URL (not the direct post URL) so that every scan is counted. The visitor is then redirected to the original post or page.', 'simplekitqrcode'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Tips for Best Results', 'simplekitqrcode'); ?></h3>
            <p>
                <strong><?php esc_html_e('Print Size', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('For print materials, the downloaded 4096x4096px PNG can be used at sizes up to approximately 30x30 cm (12x12 inches) while maintaining sharpness.', 'simplekitqrcode'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Testing', 'simplekitqrcode'); ?></strong>
                <br />
                <?php esc_html_e('Always test your QR codes with multiple scanner apps before printing in large quantities.', 'simplekitqrcode'); ?>
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
