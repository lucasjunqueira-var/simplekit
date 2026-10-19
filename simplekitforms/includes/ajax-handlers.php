<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// REST API: Envio de formulário (frontend) - rota moderna substituindo admin-ajax
// ---------------------------------------------------------------------------
add_action('rest_api_init', 'simplekitforms_register_rest_submit');

function simplekitforms_register_rest_submit() {
    register_rest_route('simplekitforms/v1', '/submit', [
        'methods'             => 'POST',
        'callback'            => 'simplekitforms_rest_submit',
        'permission_callback' => '__return_true',
        'args'                => [
            'form_id' => [
                'required'          => true,
                'type'              => 'integer',
                'validate_callback' => function($param) { return is_numeric($param) && (int) $param > 0; },
                'sanitize_callback' => 'absint',
            ],
            'nonce' => [
                'required'          => true,
                'type'              => 'string',
                'validate_callback' => function($param) {
                    return wp_verify_nonce($param, 'simplekitforms_submit_nonce');
                },
            ],
        ],
    ]);
}

function simplekitforms_rest_submit($request) {
    $form_id = (int) $request->get_param('form_id');
    $form    = simplekitforms_get_form($form_id);

    if (!$form) {
        return new WP_Error('not_found', 'Form not found.', ['status' => 404]);
    }

    $fields      = json_decode($form->fields_json, true) ?: [];
    $data        = [];
    $errors      = [];
    $file_fields = [];

    // Collect all POST fields (works with both JSON and multipart/form-data payloads)
    $all_params = $request->get_params();
    // Fallback to $_POST for multipart/form-data if get_params() returns empty
    if (empty($all_params) || (count($all_params) === 1 && isset($all_params['_method']))) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $all_params = wp_unslash($_POST);
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    // Uploaded files (multipart/form-data).
    $files = $request->get_file_params();
    if (!is_array($files)) {
        $files = [];
    }

    foreach ($fields as $field) {
        $name     = $field['name'] ?? '';
        $label    = $field['label'] ?? $name;
        $type     = $field['type'] ?? 'text';
        $required = !empty($field['required']);

        if (empty($name)) {
            continue;
        }

        if ($type === 'file') {
            // Files are stored after the response is created, since they need its ID.
            if ($required) {
                $has_file = isset($files[$name]) && is_array($files[$name]) && (int) ($files[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (!$has_file) {
                    $errors[] = sprintf('The field "%s" is required.', $label);
                }
            }
            $file_fields[] = $field;
            continue;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per type below.
        $value = isset($all_params[$name]) ? $all_params[$name] : '';

        // Validate required
        if ($required) {
            if ($type === 'checkboxes') {
                if (!isset($all_params[$name]) || !is_array($all_params[$name]) || empty($all_params[$name])) {
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

        // Sanitize per type
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
        return new WP_Error('validation_error', 'Validation failed.', ['status' => 400, 'messages' => $errors]);
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
        if ($ftype === 'text' && $fsub === 'email' && empty($email)) {
            $email = $data[$fname];
        }
        if ($ftype === 'text' && ($fsub === 'text' || empty($fsub)) && empty($name)) {
            $name = $data[$fname];
        }
    }

    $recaptcha_token = sanitize_text_field($all_params['sf_recaptcha_token'] ?? '');
    $protection      = simplekitforms_validate_protection($email, $name, $recaptcha_token);

    if (!$protection['valid']) {
        return new WP_Error('protection_failed', $protection['error_message'], ['status' => 403]);
    }

    // Save to database
    global $wpdb;
    $table      = simplekitforms_get_entries_table();
    $collect_ip = (bool) get_option('simplekitforms_collect_ip', true);
    $ip         = $collect_ip ? simplekitforms_get_client_ip() : '';

    // Insert the response first so its ID can name the upload folder.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert($table, [
        'form_id'   => $form_id,
        'data_json' => '{}',
        'ip'        => $ip,
    ]);

    $entry_id = $inserted ? (int) $wpdb->insert_id : 0;

    if ($inserted) {
        // Store the uploaded files and complete the response data.
        foreach ($file_fields as $file_field) {
            $file_name = $file_field['name'];
            $file      = isset($files[$file_name]) ? $files[$file_name] : null;
            if (!$file) {
                continue;
            }

            $stored = simplekitforms_store_uploaded_file($file_field, $file, $form_id, $entry_id);
            if (is_wp_error($stored)) {
                simplekitforms_delete_entry_files($form_id, $entry_id);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete($table, ['id' => $entry_id], ['%d']);
                return new WP_Error('upload_error', $stored->get_error_message(), ['status' => 400, 'messages' => [$stored->get_error_message()]]);
            }

            if (is_array($stored)) {
                $data[$file_name] = $stored;
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update($table, ['data_json' => wp_json_encode($data, JSON_UNESCAPED_UNICODE)], ['id' => $entry_id], ['%s'], ['%d']);

        simplekitforms_send_form_notification($form, $data, $fields);

        return new WP_REST_Response([
            'success' => true,
            'message' => !empty($form->thank_you_message)
                ? wp_kses_post($form->thank_you_message)
                : 'Thank you! Your form has been submitted successfully.',
        ], 200);
    } else {
        return new WP_Error('db_error', 'Error saving your response. Please try again.', ['status' => 500]);
    }
}

// ---------------------------------------------------------------------------
// AJAX: Envio de formulário (frontend) - mantido como fallback
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

    $fields      = json_decode($form->fields_json, true) ?: [];
    $data        = [];
    $errors      = [];
    $file_fields = [];

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated in simplekitforms_store_uploaded_file().
    $files = $_FILES;

    foreach ($fields as $field) {
        $name     = $field['name'] ?? '';
        $label    = $field['label'] ?? $name;
        $type     = $field['type'] ?? 'text';
        $required = !empty($field['required']);

        if (empty($name)) {
            continue;
        }

        if ($type === 'file') {
            // Files are stored after the response is created, since they need its ID.
            if ($required) {
                $has_file = isset($files[$name]) && is_array($files[$name]) && (int) ($files[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (!$has_file) {
                    $errors[] = sprintf('The field "%s" is required.', $label);
                }
            }
            $file_fields[] = $field;
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

    // Insert the response first so its ID can name the upload folder.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert($table, [
        'form_id'   => $form_id,
        'data_json' => '{}',
        'ip'        => $ip,
    ]);

    $entry_id = $inserted ? (int) $wpdb->insert_id : 0;

    if ($inserted) {
        // Store the uploaded files and complete the response data.
        foreach ($file_fields as $file_field) {
            $file_name = $file_field['name'];
            $file      = isset($files[$file_name]) ? $files[$file_name] : null;
            if (!$file) {
                continue;
            }

            $stored = simplekitforms_store_uploaded_file($file_field, $file, $form_id, $entry_id);
            if (is_wp_error($stored)) {
                simplekitforms_delete_entry_files($form_id, $entry_id);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete($table, ['id' => $entry_id], ['%d']);
                wp_send_json_error(['messages' => [$stored->get_error_message()]]);
            }

            if (is_array($stored)) {
                $data[$file_name] = $stored;
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update($table, ['data_json' => wp_json_encode($data, JSON_UNESCAPED_UNICODE)], ['id' => $entry_id], ['%s'], ['%d']);

        simplekitforms_send_form_notification($form, $data, $fields);

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
// Enviar notificação por e-mail das respostas recebidas
// ---------------------------------------------------------------------------
/**
 * Sends the received form response to the notification e-mail configured on
 * the form. If the form contains one or more e-mail fields, the first one
 * is used as the "Reply-To" address of the message.
 *
 * @param object $form   Form row from the database.
 * @param array  $data   Sanitized submitted data (name => value).
 * @param array  $fields Form field definitions.
 * @return bool True when the e-mail was handed off to wp_mail(), false otherwise.
 */
function simplekitforms_send_form_notification($form, $data, $fields) {
    $recipient = isset($form->notify_email) ? sanitize_email($form->notify_email) : '';
    if (empty($recipient) || !is_email($recipient)) {
        return false;
    }

    // First e-mail field becomes the Reply-To address.
    $reply_to = '';
    foreach ($fields as $field) {
        $ftype = $field['type'] ?? 'text';
        $fsub  = $field['subtype'] ?? '';
        $fname = $field['name'] ?? '';
        if ($ftype === 'text' && $fsub === 'email' && !empty($fname) && !empty($data[$fname])) {
            $candidate = sanitize_email($data[$fname]);
            if (is_email($candidate)) {
                $reply_to = $candidate;
                break;
            }
        }
    }

    /* translators: %s: form title. */
    $subject = sprintf(__('New response: %s', 'simplekitforms'), $form->title);

    // --- Build the field rows (label => value) ---
    $rows = [];
    foreach ($fields as $field) {
        $fname = $field['name'] ?? '';
        if ($fname === '' || !array_key_exists($fname, $data)) {
            continue;
        }
        $label = $field['label'] ?? $fname;
        $value = $data[$fname];
        if (simplekitforms_is_file_value($value)) {
            // Show the stored file name; the file can be downloaded from the dashboard.
            $value = $value['original_name'] ?? ($value['stored_name'] ?? '');
        } elseif (is_array($value)) {
            $value = implode(', ', $value);
        }
        $rows[] = ['label' => (string) $label, 'value' => (string) $value];
    }

    // Footer shown right after the responses table: submission time and site URL.
    $meta_lines = [
        current_time('mysql'),
        home_url(),
    ];

    // --- HTML body (starts directly with the responses table) ---
    $html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333333;line-height:1.5;">';
    $html .= '<table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;">';
    foreach ($rows as $row) {
        $html .= '<tr>';
        $html .= '<td style="border:1px solid #dddddd;background:#f7f7f7;font-weight:bold;vertical-align:top;width:35%;">' . esc_html($row['label']) . '</td>';
        $html .= '<td style="border:1px solid #dddddd;">' . nl2br(esc_html($row['value'])) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $html .= '<p style="font-size:12px;color:#888888;">' . nl2br(esc_html(implode("\n", $meta_lines))) . '</p>';
    $html .= '</div>';

    // --- Plain text body (starts directly with the responses) ---
    $text = '';
    foreach ($rows as $row) {
        $text .= $row['label'] . ': ' . $row['value'] . "\n";
    }
    $text .= "\n" . implode("\n", $meta_lines);

    // --- Per-form SMTP settings (same fields as Simple Kit Mailing lists) ---
    $smtp = [
        'host'       => isset($form->smtp_host) ? trim((string) $form->smtp_host) : '',
        'port'       => (isset($form->smtp_port) && $form->smtp_port !== '') ? $form->smtp_port : '587',
        'username'   => isset($form->smtp_username) ? (string) $form->smtp_username : '',
        'password'   => isset($form->smtp_password) ? (string) $form->smtp_password : '',
        'encryption' => isset($form->smtp_encryption) ? (string) $form->smtp_encryption : 'tls',
    ];

    // If no SMTP server is configured, fall back to the WordPress mailer.
    if (empty($smtp['host'])) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        return wp_mail($recipient, $subject, $html, $headers);
    }

    // --- Send via PHPMailer using the form's SMTP settings ---
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host     = $smtp['host'];
        $mail->Port     = (int) $smtp['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->CharSet  = 'UTF-8';

        if ($smtp['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Sender: SMTP username when it is a valid address, otherwise the site admin e-mail.
        $from_email = is_email($smtp['username']) ? $smtp['username'] : get_bloginfo('admin_email');
        if (!is_email($from_email)) {
            $from_email = 'wordpress@' . wp_parse_url(home_url(), PHP_URL_HOST);
        }
        $from_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($recipient);

        if (!empty($reply_to)) {
            $mail->addReplyTo($reply_to);
        }

        $mail->Subject = $subject;
        $mail->msgHTML($html);
        $mail->AltBody = $text;

        $mail->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return 'SMTP error: ' . $mail->ErrorInfo;
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
