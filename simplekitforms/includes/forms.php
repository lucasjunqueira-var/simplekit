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
                'type'        => $type,
                'label'       => $label,
                'name'        => $name,
                'required'    => !empty($field['required']),
                'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                'subtype'     => sanitize_text_field($field['subtype'] ?? ''),
            ];
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

    if ($form_id > 0) {
        // Atualizar formulário existente
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table,
            [
                'title'             => $title,
                'thank_you_message' => $thanks,
                'submit_text'       => $submit_txt,
                'fields_json'       => $fields_json,
            ],
            ['id' => $form_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    } else {
        // Criar novo formulário
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $table,
            [
                'title'             => $title,
                'thank_you_message' => $thanks,
                'submit_text'       => $submit_txt,
                'fields_json'       => $fields_json,
            ],
            ['%s', '%s', '%s', '%s']
        );
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
    $fields   = [];

    if ($form_id > 0) {
        $form = simplekitforms_get_form($form_id);
        if ($form) {
            $title       = $form->title;
            $thanks      = $form->thank_you_message;
            $submit_text = $form->submit_text;
            $fields      = json_decode($form->fields_json, true) ?: [];
        }
    }

    $is_edit = $form_id > 0 && $form;
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? 'Edit form' : 'Create form'; ?></h1>
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
    ];
    return $labels[$type] ?? 'Text';
}
