<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Database helpers
// ---------------------------------------------------------------------------
function simplekitregistration_get_all_forms() {
    global $wpdb;
    $table = simplekitregistration_forms_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results(
        $wpdb->prepare('SELECT * FROM %i ORDER BY created_at DESC', $table)
    );
}

function simplekitregistration_get_form($id) {
    global $wpdb;
    $table = simplekitregistration_forms_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $table, (int) $id)
    );
}

function simplekitregistration_get_form_by_code($code) {
    global $wpdb;
    $code = simplekitregistration_sanitize_code($code);
    if ($code === '') {
        return null;
    }
    $table = simplekitregistration_forms_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM %i WHERE code = %s', $table, $code)
    );
}

function simplekitregistration_get_entries_count($form_id) {
    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return (int) $wpdb->get_var(
        $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE form_id = %d', $table, (int) $form_id)
    );
}

// ---------------------------------------------------------------------------
// Save error notice
// ---------------------------------------------------------------------------
function simplekitregistration_render_save_error_notice() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed.
    if (!isset($_GET['skr_save_error'])) {
        return;
    }
    $key     = 'simplekitregistration_save_error_' . get_current_user_id();
    $message = get_transient($key);
    delete_transient($key);
    ?>
    <div class="notice notice-error">
        <p><strong><?php esc_html_e('Unable to save the form.', 'simplekitregistration'); ?></strong> <?php esc_html_e('The changes were not stored.', 'simplekitregistration'); ?></p>
        <?php if (!empty($message)) : ?>
            <p><code><?php echo esc_html($message); ?></code></p>
        <?php endif; ?>
    </div>
    <?php
}

function simplekitregistration_render_form_error_notice() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed.
    if (!isset($_GET['skr_form_error'])) {
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed.
    $code = sanitize_text_field(wp_unslash($_GET['skr_form_error']));
    $messages = [
        'empty_title' => __('The form title is required.', 'simplekitregistration'),
        'empty_code'  => __('The form code is required and must contain letters or digits.', 'simplekitregistration'),
        'code_exists' => __('Another form already uses this code.', 'simplekitregistration'),
        'code_locked' => __('The form code cannot be changed because this form already has responses.', 'simplekitregistration'),
    ];
    $message = $messages[$code] ?? __('The form could not be saved.', 'simplekitregistration');
    ?>
    <div class="notice notice-error"><p><?php echo esc_html($message); ?></p></div>
    <?php
}

// ---------------------------------------------------------------------------
// Process form (create / update)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_save_form', 'simplekitregistration_handle_save_form');

function simplekitregistration_handle_save_form() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    check_admin_referer('simplekitregistration_save_form', '_skrnonce');

    $form_id    = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $title      = sanitize_text_field(wp_unslash($_POST['skr_title'] ?? ''));
    $thanks     = sanitize_textarea_field(wp_unslash($_POST['skr_thanks'] ?? ''));
    $submit_txt = sanitize_text_field(wp_unslash($_POST['skr_submit_text'] ?? 'Submit'));
    $code       = simplekitregistration_sanitize_code(sanitize_text_field(wp_unslash($_POST['skr_code'] ?? '')));

    $editor_url = admin_url('admin.php?page=simplekitregistration-create' . ($form_id > 0 ? '&id=' . $form_id : ''));

    if (empty($title)) {
        wp_safe_redirect(add_query_arg('skr_form_error', 'empty_title', $editor_url));
        exit;
    }
    if ($code === '') {
        wp_safe_redirect(add_query_arg('skr_form_error', 'empty_code', $editor_url));
        exit;
    }

    $existing = simplekitregistration_get_form_by_code($code);
    if ($existing && (int) $existing->id !== $form_id) {
        wp_safe_redirect(add_query_arg('skr_form_error', 'code_exists', $editor_url));
        exit;
    }

    // The code is used as a folder name and must stay stable once responses exist.
    if ($form_id > 0) {
        $current = simplekitregistration_get_form($form_id);
        if ($current && $current->code !== $code && simplekitregistration_get_entries_count($form_id) > 0) {
            wp_safe_redirect(add_query_arg('skr_form_error', 'code_locked', $editor_url));
            exit;
        }
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nested array, sanitized per field below.
    $fields_raw  = wp_unslash($_POST['skr_fields'] ?? '');
    $fields_json = '';

    if (is_array($fields_raw)) {
        $sanitized = [];
        foreach ($fields_raw as $index => $field) {
            $type  = sanitize_text_field($field['type'] ?? 'text');
            $label = sanitize_text_field($field['label'] ?? '');
            if ($label === '') {
                continue;
            }

            $name = sanitize_key($field['name'] ?? 'field_' . $index);
            if ($name === '') {
                $name = 'field_' . $index;
            }

            $entry = [
                'type'     => $type,
                'label'    => $label,
                'name'     => $name,
                'required' => !empty($field['required']),
            ];

            if ($type === 'file') {
                $entry['description']  = sanitize_textarea_field($field['description'] ?? '');
                $max_mb                = isset($field['max_size_mb']) ? (float) $field['max_size_mb'] : 0;
                $entry['max_size_mb']  = $max_mb > 0 ? $max_mb : 0;
                $allowed               = simplekitregistration_allowed_extensions($field['allowed_ext'] ?? '');
                $entry['allowed_ext']  = implode(', ', $allowed);
            } else {
                $entry['placeholder'] = sanitize_text_field($field['placeholder'] ?? '');
                $entry['subtype']     = sanitize_text_field($field['subtype'] ?? '');
            }

            if (in_array($type, ['checkboxes', 'radio', 'select'], true) && isset($field['options']) && is_array($field['options'])) {
                $options = [];
                foreach ($field['options'] as $option) {
                    $option = sanitize_text_field($option);
                    if ($option !== '') {
                        $options[] = $option;
                    }
                }
                $entry['options'] = $options;
            }

            $sanitized[] = $entry;
        }
        $fields_json = wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    global $wpdb;
    $table = simplekitregistration_forms_table();

    $form_data = [
        'title'             => $title,
        'code'              => $code,
        'thank_you_message' => $thanks,
        'submit_text'       => $submit_txt,
        'fields_json'       => $fields_json,
    ];
    $form_formats = ['%s', '%s', '%s', '%s', '%s'];

    if ($form_id > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update($table, $form_data, ['id' => $form_id], $form_formats, ['%d']);
    } else {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($table, $form_data, $form_formats);
        if ($result) {
            $form_id = (int) $wpdb->insert_id;
        }
    }

    if ($result === false) {
        $error = trim((string) $wpdb->last_error);
        set_transient(
            'simplekitregistration_save_error_' . get_current_user_id(),
            $error !== '' ? $error : 'Unknown database error.',
            MINUTE_IN_SECONDS
        );
        $redirect = $form_id > 0
            ? admin_url('admin.php?page=simplekitregistration-create&id=' . $form_id . '&skr_save_error=1')
            : admin_url('admin.php?page=simplekitregistration&skr_save_error=1');
        wp_safe_redirect($redirect);
        exit;
    }

    simplekitregistration_ensure_uploads_root();
    wp_mkdir_p(simplekitregistration_form_dir_path($code));

    wp_safe_redirect(admin_url('admin.php?page=simplekitregistration&updated=1'));
    exit;
}

// ---------------------------------------------------------------------------
// Delete form (also removes its responses and uploaded files)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_delete_form', 'simplekitregistration_handle_delete_form');

function simplekitregistration_handle_delete_form() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    check_admin_referer('simplekitregistration_delete_form', '_skrnonce');

    $form_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $form    = $form_id > 0 ? simplekitregistration_get_form($form_id) : null;

    if ($form) {
        global $wpdb;

        // Remove the uploaded files folder for this form.
        simplekitregistration_delete_form_files($form->code);

        // Remove the messages of every response of this form.
        simplekitregistration_delete_form_messages($form->id);

        // Remove the responses.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(simplekitregistration_entries_table(), ['form_id' => (int) $form->id], ['%d']);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(simplekitregistration_forms_table(), ['id' => (int) $form->id], ['%d']);
    }

    wp_safe_redirect(admin_url('admin.php?page=simplekitregistration&deleted=1'));
    exit;
}

// ---------------------------------------------------------------------------
// Page: Forms list
// ---------------------------------------------------------------------------
function simplekitregistration_page_forms_list() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $forms = simplekitregistration_get_all_forms();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Forms', 'simplekitregistration'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitregistration-create')); ?>" class="page-title-action"><?php esc_html_e('Create new form', 'simplekitregistration'); ?></a>
        <hr class="wp-header-end">

        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Form saved successfully!', 'simplekitregistration'); ?></p></div>
        <?php endif; ?>
        <?php simplekitregistration_render_save_error_notice(); ?>
        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Form deleted successfully.', 'simplekitregistration'); ?></p></div>
        <?php endif; ?>

        <?php if (empty($forms)) : ?>
            <p><?php esc_html_e('No forms found.', 'simplekitregistration'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col"><?php esc_html_e('Title', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Form code', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Responses', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Created at', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'simplekitregistration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form) : ?>
                        <?php
                        $edit_url    = admin_url('admin.php?page=simplekitregistration-create&id=' . (int) $form->id);
                        $entries_url = admin_url('admin.php?page=simplekitregistration-entries&form_id=' . (int) $form->id);
                        $delete_url  = wp_nonce_url(
                            admin_url('admin-post.php?action=simplekitregistration_delete_form&id=' . (int) $form->id),
                            'simplekitregistration_delete_form',
                            '_skrnonce'
                        );
                        $entry_count = simplekitregistration_get_entries_count($form->id);
                        ?>
                        <tr>
                            <td><?php echo (int) $form->id; ?></td>
                            <td><strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($form->title); ?></a></strong></td>
                            <td><code><?php echo esc_html($form->code); ?></code></td>
                            <td><a href="<?php echo esc_url($entries_url); ?>"><?php echo (int) $entry_count; ?></a></td>
                            <td><?php echo esc_html($form->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small"><?php esc_html_e('Edit', 'simplekitregistration'); ?></a>
                                <a href="<?php echo esc_url($entries_url); ?>" class="button button-small"><?php esc_html_e('Responses', 'simplekitregistration'); ?></a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this form? All responses and uploaded files will also be removed.', 'simplekitregistration')); ?>')"><?php esc_html_e('Delete', 'simplekitregistration'); ?></a>
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
// Page: Create / Edit form (form builder)
// ---------------------------------------------------------------------------
function simplekitregistration_page_form_editor() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $form_id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $form     = $form_id > 0 ? simplekitregistration_get_form($form_id) : null;

    $title       = $form ? $form->title : '';
    $code        = $form ? $form->code : '';
    $thanks      = $form ? $form->thank_you_message : '';
    $submit_text = $form ? $form->submit_text : 'Submit';
    $fields      = $form ? (json_decode($form->fields_json, true) ?: []) : [];
    $is_edit     = (bool) $form;
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? esc_html__('Edit form', 'simplekitregistration') : esc_html__('Create form', 'simplekitregistration'); ?></h1>
        <?php simplekitregistration_render_save_error_notice(); ?>
        <?php simplekitregistration_render_form_error_notice(); ?>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('simplekitregistration_save_form', '_skrnonce'); ?>
            <input type="hidden" name="action" value="simplekitregistration_save_form">
            <?php if ($is_edit) : ?>
                <input type="hidden" name="form_id" value="<?php echo (int) $form->id; ?>">
            <?php endif; ?>

            <div id="simplekitregistration-builder-app">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="skr_title"><?php esc_html_e('Form title', 'simplekitregistration'); ?> <span class="required">*</span></label></th>
                        <td><input type="text" id="skr_title" name="skr_title" value="<?php echo esc_attr($title); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="skr_code"><?php esc_html_e('Form code', 'simplekitregistration'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="skr_code" name="skr_code" value="<?php echo esc_attr($code); ?>" class="regular-text" required pattern="[A-Za-z0-9_-]+">
                            <p class="description"><?php esc_html_e('Alphanumeric identifier without spaces or special characters. It is used as the name of the folder that stores the uploaded files.', 'simplekitregistration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="skr_thanks"><?php esc_html_e('Thank you message', 'simplekitregistration'); ?></label></th>
                        <td>
                            <textarea id="skr_thanks" name="skr_thanks" rows="4" class="large-text"><?php echo esc_textarea($thanks); ?></textarea>
                            <p class="description"><?php esc_html_e('Displayed after the visitor submits the form.', 'simplekitregistration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="skr_submit_text"><?php esc_html_e('Submit button text', 'simplekitregistration'); ?></label></th>
                        <td><input type="text" id="skr_submit_text" name="skr_submit_text" value="<?php echo esc_attr($submit_text); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Field palette', 'simplekitregistration'); ?></h2>
                <p class="description"><?php esc_html_e('Click a field to add it to the form. Drag to reorder.', 'simplekitregistration'); ?></p>
                <div class="sf-palette">
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="text"><?php esc_html_e('Text', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="email"><?php esc_html_e('E-mail', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="password"><?php esc_html_e('Password', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="url"><?php esc_html_e('URL', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="number"><?php esc_html_e('Number', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="text" data-subtype="tel"><?php esc_html_e('Phone', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="textarea"><?php esc_html_e('Textarea', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="checkboxes"><?php esc_html_e('Checkboxes', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="radio"><?php esc_html_e('Radio buttons', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="select"><?php esc_html_e('Dropdown', 'simplekitregistration'); ?></button>
                    <button type="button" class="button sf-add-field" data-type="file"><?php esc_html_e('File', 'simplekitregistration'); ?></button>
                </div>

                <h2><?php esc_html_e('Form fields', 'simplekitregistration'); ?></h2>
                <div id="sf-fields-container" class="sf-fields-container">
                    <?php foreach ($fields as $index => $field) : ?>
                        <?php simplekitregistration_render_field_row($index, $field); ?>
                    <?php endforeach; ?>
                </div>
                <p id="sf-no-fields" class="description" style="<?php echo !empty($fields) ? 'display:none;' : ''; ?>">
                    <?php esc_html_e('No fields added. Click the palette buttons above to add fields.', 'simplekitregistration'); ?>
                </p>

                <?php submit_button($is_edit ? __('Save form', 'simplekitregistration') : __('Create form', 'simplekitregistration')); ?>
            </div>
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Render a single field row in the builder
// ---------------------------------------------------------------------------
function simplekitregistration_render_field_row($index, $field) {
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

    $type_label = simplekitregistration_get_field_type_label($type, $subtype);
    ?>
    <div class="sf-field-row" data-index="<?php echo (int) $index; ?>">
        <div class="sf-field-header">
            <span class="sf-drag-handle dashicons dashicons-menu"></span>
            <strong class="sf-field-type-label"><?php echo esc_html($type_label); ?></strong>
            <span class="sf-field-label-preview"><?php echo esc_html($label !== '' ? $label : '(' . __('no label', 'simplekitregistration') . ')'); ?></span>
            <button type="button" class="button button-small sf-toggle-config"><?php esc_html_e('Configure', 'simplekitregistration'); ?></button>
            <button type="button" class="button button-small sf-remove-field button-link-delete"><?php esc_html_e('Remove', 'simplekitregistration'); ?></button>
        </div>
        <div class="sf-field-config" style="display:none;">
            <input type="hidden" name="skr_fields[<?php echo (int) $index; ?>][type]" value="<?php echo esc_attr($type); ?>">
            <input type="hidden" name="skr_fields[<?php echo (int) $index; ?>][subtype]" value="<?php echo esc_attr($subtype); ?>">
            <input type="hidden" name="skr_fields[<?php echo (int) $index; ?>][name]" value="<?php echo esc_attr($name); ?>" class="sf-field-name">

            <label><?php esc_html_e('Label', 'simplekitregistration'); ?>:
                <input type="text" name="skr_fields[<?php echo (int) $index; ?>][label]" value="<?php echo esc_attr($label); ?>" class="sf-field-label regular-text">
            </label>

            <?php if (in_array($type, ['text', 'textarea'], true)) : ?>
                <label><?php esc_html_e('Placeholder', 'simplekitregistration'); ?>:
                    <input type="text" name="skr_fields[<?php echo (int) $index; ?>][placeholder]" value="<?php echo esc_attr($placeholder); ?>" class="regular-text">
                </label>
            <?php endif; ?>

            <?php if ($type === 'file') : ?>
                <label style="display:block;margin-top:8px;"><?php esc_html_e('Description / instructions', 'simplekitregistration'); ?>:
                    <textarea name="skr_fields[<?php echo (int) $index; ?>][description]" rows="2" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </label>
                <label><?php esc_html_e('Maximum size (MB)', 'simplekitregistration'); ?>:
                    <input type="number" min="0" step="0.1" name="skr_fields[<?php echo (int) $index; ?>][max_size_mb]" value="<?php echo esc_attr($max_size_mb); ?>" class="small-text">
                </label>
                <label style="display:block;margin-top:4px;"><?php esc_html_e('Allowed extensions', 'simplekitregistration'); ?>:
                    <input type="text" name="skr_fields[<?php echo (int) $index; ?>][allowed_ext]" value="<?php echo esc_attr($allowed_ext); ?>" class="regular-text" placeholder="pdf, jpg, png">
                    <span class="description"><?php esc_html_e('Comma separated list. Leave empty to accept any file type.', 'simplekitregistration'); ?></span>
                </label>
            <?php endif; ?>

            <label>
                <input type="checkbox" name="skr_fields[<?php echo (int) $index; ?>][required]" value="1" <?php checked($required); ?>>
                <?php esc_html_e('Required field', 'simplekitregistration'); ?>
            </label>

            <?php if (in_array($type, ['checkboxes', 'radio', 'select'], true)) : ?>
                <div class="sf-options-group">
                    <p><strong><?php esc_html_e('Options', 'simplekitregistration'); ?>:</strong></p>
                    <div class="sf-options-list">
                        <?php foreach ($options as $option) : ?>
                            <div class="sf-option-row">
                                <span class="sf-option-drag-handle dashicons dashicons-menu"></span>
                                <input type="text" name="skr_fields[<?php echo (int) $index; ?>][options][]" value="<?php echo esc_attr($option); ?>" class="regular-text">
                                <button type="button" class="button button-small sf-remove-option"><?php esc_html_e('Remove', 'simplekitregistration'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button sf-add-option">+ <?php esc_html_e('Add option', 'simplekitregistration'); ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
