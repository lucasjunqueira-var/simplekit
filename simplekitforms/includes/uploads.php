<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// File field support: file storage, cleanup and protected downloads.
//
// Directory layout (there is no form code in this plugin, so the form ID is
// used as the folder name):
//
//   uploads/simplekitforms/
//   uploads/simplekitforms/<form-id>/
//   uploads/simplekitforms/<form-id>/<entry-id>/
// ---------------------------------------------------------------------------

function simplekitforms_uploads_root_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitforms';
}

function simplekitforms_uploads_root_url() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['baseurl']) . 'simplekitforms';
}

function simplekitforms_form_dir_path($form_id) {
    return trailingslashit(simplekitforms_uploads_root_path()) . (int) $form_id;
}

function simplekitforms_entry_dir_path($form_id, $entry_id) {
    return trailingslashit(simplekitforms_form_dir_path($form_id)) . (int) $entry_id;
}

/**
 * Prevent directory listing by dropping an index.php file in a folder.
 */
function simplekitforms_protect_dir($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $index = trailingslashit($dir) . 'index.php';
    if (!file_exists($index)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($index, "<?php\n// Silence is golden.\n");
    }
}

/**
 * Deny direct web access to the uploaded files.
 *
 * Files are streamed through the permission-checked endpoint
 * (simplekitforms_download_file), so direct requests are blocked on Apache.
 * On other web servers the endpoint still enforces the capability check; this
 * is only a hardening layer.
 */
function simplekitforms_deny_direct_access($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $htaccess = trailingslashit($dir) . '.htaccess';
    if (file_exists($htaccess)) {
        return;
    }

    $rules  = "# Deny direct access to uploaded files (Simple Kit Forms).\n";
    $rules .= "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
    $rules .= "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    file_put_contents($htaccess, $rules);
}

function simplekitforms_ensure_uploads_root() {
    $root = simplekitforms_uploads_root_path();
    if (!is_dir($root)) {
        wp_mkdir_p($root);
    }
    simplekitforms_protect_dir($root);
    simplekitforms_deny_direct_access($root);
    return $root;
}

/**
 * Recursively delete a directory (used when entries or forms are removed).
 */
function simplekitforms_delete_dir($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = trailingslashit($dir) . $item;
        if (is_dir($path)) {
            simplekitforms_delete_dir($path);
        } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            unlink($path);
        }
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    rmdir($dir);
}

function simplekitforms_delete_entry_files($form_id, $entry_id) {
    simplekitforms_delete_dir(simplekitforms_entry_dir_path($form_id, $entry_id));
}

function simplekitforms_delete_form_files($form_id) {
    simplekitforms_delete_dir(simplekitforms_form_dir_path($form_id));
}

// ---------------------------------------------------------------------------
// Allowed extension handling
// ---------------------------------------------------------------------------
/**
 * Parse the field's allowed extensions string into a lowercased array.
 * An empty string means "any extension is accepted".
 */
function simplekitforms_allowed_extensions($allowed_ext) {
    $allowed_ext = (string) $allowed_ext;
    if (trim($allowed_ext) === '') {
        return [];
    }

    $parts  = explode(',', $allowed_ext);
    $result = [];
    foreach ($parts as $part) {
        $ext = strtolower(preg_replace('/[^A-Za-z0-9]/', '', trim($part)));
        if ($ext !== '') {
            $result[] = $ext;
        }
    }
    return array_values(array_unique($result));
}

// ---------------------------------------------------------------------------
// Uploaded file value helpers
//
// A file field value is stored in data_json as:
//   [
//     'original_name' => 'cv.pdf',
//     'stored_name'   => 'cv.pdf',
//     'subpath'       => '<form-id>/<entry-id>/cv.pdf',
//     'size'          => 12345,
//   ]
// ---------------------------------------------------------------------------
function simplekitforms_is_file_value($value) {
    return is_array($value) && isset($value['subpath']);
}

function simplekitforms_file_value_path($value) {
    if (!simplekitforms_is_file_value($value)) {
        return '';
    }
    return simplekitforms_uploads_root_path() . '/' . ltrim($value['subpath'], '/');
}

/**
 * URL of the permission-checked download endpoint for a stored file.
 */
function simplekitforms_file_download_url($entry_id, $field_name) {
    $url = add_query_arg(
        [
            'action' => 'simplekitforms_download_file',
            'entry'  => (int) $entry_id,
            'field'  => $field_name,
        ],
        admin_url('admin-post.php')
    );

    return wp_nonce_url($url, 'simplekitforms_download_file_' . (int) $entry_id);
}

/**
 * Build a download link for a stored file value.
 *
 * Files are never embedded in the page: only a link is rendered, regardless of
 * the file type.
 */
function simplekitforms_file_value_html($value, $entry_id = 0, $field_name = '') {
    if (!simplekitforms_is_file_value($value)) {
        return '';
    }

    $name = $value['original_name'] ?? ($value['stored_name'] ?? '');

    if ($entry_id > 0 && $field_name !== '') {
        $url = simplekitforms_file_download_url($entry_id, $field_name);
    } else {
        $url = simplekitforms_uploads_root_url() . '/' . ltrim($value['subpath'], '/');
    }

    if ($url === '') {
        return esc_html($name);
    }

    return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($name) . '</a>';
}

// ---------------------------------------------------------------------------
// Store a single uploaded file for a submission
// ---------------------------------------------------------------------------
/**
 * Validate and move an uploaded file into the entry folder.
 *
 * @param array $field    Field definition (name, label, max_size_mb, allowed_ext).
 * @param array $file     A single $_FILES style entry.
 * @param int   $form_id  Form ID.
 * @param int   $entry_id Entry ID.
 * @return array|WP_Error|null File metadata on success, WP_Error on failure,
 *                            null when no file was sent.
 */
function simplekitforms_store_uploaded_file($field, $file, $form_id, $entry_id) {
    $label = $field['label'] ?? ($field['name'] ?? 'file');

    // Make sure the storage folder and its access guards exist.
    simplekitforms_ensure_uploads_root();

    if (!is_array($file) || !isset($file['error'])) {
        return null;
    }

    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        /* translators: %s: field label. */
        return new WP_Error('skf_upload_error', sprintf(__('The file for "%s" could not be uploaded. Please try again.', 'simplekitforms'), $label));
    }

    $tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : '';
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        /* translators: %s: field label. */
        return new WP_Error('skf_upload_error', sprintf(__('The file for "%s" could not be uploaded. Please try again.', 'simplekitforms'), $label));
    }

    $size   = isset($file['size']) ? (int) $file['size'] : 0;
    $max_mb = isset($field['max_size_mb']) ? (float) $field['max_size_mb'] : 0;
    if ($max_mb > 0 && $size > (int) round($max_mb * 1024 * 1024)) {
        /* translators: 1: field label, 2: maximum size in MB. */
        return new WP_Error('skf_upload_too_large', sprintf(__('The file for "%1$s" exceeds the maximum size of %2$s MB.', 'simplekitforms'), $label, $max_mb));
    }

    $original = isset($file['name']) ? sanitize_file_name(wp_unslash($file['name'])) : '';
    if ($original === '' || $original === '.') {
        $original = 'file';
    }

    $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
    $allowed   = simplekitforms_allowed_extensions($field['allowed_ext'] ?? '');
    if (!empty($allowed) && !in_array($extension, $allowed, true)) {
        /* translators: 1: field label, 2: comma separated list of allowed extensions. */
        return new WP_Error('skf_upload_extension', sprintf(__('The file for "%1$s" has a type that is not allowed. Allowed extensions: %2$s.', 'simplekitforms'), $label, implode(', ', $allowed)));
    }

    $dir = simplekitforms_entry_dir_path($form_id, $entry_id);
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    simplekitforms_protect_dir($dir);

    // Use the WordPress upload handler instead of a direct PHP filesystem call.
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $file['name'] = $original;

    // Redirect the WordPress upload destination to this entry's folder.
    $redirect_dir = static function ($dirs) use ($form_id, $entry_id) {
        $subdir = 'simplekitforms/' . (int) $form_id . '/' . (int) $entry_id;
        $dirs['subdir'] = '/' . $subdir;
        $dirs['path']   = $dirs['basedir'] . '/' . $subdir;
        $dirs['url']    = $dirs['baseurl'] . '/' . $subdir;
        return $dirs;
    };

    add_filter('upload_dir', $redirect_dir);

    // Size and type are validated above, so the core checks are disabled here.
    $moved = wp_handle_upload($file, [
        'test_form' => false,
        'test_size' => false,
        'test_type' => false,
    ]);

    remove_filter('upload_dir', $redirect_dir);

    if (!is_array($moved) || empty($moved['file']) || !empty($moved['error'])) {
        /* translators: %s: field label. */
        return new WP_Error('skf_upload_move', sprintf(__('The file for "%s" could not be stored on the server.', 'simplekitforms'), $label));
    }

    // wp_handle_upload() already guarantees a sanitized, unique file name.
    $stored_name = basename($moved['file']);

    return [
        'original_name' => $original,
        'stored_name'   => $stored_name,
        'subpath'       => (int) $form_id . '/' . (int) $entry_id . '/' . $stored_name,
        'size'          => $size,
    ];
}

// ---------------------------------------------------------------------------
// Permission-checked download endpoint
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_simplekitforms_download_file', 'simplekitforms_handle_file_download');
add_action('admin_post_simplekitforms_download_file', 'simplekitforms_handle_file_download');

function simplekitforms_handle_file_download() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitforms'), '', ['response' => 403]);
    }

    $entry_id   = isset($_GET['entry']) ? (int) $_GET['entry'] : 0;
    $field_name = isset($_GET['field']) ? sanitize_text_field(wp_unslash($_GET['field'])) : '';

    if ($entry_id <= 0 || $field_name === '') {
        wp_die(esc_html__('Invalid file request.', 'simplekitforms'), '', ['response' => 400]);
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'simplekitforms_download_file_' . $entry_id)) {
        wp_die(esc_html__('Access denied.', 'simplekitforms'), '', ['response' => 403]);
    }

    global $wpdb;
    $table = simplekitforms_get_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entry = $wpdb->get_row($wpdb->prepare('SELECT * FROM %i WHERE id = %d', $table, $entry_id));
    if (!$entry) {
        wp_die(esc_html__('File not found.', 'simplekitforms'), '', ['response' => 404]);
    }

    $data  = json_decode($entry->data_json, true) ?: [];
    $value = isset($data[$field_name]) ? $data[$field_name] : null;
    if (!simplekitforms_is_file_value($value)) {
        wp_die(esc_html__('File not found.', 'simplekitforms'), '', ['response' => 404]);
    }

    $root = realpath(simplekitforms_uploads_root_path());
    $path = realpath(simplekitforms_uploads_root_path() . '/' . ltrim($value['subpath'], '/'));
    if ($root === false || $path === false || strpos($path, $root) !== 0 || !is_file($path)) {
        wp_die(esc_html__('File not found.', 'simplekitforms'), '', ['response' => 404]);
    }

    $filename = $value['original_name'] ?? ($value['stored_name'] ?? basename($path));

    while (ob_get_level()) {
        ob_end_clean();
    }
    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . filesize($path));
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a validated local file.
    readfile($path);
    exit;
}
