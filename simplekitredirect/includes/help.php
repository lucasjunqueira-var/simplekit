<?php
/**
 * Help page, also used as the plugin main page.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

/**
 * Render the Help page.
 *
 * @return void
 */
function simplekitredirect_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitredirect'));
    }
    ?>
    <div class="wrap simplekitredirect-help">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitredirect'); ?></h1>

        <div class="simplekitredirect-card">
            <h2><?php esc_html_e('Simple Kit Redirect', 'simplekitredirect'); ?></h2>
            <p>
                <?php esc_html_e('Send posts and pages to a new address, configured individually in the WordPress editor.', 'simplekitredirect'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitredirect'); ?></h3>
            <ul>
                <li><?php esc_html_e('Easily redirect access to a page or post to any URL you choose.', 'simplekitredirect'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitredirect'); ?></h3>
            <p>
                <?php esc_html_e('After activating the plugin, your posts and pages will have a new setting that allows you to specify a redirect URL. Leave it blank (the default) to display the page or post itself.', 'simplekitredirect'); ?>
            </p>

            <h3><?php esc_html_e('Frequently asked questions', 'simplekitredirect'); ?></h3>
            <p>
                <strong><?php esc_html_e('What happens to parameters sent in the URL of the original page?', 'simplekitredirect'); ?></strong><br />
                <?php esc_html_e('If the redirect URL you specify contains its own parameters, those will be used. If the specified redirect URL does not include any "GET" parameters, those that were passed to the original address will be forwarded to it.', 'simplekitredirect'); ?>
            </p>
        </div>
    </div>
    <?php
}
