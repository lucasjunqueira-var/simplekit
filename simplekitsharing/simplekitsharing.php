<?php
/**
 * Plugin Name: Simple Kit Sharing
 * Plugin URI:  https://github.com/example/simplekitsharing
 * Description: Manage social sharing meta tags (Open Graph, Twitter Cards) for pages and posts.
 * Version:     1.0.0
 * Requires at least: 6.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitsharing
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITSHARING_VERSION', '1.0.0');
define('SIMPLEKITSHARING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITSHARING_PLUGIN_URL', plugin_dir_url(__FILE__));

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'simplekitsharing_activate');
register_deactivation_hook(__FILE__, 'simplekitsharing_deactivate');

function simplekitsharing_activate() {
    // No database tables needed; settings stored in options and post meta.
}

function simplekitsharing_deactivate() {
    // No cleanup needed; data is preserved.
}

// ---------------------------------------------------------------------------
// Module includes
// ---------------------------------------------------------------------------
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/admin-menu.php';
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/meta-box.php';
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/frontend.php';
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITSHARING_PLUGIN_DIR . 'includes/donate.php';
