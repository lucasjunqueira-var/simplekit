<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Query vars used by the restricted area detail page
// ---------------------------------------------------------------------------
add_filter('query_vars', 'simplekitregistration_register_query_vars');

function simplekitregistration_register_query_vars($vars) {
    $vars[] = 'skr_form';
    $vars[] = 'skr_entry';
    $vars[] = 'skr_page';
    return $vars;
}

// ---------------------------------------------------------------------------
// Permission-checked file download endpoint
//
// Files are never served directly from the uploads folder. This endpoint
// validates the nonce and either the WordPress capability or the evaluator
// session (and its access to the form) before streaming the file.
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_simplekitregistration_download_file', 'simplekitregistration_handle_file_download');
add_action('admin_post_simplekitregistration_download_file', 'simplekitregistration_handle_file_download');

function simplekitregistration_handle_file_download() {
    $entry_id   = isset($_GET['entry']) ? (int) $_GET['entry'] : 0;
    $field_name = isset($_GET['field']) ? sanitize_text_field(wp_unslash($_GET['field'])) : '';

    if ($entry_id <= 0 || $field_name === '') {
        wp_die(esc_html__('Invalid file request.', 'simplekitregistration'), '', ['response' => 400]);
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'simplekitregistration_download_file_' . $entry_id)) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'), '', ['response' => 403]);
    }

    $entry = simplekitregistration_get_entry($entry_id);
    if (!$entry) {
        wp_die(esc_html__('File not found.', 'simplekitregistration'), '', ['response' => 404]);
    }

    // Allow WordPress administrators and authorized evaluators only.
    $allowed = current_user_can('manage_options');
    if (!$allowed) {
        $evaluator = simplekitregistration_get_current_evaluator();
        $allowed   = $evaluator && simplekitregistration_evaluator_can_access($evaluator, $entry->form_id);
    }
    if (!$allowed) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'), '', ['response' => 403]);
    }

    $data  = json_decode($entry->data_json, true) ?: [];
    $value = $data[$field_name] ?? null;
    if (!simplekitregistration_is_file_value($value)) {
        wp_die(esc_html__('File not found.', 'simplekitregistration'), '', ['response' => 404]);
    }

    $root = realpath(simplekitregistration_uploads_root_path());
    $path = realpath(simplekitregistration_uploads_root_path() . '/' . ltrim($value['subpath'], '/'));
    if ($root === false || $path === false || strpos($path, $root) !== 0 || !is_file($path)) {
        wp_die(esc_html__('File not found.', 'simplekitregistration'), '', ['response' => 404]);
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

// ---------------------------------------------------------------------------
// Restricted entry detail page (opened in a new tab)
// ---------------------------------------------------------------------------
add_action('template_redirect', 'simplekitregistration_render_entry_detail_page');

function simplekitregistration_render_entry_detail_page() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view parameter, no nonce needed.
    if (is_admin() || !isset($_GET['skr_entry'])) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view parameter, cast to int below.
    $entry_id = (int) $_GET['skr_entry'];
    $evaluator = simplekitregistration_get_current_evaluator();

    nocache_headers();

    if (!$evaluator || $entry_id <= 0) {
        simplekitregistration_output_detail_page(
            __('Access denied', 'simplekitregistration'),
            '<p>' . esc_html__('You must log in to view this response.', 'simplekitregistration') . '</p>'
        );
    }

    $entry = simplekitregistration_get_entry($entry_id);
    if (!$entry) {
        simplekitregistration_output_detail_page(
            __('Response not found', 'simplekitregistration'),
            '<p>' . esc_html__('The requested response does not exist.', 'simplekitregistration') . '</p>'
        );
    }

    if (!simplekitregistration_evaluator_can_access($evaluator, $entry->form_id)) {
        simplekitregistration_output_detail_page(
            __('Access denied', 'simplekitregistration'),
            '<p>' . esc_html__('You are not allowed to view this response.', 'simplekitregistration') . '</p>'
        );
    }

    $form   = simplekitregistration_get_form($entry->form_id);
    $fields = $form ? (json_decode($form->fields_json, true) ?: []) : [];
    $data   = json_decode($entry->data_json, true) ?: [];

    $rows = '';
    foreach ($fields as $field) {
        $label = $field['label'] ?? ($field['name'] ?? '');
        $value = $data[$field['name'] ?? ''] ?? '';
        $rows .= '<tr><th>' . esc_html($label) . '</th><td>' . simplekitregistration_format_field_value($value, $field, 0, $entry->id) . '</td></tr>';
    }
    $rows .= '<tr><th>' . esc_html(simplekitregistration_evaluator_text('entries_col_datetime')) . '</th><td>' . esc_html($entry->created_at) . '</td></tr>';

    $body  = '<p><strong>' . esc_html(simplekitregistration_evaluator_text('detail_form_label')) . '</strong> ' . esc_html($form ? $form->title : '') . '</p>';
    $body .= '<h2>' . esc_html(sprintf(simplekitregistration_evaluator_text('detail_heading'), $entry->id)) . '</h2>';
    $body .= '<table class="skr-detail">' . $rows . '</table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows are escaped individually above.

    // Messages of this response, listed right below it.
    $redirect_url = remove_query_arg(['skr_message_added', 'skr_message_error']);
    $body .= '<div class="skr-messages">';
    $body .= '<h2>' . esc_html(simplekitregistration_evaluator_text('messages_heading')) . '</h2>';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status parameter, no nonce needed.
    if (isset($_GET['skr_message_error'])) {
        $body .= '<p class="skr-message-error">' . esc_html(simplekitregistration_evaluator_text('message_error')) . '</p>';
    }
    $body .= simplekitregistration_render_messages_list($entry->id, true);
    $body .= simplekitregistration_render_message_form($entry->id, $redirect_url);
    $body .= '</div>';

    simplekitregistration_output_detail_page(simplekitregistration_evaluator_text('detail_title'), $body);
}

/**
 * Print a standalone page (outside the theme) and stop.
 */
function simplekitregistration_output_detail_page($title, $body_html) {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html($title); ?> &mdash; <?php echo esc_html(get_bloginfo('name')); ?></title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin:0;padding:24px;background:#f6f7f7;color:#1d2327;}
.skr-wrap{max-width:800px;margin:0 auto;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:28px;}
.skr-wrap h1{margin-top:0;font-size:22px;}
table.skr-detail{width:100%;border-collapse:collapse;}
table.skr-detail th{text-align:left;vertical-align:top;width:30%;padding:10px;border-bottom:1px solid #eee;background:#fafafa;}
table.skr-detail td{padding:10px;border-bottom:1px solid #eee;word-break:break-word;}
.skr-back{display:inline-block;margin-top:18px;color:#2271b1;text-decoration:none;}
.skr-messages{margin-top:24px;border-top:1px solid #dcdcde;padding-top:16px;}
.skr-message-list{margin-bottom:14px;}
.skr-message-item{border:1px solid #e5e5e5;border-radius:6px;padding:10px 12px;margin-bottom:8px;background:#fafafa;}
.skr-message-meta{margin:0 0 4px;font-size:12px;color:#666;}
.skr-message-text{margin:0;white-space:pre-wrap;word-break:break-word;}
.skr-message-empty{margin:0 0 12px;color:#666;}
.skr-message-error{color:#b32d2e;}
.skr-message-form label{display:block;font-weight:600;margin-bottom:4px;}
.skr-message-form textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;}
</style>
</head>
<body>
<div class="skr-wrap">
<h1><?php echo esc_html($title); ?></h1>
<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the caller. ?>
<p><a class="skr-back" href="javascript:window.close();"><?php echo esc_html(simplekitregistration_evaluator_text('detail_close')); ?></a></p>
</div>
</body>
</html>
    <?php
    exit;
}

// ---------------------------------------------------------------------------
// Form rendering (public forms shown to visitors)
// ---------------------------------------------------------------------------
function simplekitregistration_render_form_by_attributes($attributes) {
    $form_id = isset($attributes['form_id']) ? (int) $attributes['form_id'] : 0;
    $code    = isset($attributes['code']) ? sanitize_text_field($attributes['code']) : '';

    $form = null;
    if ($form_id > 0) {
        $form = simplekitregistration_get_form($form_id);
    } elseif ($code !== '') {
        $form = simplekitregistration_get_form_by_code($code);
    }

    if (!$form) {
        return '<p>' . esc_html__('Form not found.', 'simplekitregistration') . '</p>';
    }

    return simplekitregistration_render_form($form);
}

function simplekitregistration_render_form($form) {
    $fields      = json_decode($form->fields_json, true) ?: [];
    $submit_text = $form->submit_text !== '' ? $form->submit_text : __('Submit', 'simplekitregistration');
    $thank_you   = $form->thank_you_message !== ''
        ? wp_kses_post($form->thank_you_message)
        : __('Thank you! Your registration has been submitted successfully.', 'simplekitregistration');

    $rest_url   = rest_url('simplekitregistration/v1/submit');
    $rest_nonce = wp_create_nonce('wp_rest');
    $submit_nonce = wp_create_nonce('simplekitregistration_submit_nonce');

    // A form with file fields always uses multipart/form-data.
    $has_files = false;
    foreach ($fields as $field) {
        if (($field['type'] ?? '') === 'file') {
            $has_files = true;
            break;
        }
    }

    ob_start();
    ?>
    <div class="skr-form-block" data-form-id="<?php echo (int) $form->id; ?>" data-rest-url="<?php echo esc_url($rest_url); ?>" data-rest-nonce="<?php echo esc_attr($rest_nonce); ?>" data-submit-nonce="<?php echo esc_attr($submit_nonce); ?>">
        <h3><?php echo esc_html($form->title); ?></h3>
        <form class="skr-form" method="post" <?php echo $has_files ? 'enctype="multipart/form-data"' : ''; ?>>
            <?php
            foreach ($fields as $field) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the renderer.
                echo simplekitregistration_render_frontend_field($field);
            }
            ?>
            <div class="skr-field">
                <button type="button" class="skr-submit"><?php echo esc_html($submit_text); ?></button>
            </div>
            <div class="skr-message" role="status" aria-live="polite"></div>
        </form>
        <span class="skr-thank-you" hidden><?php echo $thank_you; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() applied above. ?></span>
    </div>
    <?php
    // The submission logic lives in assets/frontend.js. Inline scripts inside
    // dynamic block output are processed by the_content filters (wpautop,
    // wptexturize) and can be corrupted, breaking the page with a JS error.
    return ob_get_clean();
}

function simplekitregistration_render_frontend_field($field) {
    $type        = $field['type'] ?? 'text';
    $subtype     = $field['subtype'] ?? '';
    $label       = $field['label'] ?? '';
    $name        = $field['name'] ?? '';
    $required    = !empty($field['required']);
    $placeholder = $field['placeholder'] ?? '';
    $options     = $field['options'] ?? [];
    $required_mark = $required ? ' <span class="required-star">*</span>' : '';

    if ($name === '') {
        return '';
    }

    $html = '<div class="skr-field">';

    switch ($type) {
        case 'textarea':
            $html .= '<label for="skr-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<textarea id="skr-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . '></textarea>';
            break;

        case 'checkboxes':
            $html .= '<div class="skr-field-label"><strong>' . esc_html($label) . '</strong>' . $required_mark . '</div>';
            foreach ($options as $option) {
                $html .= '<label class="skr-choice"><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($option) . '"> ' . esc_html($option) . '</label>';
            }
            break;

        case 'radio':
            $html .= '<div class="skr-field-label"><strong>' . esc_html($label) . '</strong>' . $required_mark . '</div>';
            foreach ($options as $option) {
                $html .= '<label class="skr-choice"><input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($option) . '" ' . ($required ? 'required' : '') . '> ' . esc_html($option) . '</label>';
            }
            break;

        case 'select':
            $html .= '<label for="skr-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<select id="skr-' . esc_attr($name) . '" name="' . esc_attr($name) . '" ' . ($required ? 'required' : '') . '>';
            $html .= '<option value="">-- ' . esc_html__('Select', 'simplekitregistration') . ' --</option>';
            foreach ($options as $option) {
                $html .= '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
            }
            $html .= '</select>';
            break;

        case 'file':
            $allowed = simplekitregistration_allowed_extensions($field['allowed_ext'] ?? '');
            $accept  = '';
            if (!empty($allowed)) {
                $accept_values = [];
                foreach ($allowed as $extension) {
                    $accept_values[] = '.' . $extension;
                }
                $accept = ' accept="' . esc_attr(implode(',', $accept_values)) . '"';
            }
            $html .= '<label for="skr-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<input type="file" id="skr-' . esc_attr($name) . '" name="' . esc_attr($name) . '"' . $accept . ' ' . ($required ? 'required' : '') . '>';
            if (!empty($field['description'])) {
                $html .= '<p class="skr-field-description">' . esc_html($field['description']) . '</p>';
            }
            break;

        case 'text':
        default:
            $input_type = in_array($subtype, ['email', 'password', 'url', 'number', 'tel'], true) ? $subtype : 'text';
            $html .= '<label for="skr-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<input type="' . esc_attr($input_type) . '" id="skr-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . '>';
            break;
    }

    $html .= '</div>';
    return $html;
}

// ---------------------------------------------------------------------------
// Restricted area block
// ---------------------------------------------------------------------------
function simplekitregistration_render_access_block() {
    $evaluator = simplekitregistration_get_current_evaluator();

    if (!$evaluator) {
        return simplekitregistration_render_login_form();
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view parameter; per-evaluator access is checked afterwards.
    $form_id = isset($_GET['skr_form']) ? (int) $_GET['skr_form'] : 0;
    if ($form_id > 0) {
        return simplekitregistration_render_evaluator_entries($evaluator, $form_id);
    }

    return simplekitregistration_render_evaluator_form_list($evaluator);
}

function simplekitregistration_render_login_form() {
    // Prefer the current permalink when available.
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url('/');
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- status parameter.
    $has_error = isset($_GET['skr_login_error']);

    ob_start();
    ?>
    <div class="skr-access">
        <h3><?php echo esc_html(simplekitregistration_evaluator_text('login_heading')); ?></h3>
        <?php if ($has_error) : ?>
            <div class="skr-message error"><?php echo esc_html(simplekitregistration_evaluator_text('login_error')); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('simplekitregistration_login', 'skr_login_nonce'); ?>
            <input type="hidden" name="action" value="simplekitregistration_login">
            <input type="hidden" name="skr_redirect" value="<?php echo esc_url($current_url); ?>">
            <div class="skr-field">
                <label for="skr-login-email"><?php echo esc_html(simplekitregistration_evaluator_text('login_email_label')); ?></label>
                <input type="email" id="skr-login-email" name="skr_email" required>
            </div>
            <div class="skr-field">
                <label for="skr-login-password"><?php echo esc_html(simplekitregistration_evaluator_text('login_password_label')); ?></label>
                <input type="password" id="skr-login-password" name="skr_password" required>
            </div>
            <button type="submit" class="skr-submit"><?php echo esc_html(simplekitregistration_evaluator_text('login_button')); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function simplekitregistration_render_evaluator_form_list($evaluator) {
    $ids   = json_decode($evaluator->forms_json, true);
    $ids   = is_array($ids) ? array_map('intval', $ids) : [];
    $forms = [];
    foreach ($ids as $id) {
        $form = simplekitregistration_get_form($id);
        if ($form) {
            $forms[] = $form;
        }
    }

    $logout_url = wp_nonce_url(add_query_arg('skr_logout', '1', get_permalink() ?: home_url('/')), 'simplekitregistration_logout');

    ob_start();
    ?>
    <div class="skr-access">
        <h3><?php echo esc_html(simplekitregistration_evaluator_text('forms_heading')); ?></h3>
        <p class="skr-access-meta">
            <?php echo esc_html(sprintf(simplekitregistration_evaluator_text('forms_logged_in_as'), $evaluator->email)); ?>
            &middot; <a href="<?php echo esc_url($logout_url); ?>"><?php echo esc_html(simplekitregistration_evaluator_text('logout_link')); ?></a>
        </p>
        <?php if (empty($forms)) : ?>
            <p><?php echo esc_html(simplekitregistration_evaluator_text('forms_empty')); ?></p>
        <?php else : ?>
            <ul class="skr-form-list">
                <?php foreach ($forms as $form) : ?>
                    <?php $link = add_query_arg('skr_form', (int) $form->id, get_permalink() ?: home_url('/')); ?>
                    <li><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($form->title); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function simplekitregistration_render_evaluator_entries($evaluator, $form_id) {
    if (!simplekitregistration_evaluator_can_access($evaluator, $form_id)) {
        return '<div class="skr-access"><div class="skr-message error">' . esc_html__('You are not allowed to view the responses of this form.', 'simplekitregistration') . '</div></div>';
    }

    $form = simplekitregistration_get_form($form_id);
    if (!$form) {
        return '<div class="skr-access"><p>' . esc_html__('Form not found.', 'simplekitregistration') . '</p></div>';
    }

    $fields = json_decode($form->fields_json, true) ?: [];
    $per_page = 15;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination parameter.
    $current_page = isset($_GET['skr_page']) ? max(1, (int) $_GET['skr_page']) : 1;
    $offset = ($current_page - 1) * $per_page;

    global $wpdb;
    $table = simplekitregistration_entries_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $total = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE form_id = %d', $table, $form_id));
    $total_pages = (int) ceil($total / $per_page);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d',
        $table,
        $form_id,
        $per_page,
        $offset
    ));

    $base_url   = get_permalink() ?: home_url('/');
    $list_url   = remove_query_arg(['skr_form', 'skr_page'], $base_url);
    $logout_url = wp_nonce_url(add_query_arg('skr_logout', '1', $base_url), 'simplekitregistration_logout');

    ob_start();
    ?>
    <div class="skr-access">
        <p class="skr-access-meta">
            <a href="<?php echo esc_url($list_url); ?>">&larr; <?php echo esc_html(simplekitregistration_evaluator_text('back_to_forms')); ?></a>
            &middot; <a href="<?php echo esc_url($logout_url); ?>"><?php echo esc_html(simplekitregistration_evaluator_text('logout_link')); ?></a>
        </p>
        <h3><?php echo esc_html(sprintf(simplekitregistration_evaluator_text('entries_heading'), $form->title)); ?></h3>

        <?php if (empty($entries)) : ?>
            <p><?php echo esc_html(simplekitregistration_evaluator_text('entries_empty')); ?></p>
        <?php else : ?>
            <table class="skr-entries">
                <thead>
                    <tr>
                        <th><?php echo esc_html(simplekitregistration_evaluator_text('entries_col_id')); ?></th>
                        <?php foreach ($fields as $field) : ?>
                            <th><?php echo esc_html($field['label'] ?? ($field['name'] ?? '')); ?></th>
                        <?php endforeach; ?>
                        <th><?php echo esc_html(simplekitregistration_evaluator_text('entries_col_datetime')); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry) : ?>
                        <?php
                        $data = json_decode($entry->data_json, true) ?: [];
                        $detail_url = add_query_arg([
                            'skr_entry' => (int) $entry->id,
                            'skr_form'  => (int) $form->id,
                        ], $base_url);
                        ?>
                        <tr>
                            <td><?php echo (int) $entry->id; ?></td>
                            <?php foreach ($fields as $field) : ?>
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally. ?>
                                <td><?php echo simplekitregistration_format_field_value($data[$field['name'] ?? ''] ?? '', $field, 60, $entry->id); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo esc_html($entry->created_at); ?></td>
                            <td><a class="skr-button" href="<?php echo esc_url($detail_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(simplekitregistration_evaluator_text('entries_col_details')); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="skr-pagination">
                    <?php
                    $pagination_args = ['skr_form' => (int) $form->id];
                    if ($current_page > 1) {
                        echo '<a class="skr-button" href="' . esc_url(add_query_arg(array_merge($pagination_args, ['skr_page' => $current_page - 1]), $base_url)) . '">&lsaquo;</a> ';
                    }
                    echo esc_html(sprintf(simplekitregistration_evaluator_text('pagination'), $current_page, $total_pages));
                    if ($current_page < $total_pages) {
                        echo ' <a class="skr-button" href="' . esc_url(add_query_arg(array_merge($pagination_args, ['skr_page' => $current_page + 1]), $base_url)) . '">&rsaquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
