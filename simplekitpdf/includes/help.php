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
            <h2><?php esc_html_e('The Simple Kit PDF Plugin', 'simplekitpdf'); ?></h2>
            <p>
                <?php esc_html_e('This plugin embeds PDF files in posts and pages using pdf.js, the open source PDF viewer maintained by Mozilla. The document is rendered directly in the visitor browser, so no external service is contacted and the file is never uploaded anywhere else.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitpdf'); ?></h3>
            <ul>
                <li><?php esc_html_e('PDF files from the Media Library or from any URL hosted on this site.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Optional toolbar with page navigation, zoom and fit-to-width controls.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Optional download button, controlled per block.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('The library only loads on pages that actually contain a PDF block, and only when the block approaches the viewport.', 'simplekitpdf'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitpdf'); ?></h3>
            <ol>
                <li><?php esc_html_e('Edit a post or page and add the block "Simple Kit PDF".', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Choose a PDF from the Media Library, or switch the source to "Manual URL" and type a URL for a PDF hosted on this site.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('Adjust the block settings in the sidebar and publish.', 'simplekitpdf'); ?></li>
                <li><?php esc_html_e('On the frontend the PDF is displayed with pdf.js, occupying the full width of the block.', 'simplekitpdf'); ?></li>
            </ol>

            <h3><?php esc_html_e('Block settings', 'simplekitpdf'); ?></h3>
            <table class="widefat striped simplekitpdf-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Setting', 'simplekitpdf'); ?></th>
                        <th scope="col"><?php esc_html_e('Default', 'simplekitpdf'); ?></th>
                        <th scope="col"><?php esc_html_e('Description', 'simplekitpdf'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Source', 'simplekitpdf'); ?></strong></td>
                        <td><?php esc_html_e('Media Library', 'simplekitpdf'); ?></td>
                        <td><?php esc_html_e('Media Library files keep working when the file is replaced. The manual URL option accepts absolute URLs or paths relative to this site.', 'simplekitpdf'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Height mode', 'simplekitpdf'); ?></strong></td>
                        <td><?php esc_html_e('Fixed height', 'simplekitpdf'); ?></td>
                        <td><?php esc_html_e('Fixed height keeps the block at the chosen pixel height. First page fits exactly one page. Full document fits every page of the document, including the spacing between them.', 'simplekitpdf'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Fixed height (px)', 'simplekitpdf'); ?></strong></td>
                        <td>600</td>
                        <td><?php esc_html_e('Used by the fixed height mode, between 100 and 2000 pixels. The width always follows the block width.', 'simplekitpdf'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Show toolbar', 'simplekitpdf'); ?></strong></td>
                        <td><?php esc_html_e('Disabled', 'simplekitpdf'); ?></td>
                        <td><?php esc_html_e('Shows the controls above the document: previous/next page, page number, zoom, fit width and, optionally, download.', 'simplekitpdf'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Allow download', 'simplekitpdf'); ?></strong></td>
                        <td><?php esc_html_e('Disabled', 'simplekitpdf'); ?></td>
                        <td><?php esc_html_e('Only available when the toolbar is visible. Adds a download button to the toolbar.', 'simplekitpdf'); ?></td>
                    </tr>
                </tbody>
            </table>

            <p>
                <?php esc_html_e('The first page and full document modes are measured in the browser after the PDF is loaded, so they follow the current zoom level. The fixed height value is used only while the document is loading, and a long document can make the block very tall.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('Same domain requirement', 'simplekitpdf'); ?></h3>
            <p>
                <?php esc_html_e('Only PDF files hosted on this site can be embedded. URLs pointing to other domains are refused both by the block editor and by the renderer, which prevents the block from being used to frame third-party content.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('About the download control', 'simplekitpdf'); ?></h3>
            <p>
                <?php esc_html_e('Disabling the download button removes the control and the no-JavaScript fallback link, but it is a display policy, not an access restriction. Any visitor can still read the file URL from the page source, exactly as with an image displayed on the page. If the document must be protected, use a membership or protected-files plugin.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('Appearance', 'simplekitpdf'); ?></h3>
            <p>
                <?php esc_html_e('The block draws no frame around the document: there is no border, no rounded corners and no backdrop color, so the pages sit directly on the page background. Only the toolbar, when enabled, has a separator line.', 'simplekitpdf'); ?>
            </p>
            <p>
                <?php esc_html_e('The appearance can be changed from the theme stylesheet or from a custom CSS plugin:', 'simplekitpdf'); ?>
            </p>
            <pre><code>/* Dark backdrop behind the pages. */
.simplekitpdf-scroll { background: #525659; }

/* Page color used by pdf.js. */
.simplekitpdf-scroll .pdfViewer .page { --page-bg-color: #ffffff; }

/* Frame around the whole block. */
.simplekitpdf-embed { border: 1px solid #dcdcde; border-radius: 4px; }</code></pre>

            <h3><?php esc_html_e('Troubleshooting', 'simplekitpdf'); ?></h3>
            <p>
                <strong><?php esc_html_e('The block keeps showing "Loading PDF...".', 'simplekitpdf'); ?></strong><br />
                <?php esc_html_e('The viewer is loaded as a JavaScript module and uses a module worker. Some security plugins and strict Content-Security-Policy rules block module workers. Check the browser console for errors and allow the plugin files (assets/pdfjs/build/pdf.worker.mjs) to be loaded as a script.', 'simplekitpdf'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Some characters are missing or replaced in a PDF.', 'simplekitpdf'); ?></strong><br />
                <?php esc_html_e('The plugin ships a reduced pdf.js build without the optional CJK character maps (cmaps) and standard font fallbacks. PDFs that depend on those make the browser substitute glyphs. Embedding the fonts in the PDF avoids the problem.', 'simplekitpdf'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Very large files feel slow.', 'simplekitpdf'); ?></strong><br />
                <?php esc_html_e('pdf.js downloads the file in the browser. Prefer optimized PDFs (for example, with compressed images) and avoid files with hundreds of pages on landing pages.', 'simplekitpdf'); ?>
            </p>

            <h3><?php esc_html_e('Library credits', 'simplekitpdf'); ?></h3>
            <p>
                <?php
                printf(
                    /* translators: %s: pdf.js version. */
                    esc_html__('This plugin bundles pdf.js version %s, developed by Mozilla and contributors.', 'simplekitpdf'),
                    esc_html(SIMPLEKITPDF_PDFJS_VERSION)
                );
                ?>
                <br />
                <?php esc_html_e('Project:', 'simplekitpdf'); ?>
                <a href="https://mozilla.github.io/pdf.js/" target="_blank" rel="noopener noreferrer">https://mozilla.github.io/pdf.js/</a>
                <br />
                <?php esc_html_e('License:', 'simplekitpdf'); ?>
                <a href="https://www.apache.org/licenses/LICENSE-2.0" target="_blank" rel="noopener noreferrer">Apache License 2.0</a>
            </p>
        </div>
    </div>
    <?php
}
