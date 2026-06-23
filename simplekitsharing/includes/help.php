<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitsharing_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitsharing'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitsharing'); ?></h1>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('The Simple Kit Sharing Plugin', 'simplekitsharing'); ?></h2>
            <p><?php esc_html_e('Simple Kit Sharing is a lightweight plugin that manages social sharing meta tags for your WordPress site. It automatically inserts Open Graph (og:) and Twitter Card meta tags into the head section of your pages, ensuring that when someone shares a link from your site on social media platforms (Facebook, Twitter/X, LinkedIn, WhatsApp, etc.), the preview includes a proper title, description, and image.', 'simplekitsharing'); ?></p>
            <p><?php esc_html_e('The plugin works entirely within your WordPress environment and does not rely on any external services. All settings are stored in your site\'s options and post meta tables.', 'simplekitsharing'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('How It Works', 'simplekitsharing'); ?></h3>
            <p><?php esc_html_e('Simple Kit Sharing provides two levels of configuration:', 'simplekitsharing'); ?></p>
            <p>
                <strong><?php esc_html_e('1. General Settings', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('The main Settings page lets you define default values for the share icon (favicon), share text (description), share image, and article tags. These defaults are used globally on every page of your site unless overridden.', 'simplekitsharing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('2. Per-Page Override (Meta Box)', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('When editing a post or page, a "Simple Kit Sharing" meta box appears in the sidebar. Here you can set custom values for that specific page or post. If a field is left empty, the corresponding global default will be used instead.', 'simplekitsharing'); ?>
            </p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Meta Tags Generated', 'simplekitsharing'); ?></h3>
            <p><?php esc_html_e('When a page is loaded, the plugin automatically inserts the following meta tags into the HTML head:', 'simplekitsharing'); ?></p>
            <p>
                <strong><?php esc_html_e('Open Graph (og:)', 'simplekitsharing'); ?></strong><br />
                <code>og:title</code>, <code>og:description</code>, <code>og:url</code>, <code>og:site_name</code>, <code>og:type</code>, <code>og:image</code>
            </p>
            <p>
                <strong><?php esc_html_e('Twitter Cards', 'simplekitsharing'); ?></strong><br />
                <code>twitter:card</code>, <code>twitter:title</code>, <code>twitter:description</code>, <code>twitter:image</code>
            </p>
            <p>
                <strong><?php esc_html_e('Article Tags', 'simplekitsharing'); ?></strong><br />
                <code>article:tag</code> (<?php esc_html_e('when tags are configured', 'simplekitsharing'); ?>)
            </p>
            <p>
                <strong><?php esc_html_e('Favicon / Icon', 'simplekitsharing'); ?></strong><br />
                <code>link rel="icon"</code>, <code>link rel="shortcut icon"</code> (<?php esc_html_e('when an icon URL is provided', 'simplekitsharing'); ?>)
            </p>
        </div></div>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Tips for Best Results', 'simplekitsharing'); ?></h2>
            <p>
                <strong><?php esc_html_e('Share Image Size', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('For optimal display on social media platforms, use a share image that is at least 1200x630 pixels. This is the standard Open Graph image size recommended by Facebook and most other platforms.', 'simplekitsharing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Share Text Length', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('Keep your share text between 50 and 160 characters. Most social media platforms truncate longer descriptions.', 'simplekitsharing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Favicon Image', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('For the favicon, upload a square PNG image. The plugin automatically generates a properly sized 32x32 pixel version for browser tab display.', 'simplekitsharing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Cache', 'simplekitsharing'); ?></strong>
                <br />
                <?php esc_html_e('Social media platforms cache shared URLs. If you update your sharing settings, use the sharing debugger tools from Facebook and Twitter/X to refresh the cached preview for your URL.', 'simplekitsharing'); ?>
            </p>
        </div></div>
    </div>
    <?php
}
