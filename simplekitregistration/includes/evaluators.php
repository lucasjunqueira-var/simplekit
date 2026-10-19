<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Evaluators: query helpers
// ---------------------------------------------------------------------------
function simplekitregistration_get_all_evaluators() {
    global $wpdb;
    $table = simplekitregistration_evaluators_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results(
        $wpdb->prepare('SELECT * FROM %i ORDER BY email ASC', $table)
    );
}

function simplekitregistration_get_evaluator($id) {
    global $wpdb;
    $table = simplekitregistration_evaluators_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $table, (int) $id)
    );
}

function simplekitregistration_get_evaluator_by_email($email) {
    global $wpdb;
    $email = sanitize_email($email);
    if (!is_email($email)) {
        return null;
    }
    $table = simplekitregistration_evaluators_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM %i WHERE email = %s', $table, $email)
    );
}

// ---------------------------------------------------------------------------
// Save evaluator
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_save_evaluator', 'simplekitregistration_handle_save_evaluator');

function simplekitregistration_handle_save_evaluator() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    check_admin_referer('simplekitregistration_save_evaluator', '_skrnonce');

    $evaluator_id = isset($_POST['evaluator_id']) ? (int) $_POST['evaluator_id'] : 0;
    $email        = sanitize_email(wp_unslash($_POST['skr_email'] ?? ''));
    $password     = sanitize_text_field(wp_unslash($_POST['skr_password'] ?? ''));

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized as integers below.
    $forms_raw = wp_unslash($_POST['skr_forms'] ?? []);
    $form_ids  = [];
    if (is_array($forms_raw)) {
        foreach ($forms_raw as $form_id) {
            $form_ids[] = (int) $form_id;
        }
    }

    $base_url = admin_url('admin.php?page=simplekitregistration-evaluators');

    if (!is_email($email)) {
        wp_safe_redirect(add_query_arg('skr_error', 'invalid_email', $base_url));
        exit;
    }

    $existing = simplekitregistration_get_evaluator_by_email($email);
    if ($existing && (int) $existing->id !== $evaluator_id) {
        wp_safe_redirect(add_query_arg('skr_error', 'email_exists', $base_url));
        exit;
    }

    if ($evaluator_id <= 0 && $password === '') {
        wp_safe_redirect(add_query_arg('skr_error', 'empty_password', $base_url));
        exit;
    }

    global $wpdb;
    $table = simplekitregistration_evaluators_table();

    $data = [
        'email'      => $email,
        'forms_json' => wp_json_encode(array_values(array_unique($form_ids))),
    ];
    $formats = ['%s', '%s'];

    if ($password !== '') {
        $data['password_hash'] = wp_hash_password($password);
        $formats[]             = '%s';
    }

    if ($evaluator_id > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update($table, $data, ['id' => $evaluator_id], $formats, ['%d']);
    } else {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($table, $data, $formats);
    }

    if ($result === false) {
        wp_safe_redirect(add_query_arg('skr_error', 'db_error', $base_url));
        exit;
    }

    wp_safe_redirect(add_query_arg('updated', '1', $base_url));
    exit;
}

// ---------------------------------------------------------------------------
// Delete evaluator
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitregistration_delete_evaluator', 'simplekitregistration_handle_delete_evaluator');

function simplekitregistration_handle_delete_evaluator() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    check_admin_referer('simplekitregistration_delete_evaluator', '_skrnonce');

    $evaluator_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($evaluator_id > 0) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(simplekitregistration_evaluators_table(), ['id' => $evaluator_id], ['%d']);
    }

    wp_safe_redirect(admin_url('admin.php?page=simplekitregistration-evaluators&deleted=1'));
    exit;
}

// ---------------------------------------------------------------------------
// Page: Evaluators
// ---------------------------------------------------------------------------
function simplekitregistration_page_evaluators() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $edit_id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $edit     = $edit_id > 0 ? simplekitregistration_get_evaluator($edit_id) : null;
    $edit_ids = [];
    if ($edit) {
        $decoded = json_decode($edit->forms_json, true);
        $edit_ids = is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    $forms = simplekitregistration_get_all_forms();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Evaluators', 'simplekitregistration'); ?></h1>
        <p class="description"><?php esc_html_e('Evaluators are not WordPress users. They log in with the e-mail and password registered here to review the responses of the selected forms on the restricted pages of your site.', 'simplekitregistration'); ?></p>
        <hr class="wp-header-end">

        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Evaluator saved successfully.', 'simplekitregistration'); ?></p></div>
        <?php endif; ?>
        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed. ?>
        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Evaluator deleted successfully.', 'simplekitregistration'); ?></p></div>
        <?php endif; ?>
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page status parameter, no nonce needed.
        $error_code = isset($_GET['skr_error']) ? sanitize_text_field(wp_unslash($_GET['skr_error'])) : '';
        if ($error_code !== '') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(simplekitregistration_evaluator_error_message($error_code)) . '</p></div>';
        }
        ?>

        <h2><?php echo $edit ? esc_html__('Edit evaluator', 'simplekitregistration') : esc_html__('Add evaluator', 'simplekitregistration'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('simplekitregistration_save_evaluator', '_skrnonce'); ?>
            <input type="hidden" name="action" value="simplekitregistration_save_evaluator">
            <?php if ($edit) : ?>
                <input type="hidden" name="evaluator_id" value="<?php echo (int) $edit->id; ?>">
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="skr_email"><?php esc_html_e('E-mail', 'simplekitregistration'); ?> <span class="required">*</span></label></th>
                    <td><input type="email" id="skr_email" name="skr_email" value="<?php echo esc_attr($edit ? $edit->email : ''); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="skr_password"><?php esc_html_e('Password', 'simplekitregistration'); ?> <?php echo $edit ? '' : '<span class="required">*</span>'; ?></label></th>
                    <td>
                        <input type="password" id="skr_password" name="skr_password" value="" class="regular-text" autocomplete="new-password">
                        <?php if ($edit) : ?>
                            <p class="description"><?php esc_html_e('Leave blank to keep the current password.', 'simplekitregistration'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Allowed forms', 'simplekitregistration'); ?></th>
                    <td>
                        <?php if (empty($forms)) : ?>
                            <p class="description"><?php esc_html_e('No forms have been created yet.', 'simplekitregistration'); ?></p>
                        <?php else : ?>
                            <?php foreach ($forms as $form) : ?>
                                <label style="display:block;margin-bottom:4px;">
                                    <input type="checkbox" name="skr_forms[]" value="<?php echo (int) $form->id; ?>" <?php checked(in_array((int) $form->id, $edit_ids, true)); ?>>
                                    <?php echo esc_html($form->title); ?>
                                    <code><?php echo esc_html($form->code); ?></code>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button($edit ? __('Save evaluator', 'simplekitregistration') : __('Add evaluator', 'simplekitregistration')); ?>
            <?php if ($edit) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitregistration-evaluators')); ?>" class="button"><?php esc_html_e('Cancel', 'simplekitregistration'); ?></a>
            <?php endif; ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Registered evaluators', 'simplekitregistration'); ?></h2>
        <?php $evaluators = simplekitregistration_get_all_evaluators(); ?>
        <?php if (empty($evaluators)) : ?>
            <p><?php esc_html_e('No evaluators registered.', 'simplekitregistration'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('E-mail', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Allowed forms', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Created at', 'simplekitregistration'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'simplekitregistration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluators as $evaluator) : ?>
                        <?php
                        $ids  = json_decode($evaluator->forms_json, true);
                        $ids  = is_array($ids) ? array_map('intval', $ids) : [];
                        $names = [];
                        foreach ($forms as $form) {
                            if (in_array((int) $form->id, $ids, true)) {
                                $names[] = $form->title;
                            }
                        }
                        $edit_url   = admin_url('admin.php?page=simplekitregistration-evaluators&id=' . (int) $evaluator->id);
                        $delete_url = wp_nonce_url(
                            admin_url('admin-post.php?action=simplekitregistration_delete_evaluator&id=' . (int) $evaluator->id),
                            'simplekitregistration_delete_evaluator',
                            '_skrnonce'
                        );
                        ?>
                        <tr>
                            <td><?php echo esc_html($evaluator->email); ?></td>
                            <td><?php echo esc_html(empty($names) ? __('None', 'simplekitregistration') : implode(', ', $names)); ?></td>
                            <td><?php echo esc_html($evaluator->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small"><?php esc_html_e('Edit', 'simplekitregistration'); ?></a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Delete this evaluator?', 'simplekitregistration')); ?>')"><?php esc_html_e('Delete', 'simplekitregistration'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function simplekitregistration_evaluator_error_message($code) {
    switch ($code) {
        case 'invalid_email':
            return __('Please provide a valid e-mail address.', 'simplekitregistration');
        case 'email_exists':
            return __('Another evaluator already uses this e-mail address.', 'simplekitregistration');
        case 'empty_password':
            return __('A password is required for a new evaluator.', 'simplekitregistration');
        case 'db_error':
            return __('The evaluator could not be saved.', 'simplekitregistration');
        default:
            return __('An error occurred.', 'simplekitregistration');
    }
}
