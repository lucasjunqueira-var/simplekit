<?php
/**
 * Plugin Name: Simple Kit Accessibility
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Add reading tools to your pages and posts: text size, line spacing, letter spacing and high contrast.
 * Version:     1.4.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitaccessibility
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITACCESSIBILITY_VERSION', '1.4.0');
define('SIMPLEKITACCESSIBILITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITACCESSIBILITY_PLUGIN_URL', plugin_dir_url(__FILE__));

// The plugin has no database tables: everything lives in a single option and
// in one post meta key. Activation and deactivation do not change any data.

require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/frontend.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/api.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/post-meta.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/setup.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/donate.php';
require_once SIMPLEKITACCESSIBILITY_PLUGIN_DIR . 'includes/admin-menu.php';
