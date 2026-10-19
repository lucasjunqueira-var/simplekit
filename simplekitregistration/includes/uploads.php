<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Directory layout
//
//   uploads/simplekitregistration/
//   uploads/simplekitregistration/<form-code>/
//   uploads/simplekitregistration/<form-code>/<entry-id>/
// ---------------------------------------------------------------------------

function simplekitregistration_uploads_root_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitregistration';
}

function simplekitregistration_uploads_root_url() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['baseurl']) . 'simplekitregistration';
}

/**
 * Turn any user supplied value into a valid folder name.
 *
 * The resulting code only contains lowercase letters, digits, hyphen and
 * underscore, which keeps it safe for both the database and the filesystem.
 */
function simplekitregistration_sanitize_code($code) {
    $code = (string) $code;
    $code = function_exists('remove_accents') ? remove_accents($code) : $code;
    $code = strtolower($code);
    $code = preg_replace('/[^a-z0-9_-]+/', '-', $code);
    $code = preg_replace('/-{2,}/', '-', (string) $code);
    $code = trim((string) $code, '-_');
    return substr((string) $code, 0, 60);
}

function simplekitregistration_form_dir_path($code) {
    return trailingslashit(simplekitregistration_uploads_root_path()) . $code;
}

function simplekitregistration_entry_dir_path($code, $entry_id) {
    return trailingslashit(simplekitregistration_form_dir_path($code)) . (int) $entry_id;
}

/**
 * Prevent directory listing by dropping an index.php file in a folder.
 */
function simplekitregistration_protect_dir($dir) {
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
 * (simplekitregistration_download_file), so direct requests are blocked on
 * Apache. On other web servers the endpoint still enforces the permission
 * check, this is only a hardening layer.
 */
function simplekitregistration_deny_direct_access($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $htaccess = trailingslashit($dir) . '.htaccess';
    if (file_exists($htaccess)) {
        return;
    }

    $rules  = "# Deny direct access to uploaded files (Simple Kit Registration).\n";
    $rules .= "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
    $rules .= "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    file_put_contents($htaccess, $rules);
}

function simplekitregistration_ensure_uploads_root() {
    $root = simplekitregistration_uploads_root_path();
    if (!is_dir($root)) {
        wp_mkdir_p($root);
    }
    simplekitregistration_protect_dir($root);
    simplekitregistration_deny_direct_access($root);
    return $root;
}

/**
 * Recursively delete a directory (used when entries or forms are removed).
 */
function simplekitregistration_delete_dir($dir) {
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
            simplekitregistration_delete_dir($path);
        } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            unlink($path);
        }
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    rmdir($dir);
}

function simplekitregistration_delete_entry_files($code, $entry_id) {
    simplekitregistration_delete_dir(simplekitregistration_entry_dir_path($code, $entry_id));
}

function simplekitregistration_delete_form_files($code) {
    simplekitregistration_delete_dir(simplekitregistration_form_dir_path($code));
}

// ---------------------------------------------------------------------------
// Allowed extension handling
// ---------------------------------------------------------------------------
/**
 * Parse the field's allowed extensions string into a lowercased array.
 * An empty string means "any extension is accepted".
 */
function simplekitregistration_allowed_extensions($allowed_ext) {
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
// Store a single uploaded file for a submission
// ---------------------------------------------------------------------------
/**
 * Validate and move an uploaded file into the entry folder.
 *
 * @param array  $field    Field definition (name, label, max_size_mb, allowed_ext).
 * @param array  $file     A single $_FILES style entry.
 * @param string $code     Sanitized form code.
 * @param int    $entry_id Entry ID.
 * @return array|WP_Error|null File metadata on success, WP_Error on failure,
 *                            null when no file was sent.
 */
function simplekitregistration_store_uploaded_file($field, $file, $code, $entry_id) {
    $label = $field['label'] ?? ($field['name'] ?? 'file');

    if (!is_array($file) || !isset($file['error'])) {
        return null;
    }

    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        /* translators: %s: field label. */
        return new WP_Error('skr_upload_error', sprintf(__('The file for "%s" could not be uploaded. Please try again.', 'simplekitregistration'), $label));
    }

    $tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : '';
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        /* translators: %s: field label. */
        return new WP_Error('skr_upload_error', sprintf(__('The file for "%s" could not be uploaded. Please try again.', 'simplekitregistration'), $label));
    }

    $size    = isset($file['size']) ? (int) $file['size'] : 0;
    $max_mb  = isset($field['max_size_mb']) ? (float) $field['max_size_mb'] : 0;
    if ($max_mb > 0 && $size > (int) round($max_mb * 1024 * 1024)) {
        /* translators: 1: field label, 2: maximum size in MB. */
        return new WP_Error('skr_upload_too_large', sprintf(__('The file for "%1$s" exceeds the maximum size of %2$s MB.', 'simplekitregistration'), $label, $max_mb));
    }

    $original = isset($file['name']) ? sanitize_file_name(wp_unslash($file['name'])) : '';
    if ($original === '' || $original === '.') {
        $original = 'file';
    }

    $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
    $allowed   = simplekitregistration_allowed_extensions($field['allowed_ext'] ?? '');
    if (!empty($allowed) && !in_array($extension, $allowed, true)) {
        /* translators: 1: field label, 2: comma separated list of allowed extensions. */
        return new WP_Error('skr_upload_extension', sprintf(__('The file for "%1$s" has a type that is not allowed. Allowed extensions: %2$s.', 'simplekitregistration'), $label, implode(', ', $allowed)));
    }

    $dir = simplekitregistration_entry_dir_path($code, $entry_id);
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    simplekitregistration_protect_dir($dir);

    // Use the WordPress upload handler instead of a direct PHP filesystem call.
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $file['name'] = $original;

    // Redirect the WordPress upload destination to this entry's folder.
    $redirect_dir = static function ($dirs) use ($code, $entry_id) {
        $subdir = 'simplekitregistration/' . $code . '/' . (int) $entry_id;
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
        return new WP_Error('skr_upload_move', sprintf(__('The file for "%s" could not be stored on the server.', 'simplekitregistration'), $label));
    }

    // wp_handle_upload() already guarantees a sanitized, unique file name.
    $stored_name = basename($moved['file']);

    return [
        'original_name' => $original,
        'stored_name'   => $stored_name,
        'subpath'       => $code . '/' . (int) $entry_id . '/' . $stored_name,
        'size'          => $size,
    ];
}
