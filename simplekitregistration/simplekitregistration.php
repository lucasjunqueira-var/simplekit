<?php
/**
 * Plugin Name: Simple Kit Registration
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Collect registration data (including file uploads) through forms and let selected evaluators review the submissions on restricted pages of your site.
 * Version:     1.4.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitregistration
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITREGISTRATION_VERSION', '1.4.0');
define('SIMPLEKITREGISTRATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITREGISTRATION_PLUGIN_URL', plugin_dir_url(__FILE__));

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'simplekitregistration_activate');
register_deactivation_hook(__FILE__, 'simplekitregistration_deactivate');

function simplekitregistration_activate() {
    simplekitregistration_create_tables();
    simplekitregistration_ensure_uploads_root();
    update_option('simplekitregistration_db_version', SIMPLEKITREGISTRATION_VERSION);

    // Restore a previously generated backup, if one exists in the uploads dir.
    if (function_exists('simplekitregistration_restore_backup_on_activation')) {
        simplekitregistration_restore_backup_on_activation();
    }
}

function simplekitregistration_deactivate() {
    // Write a ZIP backup (JSON + uploads folder) before dropping the tables.
    if (function_exists('simplekitregistration_create_backup_zip')) {
        simplekitregistration_create_backup_zip();
    }
    simplekitregistration_drop_tables();
}

// ---------------------------------------------------------------------------
// Schema upgrade check (dbDelta) when the plugin version changes
// ---------------------------------------------------------------------------
add_action('plugins_loaded', 'simplekitregistration_maybe_upgrade_schema');

function simplekitregistration_maybe_upgrade_schema() {
    if (get_option('simplekitregistration_db_version') === SIMPLEKITREGISTRATION_VERSION) {
        return;
    }
    simplekitregistration_create_tables();
    simplekitregistration_ensure_uploads_root();
    update_option('simplekitregistration_db_version', SIMPLEKITREGISTRATION_VERSION);
}

// ---------------------------------------------------------------------------
// Table name helpers
// ---------------------------------------------------------------------------
function simplekitregistration_forms_table() {
    global $wpdb;
    return $wpdb->prefix . 'skr_forms';
}

function simplekitregistration_entries_table() {
    global $wpdb;
    return $wpdb->prefix . 'skr_entries';
}

function simplekitregistration_evaluators_table() {
    global $wpdb;
    return $wpdb->prefix . 'skr_evaluators';
}

function simplekitregistration_messages_table() {
    global $wpdb;
    return $wpdb->prefix . 'skr_messages';
}

// ---------------------------------------------------------------------------
// Table creation
// ---------------------------------------------------------------------------
function simplekitregistration_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $table_forms = simplekitregistration_forms_table();
    $sql_forms = "CREATE TABLE $table_forms (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        code VARCHAR(64) NOT NULL,
        thank_you_message TEXT NOT NULL,
        submit_text VARCHAR(255) NOT NULL DEFAULT 'Submit',
        fields_json LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY code (code)
    ) $charset;";

    $table_entries = simplekitregistration_entries_table();
    $sql_entries = "CREATE TABLE $table_entries (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        form_id BIGINT UNSIGNED NOT NULL,
        data_json LONGTEXT NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY form_id (form_id)
    ) $charset;";

    $table_evaluators = simplekitregistration_evaluators_table();
    $sql_evaluators = "CREATE TABLE $table_evaluators (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        forms_json LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email)
    ) $charset;";

    $table_messages = simplekitregistration_messages_table();
    $sql_messages = "CREATE TABLE $table_messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        entry_id BIGINT UNSIGNED NOT NULL,
        form_id BIGINT UNSIGNED NOT NULL,
        author_type VARCHAR(20) NOT NULL DEFAULT 'admin',
        author_name VARCHAR(191) NOT NULL DEFAULT '',
        author_email VARCHAR(190) NOT NULL DEFAULT '',
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY entry_id (entry_id),
        KEY form_id (form_id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_forms);
    dbDelta($sql_entries);
    dbDelta($sql_evaluators);
    dbDelta($sql_messages);
}

function simplekitregistration_drop_tables() {
    global $wpdb;

    $tables = [
        simplekitregistration_messages_table(),
        simplekitregistration_evaluators_table(),
        simplekitregistration_entries_table(),
        simplekitregistration_forms_table(),
    ];

    foreach ($tables as $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $table));
    }
}

// ---------------------------------------------------------------------------
// Modules
// ---------------------------------------------------------------------------
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/helpers.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/uploads.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/auth.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/forms.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/evaluators.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/entries.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/messages.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/submit.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/block.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/frontend.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/admin-menu.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/backup.php';
require_once SIMPLEKITREGISTRATION_PLUGIN_DIR . 'includes/help.php';
