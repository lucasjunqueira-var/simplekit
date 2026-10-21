<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitdownloads_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitdownloads'); ?></h1>

        <div class="skd-card">
            <h2><?php esc_html_e('The Simple Kit Downloads plugin', 'simplekitdownloads'); ?></h2>
            <p>
                <?php esc_html_e('This plugin creates download buttons for files hosted on your site and records how many times each file and version was downloaded. Downloads can be served from a file uploaded through the plugin or from a URL on the same domain.', 'simplekitdownloads'); ?>
            </p>

            <h3><?php esc_html_e('Files', 'simplekitdownloads'); ?></h3>
            <ul>
                <li><?php esc_html_e('A download is identified by a name and can contain any number of versions.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Versions are numeric and may contain dots, such as 25, 25.1 or 25.0.1. The highest version is used as the current version.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Each version can be an uploaded file or a URL on this site. URLs are served from their original location and are not copied to the plugin folder.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Replacing the file of a version discards the previous binary. Deleting a version or an entire download keeps its statistics.', 'simplekitdownloads'); ?></li>
            </ul>

            <h3><?php esc_html_e('Showing a download button', 'simplekitdownloads'); ?></h3>
            <p>
                <?php esc_html_e('Add the "Simple Kit Download" block to a page or post, select the download and the version, and optionally override the button text and CSS. Version "Current (latest)" always resolves to the newest version when the button is clicked, even on cached pages.', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('The same result can be achieved with the shortcode:', 'simplekitdownloads'); ?>
                <code>[simplekitdownloads id="1" version="current"]</code>
            </p>
            <p>
                <?php esc_html_e('Optional shortcode attributes: text="Download now" and css=".simplekitdownloads-button{...}".', 'simplekitdownloads'); ?>
            </p>

            <h3><?php esc_html_e('Statistics', 'simplekitdownloads'); ?></h3>
            <p>
                <?php esc_html_e('A download is counted when the delivery starts. The overview lists every download with its total, oldest and newest download. Clicking a download shows the same information per version, from the newest to the oldest version.', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('The CSV export always matches the view on screen. A full log export with one line per download record is also available. Records of deleted downloads and versions remain in the statistics.', 'simplekitdownloads'); ?>
            </p>

            <h3><?php esc_html_e('Setup', 'simplekitdownloads'); ?></h3>
            <ul>
                <li><?php esc_html_e('Default button text and the text pattern used when a version is displayed.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Global CSS for the download buttons. Leave it empty to use the built-in style.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Option to record the visitor IP address with each download.', 'simplekitdownloads'); ?></li>
            </ul>

            <h3><?php esc_html_e('Backup', 'simplekitdownloads'); ?></h3>
            <p>
                <?php esc_html_e('The backup file is a JSON document containing the settings, downloads, versions and statistics. Uploaded binaries are not included; versions registered by URL keep working because only the URL is stored.', 'simplekitdownloads'); ?>
            </p>
        </div>
    </div>
    <?php
}
