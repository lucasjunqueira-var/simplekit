<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Backup: settings + downloads + versions + statistics log.
//
// Uploaded binaries are not included in the JSON backup; URL based versions
// keep working because only the URL is stored.
// ---------------------------------------------------------------------------

function simplekitdownloads_backup_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitdownloads-backup.json';
}

function simplekitdownloads_get_settings_export() {
    return [
        'button_text'         => simplekitdownloads_get_button_text(),
        'button_text_version' => simplekitdownloads_get_button_text_version(),
        'button_css'          => simplekitdownloads_get_button_css(),
        'record_ip'           => simplekitdownloads_record_ip() ? '1' : '0',
    ];
}

/**
 * Write the backup JSON directly to a file, streaming table rows in chunks so
 * a large statistics log does not exhaust memory.
 *
 * @return string|WP_Error Backup file path.
 */
function simplekitdownloads_write_backup_file() {
    global $wpdb;

    $path = simplekitdownloads_backup_file_path();

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $handle = fopen($path, 'w');
    if (!$handle) {
        return new WP_Error('skd_backup_write', __('Could not create the backup file in the uploads folder.', 'simplekitdownloads'));
    }

    $tables = [
        'downloads' => simplekitdownloads_table('downloads'),
        'versions'  => simplekitdownloads_table('versions'),
        'log'       => simplekitdownloads_table('log'),
    ];

    fwrite($handle, '{"version":' . wp_json_encode(SIMPLEKITDOWNLOADS_VERSION));
    fwrite($handle, ',"exported_at":' . wp_json_encode(current_time('mysql')));
    fwrite($handle, ',"namespace":"simplekitdownloads"');
    fwrite($handle, ',"settings":' . wp_json_encode(simplekitdownloads_get_settings_export(), JSON_UNESCAPED_UNICODE));

    $first_table = true;
    fwrite($handle, ',"tables":{');

    foreach ($tables as $key => $table) {
        if (!$first_table) {
            fwrite($handle, ',');
        }
        $first_table = false;

        fwrite($handle, wp_json_encode($key) . ':[');

        $limit      = 500;
        $offset     = 0;
        $first_row  = true;

        do {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM %i ORDER BY id ASC LIMIT %d OFFSET %d",
                $table,
                $limit,
                $offset
            ), ARRAY_A);

            foreach ((array) $rows as $row) {
                if (!$first_row) {
                    fwrite($handle, ',');
                }
                fwrite($handle, wp_json_encode($row, JSON_UNESCAPED_UNICODE));
                $first_row = false;
            }

            $count   = is_array($rows) ? count($rows) : 0;
            $offset += $limit;
        } while ($count === $limit);

        fwrite($handle, ']');
    }

    fwrite($handle, '}}');
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($handle);

    return $path;
}

/**
 * Validate the backup envelope.
 */
function simplekitdownloads_validate_backup($data) {
    if (!is_array($data)) {
        return false;
    }
    if (!isset($data['namespace']) || $data['namespace'] !== 'simplekitdownloads') {
        return false;
    }
    if (!isset($data['tables']) || !is_array($data['tables'])) {
        return false;
    }
    return true;
}

/**
 * Whitelist and sanitize one backup row before inserting it.
 */
function simplekitdownloads_sanitize_backup_row($table_key, $row) {
    if (!is_array($row)) {
        return null;
    }

    if ($table_key === 'downloads') {
        $id   = isset($row['id']) ? absint($row['id']) : 0;
        $name = isset($row['name']) ? sanitize_text_field((string) $row['name']) : '';
        $slug = isset($row['slug']) ? sanitize_title((string) $row['slug']) : '';

        if ($slug === '') {
            $slug = sanitize_title($name);
        }
        if ($slug === '') {
            $slug = 'download-' . $id;
        }

        return [
            'id'         => $id,
            'name'       => $name,
            'slug'       => $slug,
            'created_at' => isset($row['created_at']) ? sanitize_text_field((string) $row['created_at']) : current_time('mysql'),
            'updated_at' => isset($row['updated_at']) ? sanitize_text_field((string) $row['updated_at']) : current_time('mysql'),
        ];
    }

    if ($table_key === 'versions') {
        $label = isset($row['version']) ? sanitize_text_field((string) $row['version']) : '';
        $url   = isset($row['file_url']) ? esc_url_raw((string) $row['file_url']) : '';

        return [
            'id'           => isset($row['id']) ? absint($row['id']) : 0,
            'download_id'  => isset($row['download_id']) ? absint($row['download_id']) : 0,
            'version'      => $label,
            'version_sort' => simplekitdownloads_version_sort_key($label),
            'source'       => (isset($row['source']) && $row['source'] === 'url') ? 'url' : 'upload',
            'file_path'    => isset($row['file_path']) ? sanitize_text_field((string) $row['file_path']) : '',
            'file_url'     => $url,
            'file_name'    => isset($row['file_name']) ? sanitize_file_name((string) $row['file_name']) : '',
            'file_size'    => isset($row['file_size']) ? absint($row['file_size']) : 0,
            'created_at'   => isset($row['created_at']) ? sanitize_text_field((string) $row['created_at']) : current_time('mysql'),
            'updated_at'   => isset($row['updated_at']) ? sanitize_text_field((string) $row['updated_at']) : current_time('mysql'),
        ];
    }

    if ($table_key === 'log') {
        $label = isset($row['version_label']) ? sanitize_text_field((string) $row['version_label']) : '';

        return [
            'id'            => isset($row['id']) ? absint($row['id']) : 0,
            'download_id'   => isset($row['download_id']) && $row['download_id'] !== null ? absint($row['download_id']) : null,
            'version_id'    => isset($row['version_id']) && $row['version_id'] !== null ? absint($row['version_id']) : null,
            'download_name' => isset($row['download_name']) ? sanitize_text_field((string) $row['download_name']) : '',
            'version_label' => $label,
            'version_sort'  => simplekitdownloads_version_sort_key($label),
            'ip'            => isset($row['ip']) ? sanitize_text_field((string) $row['ip']) : '',
            'created_at'    => isset($row['created_at']) ? sanitize_text_field((string) $row['created_at']) : current_time('mysql'),
        ];
    }

    return null;
}

/**
 * Import a backup, replacing all plugin data.
 *
 * @return true|WP_Error
 */
function simplekitdownloads_import_backup($data) {
    global $wpdb;

    if (!simplekitdownloads_validate_backup($data)) {
        return new WP_Error('skd_invalid_backup', __('This file is not a valid Simple Kit Downloads backup.', 'simplekitdownloads'));
    }

    $tables = [
        'downloads' => simplekitdownloads_table('downloads'),
        'versions'  => simplekitdownloads_table('versions'),
        'log'       => simplekitdownloads_table('log'),
    ];

    // Remove the binaries of the downloads that are about to be replaced.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $existing_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM %i", $tables['downloads']));
    foreach ((array) $existing_ids as $existing_id) {
        simplekitdownloads_delete_download_files((int) $existing_id);
    }

    foreach ($tables as $key => $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table));

        $rows = isset($data['tables'][$key]) && is_array($data['tables'][$key]) ? $data['tables'][$key] : [];

        foreach ($rows as $row) {
            $prepared = simplekitdownloads_sanitize_backup_row($key, $row);
            if ($prepared === null) {
                continue;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table, $prepared);
        }
    }

    if (isset($data['settings']) && is_array($data['settings'])) {
        $settings = $data['settings'];

        if (isset($settings['button_text'])) {
            update_option('simplekitdownloads_button_text', sanitize_text_field((string) $settings['button_text']));
        }
        if (isset($settings['button_text_version'])) {
            update_option('simplekitdownloads_button_text_version', sanitize_text_field((string) $settings['button_text_version']));
        }
        if (isset($settings['button_css'])) {
            update_option('simplekitdownloads_button_css', simplekitdownloads_sanitize_css((string) $settings['button_css']));
        }
        if (isset($settings['record_ip'])) {
            update_option('simplekitdownloads_record_ip', ((string) $settings['record_ip'] === '1') ? '1' : '0');
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// Download of the generated backup file
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_download_backup', 'simplekitdownloads_handle_download_backup');

function simplekitdownloads_handle_download_backup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_download_backup');

    $path = simplekitdownloads_backup_file_path();
    if (!is_file($path)) {
        wp_die(esc_html__('The backup file was not found.', 'simplekitdownloads'), '', ['response' => 404]);
    }

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="simplekitdownloads-backup-' . gmdate('Ymd-His') . '.json"');
    header('Content-Length: ' . filesize($path));

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
    readfile($path);
    exit;
}

// ---------------------------------------------------------------------------
// Page: Backup
// ---------------------------------------------------------------------------
function simplekitdownloads_page_backup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }

    global $wpdb;

    $message = '';
    $type    = 'success';
    $download_url = '';

    if (isset($_POST['skd_backup_action'])
        && sanitize_text_field(wp_unslash($_POST['skd_backup_action'])) === 'export'
    ) {
        check_admin_referer('simplekitdownloads_backup_export');

        $result = simplekitdownloads_write_backup_file();
        if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $type    = 'error';
        } else {
            $message      = __('Backup file generated successfully.', 'simplekitdownloads');
            $download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitdownloads_download_backup'),
                'simplekitdownloads_download_backup'
            );
        }
    }

    if (isset($_POST['skd_backup_action'])
        && sanitize_text_field(wp_unslash($_POST['skd_backup_action'])) === 'import'
    ) {
        check_admin_referer('simplekitdownloads_backup_import');

        if (!isset($_FILES['backup_file']['error']) || (int) $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $message = __('Error uploading the backup file.', 'simplekitdownloads');
            $type    = 'error';
        } else {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- internal PHP temporary path.
            $tmp_path = $_FILES['backup_file']['tmp_name'];
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $contents = file_get_contents($tmp_path);
            $data     = json_decode((string) $contents, true);

            $result = simplekitdownloads_import_backup($data);
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $type    = 'error';
            } else {
                $message = __('Backup imported successfully.', 'simplekitdownloads');
            }
        }
    }

    $backup_exists = is_file(simplekitdownloads_backup_file_path());
    $downloads     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", simplekitdownloads_table('downloads')));
    $versions      = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", simplekitdownloads_table('versions')));
    $log_rows      = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", simplekitdownloads_table('log')));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Backup', 'simplekitdownloads'); ?></h1>

        <?php if ($message !== '') : ?>
            <div class="notice <?php echo $type === 'error' ? 'notice-error' : 'notice-success'; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <p>
            <?php echo esc_html(sprintf(
                /* translators: 1: downloads, 2: versions, 3: log rows. */
                __('Current data: %1$d downloads, %2$d versions and %3$d statistics records.', 'simplekitdownloads'),
                $downloads,
                $versions,
                $log_rows
            )); ?>
        </p>

        <div class="skd-card">
            <h2><?php esc_html_e('Export', 'simplekitdownloads'); ?></h2>
            <p class="description">
                <?php esc_html_e('The backup includes the settings, downloads, versions and statistics. Uploaded files are not included; only their metadata is exported.', 'simplekitdownloads'); ?>
            </p>

            <form method="post">
                <?php wp_nonce_field('simplekitdownloads_backup_export'); ?>
                <input type="hidden" name="skd_backup_action" value="export" />
                <?php submit_button(__('Generate backup file', 'simplekitdownloads'), 'primary', 'submit', false); ?>
            </form>

            <?php if ($download_url !== '' || $backup_exists) : ?>
                <p>
                    <a class="button" href="<?php echo esc_url($download_url !== '' ? $download_url : wp_nonce_url(admin_url('admin-post.php?action=simplekitdownloads_download_backup'), 'simplekitdownloads_download_backup')); ?>">
                        <?php esc_html_e('Download backup file', 'simplekitdownloads'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <div class="skd-card">
            <h2><?php esc_html_e('Import', 'simplekitdownloads'); ?></h2>
            <p class="description">
                <?php esc_html_e('Importing a backup replaces all current downloads, versions and statistics. This action cannot be undone.', 'simplekitdownloads'); ?>
            </p>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('simplekitdownloads_backup_import'); ?>
                <input type="hidden" name="skd_backup_action" value="import" />
                <input type="file" name="backup_file" accept=".json,application/json" required />
                <?php submit_button(__('Import backup', 'simplekitdownloads'), 'secondary', 'submit', false); ?>
            </form>
        </div>
    </div>
    <?php
}
