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
            <p><?php esc_html_e('This plugin has a very specific function: to adjust the icon displayed in browser tabs when your pages are shown, as well as to define the text and image used when someone shares a page from your website on a social network or messaging app. The configuration is simple and can be done both globally and per page/post.', 'simplekitsharing'); ?></p>

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Highlights', 'simplekitsharing'); ?></h2>
            <ul>
                <li><?php esc_html_e('Quick sharing information setup.', 'simplekitsharing'); ?></li>
                <li><?php esc_html_e('Global and individual page setup.', 'simplekitsharing'); ?></li>
            </ul>

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('How the plugin works', 'simplekitsharing'); ?></h2>
            <p><?php esc_html_e('After activating the plugin, access the "global" page in the SK Sharing menu. Here you can configure the general sharing settings for your website.', 'simplekitsharing'); ?></p>
            <img style="width:100%;" src="<?php echo esc_url(str_replace('/includes', '', plugins_url( 'assets/screenshot-1.jpg', __FILE__ ))) ?>" />
            <p><?php esc_html_e('In addition, in your posts and pages, look for the "Simple Kit Sharing" setting. Any values ​​you enter here will override the global settings.', 'simplekitsharing'); ?></p>
            <img style="width:100%;" src="<?php echo esc_url(str_replace('/includes', '',plugins_url( 'assets/screenshot-2.jpg', __FILE__ ))) ?>" />

            <h3 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Frequently asked questions', 'simplekitsharing'); ?></h2>
            <p>
                <strong><?php esc_html_e('What are the best image formats for icons and sharing?', 'simplekitsharing'); ?></strong><br />
                <?php esc_html_e('Icons work best with 512x512 pixel PNG images, which can contain transparency. The recommended format for sharing images is 1200x630 pixel JPEGs with a quality of around 60%.', 'simplekitsharing'); ?>
            </p>
        </div></div>
    </div>
    <?php
}
