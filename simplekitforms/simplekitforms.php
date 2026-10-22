<?php
/**
 * Plugin Name: Simple Kit Forms
 * Plugin URI:  https://github.com/lucasjunqueira-var/simplekit
 * Description: Create forms and manage responses directly from your dashboard.
 * Version:     1.4.0
 * Requires at least: 6.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitforms
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('SIMPLEKITFORMS_VERSION', '1.4.0');
define('SIMPLEKITFORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITFORMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// ---------------------------------------------------------------------------
// Ativação / Desativação
// ---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'simplekitforms_activate');
register_deactivation_hook(__FILE__, 'simplekitforms_deactivate');

function simplekitforms_activate() {
    simplekitforms_create_tables();
    simplekitforms_ensure_form_columns();
    simplekitforms_ensure_uploads_root();
    update_option('simplekitforms_db_version', SIMPLEKITFORMS_VERSION);
}

// ---------------------------------------------------------------------------
// Atualização do esquema do banco (dbDelta) quando a versão muda
// ---------------------------------------------------------------------------
add_action('plugins_loaded', 'simplekitforms_maybe_upgrade_schema');

function simplekitforms_maybe_upgrade_schema() {
    if (get_option('simplekitforms_db_version') === SIMPLEKITFORMS_VERSION) {
        return;
    }
    simplekitforms_create_tables();
    simplekitforms_ensure_form_columns();
    simplekitforms_ensure_uploads_root();
    update_option('simplekitforms_db_version', SIMPLEKITFORMS_VERSION);
}

// ---------------------------------------------------------------------------
// Definição das colunas adicionais da tabela de formulários.
//
// Mantidas aqui para que a criação da tabela e a migração (ensure) fiquem em
// sincronia. A chave é o nome da coluna e o valor a definição SQL.
// ---------------------------------------------------------------------------
function simplekitforms_form_extra_columns() {
    return [
        'notify_email'    => "VARCHAR(255) NOT NULL DEFAULT ''",
        'smtp_host'       => "VARCHAR(255) NOT NULL DEFAULT ''",
        'smtp_port'       => "VARCHAR(10) NOT NULL DEFAULT '587'",
        'smtp_username'   => "VARCHAR(255) NOT NULL DEFAULT ''",
        'smtp_password'   => "VARCHAR(255) NOT NULL DEFAULT ''",
        'smtp_encryption' => "VARCHAR(10) NOT NULL DEFAULT 'tls'",
    ];
}

// ---------------------------------------------------------------------------
// Garante que todas as colunas de configuração existam na tabela de formulários.
//
// dbDelta pode falhar silenciosamente em alguns ambientes (por exemplo com o
// adaptador de banco SQLite), deixando colunas ausentes. Como os formulários
// gravam nessas colunas, garantimos a sua existência com um ALTER TABLE
// explícito, executado de forma idempotente.
// ---------------------------------------------------------------------------
function simplekitforms_ensure_form_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'sf_forms';

    // Detecção de colunas no mesmo estilo usado pelo dbDelta do core.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $columns = $wpdb->get_results($wpdb->prepare("DESCRIBE %i", $table), ARRAY_A);
    $detected = is_array($columns) && !empty($columns);
    if (!$detected) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $columns = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM %i", $table), ARRAY_A);
        $detected = is_array($columns) && !empty($columns);
    }

    $existing = [];
    if ($detected) {
        foreach ($columns as $column) {
            if (isset($column['Field'])) {
                $existing[] = strtolower($column['Field']);
            }
        }
    }

    foreach (simplekitforms_form_extra_columns() as $name => $definition) {
        if ($detected && in_array(strtolower($name), $existing, true)) {
            continue;
        }
        // O placeholder %i é usado para o nome da tabela e da coluna; a definição
        // da coluna ($definition) é um valor fixo interno e não pode ser parametrizada.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE %i ADD COLUMN %i $definition",
                $table,
                $name
            )
        );
    }

    return true;
}

function simplekitforms_deactivate() {
    // Export all data to JSON backup before dropping tables
    $data    = simplekitforms_export_backup();
    $json    = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filepath = simplekitforms_backup_file_path();
    file_put_contents($filepath, $json);

    // Drop all plugin tables
    simplekitforms_drop_tables();
}

// ---------------------------------------------------------------------------
// Criação das tabelas no banco de dados
// ---------------------------------------------------------------------------
function simplekitforms_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $table_forms = $wpdb->prefix . 'sf_forms';
    $sql1 = "CREATE TABLE IF NOT EXISTS $table_forms (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        thank_you_message TEXT NOT NULL,
        submit_text VARCHAR(255) NOT NULL DEFAULT 'Enviar',
        notify_email VARCHAR(255) NOT NULL DEFAULT '',
        smtp_host VARCHAR(255) NOT NULL DEFAULT '',
        smtp_port VARCHAR(10) NOT NULL DEFAULT '587',
        smtp_username VARCHAR(255) NOT NULL DEFAULT '',
        smtp_password VARCHAR(255) NOT NULL DEFAULT '',
        smtp_encryption VARCHAR(10) NOT NULL DEFAULT 'tls',
        fields_json LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    $table_entries = $wpdb->prefix . 'sf_entries';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table_entries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        form_id BIGINT UNSIGNED NOT NULL,
        data_json LONGTEXT NOT NULL,
        ip VARCHAR(45) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (form_id) REFERENCES $table_forms(id) ON DELETE CASCADE
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql1);
    dbDelta($sql2);
}

// ---------------------------------------------------------------------------
// Drop all plugin tables
// ---------------------------------------------------------------------------
function simplekitforms_drop_tables() {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'sf_entries',
        $wpdb->prefix . 'sf_forms',
    ];

    foreach ($tables as $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table));
    }
}

// ---------------------------------------------------------------------------
// Inclusão dos módulos
// ---------------------------------------------------------------------------
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/settings.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/uploads.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/admin-menu.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/forms.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/entries.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/block.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/backup.php';
require_once SIMPLEKITFORMS_PLUGIN_DIR . 'includes/protection.php';
