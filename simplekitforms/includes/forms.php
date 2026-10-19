<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Helpers de banco de dados
// ---------------------------------------------------------------------------
function simplekitforms_get_forms_table() {
    global $wpdb;
    return $wpdb->prefix . 'sf_forms';
}

function simplekitforms_get_entries_table() {
    global $wpdb;
    return $wpdb->prefix . 'sf_entries';
}

function simplekitforms_get_all_forms() {
    global $wpdb;
    $table = simplekitforms_get_forms_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM %i ORDER BY created_at DESC", $table)
    );
}

function simplekitforms_get_form($id) {
    global $wpdb;
    $table = simplekitforms_get_forms_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, $id)
    );
}

// ---------------------------------------------------------------------------
// Exibir aviso de erro caso a última gravação de formulário tenha falhado
// ---------------------------------------------------------------------------
function simplekitforms_render_save_error_notice() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed.
    if (!isset($_GET['sf_save_error'])) {
        return;
    }
    $key     = 'simplekitforms_save_error_' . get_current_user_id();
    $message = get_transient($key);
    delete_transient($key);
    ?>
    <div class="notice notice-error">
        <p><strong>Unable to save the form.</strong> The changes were not stored.</p>
        <?php if (!empty($message)) : ?>
            <p><code><?php echo esc_html($message); ?></code></p>
        <?php endif; ?>
    </div>
    <?php
}

function simplekitforms_get_entries_count($form_id) {
    global $wpdb;
    $table = simplekitforms_get_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM %i WHERE form_id = %d", $table, $form_id)
    );
}

// ---------------------------------------------------------------------------
// Processar formulário (create/update)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitforms_save_form', 'simplekitforms_handle_save_form');

function simplekitforms_handle_save_form() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }
    check_admin_referer('simplekitforms_save_form', '_sfnonce');

    $form_id    = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $title      = sanitize_text_field(wp_unslash($_POST['sf_title'] ?? ''));
    $thanks     = sanitize_textarea_field(wp_unslash($_POST['sf_thanks'] ?? ''));
    $submit_txt = sanitize_text_field(wp_unslash($_POST['sf_submit_text'] ?? 'Submit'));
    $notify_email = sanitize_email(wp_unslash($_POST['sf_notify_email'] ?? ''));

    // Validate notification e-mail (optional, but must be valid when provided)
    if (!empty($notify_email) && !is_email($notify_email)) {
        $notify_email = '';
    }

    // SMTP settings used to send the notification e-mail (same fields as the
    // per-list SMTP settings in the Simple Kit Mailing plugin).
    $smtp_host       = sanitize_text_field(wp_unslash($_POST['sf_smtp_host'] ?? ''));
    $smtp_port       = sanitize_text_field(wp_unslash($_POST['sf_smtp_port'] ?? '587'));
    $smtp_username   = sanitize_text_field(wp_unslash($_POST['sf_smtp_username'] ?? ''));
    $smtp_password   = sanitize_text_field(wp_unslash($_POST['sf_smtp_password'] ?? ''));
    $smtp_encryption = sanitize_text_field(wp_unslash($_POST['sf_smtp_encryption'] ?? 'tls'));
    if (!in_array($smtp_encryption, ['tls', 'ssl', 'none'], true)) {
        $smtp_encryption = 'tls';
    }
    // A blank password means "keep the currently stored password".
    $smtp_password_changed = ($smtp_password !== '');

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sf_fields is a nested array; sanitized per field in the loop below.
    $fields_raw = wp_unslash($_POST['sf_fields'] ?? '');

    // Validar título
    if (empty($title)) {
        wp_safe_redirect(add_query_arg(['error' => 'empty_title'], wp_get_referer()));
        exit;
    }

    // Processar fields_json
    $fields_json = '';
    if (is_array($fields_raw)) {
        $sanitized = [];
        foreach ($fields_raw as $index => $field) {
            $type  = sanitize_text_field($field['type'] ?? 'text');
            $label = sanitize_text_field($field['label'] ?? '');
            if (empty($label)) {
                continue;
            }
            $name = sanitize_key($field['name'] ?? 'field_' . $index);
            if (empty($name)) {
                $name = 'field_' . $index;
            }
            $entry = [
                'type'     => $type,
                'label'    => $label,
                'name'     => $name,
                'required' => !empty($field['required']),
            ];

            if ($type === 'file') {
                // File fields store instructions, a size limit and an extension whitelist.
                $entry['description'] = sanitize_textarea_field($field['description'] ?? '');
                $max_mb               = isset($field['max_size_mb']) ? (float) $field['max_size_mb'] : 0;
                $entry['max_size_mb'] = $max_mb > 0 ? $max_mb : 0;
                $allowed              = simplekitforms_allowed_extensions($field['allowed_ext'] ?? '');
                $entry['allowed_ext'] = implode(', ', $allowed);
            } else {
                $entry['placeholder'] = sanitize_text_field($field['placeholder'] ?? '');
                $entry['subtype']     = sanitize_text_field($field['subtype'] ?? '');
            }

            // Options para checkboxes, radio, select
            if (in_array($type, ['checkboxes', 'radio', 'select']) && isset($field['options']) && is_array($field['options'])) {
                $opts = [];
                foreach ($field['options'] as $opt) {
                    $opt_val = sanitize_text_field($opt);
                    if (!empty($opt_val)) {
                        $opts[] = $opt_val;
                    }
                }
                $entry['options'] = $opts;
            }
            $sanitized[] = $entry;
        }
        $fields_json = wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    global $wpdb;
    $table = simplekitforms_get_forms_table();

    // Garantir que as colunas de configuração existam antes de gravar.
    if (function_exists('simplekitforms_ensure_form_columns')) {
        simplekitforms_ensure_form_columns();
    }

    $form_data = [
        'title'             => $title,
        'thank_you_message' => $thanks,
        'submit_text'       => $submit_txt,
        'notify_email'      => $notify_email,
        'smtp_host'         => $smtp_host,
        'smtp_port'         => ($smtp_port !== '' ? $smtp_port : '587'),
        'smtp_username'     => $smtp_username,
        'smtp_encryption'   => $smtp_encryption,
        'fields_json'       => $fields_json,
    ];
    $form_formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

    if ($smtp_password_changed) {
        $form_data['smtp_password'] = $smtp_password;
        $form_formats[]             = '%s';
    }

    if ($form_id > 0) {
        // Atualizar formulário existente
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $table,
            $form_data,
            ['id' => $form_id],
            $form_formats,
            ['%d']
        );
    } else {
        // Criar novo formulário
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert(
            $table,
            $form_data,
            $form_formats
        );
        if ($result) {
            $form_id = (int) $wpdb->insert_id;
        }
    }

    // Falha ao gravar: informar o usuário em vez de exibir falso sucesso.
    if ($result === false) {
        $error = trim((string) $wpdb->last_error);
        set_transient(
            'simplekitforms_save_error_' . get_current_user_id(),
            $error !== '' ? $error : 'Unknown database error.',
            MINUTE_IN_SECONDS
        );
        $redirect = $form_id > 0
            ? admin_url('admin.php?page=simplekitforms-create&id=' . $form_id . '&sf_save_error=1')
            : admin_url('admin.php?page=simplekitforms&sf_save_error=1');
        wp_safe_redirect($redirect);
        exit;
    }

    // Redirecionar para a lista de formulários
    wp_safe_redirect(admin_url('admin.php?page=simplekitforms&updated=1'));
    exit;
}

// ---------------------------------------------------------------------------
// Processar exclusão de formulário
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitforms_delete_form', 'simplekitforms_handle_delete_form');

function simplekitforms_handle_delete_form() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }
    check_admin_referer('simplekitforms_delete_form', '_sfnonce');

    $form_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($form_id > 0) {
        global $wpdb;

        // Remove the uploaded files of every response of this form.
        simplekitforms_delete_form_files($form_id);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(simplekitforms_get_forms_table(), ['id' => $form_id], ['%d']);
        // Entradas são removidas em cascata (FOREIGN KEY)
    }

    wp_safe_redirect(admin_url('admin.php?page=simplekitforms&deleted=1'));
    exit;
}

// ---------------------------------------------------------------------------
// Página: Lista de Formulários
// ---------------------------------------------------------------------------
function simplekitforms_page_forms_list() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    $forms = simplekitforms_get_all_forms();
    ?>
    <div class="wrap">
        <h1>Forms</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitforms-create')); ?>" class="page-title-action">Create new form</a>
        <hr class="wp-header-end">

        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Form saved successfully!</p>
            </div>
        <?php endif; ?>
        <?php simplekitforms_render_save_error_notice(); ?>
        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Form deleted successfully.</p>
            </div>
        <?php endif; ?>

        <?php if (empty($forms)) : ?>
            <p>No forms found.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Title</th>
                        <th scope="col">Shortcode</th>
                        <th scope="col">Responses</th>
                        <th scope="col">Created at</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form) : ?>
                        <?php
                        $edit_url    = admin_url('admin.php?page=simplekitforms-create&id=' . $form->id);
                        $entries_url = admin_url('admin.php?page=simplekitforms-entries&form_id=' . $form->id);
                        $delete_url  = wp_nonce_url(
                            admin_url('admin-post.php?action=simplekitforms_delete_form&id=' . $form->id),
                            'simplekitforms_delete_form',
                            '_sfnonce'
                        );
                        $entry_count = simplekitforms_get_entries_count($form->id);
                        ?>
                        <tr>
                            <td><?php echo (int) $form->id; ?></td>
                            <td><strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($form->title); ?></a></strong></td>
                            <td><code>[simplekitforms id="<?php echo (int) $form->id; ?>"]</code></td>
                            <td><a href="<?php echo esc_url($entries_url); ?>"><?php echo (int) $entry_count; ?></a></td>
                            <td><?php echo esc_html($form->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo esc_url($entries_url); ?>" class="button button-small">Responses</a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" onclick="return confirm('Are you sure you want to delete this form? All associated responses will also be removed.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Página: Criar / Editar Formulário (Form Builder)
// ---------------------------------------------------------------------------
function simplekitforms_page_form_editor() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $form_id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $form     = null;
    $title    = '';
    $thanks   = '';
    $submit_text = 'Submit';
    $notify_email = '';
    $smtp_host       = '';
    $smtp_port       = '587';
    $smtp_username   = '';
    $smtp_encryption = 'tls';
    $fields   = [];

    if ($form_id > 0) {
        $form = simplekitforms_get_form($form_id);
        if ($form) {
            $title       = $form->title;
            $thanks      = $form->thank_you_message;
            $submit_text = $form->submit_text;
            $notify_email = $form->notify_email ?? '';
            $smtp_host       = $form->smtp_host ?? '';
            $smtp_port       = $form->smtp_port ?? '587';
            $smtp_username   = $form->smtp_username ?? '';
            $smtp_encryption = $form->smtp_encryption ?? 'tls';
            $fields      = json_decode($form->fields_json, true) ?: [];
        }
    }

    $is_edit = $form_id > 0 && $form;
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? 'Edit form' : 'Create form'; ?></h1>
        <?php simplekitforms_render_save_error_notice(); ?>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('simplekitforms_save_form', '_sfnonce'); ?>
            <input type="hidden" name="action" value="simplekitforms_save_form">
            <?php if ($is_edit) : ?>
                <input type="hidden" name="form_id" value="<?php echo (int) $form->id; ?>">
            <?php endif; ?>

            <div id="simplekitforms-builder-app">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="sf_title">Form title <span class="required">*</span></label></th>
                        <td><input type="text" id="sf_title" name="sf_title" value="<?php echo esc_attr($title); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_thanks">Thank you message</label></th>
                        <td>
                            <textarea id="sf_thanks" name="sf_thanks" rows="4" class="large-text"><?php echo esc_textarea($thanks); ?></textarea>
                            <p class="description">Displayed after the visitor submits the form.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_submit_text">Submit button text</label></th>
                        <td><input type="text" id="sf_submit_text" name="sf_submit_text" value="<?php echo esc_attr($submit_text); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_notify_email">Notification e-mail</label></th>
                        <td>
                            <input type="email" id="sf_notify_email" name="sf_notify_email" value="<?php echo esc_attr($notify_email); ?>" class="regular-text">
                            <p class="description">All received responses will also be sent to this address. Leave empty to only store them in the dashboard. If the form has an e-mail field, it will be used as the "Reply-To" of the notification.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2" style="padding-bottom:0;">
                            <h2 style="margin:0;">SMTP settings</h2>
                            <p class="description" style="margin:4px 0 0;">Used to send the notification e-mail above. Leave the SMTP server blank to fall back to the default WordPress mailer.</p>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_smtp_host">SMTP server</label></th>
                        <td><input type="text" id="sf_smtp_host" name="sf_smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_smtp_port">SMTP port</label></th>
                        <td><input type="text" id="sf_smtp_port" name="sf_smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_smtp_username">SMTP username</label></th>
                        <td><input type="text" id="sf_smtp_username" name="sf_smtp_username" value="<?php echo esc_attr($smtp_username); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_smtp_password">SMTP password</label></th>
                        <td>
                            <input type="password" id="sf_smtp_password" name="sf_smtp_password" value="" class="regular-text" autocomplete="new-password">
                            <p class="description">Leave blank to keep the current password.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sf_smtp_encryption">Encryption</label></th>
                        <td>
                            <select id="sf_smtp_encryption" name="sf_smtp_encryption">
                                <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
                                <option value="none" <?php selected($smtp_encryption, 'none'); ?>>None</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2>Field palette</h2>
                <p class="description">Click a field to add it to the form. Drag to reorder.</p>
                <div class="sf-palette">
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="text">Text</button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="email">E-mail</button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="password">Password</button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="url">URL</button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="number">Number</button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="tel">Phone</button>
                    <button type="button" class="button sf-add-field" data-type="textarea">Textarea</button>
                    <button type="button" class="button sf-add-field" data-type="checkboxes">Checkboxes</button>
                    <button type="button" class="button sf-add-field" data-type="radio">Radio buttons</button>
                    <button type="button" class="button sf-add-field" data-type="select">Dropdown</button>
                    <button type="button" class="button sf-add-field" data-type="file">File</button>
                </div>

                <h2>Form fields</h2>
                <div id="sf-fields-container" class="sf-fields-container">
                    <?php if (!empty($fields)) : ?>
                        <?php foreach ($fields as $index => $field) : ?>
                            <?php simplekitforms_render_field_row($index, $field); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p id="sf-no-fields" class="description" style="<?php echo !empty($fields) ? 'display:none;' : ''; ?>">
                    No fields added. Click the palette buttons above to add fields.
                </p>

                <?php submit_button($is_edit ? 'Save form' : 'Create form'); ?>
            </div>
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Renderizar linha de campo no builder
// ---------------------------------------------------------------------------
function simplekitforms_render_field_row($index, $field) {
    $type        = $field['type'] ?? 'text';
    $subtype     = $field['subtype'] ?? '';
    $label       = $field['label'] ?? '';
    $name        = $field['name'] ?? ('field_' . $index);
    $required    = !empty($field['required']);
    $placeholder = $field['placeholder'] ?? '';
    $options     = $field['options'] ?? [];

    $description = $field['description'] ?? '';
    $max_size_mb = isset($field['max_size_mb']) ? $field['max_size_mb'] : '';
    $allowed_ext = $field['allowed_ext'] ?? '';

    $type_label  = simplekitforms_get_field_type_label($type, $subtype);
    ?>
    <div class="sf-field-row" data-index="<?php echo (int) $index; ?>">
        <div class="sf-field-header">
            <span class="sf-drag-handle dashicons dashicons-menu"></span>
            <strong class="sf-field-type-label"><?php echo esc_html($type_label); ?></strong>
            <span class="sf-field-label-preview"><?php echo esc_html($label ?: '(no label)'); ?></span>
            <button type="button" class="button button-small sf-toggle-config">Configure</button>
            <button type="button" class="button button-small sf-remove-field button-link-delete">Remove</button>
        </div>
        <div class="sf-field-config" style="display:none;">
            <input type="hidden" name="sf_fields[<?php echo (int) $index; ?>][type]" value="<?php echo esc_attr($type); ?>">
            <input type="hidden" name="sf_fields[<?php echo (int) $index; ?>][subtype]" value="<?php echo esc_attr($subtype); ?>">
            <input type="hidden" name="sf_fields[<?php echo (int) $index; ?>][name]" value="<?php echo esc_attr($name); ?>" class="sf-field-name">

            <label>Label:
                <input type="text" name="sf_fields[<?php echo (int) $index; ?>][label]" value="<?php echo esc_attr($label); ?>" class="sf-field-label regular-text">
            </label>

            <?php if (in_array($type, ['text', 'textarea'])) : ?>
                <label>Placeholder:
                    <input type="text" name="sf_fields[<?php echo (int) $index; ?>][placeholder]" value="<?php echo esc_attr($placeholder); ?>" class="regular-text">
                </label>
            <?php endif; ?>

            <?php if ($type === 'file') : ?>
                <label style="display:block;margin-top:8px;">Description / instructions:
                    <textarea name="sf_fields[<?php echo (int) $index; ?>][description]" rows="2" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </label>
                <label>Maximum size (MB):
                    <input type="number" min="0" step="0.1" name="sf_fields[<?php echo (int) $index; ?>][max_size_mb]" value="<?php echo esc_attr($max_size_mb); ?>" class="small-text">
                </label>
                <label style="display:block;margin-top:4px;">Allowed extensions:
                    <input type="text" name="sf_fields[<?php echo (int) $index; ?>][allowed_ext]" value="<?php echo esc_attr($allowed_ext); ?>" class="regular-text" placeholder="pdf, jpg, png">
                    <span class="description">Comma separated list. Leave empty to accept any file type.</span>
                </label>
            <?php endif; ?>

            <label>
                <input type="checkbox" name="sf_fields[<?php echo (int) $index; ?>][required]" value="1" <?php checked($required); ?>>
                Required field
            </label>

            <?php if (in_array($type, ['checkboxes', 'radio', 'select'])) : ?>
                <div class="sf-options-group">
                    <p><strong>Options:</strong></p>
                    <div class="sf-options-list">
                        <?php if (!empty($options)) : ?>
                            <?php foreach ($options as $opt_idx => $opt_val) : ?>
                                <div class="sf-option-row">
                                    <span class="sf-option-drag-handle dashicons dashicons-menu"></span>
                                    <input type="text" name="sf_fields[<?php echo (int) $index; ?>][options][]" value="<?php echo esc_attr($opt_val); ?>" class="regular-text">
                                    <button type="button" class="button button-small sf-remove-option">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button sf-add-option">+ Add option</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Helper: label do tipo de campo
// ---------------------------------------------------------------------------
function simplekitforms_get_field_type_label($type, $subtype = '') {
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
