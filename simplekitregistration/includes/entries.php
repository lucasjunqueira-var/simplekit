<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Hidden subpage for responses
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitregistration_entries_subpage');

function simplekitregistration_entries_subpage() {
    add_submenu_page(
        null,
        __('Responses', 'simplekitregistration'),
        __('Responses', 'simplekitregistration'),
        'manage_options',
        'simplekitregistration-entries',
        'simplekitregistration_page_entries'
    );
}

// ---------------------------------------------------------------------------
// Delete a single response and its uploaded files
// ---------------------------------------------------------------------------
function simplekitregistration_delete_entry_record($entry_id) {
    global $wpdb;

    $entry = simplekitregistration_get_entry($entry_id);
    if (!$entry) {
        return false;
    }

    $form = simplekitregistration_get_form($entry->form_id);
    if ($form) {
        simplekitregistration_delete_entry_files($form->code, $entry->id);
    }

    // Messages belong to the response and are removed together with it.
    simplekitregistration_delete_entry_messages($entry->id);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return (bool) $wpdb->delete(simplekitregistration_entries_table(), ['id' => (int) $entry->id], ['%d']);
}

function simplekitregistration_get_entry($entry_id) {
    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row(
        $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $table, (int) $entry_id)
    );
}

// ---------------------------------------------------------------------------
// CSV export (runs before any output)
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitregistration_handle_csv_export');

function simplekitregistration_handle_csv_export() {
    if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'simplekitregistration-entries') {
        return;
    }
    if (!isset($_GET['export']) || sanitize_text_field(wp_unslash($_GET['export'])) !== 'csv') {
        return;
    }
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitregistration_csv_export')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, nonce already verified above.
    $form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
    if ($form_id <= 0) {
        return;
    }

    $form = simplekitregistration_get_form($form_id);
    if (!$form) {
        return;
    }

    $fields = json_decode($form->fields_json, true) ?: [];

    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC',
        $table,
        $form_id
    ));

    $filename = sanitize_title($form->code) . '-responses.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for spreadsheet compatibility.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary BOM, escaping would corrupt it.
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);

    $headers = [simplekitregistration_spreadsheet_text('responses_id')];
    foreach ($fields as $field) {
        $headers[] = $field['label'] ?? ($field['name'] ?? '');
    }
    $headers[] = simplekitregistration_spreadsheet_text('responses_ip');
    $headers[] = simplekitregistration_spreadsheet_text('responses_datetime');
    fputcsv($output, $headers, ';');

    foreach ($entries as $entry) {
        $data = json_decode($entry->data_json, true) ?: [];
        $row  = [$entry->id];
        foreach ($fields as $field) {
            $name  = $field['name'] ?? '';
            $value = $data[$name] ?? '';
            if (simplekitregistration_is_file_value($value)) {
                $row[] = str_replace('&', '&', simplekitregistration_file_download_url($entry->id, $name));
            } elseif (is_array($value)) {
                $row[] = implode(', ', $value);
            } else {
                $row[] = $value;
            }
        }
        $row[] = $entry->ip;
        $row[] = $entry->created_at;
        fputcsv($output, $row, ';');
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export to php://output requires direct file access.
    fclose($output);
    exit;
}

// ---------------------------------------------------------------------------
// AJAX: response details (Thickbox modal)
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_get_entry', 'simplekitregistration_ajax_get_entry');

function simplekitregistration_ajax_get_entry() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
    $entry    = $entry_id > 0 ? simplekitregistration_get_entry($entry_id) : null;
    if (!$entry) {
        wp_send_json_error(['message' => __('Response not found.', 'simplekitregistration')]);
    }

    $form   = simplekitregistration_get_form($entry->form_id);
    $fields = $form ? (json_decode($form->fields_json, true) ?: []) : [];
    $data   = json_decode($entry->data_json, true) ?: [];

    $html = '<div style="padding:10px;font-family:sans-serif;">';
    /* translators: %d: response ID. */
    $html .= '<h2 style="margin-top:0;">' . esc_html(sprintf(__('Response #%d', 'simplekitregistration'), $entry->id)) . '</h2>';
    if ($form) {
        $html .= '<p><strong>' . esc_html__('Form:', 'simplekitregistration') . '</strong> ' . esc_html($form->title) . '</p>';
    }
    $html .= '<table class="widefat striped" style="width:100%;border-collapse:collapse;"><tbody>';

    foreach ($fields as $field) {
        $label = $field['label'] ?? ($field['name'] ?? '');
        $value = $data[$field['name'] ?? ''] ?? '';
        $html .= '<tr>';
        $html .= '<th style="text-align:left;padding:8px;width:30%;vertical-align:top;">' . esc_html($label) . '</th>';
        $html .= '<td style="padding:8px;">' . simplekitregistration_format_field_value($value, $field, 0, $entry->id) . '</td>';
        $html .= '</tr>';
    }

    $html .= '<tr><th style="text-align:left;padding:8px;">IP</th><td style="padding:8px;">' . esc_html($entry->ip) . '</td></tr>';
    $html .= '<tr><th style="text-align:left;padding:8px;">' . esc_html__('Date/Time', 'simplekitregistration') . '</th><td style="padding:8px;">' . esc_html($entry->created_at) . '</td></tr>';
    $html .= '</tbody></table>';

    // Messages attached to this response.
    $html .= '<div class="skr-messages" style="margin-top:18px;border-top:1px solid #eee;padding-top:12px;">';
    $html .= '<h3 style="margin:0 0 8px;font-size:15px;">' . esc_html__('Messages', 'simplekitregistration') . '</h3>';
    $html .= simplekitregistration_render_messages_list($entry->id);
    $html .= '<div id="skr-message-form-' . (int) $entry->id . '">';
    $html .= '<textarea class="simplekitregistration-message-input" rows="3" style="width:100%;" placeholder="' . esc_attr__('Write a message...', 'simplekitregistration') . '"></textarea>';
    $html .= '<p style="text-align:right;margin-top:6px;">';
    $html .= '<button type="button" class="button button-primary simplekitregistration-add-message" data-entry-id="' . (int) $entry->id . '">' . esc_html__('Send message', 'simplekitregistration') . '</button>';
    $html .= '</p></div></div>';

    $html .= '<p style="text-align:right;margin-top:15px;"><button type="button" class="button" onclick="tb_remove();">' . esc_html__('Close', 'simplekitregistration') . '</button></p>';
    $html .= '</div>';

    wp_send_json_success(['html' => $html]);
}

// ---------------------------------------------------------------------------
// AJAX: delete one response
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_delete_entry', 'simplekitregistration_ajax_delete_entry');

function simplekitregistration_ajax_delete_entry() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
    if ($entry_id <= 0) {
        wp_send_json_error(['message' => __('Invalid response.', 'simplekitregistration')]);
    }

    if (simplekitregistration_delete_entry_record($entry_id)) {
        wp_send_json_success(['message' => __('Response deleted successfully.', 'simplekitregistration')]);
    }
    wp_send_json_error(['message' => __('Error deleting response.', 'simplekitregistration')]);
}

// ---------------------------------------------------------------------------
// AJAX: delete all responses of a form
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_delete_all_entries', 'simplekitregistration_ajax_delete_all_entries');

function simplekitregistration_ajax_delete_all_entries() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $form    = $form_id > 0 ? simplekitregistration_get_form($form_id) : null;
    if (!$form) {
        wp_send_json_error(['message' => __('Invalid form.', 'simplekitregistration')]);
    }

    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM %i WHERE form_id = %d', $table, $form_id));

    // Remove the whole folder tree of this form (all entries at once).
    simplekitregistration_delete_form_files($form->code);

    // Remove the messages of every response of this form.
    simplekitregistration_delete_form_messages($form_id);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->delete($table, ['form_id' => $form_id], ['%d']);

    /* translators: %d: number of responses. */
    $message = sprintf(_n('%d response deleted.', '%d responses deleted.', (int) $deleted, 'simplekitregistration'), (int) $deleted);
    wp_send_json_success(['message' => $message]);
}

// ---------------------------------------------------------------------------
// AJAX: keep only the most recent responses
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_keep_recent_entries', 'simplekitregistration_ajax_keep_recent_entries');

function simplekitregistration_ajax_keep_recent_entries() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $keep    = isset($_POST['keep']) ? (int) $_POST['keep'] : 0;
    if ($form_id <= 0 || $keep <= 0) {
        wp_send_json_error(['message' => __('Invalid parameters.', 'simplekitregistration')]);
    }

    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $keep_ids = $wpdb->get_col($wpdb->prepare(
        'SELECT id FROM %i WHERE form_id = %d ORDER BY created_at DESC LIMIT %d',
        $table,
        $form_id,
        $keep
    ));

    if (empty($keep_ids)) {
        wp_send_json_error(['message' => __('No responses to keep.', 'simplekitregistration')]);
    }

    $keep_ids = array_map('intval', $keep_ids);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $all_ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM %i WHERE form_id = %d', $table, $form_id));

    $deleted = 0;
    foreach ($all_ids as $entry_id) {
        if (!in_array((int) $entry_id, $keep_ids, true)) {
            if (simplekitregistration_delete_entry_record((int) $entry_id)) {
                $deleted++;
            }
        }
    }

    /* translators: 1: number of deleted responses, 2: number of kept responses. */
    $message = sprintf(__('%1$d responses deleted, keeping the %2$d most recent.', 'simplekitregistration'), $deleted, $keep);
    wp_send_json_success(['message' => $message]);
}

// ---------------------------------------------------------------------------
// AJAX: delete responses older than a date
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_delete_entries_before_date', 'simplekitregistration_ajax_delete_entries_before_date');

function simplekitregistration_ajax_delete_entries_before_date() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $date    = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
    if ($form_id <= 0 || $date === '') {
        wp_send_json_error(['message' => __('Invalid parameters.', 'simplekitregistration')]);
    }

    global $wpdb;
    $table = simplekitregistration_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $ids = $wpdb->get_col($wpdb->prepare(
        'SELECT id FROM %i WHERE form_id = %d AND created_at < %s',
        $table,
        $form_id,
        $date . ' 00:00:00'
    ));

    $deleted = 0;
    foreach ($ids as $entry_id) {
        if (simplekitregistration_delete_entry_record((int) $entry_id)) {
            $deleted++;
        }
    }

    /* translators: 1: number of deleted responses, 2: date. */
    $message = sprintf(__('%1$d responses deleted before %2$s.', 'simplekitregistration'), $deleted, $date);
    wp_send_json_success(['message' => $message]);
}

// ---------------------------------------------------------------------------
// Page: responses of a form
// ---------------------------------------------------------------------------
function simplekitregistration_page_entries() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
    if ($form_id <= 0) {
        echo '<div class="wrap"><h1>' . esc_html__('Responses', 'simplekitregistration') . '</h1>';
        echo '<p>' . esc_html__('Select a form to view responses.', 'simplekitregistration') . '</p></div>';
        return;
    }

    $form = simplekitregistration_get_form($form_id);
    if (!$form) {
        echo '<div class="wrap"><h1>' . esc_html__('Responses', 'simplekitregistration') . '</h1>';
        echo '<p>' . esc_html__('Form not found.', 'simplekitregistration') . '</p></div>';
        return;
    }

    $fields = json_decode($form->fields_json, true) ?: [];

    $per_page = 15;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination parameter, no nonce needed.
    $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $offset       = ($current_page - 1) * $per_page;

    global $wpdb;
    $table = simplekitregistration_entries_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $total_entries = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM %i WHERE form_id = %d',
        $table,
        $form_id
    ));
    $total_pages = (int) ceil($total_entries / $per_page);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d',
        $table,
        $form_id,
        $per_page,
        $offset
    ));

    $base_url = admin_url('admin.php?page=simplekitregistration-entries&form_id=' . $form_id);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(sprintf(/* translators: %s: form title. */ __('Responses: %s', 'simplekitregistration'), $form->title)); ?></h1>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitregistration')); ?>" class="button"><?php esc_html_e('Back to forms list', 'simplekitregistration'); ?></a>
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('export', 'csv'), 'simplekitregistration_csv_export')); ?>" class="button button-primary"><?php esc_html_e('Export CSV', 'simplekitregistration'); ?></a>
        </p>
        <hr class="wp-header-end">

        <?php if (empty($entries)) : ?>
            <p><?php esc_html_e('No responses received for this form.', 'simplekitregistration'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" id="simplekitregistration-entries-table">
                <thead>
                    <tr>
                        <th scope="col" style="width:60px;">ID</th>
                        <?php foreach ($fields as $field) : ?>
                            <th scope="col"><?php echo esc_html($field['label'] ?? ($field['name'] ?? '')); ?></th>
                        <?php endforeach; ?>
                        <th scope="col">IP</th>
                        <th scope="col"><?php esc_html_e('Date/Time', 'simplekitregistration'); ?></th>
                        <th scope="col" style="width:150px;"><?php esc_html_e('Actions', 'simplekitregistration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry) : ?>
                        <?php $data = json_decode($entry->data_json, true) ?: []; ?>
                        <tr id="skr-entry-row-<?php echo (int) $entry->id; ?>">
                            <td><?php echo (int) $entry->id; ?></td>
                            <?php foreach ($fields as $field) : ?>
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- simplekitregistration_format_field_value() escapes internally. ?>
                                <td><?php echo simplekitregistration_format_field_value($data[$field['name'] ?? ''] ?? '', $field, 60, $entry->id); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo esc_html($entry->ip); ?></td>
                            <td><?php echo esc_html($entry->created_at); ?></td>
                            <td>
                                <button type="button" class="button button-small simplekitregistration-view-entry" data-entry-id="<?php echo (int) $entry->id; ?>"><?php esc_html_e('Details', 'simplekitregistration'); ?></button>
                                <button type="button" class="button button-small simplekitregistration-delete-entry" data-entry-id="<?php echo (int) $entry->id; ?>"><?php esc_html_e('Delete', 'simplekitregistration'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav bottom" style="margin-top:10px;">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        /* translators: %d: total number of responses. */
                        echo esc_html(sprintf(_n('%d response', '%d responses', $total_entries, 'simplekitregistration'), $total_entries));
                        ?>
                    </span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php if ($current_page > 1) : ?>
                                <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>">&laquo;</a>
                                <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>">&lsaquo;</a>
                            <?php endif; ?>
                            <span class="paging-input">
                                <span class="current-page"><?php echo (int) $current_page; ?></span>
                                <?php esc_html_e('of', 'simplekitregistration'); ?>
                                <span class="total-pages"><?php echo (int) $total_pages; ?></span>
                            </span>
                            <?php if ($current_page < $total_pages) : ?>
                                <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>">&rsaquo;</a>
                                <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>">&raquo;</a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="simplekitregistration-bulk-actions" style="margin-top:30px;padding:20px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;">
                <h3><?php esc_html_e('Bulk actions', 'simplekitregistration'); ?></h3>

                <div style="margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #ddd;">
                    <strong><?php esc_html_e('Remove all responses:', 'simplekitregistration'); ?></strong>
                    <button type="button" class="button button-link-delete simplekitregistration-bulk-delete-all" data-form-id="<?php echo (int) $form_id; ?>" style="margin-left:10px;"><?php esc_html_e('Delete all responses', 'simplekitregistration'); ?></button>
                </div>

                <div style="margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #ddd;">
                    <strong><?php esc_html_e('Keep only the most recent:', 'simplekitregistration'); ?></strong>
                    <select class="simplekitregistration-keep-count" style="margin:0 10px;">
                        <option value="200">200</option>
                        <option value="150">150</option>
                        <option value="100" selected>100</option>
                        <option value="50">50</option>
                        <option value="25">25</option>
                        <option value="10">10</option>
                    </select>
                    <button type="button" class="button button-link-delete simplekitregistration-bulk-keep-recent" data-form-id="<?php echo (int) $form_id; ?>"><?php esc_html_e('Apply', 'simplekitregistration'); ?></button>
                </div>

                <div>
                    <strong><?php esc_html_e('Remove all responses before:', 'simplekitregistration'); ?></strong>
                    <input type="date" class="simplekitregistration-before-date" style="margin:0 10px;">
                    <button type="button" class="button button-link-delete simplekitregistration-bulk-delete-before-date" data-form-id="<?php echo (int) $form_id; ?>"><?php esc_html_e('Delete', 'simplekitregistration'); ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="simplekitregistration-entry-detail" style="display:none;"></div>

    <script>
    (function($) {
        var nonce  = '<?php echo esc_js(wp_create_nonce('simplekitregistration_nonce')); ?>';
        var formId = <?php echo (int) $form_id; ?>;

        function skrLoadEntry(entryId, showModal, note) {
            var $detail = $('#simplekitregistration-entry-detail');
            $detail.html('<p style="text-align:center;padding:40px;"><?php echo esc_js(__('Loading...', 'simplekitregistration')); ?></p>');

            $.post(ajaxurl, { action: 'simplekitregistration_get_entry', entry_id: entryId, nonce: nonce }, function(response) {
                if (response.success) {
                    var html = response.data.html;
                    if (note) {
                        html = '<div class="notice notice-success inline" style="margin:0 0 10px;"><p>' + note + '</p></div>' + html;
                    }

                    $detail.html(html);

                    if (showModal) {
                        tb_show('<?php echo esc_js(__('Response details', 'simplekitregistration')); ?>', '#TB_inline?width=640&height=620&inlineId=simplekitregistration-entry-detail');
                    } else if ($('#TB_ajaxContent').length) {
                        // Thickbox renders a copy of the inline content, so the
                        // visible container has to be refreshed as well.
                        $('#TB_ajaxContent').html(html);
                        $('#TB_ajaxContent').scrollTop(0);
                    }
                } else {
                    alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Error loading details.', 'simplekitregistration')); ?>');
                }
            });
        }

        $(document).on('click', '.simplekitregistration-view-entry', function() {
            skrLoadEntry($(this).data('entry-id'), true);
        });

        // Send a message for a response (messages are permanent).
        $(document).on('click', '.simplekitregistration-add-message', function() {
            var $btn = $(this);
            var entryId = $btn.data('entry-id');
            var $input = $('#skr-message-form-' + entryId).find('.simplekitregistration-message-input');
            var text = $input.val();

            if (!text || !$.trim(text)) {
                alert('<?php echo esc_js(__('Please write a message before sending.', 'simplekitregistration')); ?>');
                return;
            }

            $btn.prop('disabled', true);
            $.post(ajaxurl, { action: 'simplekitregistration_add_message', entry_id: entryId, message: text, nonce: nonce }, function(response) {
                if (response.success) {
                    skrLoadEntry(entryId, false, '<?php echo esc_js(__('Message sent.', 'simplekitregistration')); ?>');
                } else {
                    alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('The message could not be saved.', 'simplekitregistration')); ?>');
                    $btn.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.simplekitregistration-delete-entry', function() {
            var entryId = $(this).data('entry-id');
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this response? Uploaded files will also be removed.', 'simplekitregistration')); ?>')) {
                return;
            }
            $.post(ajaxurl, { action: 'simplekitregistration_delete_entry', entry_id: entryId, nonce: nonce }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Error deleting response.', 'simplekitregistration')); ?>');
                }
            });
        });

        $(document).on('click', '.simplekitregistration-bulk-delete-all', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete ALL responses for this form? Uploaded files will also be removed.', 'simplekitregistration')); ?>')) {
                return;
            }
            $.post(ajaxurl, { action: 'simplekitregistration_delete_all_entries', form_id: formId, nonce: nonce }, function(response) {
                if (response.success) { alert(response.data.message); location.reload(); }
                else { alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Error.', 'simplekitregistration')); ?>'); }
            });
        });

        $(document).on('click', '.simplekitregistration-bulk-keep-recent', function() {
            var keep = parseInt($('.simplekitregistration-keep-count').val(), 10);
            if (!confirm('<?php echo esc_js(__('This will delete all responses except the most recent ones. Are you sure?', 'simplekitregistration')); ?>')) {
                return;
            }
            $.post(ajaxurl, { action: 'simplekitregistration_keep_recent_entries', form_id: formId, keep: keep, nonce: nonce }, function(response) {
                if (response.success) { alert(response.data.message); location.reload(); }
                else { alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Error.', 'simplekitregistration')); ?>'); }
            });
        });

        $(document).on('click', '.simplekitregistration-bulk-delete-before-date', function() {
            var date = $('.simplekitregistration-before-date').val();
            if (!date) { alert('<?php echo esc_js(__('Please select a date.', 'simplekitregistration')); ?>'); return; }
            if (!confirm('<?php echo esc_js(__('This will delete all responses before the selected date. Are you sure?', 'simplekitregistration')); ?>')) {
                return;
            }
            $.post(ajaxurl, { action: 'simplekitregistration_delete_entries_before_date', form_id: formId, date: date, nonce: nonce }, function(response) {
                if (response.success) { alert(response.data.message); location.reload(); }
                else { alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Error.', 'simplekitregistration')); ?>'); }
            });
        });
    })(jQuery);
    </script>
    <?php
}
