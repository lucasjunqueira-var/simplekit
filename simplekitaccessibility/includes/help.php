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
                <?php esc_html_e('Add reading tools to your site: text size, line spacing, letter spacing and high contrast. Visitors control everything from a small floating panel.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('Highlights', 'simplekitaccessibility'); ?></h3>
            <ul>
                <li><?php esc_html_e('Four independent adjustments, each with increase, decrease and reset actions.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('Floating button in eight positions, with configurable size, shape, colors, image and texts.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('A public JavaScript API so any element of the page can drive the adjustments.', 'simplekitaccessibility'); ?></li>
                <li><?php esc_html_e('The button can be hidden on individual posts, pages or any public custom post type.', 'simplekitaccessibility'); ?></li>
            </ul>

            <h3><?php esc_html_e('How the plugin works', 'simplekitaccessibility'); ?></h3>
            <p>
                <?php esc_html_e('Configure the floating button and the panel on the Setup page. The adjustments are applied by adding classes to the html element, so no page reload is needed.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('By default the settings of each visitor are not saved: every new page starts from the original state. Change "Remember choices" on the Setup page to keep the choices in the browser.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('The text size can be applied by CSS selectors (default) or by measuring each text element in the browser. The measured method preserves the sizes defined by themes that use pixels and by page builders, at the cost of a little more processing when the page changes.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('Positions anchored to a side use the edge distances. Positions centered on an axis use the center shift to move the button away from the center of that axis; negative values move it up or to the left.', 'simplekitaccessibility'); ?>
            </p>
            <p>
                <?php esc_html_e('To hide the button on a specific content, uncheck the option in the Simple Kit Accessibility box of the editor sidebar.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('Custom triggers', 'simplekitaccessibility'); ?></h3>
            <p>
                <?php esc_html_e('Add the data-skacc-action attribute to any element, or use the shortcode, to create your own controls:', 'simplekitaccessibility'); ?>
            </p>
            <p><code><button data-skacc-action="font-up">A+</button></code></p>
            <p><code>[simplekitaccessibility action="contrast" label="High contrast"]</code></p>
            <p>
                <?php esc_html_e('Available actions: font-up, font-down, font-reset, line-up, line-down, line-reset, letter-up, letter-down, letter-reset, contrast, reset-all and panel.', 'simplekitaccessibility'); ?>
            </p>

            <h3><?php esc_html_e('JavaScript API', 'simplekitaccessibility'); ?></h3>
            <p><code>window.simplekitaccessibility.increaseFont()</code></p>
            <p><code>window.simplekitaccessibility.setContrast(true)</code></p>
            <p><code>window.simplekitaccessibility.getState()</code></p>
            <p>
                <?php esc_html_e('The API is available on every page where the plugin is enabled, even when the floating button is hidden.', 'simplekitaccessibility'); ?>
            </p>
        </div>
    </div>
    <?php
}
