<?php
/**
 * Plugin Name: Simple Kit PDF
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Embed PDF files in posts and pages using pdf.js, with an optional toolbar and download control.
 * Version:     1.4.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitpdf
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIMPLEKITPDF_VERSION', '1.4.0');
define('SIMPLEKITPDF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITPDF_PLUGIN_URL', plugin_dir_url(__FILE__));

/*
 * Bundled pdf.js (mozilla/pdf.js), Apache License 2.0.
 * See assets/pdfjs/VERSION.txt and assets/pdfjs/LICENSE.
 */
define('SIMPLEKITPDF_PDFJS_VERSION', '4.10.38');
define('SIMPLEKITPDF_PDFJS_URL', SIMPLEKITPDF_PLUGIN_URL . 'assets/pdfjs/');

// Block defaults.
define('SIMPLEKITPDF_DEFAULT_HEIGHT', 600);
define('SIMPLEKITPDF_MIN_HEIGHT', 100);
define('SIMPLEKITPDF_MAX_HEIGHT', 2000);

/*
 * This plugin keeps no custom database tables. Everything the block needs lives in the
 * block attributes, so there is no activation/deactivation data handling to perform.
 */
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/helpers.php';
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/frontend-assets.php';
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/block.php';
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/admin-menu.php';
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITPDF_PLUGIN_DIR . 'includes/donate.php';

// ---------------------------------------------------------------------------
// Translations
// ---------------------------------------------------------------------------
add_action('init', 'simplekitpdf_load_textdomain', 5);

function simplekitpdf_load_textdomain() {
    load_plugin_textdomain(
        'simplekitpdf',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
