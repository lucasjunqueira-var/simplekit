<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Backup format
//
//   simplekitregistration-backup.zip
//     ├── simplekitregistration-backup.json   (plugin tables)
//     └── simplekitregistration/...           (uploaded files)
//
// A plain JSON file is also accepted on import for backwards compatibility.
// ---------------------------------------------------------------------------

function simplekitregistration_backup_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitregistration-backup.zip';
}

function simplekitregistration_backup_json_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitregistration-backup.json';
}

function simplekitregistration_backup_json_name() {
    return 'simplekitregistration-backup.json';
}

// ---------------------------------------------------------------------------
// Export / import of the plugin tables
// ---------------------------------------------------------------------------
function simplekitregistration_export_backup_data() {
    global $wpdb;

    $tables = [
        'skr_forms'      => simplekitregistration_forms_table(),
        'skr_entries'    => simplekitregistration_entries_table(),
        'skr_evaluators' => simplekitregistration_evaluators_table(),
        'skr_messages'   => simplekitregistration_messages_table(),
    ];

    $data = [
        'version'     => SIMPLEKITREGISTRATION_VERSION,
        'exported_at' => current_time('mysql'),
        'namespace'   => 'simplekitregistration',
        'tables'      => [],
    ];

    foreach ($tables as $key => $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM %i', $table), ARRAY_A);
        $data['tables'][$key] = is_array($rows) ? $rows : [];
    }

    return $data;
}

function simplekitregistration_validate_backup($data) {
    if (!is_array($data)) {
        return false;
    }
    if (!isset($data['namespace']) || $data['namespace'] !== 'simplekitregistration') {
        return false;
    }
    if (!isset($data['tables']) || !is_array($data['tables'])) {
        return false;
    }
    return true;
}

function simplekitregistration_import_backup($data) {
    global $wpdb;

    if (!simplekitregistration_validate_backup($data)) {
        return false;
    }

    $tables = [
        'skr_forms'      => simplekitregistration_forms_table(),
        'skr_entries'    => simplekitregistration_entries_table(),
        'skr_evaluators' => simplekitregistration_evaluators_table(),
        'skr_messages'   => simplekitregistration_messages_table(),
    ];

    // Remove current data first.
    foreach (['skr_messages', 'skr_entries', 'skr_forms', 'skr_evaluators'] as $key) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare('TRUNCATE TABLE %i', $tables[$key]));
    }

    foreach ($tables as $key => $table) {
        if (!isset($data['tables'][$key]) || !is_array($data['tables'][$key])) {
            continue;
        }
        foreach ($data['tables'][$key] as $row) {
            if (!is_array($row)) {
                continue;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert($table, $row);
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// ZIP creation
// ---------------------------------------------------------------------------
function simplekitregistration_zip_add_dir($zip, $dir, $local_prefix) {
    $items = scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path  = trailingslashit($dir) . $item;
        $local = $local_prefix . '/' . $item;
        if (is_dir($path)) {
            simplekitregistration_zip_add_dir($zip, $path, $local);
        } else {
            $zip->addFile($path, $local);
        }
    }
}

/**
 * Create the ZIP backup in the uploads directory.
 *
 * @return string|false Path to the backup file, or false on failure.
 */
function simplekitregistration_create_backup_zip() {
    $json = wp_json_encode(
        simplekitregistration_export_backup_data(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    if (!class_exists('ZipArchive')) {
        $fallback = simplekitregistration_backup_json_path();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        return file_put_contents($fallback, $json) !== false ? $fallback : false;
    }

    $path = simplekitregistration_backup_file_path();
    $zip  = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $zip->addFromString(simplekitregistration_backup_json_name(), $json);

    $root = simplekitregistration_uploads_root_path();
    if (is_dir($root)) {
        simplekitregistration_zip_add_dir($zip, $root, 'simplekitregistration');
    }

    $zip->close();
    return $path;
}

// ---------------------------------------------------------------------------
// Individual (per form) backup: ZIP with a CSV of responses + messages and the
// complete upload folder of the form. Export only (no import).
// ---------------------------------------------------------------------------
function simplekitregistration_form_backup_file_path($code) {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitregistration-' . $code . '-backup.zip';
}

/**
 * Build a single CSV spreadsheet with all responses of a form and all the
 * messages sent about them.
 *
 * Rows are typed: "Response" rows carry the submitted values, "Message" rows
 * carry the author and the text of a message, always referring to the response
 * ID. The file uses ";" as separator and a UTF-8 BOM so it opens in Excel.
 */
/**
 * Encode a single CSV row using ";" as the separator.
 *
 * The row is built as a plain string so the plugin does not need direct PHP
 * filesystem or stream calls to generate the spreadsheet.
 */
function simplekitregistration_csv_row_line($values) {
    $cells = [];
    foreach ($values as $value) {
        $value = (string) $value;
        if (strpbrk($value, ";\"\r\n") !== false) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        $cells[] = $value;
    }
    return implode(';', $cells) . "\r\n";
}

function simplekitregistration_build_form_backup_csv($form) {
    global $wpdb;

    $fields = json_decode($form->fields_json, true) ?: [];

    // UTF-8 BOM so the file opens correctly in Excel.
    $csv = chr(0xEF) . chr(0xBB) . chr(0xBF);

    $header = [
        simplekitregistration_spreadsheet_text('backup_record'),
        simplekitregistration_spreadsheet_text('backup_response_id'),
    ];
    foreach ($fields as $field) {
        $header[] = $field['label'] ?? ($field['name'] ?? '');
    }
    $header[] = simplekitregistration_spreadsheet_text('backup_ip');
    $header[] = simplekitregistration_spreadsheet_text('backup_datetime_response');
    $header[] = simplekitregistration_spreadsheet_text('backup_message_author');
    $header[] = simplekitregistration_spreadsheet_text('backup_message_email');
    $header[] = simplekitregistration_spreadsheet_text('backup_message_text');
    $header[] = simplekitregistration_spreadsheet_text('backup_datetime_message');
    $csv .= simplekitregistration_csv_row_line($header);

    $entries_table  = simplekitregistration_entries_table();
    $messages_table = simplekitregistration_messages_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM %i WHERE form_id = %d ORDER BY created_at ASC, id ASC',
        $entries_table,
        (int) $form->id
    ));

    foreach ($entries as $entry) {
        $data = json_decode($entry->data_json, true) ?: [];

        $row = [simplekitregistration_spreadsheet_text('backup_row_response'), $entry->id];
        foreach ($fields as $field) {
            $name  = $field['name'] ?? '';
            $value = $data[$name] ?? '';
            if (simplekitregistration_is_file_value($value)) {
                // The file is included in this ZIP, so the cell points to its
                // path inside the archive instead of a public URL.
                $row[] = 'uploads/' . ltrim($value['subpath'], '/');
            } elseif (is_array($value)) {
                $row[] = implode(', ', $value);
            } else {
                $row[] = (string) $value;
            }
        }
        $row[] = $entry->ip;
        $row[] = $entry->created_at;
        $row[] = '';
        $row[] = '';
        $row[] = '';
        $row[] = '';
        $csv .= simplekitregistration_csv_row_line($row);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $messages = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM %i WHERE entry_id = %d ORDER BY created_at ASC, id ASC',
            $messages_table,
            (int) $entry->id
        ));

        foreach ($messages as $message) {
            $row = [simplekitregistration_spreadsheet_text('backup_row_message'), $entry->id];
            foreach ($fields as $field) {
                $row[] = '';
            }
            $row[] = '';
            $row[] = '';
            $row[] = ($message->author_name !== '') ? $message->author_name : $message->author_email;
            $row[] = $message->author_email;
            $row[] = $message->message;
            $row[] = $message->created_at;
            $csv .= simplekitregistration_csv_row_line($row);
        }
    }

    return $csv;
}

/**
 * Create the ZIP backup of a single form.
 *
 * @return string|false Path to the ZIP file, or false on failure.
 */
function simplekitregistration_create_form_backup_zip($form_id) {
    $form = simplekitregistration_get_form($form_id);
    if (!$form || !class_exists('ZipArchive')) {
        return false;
    }

    $path = simplekitregistration_form_backup_file_path($form->code);
    $zip  = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $zip->addFromString($form->code . '.csv', simplekitregistration_build_form_backup_csv($form));

    // Complete upload folder of the form (files of all responses).
    $form_dir = simplekitregistration_form_dir_path($form->code);
    if (is_dir($form_dir)) {
        simplekitregistration_zip_add_dir($zip, $form_dir, 'uploads/' . $form->code);
    }

    $zip->close();
    return $path;
}

/**
 * Import a backup file (ZIP or JSON).
 *
 * @return bool
 */
function simplekitregistration_import_backup_file($path) {
    if (!file_exists($path)) {
        return false;
    }

    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    if ($extension === 'json') {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
        $contents = file_get_contents($path);
        $data     = json_decode($contents, true);
        return simplekitregistration_import_backup($data);
    }

    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return false;
    }

    $json = $zip->getFromName(simplekitregistration_backup_json_name());
    if ($json === false) {
        $zip->close();
        return false;
    }

    $data = json_decode($json, true);
    if (!simplekitregistration_validate_backup($data)) {
        $zip->close();
        return false;
    }

    // Extract only the plugin's own uploads folder (guards against zip-slip).
    $entries   = [];
    $upload_dir = wp_upload_dir();
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (is_string($name) && strpos($name, 'simplekitregistration/') === 0 && strpos($name, '..') === false) {
            $entries[] = $name;
        }
    }
    if (!empty($entries)) {
        $zip->extractTo($upload_dir['basedir'], $entries);
    }

    $zip->close();

    return simplekitregistration_import_backup($data);
}

/**
 * Restore the backup generated on deactivation (runs on activation).
 */
function simplekitregistration_restore_backup_on_activation() {
    $zip = simplekitregistration_backup_file_path();
    if (file_exists($zip)) {
        simplekitregistration_import_backup_file($zip);
        return;
    }

    $json = simplekitregistration_backup_json_path();
    if (file_exists($json)) {
        simplekitregistration_import_backup_file($json);
    }
}

// ---------------------------------------------------------------------------
// Download handler
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_download_backup', 'simplekitregistration_download_backup_handler');

function simplekitregistration_download_backup_handler() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitregistration_download_backup')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $path = simplekitregistration_backup_file_path();
    if (!file_exists($path)) {
        $path = simplekitregistration_backup_json_path();
    }
    if (!file_exists($path)) {
        wp_die(esc_html__('Backup file not found. Please generate a new backup.', 'simplekitregistration'));
    }

    $contents = file_get_contents($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents

    while (ob_get_level()) {
        ob_end_clean();
    }

    $is_zip = strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'zip';

    header('Content-Type: ' . ($is_zip ? 'application/zip' : 'application/json') . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . strlen((string) $contents));
    header('Pragma: no-cache');
    header('Expires: 0');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file download, escaping would corrupt the data.
    echo $contents;
    exit;
}

// ---------------------------------------------------------------------------
// Download handler: individual form backup
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_download_form_backup', 'simplekitregistration_download_form_backup_handler');

function simplekitregistration_download_form_backup_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
    $nonce   = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

    if ($form_id <= 0 || !wp_verify_nonce($nonce, 'simplekitregistration_download_form_backup_' . $form_id)) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $form = simplekitregistration_get_form($form_id);
    if (!$form) {
        wp_die(esc_html__('Form not found.', 'simplekitregistration'));
    }

    $path = simplekitregistration_form_backup_file_path($form->code);
    if (!file_exists($path)) {
        $path = simplekitregistration_create_form_backup_zip($form_id);
    }
    if (!$path || !file_exists($path)) {
        wp_die(esc_html__('Unable to generate the backup file. The ZIP extension may not be available on this server.', 'simplekitregistration'));
    }

    $contents = file_get_contents($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/zip; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . strlen((string) $contents));
    header('Pragma: no-cache');
    header('Expires: 0');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file download, escaping would corrupt the data.
    echo $contents;
    exit;
}

// ---------------------------------------------------------------------------
// Page: Backup
// ---------------------------------------------------------------------------
function simplekitregistration_page_backup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $msg  = '';
    $type = 'success';
    $download_url = '';
    $form_download_url = '';

    // Export
    if (
        isset($_POST['skr_action']) && sanitize_text_field(wp_unslash($_POST['skr_action'])) === 'export_backup'
        && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'simplekitregistration_backup_export')
    ) {
        $created = simplekitregistration_create_backup_zip();
        if ($created === false) {
            $msg  = __('Failed to generate the backup file.', 'simplekitregistration');
            $type = 'error';
        } else {
            $download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitregistration_download_backup'),
                'simplekitregistration_download_backup'
            );
            $msg = __('Backup file generated successfully.', 'simplekitregistration');
        }
    }

    // Import
    if (
        isset($_POST['skr_action']) && sanitize_text_field(wp_unslash($_POST['skr_action'])) === 'import_backup'
        && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'simplekitregistration_backup_import')
    ) {
        if (!isset($_FILES['backup_file']) || !isset($_FILES['backup_file']['error']) || (int) $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $msg  = __('Error uploading the backup file.', 'simplekitregistration');
            $type = 'error';
        } else {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- internal PHP temporary path.
            $tmp = $_FILES['backup_file']['tmp_name'] ?? '';
            if ($tmp === '' || !simplekitregistration_import_backup_file($tmp)) {
                $msg  = __('Invalid backup file. The file structure does not match the expected format.', 'simplekitregistration');
                $type = 'error';
            } else {
                $msg  = __('Backup imported successfully. All previous data has been replaced.', 'simplekitregistration');
                $type = 'success';
            }
        }
    }

    // Individual backup of a single form (export only).
    if (
        isset($_POST['skr_action']) && sanitize_text_field(wp_unslash($_POST['skr_action'])) === 'export_form_backup'
        && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'simplekitregistration_backup_export_form')
    ) {
        $form_id = isset($_POST['skr_form_id']) ? (int) $_POST['skr_form_id'] : 0;
        $created = $form_id > 0 ? simplekitregistration_create_form_backup_zip($form_id) : false;

        if ($created === false) {
            $msg  = __('Failed to generate the backup file. Make sure the ZIP extension is available on this server.', 'simplekitregistration');
            $type = 'error';
        } else {
            $form_download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitregistration_download_form_backup&form_id=' . $form_id),
                'simplekitregistration_download_form_backup_' . $form_id
            );
            $msg = __('Individual backup generated successfully.', 'simplekitregistration');
        }
    }

    $path      = simplekitregistration_backup_file_path();
    $all_forms = simplekitregistration_get_all_forms();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Backup', 'simplekitregistration'); ?></h1>

        <?php if ($msg !== '') : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible"><p><?php echo esc_html($msg); ?></p></div>
        <?php endif; ?>

        <h2><?php esc_html_e('Export backup', 'simplekitregistration'); ?></h2>
        <p><?php esc_html_e('Generate a ZIP file containing all the plugin data (forms, responses, messages and evaluators) and all uploaded files. The file is saved in the WordPress uploads directory and its download starts automatically.', 'simplekitregistration'); ?></p>
        <?php if (file_exists($path)) : ?>
            <p class="description">
                <?php
                /* translators: %s: file path. */
                echo esc_html(sprintf(__('Current backup file: %s', 'simplekitregistration'), $path));
                ?>
            </p>
        <?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitregistration_backup_export', '_wpnonce'); ?>
            <input type="hidden" name="skr_action" value="export_backup">
            <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Export backup', 'simplekitregistration'); ?>">
        </form>

        <?php if ($download_url !== '') : ?>
            <p><a href="<?php echo esc_url($download_url); ?>" class="button" id="skr-download-backup-link"><?php esc_html_e('Download backup file', 'simplekitregistration'); ?></a></p>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                window.location.href = $('#skr-download-backup-link').attr('href');
            });
            </script>
        <?php endif; ?>

        <hr>

        <h2><?php esc_html_e('Import backup', 'simplekitregistration'); ?></h2>
        <p><strong><?php esc_html_e('Warning:', 'simplekitregistration'); ?></strong> <?php esc_html_e('All current plugin data will be replaced by the data from the backup file. Uploaded files are restored as well. This action cannot be undone.', 'simplekitregistration'); ?></p>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return confirm('<?php echo esc_js(__('Are you sure? All current data will be replaced by the backup data.', 'simplekitregistration')); ?>');">
            <?php wp_nonce_field('simplekitregistration_backup_import', '_wpnonce'); ?>
            <input type="hidden" name="skr_action" value="import_backup">
            <input type="file" name="backup_file" accept=".zip,.json" required>
            <br><br>
            <input type="submit" class="button" value="<?php echo esc_attr__('Import backup', 'simplekitregistration'); ?>">
        </form>

        <hr>

        <h2><?php esc_html_e('Individual form backup', 'simplekitregistration'); ?></h2>
        <p><?php esc_html_e('Export a ZIP file with the selected form only. The ZIP contains a CSV spreadsheet with all the responses and all the messages of the form, plus the complete upload folder with the files of every response. This export cannot be imported back.', 'simplekitregistration'); ?></p>

        <?php if (empty($all_forms)) : ?>
            <p><?php esc_html_e('No forms found.', 'simplekitregistration'); ?></p>
        <?php else : ?>
            <form method="post" action="">
                <?php wp_nonce_field('simplekitregistration_backup_export_form', '_wpnonce'); ?>
                <input type="hidden" name="skr_action" value="export_form_backup">
                <select name="skr_form_id" required>
                    <option value=""><?php esc_html_e('-- Select form --', 'simplekitregistration'); ?></option>
                    <?php foreach ($all_forms as $the_form) : ?>
                        <option value="<?php echo (int) $the_form->id; ?>">
                            <?php echo esc_html($the_form->title . ' (' . $the_form->code . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Export form backup', 'simplekitregistration'); ?>">
            </form>

            <?php if ($form_download_url !== '') : ?>
                <p><a href="<?php echo esc_url($form_download_url); ?>" class="button" id="skr-download-form-backup-link"><?php esc_html_e('Download form backup', 'simplekitregistration'); ?></a></p>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    window.location.href = $('#skr-download-form-backup-link').attr('href');
                });
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
