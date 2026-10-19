<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Client IP helper
// ---------------------------------------------------------------------------
function simplekitregistration_get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per part below.
        $parts = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        return sanitize_text_field(trim($parts[0]));
    }
    return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
}

// ---------------------------------------------------------------------------
// Field type labels (used by the builder and the help page)
// ---------------------------------------------------------------------------
function simplekitregistration_get_field_type_label($type, $subtype = '') {
    if ($type === 'text' && !empty($subtype)) {
        $labels = [
            'text'     => 'Text',
            'email'    => 'E-mail',
            'password' => 'Password',
            'url'      => 'URL',
            'number'   => 'Number',
            'tel'      => 'Phone',
        ];
        return $labels[$subtype] ?? 'Text';
    }

    $labels = [
        'text'       => 'Text',
        'textarea'   => 'Textarea',
        'checkboxes' => 'Checkboxes',
        'radio'      => 'Radio buttons',
        'select'     => 'Dropdown',
        'file'       => 'File',
    ];
    return $labels[$type] ?? 'Text';
}

// ---------------------------------------------------------------------------
// Uploaded file value helpers
//
// A file field value is stored in data_json as:
//   [
//     'original_name' => 'cv.pdf',
//     'stored_name'   => 'cv.pdf',
//     'subpath'       => '<form-code>/<entry-id>/cv.pdf',
//     'size'          => 12345,
//   ]
// ---------------------------------------------------------------------------
function simplekitregistration_is_file_value($value) {
    return is_array($value) && isset($value['subpath']);
}

function simplekitregistration_file_value_url($value) {
    if (!simplekitregistration_is_file_value($value)) {
        return '';
    }
    return simplekitregistration_uploads_root_url() . '/' . ltrim($value['subpath'], '/');
}

/**
 * Build a download link for a stored file value.
 *
 * Files are never embedded in the page: only a link is rendered, regardless of
 * the file type.
 */
/**
 * URL of the permission-checked download endpoint for a stored file.
 *
 * Files are never served directly from the uploads folder for evaluators;
 * the endpoint validates the evaluator session before streaming the file.
 */
function simplekitregistration_file_download_url($entry_id, $field_name) {
    $url = add_query_arg(
        [
            'action' => 'simplekitregistration_download_file',
            'entry'  => (int) $entry_id,
            'field'  => $field_name,
        ],
        admin_url('admin-post.php')
    );

    return wp_nonce_url($url, 'simplekitregistration_download_file_' . (int) $entry_id);
}

function simplekitregistration_file_value_html($value, $entry_id = 0, $field_name = '') {
    if (!simplekitregistration_is_file_value($value)) {
        return '';
    }

    $name = $value['original_name'] ?? ($value['stored_name'] ?? '');

    if ($entry_id > 0 && $field_name !== '') {
        $url = simplekitregistration_file_download_url($entry_id, $field_name);
    } else {
        $url = simplekitregistration_file_value_url($value);
    }

    if ($url === '') {
        return esc_html($name);
    }

    return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($name) . '</a>';
}

/**
 * Format a field value for display. Returns already escaped HTML.
 */
function simplekitregistration_format_field_value($value, $field, $truncate = 0, $entry_id = 0) {
    // File values render as a download link and must never be truncated,
    // otherwise the HTML markup could be broken mid-tag.
    if (simplekitregistration_is_file_value($value)) {
        return simplekitregistration_file_value_html($value, $entry_id, $field['name'] ?? '');
    }

    if (is_array($value)) {
        $html = implode(', ', array_map('esc_html', $value));
    } else {
        $html = esc_html((string) $value);
    }

    if ($truncate > 0 && mb_strlen($html) > $truncate) {
        $html = mb_substr($html, 0, $truncate) . '...';
    }

    return $html;
}
