<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Backup: export all plugin data to array
// ---------------------------------------------------------------------------
function simplekitforms_export_backup() {
    global $wpdb;

    $table_forms   = $wpdb->prefix . 'sf_forms';
    $table_entries = $wpdb->prefix . 'sf_entries';

    $data = [
        'version'     => SIMPLEKITFORMS_VERSION,
        'exported_at' => current_time('mysql'),
        'namespace'   => 'simplekitforms',
        'tables'      => [],
    ];

    $tables = [
        'sf_forms'   => $table_forms,
        'sf_entries' => $table_entries,
    ];

    foreach ($tables as $key => $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i", $table),
            ARRAY_A
        );
        $data['tables'][$key] = is_array($rows) ? $rows : [];
    }

    return $data;
}

// ---------------------------------------------------------------------------
// Backup: validate backup data structure
// ---------------------------------------------------------------------------
function simplekitforms_validate_backup($data) {
    if (!is_array($data)) {
        return false;
    }
    if (!isset($data['namespace']) || $data['namespace'] !== 'simplekitforms') {
        return false;
    }
    if (!isset($data['tables']) || !is_array($data['tables'])) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------
// Backup: import data from a validated backup array
// ---------------------------------------------------------------------------
function simplekitforms_import_backup($data) {
    global $wpdb;

    if (!simplekitforms_validate_backup($data)) {
        return false;
    }

    $tables = [
        'sf_forms'   => $wpdb->prefix . 'sf_forms',
        'sf_entries' => $wpdb->prefix . 'sf_entries',
    ];

    foreach ($tables as $key => $table) {
        if (!isset($data['tables'][$key]) || !is_array($data['tables'][$key])) {
            continue;
        }

        // Clear existing data
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table));

        // Insert backup rows
        foreach ($data['tables'][$key] as $row) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table, $row);
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// Backup: get uploads directory path
// ---------------------------------------------------------------------------
function simplekitforms_backup_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitforms-backup.json';
}

// ---------------------------------------------------------------------------
// Page: Backup
// ---------------------------------------------------------------------------
function simplekitforms_page_backup() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    $msg  = '';
    $type = 'success';
    $download_url = '';

    // Handle export action (POST form submission)
    if (isset($_POST['action']) && sanitize_text_field(wp_unslash($_POST['action'])) === 'export_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitforms_backup_export')
    ) {
        $data    = simplekitforms_export_backup();
        $json    = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filepath = simplekitforms_backup_file_path();

        $written = file_put_contents($filepath, $json);
        if ($written === false) {
            $msg  = 'Failed to write backup file to the uploads directory.';
            $type = 'error';
        } else {
            // Build download URL via admin-post.php
            $download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitforms_download_backup'),
                'simplekitforms_download_backup'
            );
            $msg = 'Backup file generated successfully.';
        }
    }

    // Handle import action (POST form submission)
    if (isset($_POST['action']) && sanitize_text_field(wp_unslash($_POST['action'])) === 'import_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitforms_backup_import')
    ) {
        if (!isset($_FILES['backup_file']) || !isset($_FILES['backup_file']['error']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $msg  = 'Error uploading the backup file.';
            $type = 'error';
        } else {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES['tmp_name'] is an internal PHP path, not user input.
            $tmp_path = $_FILES['backup_file']['tmp_name'] ?? '';
            $contents = file_get_contents($tmp_path);
            $data     = json_decode($contents, true);

            if (!simplekitforms_validate_backup($data)) {
                $msg  = 'Invalid backup file. The file structure does not match the expected format.';
                $type = 'error';
            } else {
                $imported = simplekitforms_import_backup($data);
                if ($imported) {
                    $msg  = 'Backup imported successfully. All previous data has been replaced.';
                    $type = 'success';
                } else {
                    $msg  = 'Failed to import the backup file.';
                    $type = 'error';
                }
            }
        }
    }

    ?>
    <div class="wrap">
        <h1>Backup</h1>

        <?php if ($msg) : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($msg); ?></p>
            </div>
        <?php endif; ?>

        <h2>Export Backup</h2>
        <p>Download a JSON backup file containing all forms and responses. The file will also be saved to the WordPress uploads directory.</p>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitforms_backup_export', '_wpnonce'); ?>
            <input type="hidden" name="action" value="export_backup" />
            <input type="submit" class="button button-primary" value="Export Backup" />
        </form>

        <?php if ($download_url) : ?>
        <p>
            <a href="<?php echo esc_url($download_url); ?>" class="button button-primary" id="sf-download-backup-link">
                Download backup file
            </a>
        </p>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            window.location.href = $('#sf-download-backup-link').attr('href');
        });
        </script>
        <?php endif; ?>

        <hr />

        <h2>Import Backup</h2>
        <p>Upload a previously exported JSON backup file to restore your data.</p>
        <p><strong>Warning:</strong> All current data will be replaced by the data from the backup file. This action cannot be undone.</p>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return confirm('Are you sure? All current data will be replaced by the backup data.');">
            <?php wp_nonce_field('simplekitforms_backup_import', '_wpnonce'); ?>
            <input type="hidden" name="action" value="import_backup" />
            <input type="file" name="backup_file" accept=".json" required />
            <br /><br />
            <input type="submit" class="button" value="Import Backup" />
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Download backup file via admin-post.php
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitforms_download_backup', 'simplekitforms_download_backup_handler');
function simplekitforms_download_backup_handler() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitforms_download_backup')) {
        wp_die('Access denied.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    $filepath = simplekitforms_backup_file_path();

    if (!file_exists($filepath)) {
        wp_die('Backup file not found. Please generate a new backup.');
    }

    $json = file_get_contents($filepath);

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="simplekitforms-backup.json"');
    header('Content-Length: ' . strlen($json));
    header('Pragma: no-cache');
    header('Expires: 0');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON output for file download, escaping would corrupt the data.
    echo $json;
    exit;
}
