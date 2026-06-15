<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// AJAX: Envio de formulário (frontend)
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitforms_submit', 'simplekitforms_ajax_submit');
add_action('wp_ajax_nopriv_simplekitforms_submit', 'simplekitforms_ajax_submit');

function simplekitforms_ajax_submit() {
    check_ajax_referer('simplekitforms_submit_nonce', 'nonce');

    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    if ($form_id <= 0) {
        wp_send_json_error(['message' => 'Invalid form.']);
    }

    $form = simplekitforms_get_form($form_id);
    if (!$form) {
        wp_send_json_error(['message' => 'Form not found.']);
    }

    $fields = json_decode($form->fields_json, true) ?: [];
    $data = [];
    $errors = [];

    foreach ($fields as $field) {
        $name     = $field['name'] ?? '';
        $label    = $field['label'] ?? $name;
        $type     = $field['type'] ?? 'text';
        $required = !empty($field['required']);

        if (empty($name)) {
            continue;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per type in switch below.
        $value = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : '';

        // Validar obrigatório
        if ($required) {
            if ($type === 'checkboxes') {
                if (!isset($_POST[$name]) || !is_array($_POST[$name]) || empty($_POST[$name])) {
                    $errors[] = sprintf('The field "%s" is required.', $label);
                    continue;
                }
            } else {
                $trimmed = is_string($value) ? trim($value) : $value;
                if (empty($trimmed) && $trimmed !== '0') {
                    $errors[] = sprintf('The field "%s" is required.', $label);
                    continue;
                }
            }
        }

        // Sanitizar conforme o tipo
        switch ($type) {
            case 'text':
                $subtype = $field['subtype'] ?? 'text';
                switch ($subtype) {
                    case 'email':
                        $value = sanitize_email($value);
                        if (!empty($value) && !is_email($value)) {
                            $errors[] = sprintf('The field "%s" must contain a valid email.', $label);
                        }
                        break;
                    case 'url':
                        $value = esc_url_raw($value);
                        break;
                    case 'number':
                        $value = floatval($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                break;
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;
            case 'checkboxes':
                if (is_array($value)) {
                    $sanitized = [];
                    foreach ($value as $v) {
                        $sanitized[] = sanitize_text_field($v);
                    }
                    $value = $sanitized;
                } else {
                    $value = [];
                }
                break;
            case 'radio':
            case 'select':
                $value = sanitize_text_field($value);
                break;
            default:
                $value = sanitize_text_field($value);
                break;
        }

        $data[$name] = $value;
    }

    if (!empty($errors)) {
        wp_send_json_error(['messages' => $errors]);
    }

    // Protection validation
    $email = '';
    $name  = '';
    foreach ($fields as $field) {
        $fname = $field['name'] ?? '';
        $ftype = $field['type'] ?? 'text';
        $fsub  = $field['subtype'] ?? '';
        if (empty($fname) || !isset($data[$fname])) {
            continue;
        }
        // Capture email for Akismet check
        if ($ftype === 'text' && $fsub === 'email' && empty($email)) {
            $email = $data[$fname];
        }
        // Capture first text field as name
        if ($ftype === 'text' && ($fsub === 'text' || empty($fsub)) && empty($name)) {
            $name = $data[$fname];
        }
    }

    $recaptcha_token = sanitize_text_field(wp_unslash($_POST['sf_recaptcha_token'] ?? ''));
    $protection      = simplekitforms_validate_protection($email, $name, $recaptcha_token);

    if (!$protection['valid']) {
        wp_send_json_error(['messages' => [$protection['error_message']]]);
    }

    // Salvar no banco
    global $wpdb;
    $table = simplekitforms_get_entries_table();
    $collect_ip = (bool) get_option('simplekitforms_collect_ip', true);
    $ip = $collect_ip ? simplekitforms_get_client_ip() : '';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert($table, [
        'form_id'   => $form_id,
        'data_json' => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
        'ip'        => $ip,
    ]);

    if ($inserted) {
        wp_send_json_success([
            'message' => !empty($form->thank_you_message)
                ? wp_kses_post($form->thank_you_message)
                : 'Thank you! Your form has been submitted successfully.',
        ]);
    } else {
        wp_send_json_error(['messages' => ['Error saving your response. Please try again.']]);
    }
}

// ---------------------------------------------------------------------------
// Obter IP do cliente
// ---------------------------------------------------------------------------
function simplekitforms_get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per part below.
        $parts = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        return sanitize_text_field($parts[0]);
    } else {
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}
