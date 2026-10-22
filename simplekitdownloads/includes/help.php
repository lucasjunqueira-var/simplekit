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
            <h2><?php esc_html_e('Simple Kit Downloads', 'simplekitdownloads'); ?></h2>
            <p>
                <?php esc_html_e('Manage file downloads on your website, with access counters and version management.', 'simplekitdownloads'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitdownloads'); ?></h3>
            <ul>
                <li><?php esc_html_e('Create as many file downloads as you want.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Register multiple versions of the same file and allow independent downloads, or always download the most recent one.', 'simplekitdownloads'); ?></li>
                <li><?php esc_html_e('Track download statistics for your files by version and export information including the date and time of file access.', 'simplekitdownloads'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitdownloads'); ?></h3>
            <p>
                <?php esc_html_e('Start by configuring the global display of your download buttons, including their graphic and text properties.', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('Create a download record.', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('Add the various versions of your file, numbering them. You can use three numbering patterns: #, #.#, or #.#.# (such as "25", "25.2", or "25.0.15").', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('Add the corresponding block to your post or page, indicating the download to be performed and the version. You can also adjust the button\'s visual properties. When clicked, the button starts the file download and records the moment the file is accessed.', 'simplekitdownloads'); ?>
            </p>
            <p>
                <?php esc_html_e('You can track and export your file\'s download statistics both globally and by version.', 'simplekitdownloads'); ?>
            </p>
        </div>
    </div>
    <?php
}
