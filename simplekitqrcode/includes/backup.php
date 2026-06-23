<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Backup: export all QR code stats data to array
// ---------------------------------------------------------------------------
function simplekitqrcode_export_backup() {
    global $wpdb;

    $table_stats = $wpdb->prefix . 'qr_code_stats';

    $data = [
        'version'     => SIMPLEKITQRCODE_VERSION,
        'exported_at' => current_time('mysql'),
        'namespace'   => 'simplekitqrcode',
        'tables'      => [],
    ];

    // data is being exported from the plugin tables, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM %i", $table_stats),
        ARRAY_A
    );
    $data['tables']['qr_code_stats'] = is_array($rows) ? $rows : [];

    return $data;
}

// ---------------------------------------------------------------------------
// Backup: validate backup data structure
// ---------------------------------------------------------------------------
function simplekitqrcode_validate_backup($data) {
    if (!is_array($data)) {
        return false;
    }
    if (!isset($data['namespace']) || $data['namespace'] !== 'simplekitqrcode') {
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
function simplekitqrcode_import_backup($data) {
    global $wpdb;

    if (!simplekitqrcode_validate_backup($data)) {
        return false;
    }

    $table_stats = $wpdb->prefix . 'qr_code_stats';

    if (isset($data['tables']['qr_code_stats']) && is_array($data['tables']['qr_code_stats'])) {
        // Clear existing data
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table_stats));

        // Insert backup rows
        foreach ($data['tables']['qr_code_stats'] as $row) {
            // data must be inserted on custom plugin table
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table_stats, $row);
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// Backup: get uploads directory path
// ---------------------------------------------------------------------------
function simplekitqrcode_backup_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitqrcode-backup.json';
}

// ---------------------------------------------------------------------------
// Page: Backup
// ---------------------------------------------------------------------------
function simplekitqrcode_page_backup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitqrcode'));
    }

    $msg  = '';
    $type = 'success';
    $download_url = '';

    // Handle export action (POST form submission)
    if (isset($_POST['action']) && $_POST['action'] === 'export_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitqrcode_backup_export')
    ) {
        $data    = simplekitqrcode_export_backup();
        $json    = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filepath = simplekitqrcode_backup_file_path();

        $written = file_put_contents($filepath, $json);
        if ($written === false) {
            $msg  = __('Failed to write backup file to the uploads directory.', 'simplekitqrcode');
            $type = 'error';
        } else {
            // Build download URL via admin-post.php
            $download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitqrcode_download_backup'),
                'simplekitqrcode_download_backup'
            );
            $msg = __('Backup file generated successfully.', 'simplekitqrcode');
        }
    }

    // Handle import action (POST form submission)
    if (isset($_POST['action']) && $_POST['action'] === 'import_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitqrcode_backup_import')
    ) {
        if (!isset($_FILES['backup_file']['error']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $msg  = __('Error uploading the backup file.', 'simplekitqrcode');
            $type = 'error';
        } else {
            $tmp_path = isset($_FILES['backup_file']['tmp_name']) ? sanitize_text_field(wp_unslash($_FILES['backup_file']['tmp_name'])) : '';
            $contents = file_get_contents($tmp_path);
            $data     = json_decode($contents, true);

            if (!simplekitqrcode_validate_backup($data)) {
                $msg  = __('Invalid backup file. The file structure does not match the expected format.', 'simplekitqrcode');
                $type = 'error';
            } else {
                $imported = simplekitqrcode_import_backup($data);
                if ($imported) {
                    $msg  = __('Backup imported successfully. All previous data has been replaced.', 'simplekitqrcode');
                    $type = 'success';
                } else {
                    $msg  = __('Failed to import the backup file.', 'simplekitqrcode');
                    $type = 'error';
                }
            }
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Backup', 'simplekitqrcode'); ?></h1>

        <?php if ($msg) : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($msg); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Export Backup', 'simplekitqrcode'); ?></h2>
        <p><?php esc_html_e('Download a JSON backup file containing all QR codes and access statistics. The file will also be saved to the WordPress uploads directory.', 'simplekitqrcode'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitqrcode_backup_export', '_wpnonce'); ?>
            <input type="hidden" name="action" value="export_backup" />
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Export Backup', 'simplekitqrcode'); ?>" />
        </form>

        <?php if ($download_url) : ?>
        <p>
            <a href="<?php echo esc_url($download_url); ?>" class="button button-primary" id="skqrcode-download-backup-link">
                <?php esc_html_e('Download backup file', 'simplekitqrcode'); ?>
            </a>
        </p>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            window.location.href = $('#skqrcode-download-backup-link').attr('href');
        });
        </script>
        <?php endif; ?>

        <hr />

        <h2><?php esc_html_e('Import Backup', 'simplekitqrcode'); ?></h2>
        <p><?php esc_html_e('Upload a previously exported JSON backup file to restore your QR codes and statistics.', 'simplekitqrcode'); ?></p>
        <p><strong><?php esc_html_e('Warning:', 'simplekitqrcode'); ?></strong> <?php esc_html_e('All current data will be replaced by the data from the backup file. This action cannot be undone.', 'simplekitqrcode'); ?></p>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return confirm('<?php esc_attr_e('Are you sure? All current data will be replaced by the backup data.', 'simplekitqrcode'); ?>');">
            <?php wp_nonce_field('simplekitqrcode_backup_import', '_wpnonce'); ?>
            <input type="hidden" name="action" value="import_backup" />
            <input type="file" name="backup_file" accept=".json" required />
            <br /><br />
            <input type="submit" class="button" value="<?php esc_attr_e('Import Backup', 'simplekitqrcode'); ?>" />
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Download backup file via admin-post.php
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitqrcode_download_backup', 'simplekitqrcode_download_backup_handler');
function simplekitqrcode_download_backup_handler() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitqrcode_download_backup')) {
        wp_die(esc_html__('Access denied.', 'simplekitqrcode'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitqrcode'));
    }

    $filepath = simplekitqrcode_backup_file_path();

    if (!file_exists($filepath)) {
        wp_die(esc_html__('Backup file not found. Please generate a new backup.', 'simplekitqrcode'));
    }

    $json = file_get_contents($filepath);

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="simplekitqrcode-backup.json"');
    header('Content-Length: ' . strlen($json));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo wp_json_encode(json_decode($json, true));
    exit;
}
