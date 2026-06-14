<?php
/**
 * Plugin Name: Simple Kit Mailing
 * Plugin URI:  https://github.com/example/simplekitmailing
 * Description: The "Simple Kit Mailing" plugin is part of the "Simple Kit" suite and offers a simplified way to manage both contact registration in mailing lists and sending messages to limited numbers of recipients, directly from your dashboard.
 * Version:     1.0.0
 * Requires at least: 6.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author:      Simple Kit Mailing
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitmailing
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITMAILING_VERSION', '2.0.0');
define('SIMPLEKITMAILING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITMAILING_PLUGIN_URL', plugin_dir_url(__FILE__));

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'simplekitmailing_activate');
register_deactivation_hook(__FILE__, 'simplekitmailing_deactivate');

function simplekitmailing_activate() {
    simplekitmailing_create_tables();
    simplekitmailing_create_default_lists();
    simplekitmailing_schedule_cron();
    flush_rewrite_rules();

}

function simplekitmailing_deactivate() {
    wp_clear_scheduled_hook('simplekitmailing_cron_send');
    wp_clear_scheduled_hook('simplekitmailing_cleanup_pending_cron');

    // Export all data to JSON backup before dropping tables
    $data    = simplekitmailing_export_backup();
    $json    = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filepath = simplekitmailing_backup_file_path();
    file_put_contents($filepath, $json);

    // Drop all plugin tables
    simplekitmailing_drop_tables();
}

// ---------------------------------------------------------------------------
// Drop all plugin tables
// ---------------------------------------------------------------------------
function simplekitmailing_drop_tables() {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'sm_messages',
        $wpdb->prefix . 'sm_removed',
        $wpdb->prefix . 'sm_subscribers',
        $wpdb->prefix . 'sm_pending',
        $wpdb->prefix . 'sm_lists',
    ];

    foreach ($tables as $table) {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // also, the tables must be removed due to the plugin deactivation
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table));
    }
}

// ---------------------------------------------------------------------------
// Table creation using dbDelta
// ---------------------------------------------------------------------------
function simplekitmailing_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $table_lists = $wpdb->prefix . 'sm_lists';
    $sql0 = "CREATE TABLE IF NOT EXISTS $table_lists (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT '',
        settings LONGTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $sql1 = "CREATE TABLE IF NOT EXISTS $table_subscribers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        list_id BIGINT UNSIGNED DEFAULT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT '',
        phone VARCHAR(50) DEFAULT '',
        ip VARCHAR(45) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_list_email (list_id, email)
    ) $charset;";

    $table_removed = $wpdb->prefix . 'sm_removed';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table_removed (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        list_id BIGINT UNSIGNED DEFAULT NULL,
        email VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_list_removed_email (list_id, email)
    ) $charset;";

    $table_messages = $wpdb->prefix . 'sm_messages';
    $sql3 = "CREATE TABLE IF NOT EXISTS $table_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        list_id BIGINT UNSIGNED DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending',
        total INT UNSIGNED DEFAULT 0,
        sent INT UNSIGNED DEFAULT 0,
        last_sent_at DATETIME NULL
    ) $charset;";

    $table_pending = $wpdb->prefix . 'sm_pending';
    $sql4 = "CREATE TABLE IF NOT EXISTS $table_pending (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        list_id BIGINT UNSIGNED DEFAULT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT '',
        phone VARCHAR(50) DEFAULT '',
        ip VARCHAR(45) DEFAULT '',
        activation_code VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_list_email (list_id, email),
        KEY idx_activation_code (activation_code)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql0);
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);
}

// ---------------------------------------------------------------------------
// Create 3 default lists on activation
// ---------------------------------------------------------------------------
function simplekitmailing_create_default_lists() {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';

    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $count = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM %i", $table_lists)
    );
    if ($count > 0) {
        return;
    }

    $defaults = [
        ['name' => __('List 1', 'simplekitmailing'), 'description' => __('First mailing list', 'simplekitmailing')],
        ['name' => __('List 2', 'simplekitmailing'), 'description' => __('Second mailing list', 'simplekitmailing')],
        ['name' => __('List 3', 'simplekitmailing'), 'description' => __('Third mailing list', 'simplekitmailing')],
    ];

    foreach ($defaults as $list) {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert($table_lists, $list);
    }
}

// ---------------------------------------------------------------------------
// Helper: get all lists
// ---------------------------------------------------------------------------
function simplekitmailing_get_lists() {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM %i ORDER BY id ASC", $table_lists)
    );
}

// ---------------------------------------------------------------------------
// Helper: get list name by ID
// ---------------------------------------------------------------------------
function simplekitmailing_get_list_name($list_id) {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_var(
        $wpdb->prepare("SELECT name FROM %i WHERE id = %d", $table_lists, $list_id)
    );
}

// ---------------------------------------------------------------------------
// Helper: get subscriber count for a list
// ---------------------------------------------------------------------------
function simplekitmailing_get_list_subscriber_count($list_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'sm_subscribers';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM %i WHERE list_id = %d", $table, $list_id)
    );
}

// ---------------------------------------------------------------------------
// Helper: get/save list settings (JSON)
// ---------------------------------------------------------------------------
function simplekitmailing_get_list_settings($list_id) {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $raw = $wpdb->get_var(
        $wpdb->prepare("SELECT settings FROM %i WHERE id = %d", $table_lists, $list_id)
    );
    if ($raw) {
        $settings = json_decode($raw, true);
        if (is_array($settings)) {
            return $settings;
        }
    }
    return [];
}

function simplekitmailing_save_list_settings($list_id, $settings) {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->update($table_lists, ['settings' => wp_json_encode($settings)], ['id' => $list_id]);
}

// ---------------------------------------------------------------------------
// Helper: get a specific list setting with global fallback
// ---------------------------------------------------------------------------
function simplekitmailing_get_list_setting($list_id, $key, $global_option, $default = '') {
    if ($list_id) {
        $settings = simplekitmailing_get_list_settings($list_id);
        if (isset($settings[$key]) && $settings[$key] !== '' && $settings[$key] !== null) {
            return $settings[$key];
        }
    }
    // Fallback to global option
    $global_value = get_option($global_option, null);
    if ($global_value !== null) {
        return $global_value;
    }
    return $default;
}

// ---------------------------------------------------------------------------
// Cron (WP-Cron)
// ---------------------------------------------------------------------------
function simplekitmailing_schedule_cron() {
    // Clear old send cron
    $timestamp = wp_next_scheduled('simplekitmailing_cron_send');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'simplekitmailing_cron_send');
    }

    // Schedule pending cleanup if not already scheduled
    if (!wp_next_scheduled('simplekitmailing_cleanup_pending_cron')) {
        wp_schedule_event(time(), 'daily', 'simplekitmailing_cleanup_pending_cron');
    }
}

// ---------------------------------------------------------------------------
// Load translations (remove?)
// ---------------------------------------------------------------------------
//add_action('plugins_loaded', function () {
//    load_plugin_textdomain('simplekitmailing', false, dirname(plugin_basename(__FILE__)) . '/languages');
//});


// ---------------------------------------------------------------------------
// Include modules
// ---------------------------------------------------------------------------
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/backup.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/admin-menu.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/subscribers.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/messages.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/send-handler.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/block.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/unsubscribe.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/double-optin.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/protection.php';
require_once SIMPLEKITMAILING_PLUGIN_DIR . 'includes/help.php';
