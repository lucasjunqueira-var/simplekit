<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitaccessibility_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitaccessibility'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitaccessibility'); ?></h1>

        <div class="skacc-card">
            <h2><?php esc_html_e('Simple Kit Accessibility', 'simplekitaccessibility'); ?></h2>
            <p>
                <?php esc_html_e('Include accessibility tools for the text on your website.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitaccessibility'); ?></h3>
            <ul>
                <li><?php esc_html_e('Four adjustments with increase, decrease and reset: text size, line spacing and letter spacing, plus a high contrast toggle.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('Optional floating button in eight positions, with configurable size, shape, colors, optional image and optional text.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('Works with themes that declare sizes in pixels, not only with themes based on rem or em.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('Implements JavaScript methods so that your theme can use the accessibility features, even if the floating button is not enabled.', 'simplekitaccessibility'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitaccessibility'); ?></h3>
            <p>
                <?php esc_html_e('After activating the plugin, open the settings page in the SK Accessibility menu and choose whether and where the button should appear.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('Adjust the button: optional text, accessible name, optional image, size, shape, background color, text color and hover color. When neither an image nor a text is provided, the plugin uses the universal access icon.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('Configure the panel: background, text and border colors, font size, width and corner radius. You can also replace the texts shown in the panel, from its title to the labels of each action.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('Even if you use the floating button by default on your site, it is possible to prevent it from being displayed on an individual page or post basis.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('Custom triggers', 'simplekitaccessibility'); ?></h3>
            <p>
                <?php esc_html_e('Any element of the page can drive the adjustments through the data-skacc-action attribute:', 'simplekitaccessibility'); ?>
            </p>
            <p><code><button data-skacc-action="font-up">A+</button></code></p>
            <p><code><button data-skacc-action="contrast">High contrast</button></code></p>
            <p>
                <?php esc_html_e('The same result is available as a shortcode:', 'simplekitaccessibility'); ?>
            </p>
            <p><code>[simplekitaccessibility action="font-up" label="A+"]</code></p>
            <p>
                <?php esc_html_e('Available actions: font-up, font-down, font-reset, line-up, line-down, line-reset, letter-up, letter-down, letter-reset, contrast, reset-all and panel.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('Frequently asked questions', 'simplekitaccessibility'); ?></h3>
            <p>
                <strong><?php esc_html_e('Does the plugin change the theme files?', 'simplekitaccessibility'); ?></strong><br />
                <?php esc_html_e('No. The adjustments are applied by classes on the html element and, in the measured method, by inline font sizes that are restored when the visitor returns to the original state.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Are the visitor choices sent to the server?', 'simplekitaccessibility'); ?></strong><br />
                <?php esc_html_e('No. When the "Remember choices" option is enabled, the state is stored only in the browser, using local or session storage.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Why does the text size not change on some themes?', 'simplekitaccessibility'); ?></strong><br />
                <?php esc_html_e('The default method multiplies the size inherited from the parent element. Themes that declare sizes in pixels keep working, but the original size of each element is not preserved. On those sites, switch the text size method to "Measured per element". A few very specific cases may still need rules in the Custom CSS field.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Can I use the plugin with a page builder?', 'simplekitaccessibility'); ?></strong><br />
                <?php esc_html_e('Yes. The measured method was designed for pages with inline sizes, which is the usual output of builders such as Elementor and Divi. The text containers used by the adjustments can also be extended with the simplekitaccessibility_text_selectors filter.', 'simplekitaccessibility'); ?>
            </p>
        </div>
    </div>
    <?php
}
