<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page (main plugin page)
// ---------------------------------------------------------------------------
function simplekitpdf_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitpdf'));
    }
    ?>
    <div class="wrap simplekitpdf-wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitpdf'); ?></h1>

        <div class="simplekitpdf-help-card">
            <h2><?php esc_html_e('Simple Kit PDF', 'simplekitpdf'); ?></h2>
            <p>
                <?php esc_html_e('Use this plugin to easily and quickly display PDFs on your pages and posts.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitpdf'); ?></h3>
            <ul>
                <li><?php esc_html_e('Quick import of PDFs using the block editor.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Display customization.', 'simplekitpdf'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitpdf'); ?></h3>
            <p>
                <?php esc_html_e('On your page or post, include the PDF importer block.', 'simplekitpdf'); ?>
            </p>
            <p>
                <?php esc_html_e('Select the file to display from your media folder or specify the URL of a PDF file hosted on your website. After that, make some adjustments:', 'simplekitpdf'); ?>
            </p>
            <ol>
                <li><?php esc_html_e('What is the display height? A fixed size? The height of the first page? The size of the entire PDF content?', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Should the PDF viewer toolbar appear, with pagination, zoom, and other tools?', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('If the toolbar is displayed, include a button to download the PDF?', 'simplekitpdf'); ?></li>
            </ol>

            <h3><?php esc_html_e('Mozilla pdf.js', 'simplekitpdf'); ?></h3>
            <p>
                <?php esc_html_e('This plugin uses the Mozilla pdf.js project to display content. This project is licensed under Apache-2.0. To learn more, visit:', 'simplekitpdf'); ?>
                <a href="https://mozilla.github.io/pdf.js/" target="_blank" rel="noopener noreferrer">https://mozilla.github.io/pdf.js/</a>
            </p>
        </div>
    </div>
    <?php
}
