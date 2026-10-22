<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// File storage for uploaded versions.
//
// Layout:
//   uploads/simplekitdownloads/<download-id>/<version-id>/<file>
//
// Files registered as a URL are NOT copied to this folder; they are served
// from their original location through a redirect.
// ---------------------------------------------------------------------------

function simplekitdownloads_uploads_root_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitdownloads';
}

function simplekitdownloads_uploads_root_url() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['baseurl']) . 'simplekitdownloads';
}

function simplekitdownloads_download_dir_path($download_id) {
    return trailingslashit(simplekitdownloads_uploads_root_path()) . (int) $download_id;
}

function simplekitdownloads_version_dir_path($download_id, $version_id) {
    return trailingslashit(simplekitdownloads_download_dir_path($download_id)) . (int) $version_id;
}

function simplekitdownloads_version_dir_url($download_id, $version_id) {
    return trailingslashit(simplekitdownloads_uploads_root_url()) . (int) $download_id . '/' . (int) $version_id;
}

/**
 * Prevent directory listing by dropping an index.php file in a folder.
 */
function simplekitdownloads_protect_dir($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $index = trailingslashit($dir) . 'index.php';
    if (!file_exists($index)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($index, "<?php\n// Silence is golden.\n");
    }
}

function simplekitdownloads_ensure_uploads_root() {
    $root = simplekitdownloads_uploads_root_path();
    if (!is_dir($root)) {
        wp_mkdir_p($root);
    }
    simplekitdownloads_protect_dir($root);
    return $root;
}

/**
 * Recursively delete a directory, but only inside the plugin uploads root.
 */
function simplekitdownloads_delete_dir($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $root   = realpath(simplekitdownloads_uploads_root_path());
    $target = realpath($dir);
    if (!$root || !$target || strpos($target, $root) !== 0) {
        return;
    }

    $items = scandir($target);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = trailingslashit($target) . $item;
        if (is_dir($path)) {
            simplekitdownloads_delete_dir($path);
        } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            unlink($path);
        }
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    rmdir($target);
}

/**
 * Return the WordPress filesystem instance used by the storage helpers.
 *
 * @return WP_Filesystem_Base|false
 */
function simplekitdownloads_filesystem() {
    global $wp_filesystem;

    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!$wp_filesystem && !WP_Filesystem()) {
        return false;
    }

    return $wp_filesystem;
}

/**
 * Extensions that must never be accepted as a downloadable file.
 */
function simplekitdownloads_blocked_extensions() {
    return [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps',
        'phar', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'htaccess', 'htpasswd',
        'ini', 'conf', 'config', 'asp', 'aspx', 'jsp', 'jspx', 'shtml', 'shtm',
    ];
}

function simplekitdownloads_has_blocked_extension($filename) {
    $filename = strtolower((string) $filename);
    if ($filename === '' || strpos($filename, '.') === false) {
        return false;
    }
    $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension === '') {
        return false;
    }
    return in_array($extension, simplekitdownloads_blocked_extensions(), true);
}

/**
 * Unique filename callback used by wp_handle_upload().
 */
function simplekitdownloads_unique_filename($dir, $name, $ext) {
    $name = sanitize_file_name($name);
    $ext  = sanitize_file_name($ext);
    if ($name === '') {
        $name = 'file';
    }

    $filename = $name . $ext;
    $counter  = 1;
    while (file_exists(trailingslashit($dir) . $filename)) {
        $filename = $name . '-' . $counter . $ext;
        $counter++;
    }
    return $filename;
}

/**
 * Move an uploaded file into the version folder.
 *
 * @param array $file        Entry from $_FILES.
 * @param int   $download_id Download ID.
 * @param int   $version_id  Version ID.
 * @return array|WP_Error {
 *     @type string $file      Absolute path.
 *     @type string $file_path Relative path inside the plugin uploads root.
 *     @type string $file_name Original file name.
 *     @type int    $file_size File size in bytes.
 * }
 */
function simplekitdownloads_handle_upload($file, $download_id, $version_id) {
    if (!is_array($file) || !isset($file['name'], $file['tmp_name'], $file['error'])
        || !is_string($file['name'])
    ) {
        return new WP_Error('skd_no_file', __('Please select a file to upload.', 'simplekitdownloads'));
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('skd_upload_error', __('The file could not be uploaded. Please try again.', 'simplekitdownloads'));
    }

    if (simplekitdownloads_has_blocked_extension($file['name'])) {
        return new WP_Error('skd_blocked_extension', __('This file type is not allowed.', 'simplekitdownloads'));
    }

    if (isset($file['size']) && $file['size'] > wp_max_upload_size()) {
        return new WP_Error('skd_file_too_big', __('The file is larger than the maximum upload size allowed by this server.', 'simplekitdownloads'));
    }

    $dir = simplekitdownloads_version_dir_path($download_id, $version_id);
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    simplekitdownloads_protect_dir($dir);

    $upload_dir = [
        'path'    => $dir,
        'url'     => simplekitdownloads_version_dir_url($download_id, $version_id),
        'subdir'  => '',
        'basedir' => $dir,
        'baseurl' => simplekitdownloads_version_dir_url($download_id, $version_id),
        'error'   => false,
    ];

    $overrides = [
        'test_form'                => false,
        'test_type'                => false,
        'upload_dir'               => $upload_dir,
        'unique_filename_callback' => 'simplekitdownloads_unique_filename',
    ];

    /*
     * Some WordPress versions ignore the upload_dir override. Forcing it through
     * the upload_dir filter guarantees the file is written to the plugin folder.
     */
    $force_upload_dir = static function ($uploads) use ($upload_dir) {
        return array_merge((array) $uploads, $upload_dir);
    };
    add_filter('upload_dir', $force_upload_dir, 999999);
    $result = wp_handle_upload($file, $overrides);
    remove_filter('upload_dir', $force_upload_dir, 999999);

    if (!is_array($result) || isset($result['error'])) {
        $message = is_array($result) && isset($result['error'])
            ? $result['error']
            : __('The file could not be moved to the uploads folder.', 'simplekitdownloads');
        return new WP_Error('skd_upload_failed', $message);
    }

    $absolute = $result['file'];
    $root     = trailingslashit(simplekitdownloads_uploads_root_path());

    // Fallback: move the file when the upload landed outside the plugin folder.
    if (strpos($absolute, $root) !== 0) {
        $basename = basename($absolute);
        $ext      = pathinfo($basename, PATHINFO_EXTENSION);
        $name     = pathinfo($basename, PATHINFO_FILENAME);
        $target   = trailingslashit($dir) . $basename;
        $counter  = 1;

        while (file_exists($target)) {
            $target = trailingslashit($dir) . $name . '-' . $counter . ($ext !== '' ? '.' . $ext : '');
            $counter++;
        }

        $filesystem = simplekitdownloads_filesystem();
        if ($filesystem && $filesystem->move($absolute, $target, true)) {
            $absolute = $target;
        }
    }

    if (strpos($absolute, $root) !== 0) {
        return new WP_Error('skd_upload_failed', __('The file could not be moved to the plugin uploads folder.', 'simplekitdownloads'));
    }

    $relative = ltrim(str_replace($root, '', $absolute), '/\\');

    return [
        'file'      => $absolute,
        'file_path' => $relative,
        'file_name' => basename($absolute),
        'file_size' => (int) filesize($absolute),
    ];
}

function simplekitdownloads_delete_version_files($download_id, $version_id) {
    simplekitdownloads_delete_dir(simplekitdownloads_version_dir_path($download_id, $version_id));
}

function simplekitdownloads_delete_download_files($download_id) {
    simplekitdownloads_delete_dir(simplekitdownloads_download_dir_path($download_id));
}

/**
 * Absolute path of an uploaded version file, or '' when the file is missing.
 */
function simplekitdownloads_version_file_path($version) {
    if (!is_object($version) || ($version->source ?? '') !== 'upload') {
        return '';
    }
    $relative = ltrim((string) ($version->file_path ?? ''), '/');
    if ($relative === '') {
        return '';
    }

    // Legacy records may contain an absolute path; they are repaired on demand.
    if (preg_match('#^(/|[A-Za-z]:[\\\\/])#', $relative)) {
        return '';
    }

    return trailingslashit(simplekitdownloads_uploads_root_path()) . $relative;
}

/**
 * Try to recover a version file stored outside the plugin folder.
 *
 * Older records can point to the default year/month uploads folder. The file is
 * located by its name, moved into the version folder and the record is fixed.
 *
 * @param object $version Version row.
 * @return bool True when the file exists inside the plugin folder.
 */
function simplekitdownloads_repair_version_file($version) {
    if (!is_object($version) || ($version->source ?? '') !== 'upload') {
        return false;
    }

    $current = simplekitdownloads_version_file_path($version);
    if ($current !== '' && is_file($current)) {
        return true;
    }

    $filename = basename((string) ($version->file_name ?? ''));
    if ($filename === '') {
        return false;
    }

    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
        return false;
    }

    $root       = simplekitdownloads_uploads_root_path();
    $candidates = array_unique(array_merge(
        (array) glob(trailingslashit($uploads['basedir']) . '*/*/' . $filename),
        (array) glob(trailingslashit($root) . '*/*/' . $filename)
    ));

    if (empty($candidates)) {
        return false;
    }

    $source = '';
    foreach ($candidates as $candidate) {
        if (!is_file($candidate)) {
            continue;
        }
        if ((int) $version->file_size > 0 && (int) filesize($candidate) !== (int) $version->file_size) {
            continue;
        }
        $source = $candidate;
        break;
    }
    if ($source === '' && is_file($candidates[0])) {
        $source = $candidates[0];
    }
    if ($source === '') {
        return false;
    }

    $source_real = realpath($source);
    $root_real   = realpath($root);
    $source_norm = ($source_real !== false) ? wp_normalize_path($source_real) : '';
    $root_norm   = ($root_real !== false) ? wp_normalize_path($root_real) : '';
    $root_prefix = ($root_norm !== '') ? trailingslashit($root_norm) : '';

    if ($source_norm !== '' && $root_prefix !== '' && strpos($source_norm, $root_prefix) === 0) {
        $relative = ltrim(substr($source_norm, strlen($root_prefix)), '/');
    } else {
        $dir = simplekitdownloads_version_dir_path((int) $version->download_id, (int) $version->id);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        simplekitdownloads_protect_dir($dir);

        $basename = basename($source);
        $ext      = pathinfo($basename, PATHINFO_EXTENSION);
        $name     = pathinfo($basename, PATHINFO_FILENAME);
        $target   = trailingslashit($dir) . $basename;
        $counter  = 1;

        while (file_exists($target) && realpath($target) !== $source_real) {
            $target = trailingslashit($dir) . $name . '-' . $counter . ($ext !== '' ? '.' . $ext : '');
            $counter++;
        }

        if (realpath($target) !== $source_real) {
            $filesystem = simplekitdownloads_filesystem();
            $moved      = $filesystem ? $filesystem->move($source, $target, true) : false;

            if (!$moved && !@copy($source, $target)) {
                return false;
            }
            if (is_file($source) && realpath($source) !== realpath($target)) {
                wp_delete_file($source);
            }
        }

        $relative = ltrim(str_replace(trailingslashit(wp_normalize_path($root)), '', wp_normalize_path($target)), '/');
    }

    if ($relative === '') {
        return false;
    }

    $absolute = trailingslashit($root) . $relative;
    if (!is_file($absolute)) {
        return false;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->update(
        simplekitdownloads_table('versions'),
        [
            'file_path' => $relative,
            'file_name' => basename($relative),
            'file_size' => (int) filesize($absolute),
        ],
        ['id' => (int) $version->id]
    );

    return true;
}

// ---------------------------------------------------------------------------
// Version label helpers
// ---------------------------------------------------------------------------

/**
 * Validate a version label. Accepted formats: 25, 25.1, 25.0.1 (up to 5 parts).
 *
 * @return string|WP_Error
 */
function simplekitdownloads_validate_version_label($label) {
    if (!is_string($label)) {
        return new WP_Error('skd_invalid_version', __('Please enter a version.', 'simplekitdownloads'));
    }
    $label = trim($label);

    if ($label === '') {
        return new WP_Error('skd_invalid_version', __('Please enter a version.', 'simplekitdownloads'));
    }
    if (strlen($label) > 50) {
        return new WP_Error('skd_invalid_version', __('The version is too long.', 'simplekitdownloads'));
    }
    if (!preg_match('/^\d{1,9}(\.\d{1,9}){0,4}$/', $label)) {
        return new WP_Error('skd_invalid_version', __('The version must be numeric, for example 25, 25.1 or 25.0.1.', 'simplekitdownloads'));
    }

    return $label;
}

/**
 * Sortable representation of a version label (zero padded, 5 segments).
 */
function simplekitdownloads_version_sort_key($label) {
    $segments = explode('.', (string) $label);
    $segments = array_slice($segments, 0, 5);
    $segments = array_pad($segments, 5, '0');

    foreach ($segments as $index => $segment) {
        $digits = preg_replace('/\D/', '', $segment);
        if ($digits === '') {
            $digits = '0';
        }
        $segments[$index] = str_pad($digits, 9, '0', STR_PAD_LEFT);
    }

    return implode('.', $segments);
}

// ---------------------------------------------------------------------------
// Internal URL helper
// ---------------------------------------------------------------------------

/**
 * Only URLs pointing to the same host as the site are accepted.
 *
 * @return string|WP_Error Absolute URL.
 */
function simplekitdownloads_validate_internal_url($url) {
    if (!is_string($url)) {
        return new WP_Error('skd_invalid_url', __('Please enter a URL.', 'simplekitdownloads'));
    }
    $url = trim($url);

    if ($url === '') {
        return new WP_Error('skd_invalid_url', __('Please enter a URL.', 'simplekitdownloads'));
    }
    if (strlen($url) > 1000) {
        return new WP_Error('skd_invalid_url', __('The URL is too long.', 'simplekitdownloads'));
    }
    if (strpos($url, '//') === 0) {
        return new WP_Error('skd_invalid_url', __('The URL must start with http:// or https://, or be a path starting with /.', 'simplekitdownloads'));
    }

    if (preg_match('#^https?://#i', $url)) {
        $parts = wp_parse_url($url);
        $home  = wp_parse_url(home_url('/'));

        if (empty($parts['host']) || empty($home['host'])) {
            return new WP_Error('skd_invalid_url', __('The URL is not valid.', 'simplekitdownloads'));
        }
        if (strtolower($parts['host']) !== strtolower($home['host'])) {
            return new WP_Error('skd_external_url', __('The URL must point to a file on this site (same domain).', 'simplekitdownloads'));
        }

        return esc_url_raw($url);
    }

    if ($url[0] !== '/') {
        return new WP_Error('skd_invalid_url', __('The URL must start with http:// or https://, or be a path starting with /.', 'simplekitdownloads'));
    }

    return esc_url_raw(trailingslashit(home_url('/')) . ltrim($url, '/'));
}

// ---------------------------------------------------------------------------
// File streaming (supports HTTP Range for resume and large files)
// ---------------------------------------------------------------------------
function simplekitdownloads_stream_file($path, $filename = '') {
    if (!is_file($path) || !is_readable($path)) {
        status_header(404);
        wp_die(esc_html__('File not found.', 'simplekitdownloads'), '', ['response' => 404]);
    }

    $size = filesize($path);
    if ($size === false) {
        status_header(404);
        wp_die(esc_html__('File not found.', 'simplekitdownloads'), '', ['response' => 404]);
    }

    $filename = $filename !== '' ? $filename : basename($path);
    $filename = str_replace(["\"", "\r", "\n"], '', $filename);
    $ascii    = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename);
    if ($ascii === '') {
        $ascii = 'download';
    }

    $start   = 0;
    $end     = ($size > 0) ? ($size - 1) : 0;
    $partial = false;

    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = sanitize_text_field(wp_unslash($_SERVER['HTTP_RANGE']));
        if (preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $matches)) {
            if ($matches[1] !== '') {
                $start = (int) $matches[1];
            }
            if ($matches[2] !== '') {
                $end = (int) $matches[2];
            }
            if ($start > $end || $start >= $size) {
                status_header(416);
                header('Content-Range: bytes */' . $size);
                exit;
            }
            if ($end >= $size) {
                $end = $size - 1;
            }
            $partial = true;
        }
    }

    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . max(0, $end - $start + 1));

    if ($partial) {
        status_header(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $handle = fopen($path, 'rb');
    if (!$handle) {
        return;
    }

    fseek($handle, $start);
    $remaining = $end - $start + 1;

    while ($remaining > 0 && !feof($handle)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
        $chunk = fread($handle, (int) min(8192, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary stream.
        if (function_exists('flush')) {
            flush();
        }
        $remaining -= strlen($chunk);
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($handle);
}
