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
                <?php esc_html_e('This plugin adds a redirect field to posts and pages. Fill in a new address and every visit to that content is sent there. Leave the field empty and the content is displayed normally.', 'simplekitredirect'); ?>
            </p>

            <h3><?php esc_html_e('How to use', 'simplekitredirect'); ?></h3>
            <ol>
                <li><?php esc_html_e('Open the post or page in the editor.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Find the "Simple Kit Redirect" box in the sidebar.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Type the destination address and choose the redirect type.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Save or update the content.', 'simplekitredirect'); ?></li>
            </ol>

            <h3><?php esc_html_e('Accepted addresses', 'simplekitredirect'); ?></h3>
            <ul>
                <li><?php esc_html_e('Absolute addresses, including other domains: https://example.com/new-page', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Paths starting with a slash, resolved against this site address: /new-page', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Protocol-relative addresses: //example.com/new-page', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Anything else (javascript:, data:, mailto:, ftp:) is rejected.', 'simplekitredirect'); ?></li>
            </ul>

            <h3><?php esc_html_e('Redirect types', 'simplekitredirect'); ?></h3>
            <ul>
                <li><?php esc_html_e('302 - Temporary: the default. Browsers do not keep it for long, so the destination can be changed later.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('301 - Permanent: browsers and search engines may cache it for a long time. Visitors may keep going to the old destination even after the field is cleared.', 'simplekitredirect'); ?></li>
            </ul>

            <h3><?php esc_html_e('Who still sees the original content', 'simplekitredirect'); ?></h3>
            <p>
                <?php esc_html_e('Previews, REST requests, feeds, robots.txt, sitemaps and 404 requests are never redirected. Password-protected content also keeps showing its password form.', 'simplekitredirect'); ?>
            </p>
            <p>
                <?php esc_html_e('Logged-in editors are redirected like any visitor. To open the original content, add ?simplekitredirect=0 to the address. This only works for users who can edit the content.', 'simplekitredirect'); ?>
            </p>

            <h3><?php esc_html_e('Query strings', 'simplekitredirect'); ?></h3>
            <p>
                <?php esc_html_e('When the destination has no query string, the query string of the original request is appended by default. Use the simplekitredirect_preserve_query filter to disable this behavior.', 'simplekitredirect'); ?>
            </p>

            <h3><?php esc_html_e('Developer filters', 'simplekitredirect'); ?></h3>
            <ul>
                <li><code>simplekitredirect_post_types</code> - <?php esc_html_e('changes which post types can be redirected. Default: post and page.', 'simplekitredirect'); ?></li>
                <li><code>simplekitredirect_should_redirect</code> - <?php esc_html_e('decides at request time whether the redirect should happen.', 'simplekitredirect'); ?></li>
                <li><code>simplekitredirect_preserve_query</code> - <?php esc_html_e('enables or disables query string preservation.', 'simplekitredirect'); ?></li>
                <li><code>simplekitredirect_target_url</code> - <?php esc_html_e('changes the final destination before it is validated and sent.', 'simplekitredirect'); ?></li>
            </ul>

            <h3><?php esc_html_e('Troubleshooting', 'simplekitredirect'); ?></h3>
            <ul>
                <li><?php esc_html_e('Nothing happens: confirm the field was saved, the target is a full http(s) address or a path starting with a slash, and the destination is not the same address as the content.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Headers already sent: another plugin or the theme printed output before WordPress could send the redirect. Test with other plugins disabled.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('A 301 keeps redirecting after removal: the browser cached it. Clear the browser cache or test in a private window, and prefer 302 while testing.', 'simplekitredirect'); ?></li>
                <li><?php esc_html_e('Page cache: purge the cache after changing a redirect.', 'simplekitredirect'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}
