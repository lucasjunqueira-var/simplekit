<?php
/**
 * Plugin Name: Simple Kit Downloads
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Create download buttons for files and track download statistics.
 * Version:     1.0.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitdownloads
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITDOWNLOADS_VERSION', '1.0.0');
define('SIMPLEKITDOWNLOADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITDOWNLOADS_PLUGIN_URL', plugin_dir_url(__FILE__));

// ---------------------------------------------------------------------------
// Activation / deactivation
// ---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'simplekitdownloads_activate');

function simplekitdownloads_activate() {
    simplekitdownloads_create_tables();
    simplekitdownloads_ensure_uploads_root();
    update_option('simplekitdownloads_db_version', SIMPLEKITDOWNLOADS_VERSION);
}

// Deactivation intentionally keeps all data (downloads, files and statistics).

// ---------------------------------------------------------------------------
// Schema upgrade
// ---------------------------------------------------------------------------
add_action('plugins_loaded', 'simplekitdownloads_maybe_upgrade_schema');

function simplekitdownloads_maybe_upgrade_schema() {
    if (get_option('simplekitdownloads_db_version') === SIMPLEKITDOWNLOADS_VERSION) {
        return;
    }
    simplekitdownloads_create_tables();
    simplekitdownloads_ensure_uploads_root();
    update_option('simplekitdownloads_db_version', SIMPLEKITDOWNLOADS_VERSION);
}

// ---------------------------------------------------------------------------
// Table name helper
// ---------------------------------------------------------------------------
function simplekitdownloads_table($name) {
    global $wpdb;
    return $wpdb->prefix . 'skd_' . $name;
}

// ---------------------------------------------------------------------------
// Table creation
// ---------------------------------------------------------------------------
function simplekitdownloads_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $table_downloads = simplekitdownloads_table('downloads');
    $table_versions  = simplekitdownloads_table('versions');
    $table_log       = simplekitdownloads_table('log');

    $sql_downloads = "CREATE TABLE $table_downloads (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL DEFAULT '',
        slug VARCHAR(200) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY name (name),
        UNIQUE KEY slug (slug)
    ) $charset;";

    $sql_versions = "CREATE TABLE $table_versions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        download_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        version VARCHAR(50) NOT NULL DEFAULT '',
        version_sort VARCHAR(120) NOT NULL DEFAULT '',
        source VARCHAR(10) NOT NULL DEFAULT 'upload',
        file_path VARCHAR(500) NOT NULL DEFAULT '',
        file_url VARCHAR(1000) NOT NULL DEFAULT '',
        file_name VARCHAR(255) NOT NULL DEFAULT '',
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY download_version (download_id, version),
        KEY download_id (download_id),
        KEY version_sort (version_sort)
    ) $charset;";

    $sql_log = "CREATE TABLE $table_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        download_id BIGINT UNSIGNED NULL DEFAULT NULL,
        version_id BIGINT UNSIGNED NULL DEFAULT NULL,
        download_name VARCHAR(255) NOT NULL DEFAULT '',
        version_label VARCHAR(50) NOT NULL DEFAULT '',
        version_sort VARCHAR(120) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY download_id (download_id),
        KEY version_id (version_id),
        KEY download_name (download_name),
        KEY created_at (created_at)
    ) $charset;";

    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    dbDelta($sql_downloads);
    dbDelta($sql_versions);
    dbDelta($sql_log);
}

// ---------------------------------------------------------------------------
// Modules
// ---------------------------------------------------------------------------
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/storage.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/downloads.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/download-handler.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/statistics.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/backup.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/block.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/donate.php';
require_once SIMPLEKITDOWNLOADS_PLUGIN_DIR . 'includes/admin-menu.php';
