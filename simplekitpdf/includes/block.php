<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Gutenberg block: simplekitpdf/pdf
// ---------------------------------------------------------------------------
add_action('init', 'simplekitpdf_register_block');

function simplekitpdf_register_block() {
    wp_register_script(
        'simplekitpdf-block',
        SIMPLEKITPDF_PLUGIN_URL . 'assets/block.js',
        array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'),
        SIMPLEKITPDF_VERSION,
        true
    );

    wp_register_style(
        'simplekitpdf-editor',
        SIMPLEKITPDF_PLUGIN_URL . 'assets/editor.css',
        array(),
        SIMPLEKITPDF_VERSION
    );

    wp_localize_script('simplekitpdf-block', 'simplekitpdf_block_data', array(
        'siteHost'      => simplekitpdf_get_site_host(),
        'siteUrl'       => home_url('/'),
        'defaultHeight' => SIMPLEKITPDF_DEFAULT_HEIGHT,
        'minHeight'     => SIMPLEKITPDF_MIN_HEIGHT,
        'maxHeight'     => SIMPLEKITPDF_MAX_HEIGHT,
    ));

    register_block_type('simplekitpdf/pdf', array(
        'api_version'     => 3,
        'title'           => __('Simple Kit PDF', 'simplekitpdf'),
        'description'     => __('Embeds a PDF file rendered with pdf.js.', 'simplekitpdf'),
        'category'        => 'media',
        'icon'            => 'media-document',
        'editor_script'   => 'simplekitpdf-block',
        'editor_style'    => 'simplekitpdf-editor',
        'render_callback' => 'simplekitpdf_render_block',
        'supports'        => array(
            'html'  => false,
            'align' => false,
        ),
        'attributes'      => array(
            'source' => array(
                'type'    => 'string',
                'default' => 'media',
            ),
            'attachmentId' => array(
                'type'    => 'number',
                'default' => 0,
            ),
            'pdfUrl' => array(
                'type'    => 'string',
                'default' => '',
            ),
            'fileName' => array(
                'type'    => 'string',
                'default' => '',
            ),
            'height' => array(
                'type'    => 'number',
                'default' => SIMPLEKITPDF_DEFAULT_HEIGHT,
            ),
            'heightMode' => array(
                'type'    => 'string',
                'default' => 'fixed',
            ),
            'showToolbar' => array(
                'type'    => 'boolean',
                'default' => false,
            ),
            'allowDownload' => array(
                'type'    => 'boolean',
                'default' => false,
            ),
        ),
    ));
}

// ---------------------------------------------------------------------------
// Shortcode fallback: [simplekitpdf url="..." id="0" height="600" toolbar="0" download="0"]
// ---------------------------------------------------------------------------
add_shortcode('simplekitpdf', 'simplekitpdf_shortcode');

function simplekitpdf_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url'         => '',
        'id'          => 0,
        'height'      => SIMPLEKITPDF_DEFAULT_HEIGHT,
        'height_mode' => 'fixed',
        'toolbar'     => '0',
        'download'    => '0',
    ), $atts, 'simplekitpdf');

    $attachment_id = (int) $atts['id'];

    return simplekitpdf_render_block(array(
        'source'        => $attachment_id > 0 ? 'media' : 'url',
        'attachmentId'  => $attachment_id,
        'pdfUrl'        => (string) $atts['url'],
        'height'        => (int) $atts['height'],
        'heightMode'    => sanitize_key((string) $atts['height_mode']),
        'showToolbar'   => simplekitpdf_to_bool($atts['toolbar']) || simplekitpdf_to_bool($atts['download']),
        'allowDownload' => simplekitpdf_to_bool($atts['download']),
    ));
}

// ---------------------------------------------------------------------------
// Render callback
// ---------------------------------------------------------------------------
function simplekitpdf_render_block($attributes = array()) {
    $attributes = simplekitpdf_normalize_attributes($attributes);
    $source     = simplekitpdf_get_pdf_source($attributes);
    $url        = $source['url'];

    if ($url === '') {
        return simplekitpdf_render_block_notice(__('Select a PDF file in the block settings.', 'simplekitpdf'));
    }

    if (!simplekitpdf_is_allowed_pdf_url($url)) {
        return simplekitpdf_render_block_notice(__('This PDF URL is not allowed. The file must be hosted on this site.', 'simplekitpdf'));
    }

    // Guarantees the assets exist even when the page detection missed the block
    // (reusable blocks, for example). Late styles are printed in the footer.
    simplekitpdf_enqueue_assets();

    $height         = (int) $attributes['height'];
    $height_mode    = $attributes['height_mode'];
    $show_toolbar   = !empty($attributes['show_toolbar']);
    $allow_download = !empty($attributes['allow_download']) && $show_toolbar;
    $file_name      = $source['file_name'];

    // In the measured modes the inline height is only a reserved placeholder;
    // assets/viewer.js replaces it once the pages are laid out.
    $is_auto_height = $height_mode !== 'fixed';

    $download_name = $file_name !== '' ? sanitize_file_name($file_name) : 'document.pdf';
    if (strtolower(substr($download_name, -4)) !== '.pdf') {
        $download_name .= '.pdf';
    }

    ob_start();
    ?>
    <div class="simplekitpdf-embed<?php echo $is_auto_height ? ' simplekitpdf-auto-height' : ''; ?>"
        data-simplekitpdf="1"
        data-pdf-url="<?php echo esc_url($url); ?>"
        data-height="<?php echo esc_attr($height); ?>"
        data-height-mode="<?php echo esc_attr($height_mode); ?>"
        data-show-toolbar="<?php echo $show_toolbar ? '1' : '0'; ?>"
        data-allow-download="<?php echo $allow_download ? '1' : '0'; ?>"
        data-file-name="<?php echo esc_attr($file_name); ?>"
        style="height:<?php echo esc_attr($height); ?>px;">
        <?php if ($show_toolbar) : ?>
            <div class="simplekitpdf-toolbar" role="toolbar" aria-label="<?php esc_attr_e('PDF controls', 'simplekitpdf'); ?>">
                <button type="button" class="simplekitpdf-btn simplekitpdf-prev" aria-label="<?php esc_attr_e('Previous page', 'simplekitpdf'); ?>" title="<?php esc_attr_e('Previous page', 'simplekitpdf'); ?>">&#8249;</button>
                <span class="simplekitpdf-page">
                    <input type="number" class="simplekitpdf-page-input" min="1" step="1" value="1" aria-label="<?php esc_attr_e('Page number', 'simplekitpdf'); ?>" />
                    <span class="simplekitpdf-page-count" aria-live="polite">/ 0</span>
                </span>
                <button type="button" class="simplekitpdf-btn simplekitpdf-next" aria-label="<?php esc_attr_e('Next page', 'simplekitpdf'); ?>" title="<?php esc_attr_e('Next page', 'simplekitpdf'); ?>">&#8250;</button>

                <span class="simplekitpdf-spacer"></span>

                <button type="button" class="simplekitpdf-btn simplekitpdf-zoom-out" aria-label="<?php esc_attr_e('Zoom out', 'simplekitpdf'); ?>" title="<?php esc_attr_e('Zoom out', 'simplekitpdf'); ?>">&#8722;</button>
                <span class="simplekitpdf-zoom-value" aria-live="polite">100%</span>
                <button type="button" class="simplekitpdf-btn simplekitpdf-zoom-in" aria-label="<?php esc_attr_e('Zoom in', 'simplekitpdf'); ?>" title="<?php esc_attr_e('Zoom in', 'simplekitpdf'); ?>">+</button>
                <button type="button" class="simplekitpdf-btn simplekitpdf-fit-width" aria-label="<?php esc_attr_e('Fit width', 'simplekitpdf'); ?>" title="<?php esc_attr_e('Fit width', 'simplekitpdf'); ?>"><?php esc_html_e('Fit width', 'simplekitpdf'); ?></button>

                <?php if ($allow_download) : ?>
                    <a class="simplekitpdf-btn simplekitpdf-download"
                        href="<?php echo esc_url($url); ?>"
                        download="<?php echo esc_attr($download_name); ?>"
                        rel="noopener"
                        aria-label="<?php esc_attr_e('Download PDF', 'simplekitpdf'); ?>"
                        title="<?php esc_attr_e('Download PDF', 'simplekitpdf'); ?>"><?php esc_html_e('Download', 'simplekitpdf'); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="simplekitpdf-viewport">
            <div class="simplekitpdf-scroll" tabindex="0">
                <div class="simplekitpdf-viewer pdfViewer"></div>
            </div>
        </div>

        <div class="simplekitpdf-status" role="status" aria-live="polite"><?php esc_html_e('Loading PDF...', 'simplekitpdf'); ?></div>

        <noscript>
            <div class="simplekitpdf-noscript">
                <?php if ($allow_download) : ?>
                    <a href="<?php echo esc_url($url); ?>" download="<?php echo esc_attr($download_name); ?>" rel="noopener"><?php esc_html_e('Download PDF', 'simplekitpdf'); ?></a>
                <?php else : ?>
                    <?php esc_html_e('JavaScript is required to display this PDF.', 'simplekitpdf'); ?>
                <?php endif; ?>
            </div>
        </noscript>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders a configuration warning, visible only to users allowed to edit content.
 *
 * @param string $message Message.
 * @return string
 */
function simplekitpdf_render_block_notice($message) {
    if (!current_user_can('edit_posts')) {
        return '';
    }
    return '<div class="simplekitpdf-embed-notice">' . esc_html($message) . '</div>';
}
