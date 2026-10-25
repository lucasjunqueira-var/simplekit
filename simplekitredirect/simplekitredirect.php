<?php
/**
 * Plugin Name: Simple Kit Redirect
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Redirect posts and pages to a new address.
 * Version:     1.4.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitredirect
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITREDIRECT_VERSION', '1.4.0');
define('SIMPLEKITREDIRECT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITREDIRECT_PLUGIN_URL', plugin_dir_url(__FILE__));

// The plugin has no database tables and no options: every redirect is stored
// as post meta on the post or page that owns it. Activation and deactivation
// never change data.

register_activation_hook(__FILE__, 'simplekitredirect_activate');
register_deactivation_hook(__FILE__, 'simplekitredirect_deactivate');

/**
 * Nothing to create: redirects live in post meta.
 *
 * @return void
 */
function simplekitredirect_activate() {
}

/**
 * Nothing to remove: data is preserved until the plugin is uninstalled.
 *
 * @return void
 */
function simplekitredirect_deactivate() {
}

require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/helpers.php';
require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/redirect.php';
require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/post-meta.php';
require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/donate.php';
require_once SIMPLEKITREDIRECT_PLUGIN_DIR . 'includes/admin-menu.php';
