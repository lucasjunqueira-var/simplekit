<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// REST route: frontend submissions (supports multipart/form-data with files)
// ---------------------------------------------------------------------------
add_action('rest_api_init', 'simplekitregistration_register_rest_submit');

function simplekitregistration_register_rest_submit() {
    register_rest_route('simplekitregistration/v1', '/submit', [
        'methods'             => 'POST',
        'callback'            => 'simplekitregistration_rest_submit',
        'permission_callback' => '__return_true',
    ]);
}

function simplekitregistration_rest_submit($request) {
    $form_id = (int) $request->get_param('form_id');
    $nonce   = (string) $request->get_param('nonce');

    if (!wp_verify_nonce($nonce, 'simplekitregistration_submit_nonce')) {
        return new WP_Error('invalid_nonce', __('Invalid request. Please reload the page and try again.', 'simplekitregistration'), ['status' => 403]);
    }

    $form = simplekitregistration_get_form($form_id);
    if (!$form) {
        return new WP_Error('not_found', __('Form not found.', 'simplekitregistration'), ['status' => 404]);
    }

    $params = $request->get_params();
    if (!is_array($params)) {
        $params = [];
    }
    // Fallback for multipart bodies where get_params() may be empty.
    if (empty($params)) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above.
        $params = wp_unslash($_POST);
    }
    unset($params['form_id'], $params['nonce'], $params['_method']);

    $files = $request->get_file_params();
    if (!is_array($files)) {
        $files = [];
    }

    $result = simplekitregistration_process_submission($form, $params, $files);
    if (is_wp_error($result)) {
        $data = $result->get_error_data();
        $messages = is_array($data) && isset($data['messages']) ? $data['messages'] : [$result->get_error_message()];
        return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 400, 'messages' => $messages]);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => $result['message'],
    ], 200);
}

// ---------------------------------------------------------------------------
// AJAX fallback (admin-ajax.php)
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_submit', 'simplekitregistration_ajax_submit');
add_action('wp_ajax_nopriv_simplekitregistration_submit', 'simplekitregistration_ajax_submit');

function simplekitregistration_ajax_submit() {
    check_ajax_referer('simplekitregistration_submit_nonce', 'nonce');

    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $form    = simplekitregistration_get_form($form_id);
    if (!$form) {
        wp_send_json_error(['messages' => [__('Form not found.', 'simplekitregistration')]]);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by check_ajax_referer above.
    $params = wp_unslash($_POST);
    unset($params['form_id'], $params['nonce'], $params['action'], $params['_method']);

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- file array validated in simplekitregistration_store_uploaded_file().
    $files = $_FILES;

    $result = simplekitregistration_process_submission($form, $params, $files);
    if (is_wp_error($result)) {
        $data     = $result->get_error_data();
        $messages = is_array($data) && isset($data['messages']) ? $data['messages'] : [$result->get_error_message()];
        wp_send_json_error(['messages' => $messages]);
    }

    wp_send_json_success(['message' => $result['message']]);
}

// ---------------------------------------------------------------------------
// Shared submission processor
// ---------------------------------------------------------------------------
/**
 * Validate a submission, store the response and its uploaded files.
 *
 * @param object $form   Form row.
 * @param array  $params Submitted scalar values (already unslashed).
 * @param array  $files  $_FILES style array.
 * @return array|WP_Error ['message' => string] on success.
 */
function simplekitregistration_process_submission($form, $params, $files) {
    $fields = json_decode($form->fields_json, true) ?: [];
    $data   = [];
    $errors = [];
    $file_fields = [];

    foreach ($fields as $index => $field) {
        $name     = $field['name'] ?? '';
        $label    = $field['label'] ?? $name;
        $type     = $field['type'] ?? 'text';
        $required = !empty($field['required']);

        if ($name === '') {
            continue;
        }

        if ($type === 'file') {
            $file_fields[] = $field;

            if ($required) {
                $has_file = isset($files[$name]) && is_array($files[$name]) && (int) ($files[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (!$has_file) {
                    /* translators: %s: field label. */
                    $errors[] = sprintf(__('The field "%s" is required.', 'simplekitregistration'), $label);
                }
            }
            continue;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per type below.
        $value = isset($params[$name]) ? $params[$name] : '';

        if ($required) {
            if ($type === 'checkboxes') {
                if (!is_array($value) || empty($value)) {
                    /* translators: %s: field label. */
                    $errors[] = sprintf(__('The field "%s" is required.', 'simplekitregistration'), $label);
                    continue;
                }
            } else {
                $trimmed = is_string($value) ? trim($value) : $value;
                if ((empty($trimmed) && $trimmed !== '0') || (!is_string($value) && !is_array($value) && $value === null)) {
                    /* translators: %s: field label. */
                    $errors[] = sprintf(__('The field "%s" is required.', 'simplekitregistration'), $label);
                    continue;
                }
            }
        }

        switch ($type) {
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;

            case 'checkboxes':
                if (is_array($value)) {
                    $clean = [];
                    foreach ($value as $item) {
                        $clean[] = sanitize_text_field($item);
                    }
                    $value = $clean;
                } else {
                    $value = [];
                }
                break;

            case 'radio':
            case 'select':
                $value = sanitize_text_field($value);
                break;

            case 'text':
            default:
                $subtype = $field['subtype'] ?? 'text';
                switch ($subtype) {
                    case 'email':
                        $value = sanitize_email($value);
                        if ($value !== '' && !is_email($value)) {
                            /* translators: %s: field label. */
                            $errors[] = sprintf(__('The field "%s" must contain a valid email.', 'simplekitregistration'), $label);
                        }
                        break;
                    case 'url':
                        $value = esc_url_raw($value);
                        break;
                    case 'number':
                        $value = ('' === $value) ? '' : floatval($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                break;
        }

        $data[$name] = $value;
    }

    if (!empty($errors)) {
        return new WP_Error('validation_error', __('Validation failed.', 'simplekitregistration'), ['messages' => $errors]);
    }

    global $wpdb;
    $table = simplekitregistration_entries_table();

    // Insert the response first so its ID can name the upload folder.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert($table, [
        'form_id'   => (int) $form->id,
        'data_json' => '{}',
        'ip'        => simplekitregistration_get_client_ip(),
    ]);

    if (!$inserted) {
        return new WP_Error('db_error', __('Error saving your response. Please try again.', 'simplekitregistration'));
    }

    $entry_id = (int) $wpdb->insert_id;

    // Store uploaded files.
    foreach ($file_fields as $field) {
        $name = $field['name'];
        $file = isset($files[$name]) ? $files[$name] : null;
        if (!$file) {
            continue;
        }

        $stored = simplekitregistration_store_uploaded_file($field, $file, $form->code, $entry_id);
        if (is_wp_error($stored)) {
            // Roll back the response and any files already stored for it.
            simplekitregistration_delete_entry_files($form->code, $entry_id);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete($table, ['id' => $entry_id], ['%d']);
            return new WP_Error('upload_error', $stored->get_error_message(), ['messages' => [$stored->get_error_message()]]);
        }

        if (is_array($stored)) {
            $data[$name] = $stored;
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated = $wpdb->update(
        $table,
        ['data_json' => wp_json_encode($data, JSON_UNESCAPED_UNICODE)],
        ['id' => $entry_id],
        ['%s'],
        ['%d']
    );

    if ($updated === false) {
        simplekitregistration_delete_entry_files($form->code, $entry_id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($table, ['id' => $entry_id], ['%d']);
        return new WP_Error('db_error', __('Error saving your response. Please try again.', 'simplekitregistration'));
    }

    $message = !empty($form->thank_you_message)
        ? $form->thank_you_message
        : __('Thank you! Your registration has been submitted successfully.', 'simplekitregistration');

    return ['message' => wp_kses_post($message)];
}
