<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Registrar subpágina de Entradas (não aparece no menu, mas é acessível)
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitforms_entries_subpage');

function simplekitforms_entries_subpage() {
    add_submenu_page(
        null,
        'Responses',
        'Responses',
        'manage_options',
        'simplekitforms-entries',
        'simplekitforms_page_entries'
    );
}

// ---------------------------------------------------------------------------
// Exportar CSV antes de qualquer saída (admin_init)
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitforms_handle_csv_export');

function simplekitforms_handle_csv_export() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'simplekitforms-entries') {
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    if (!isset($_GET['export']) || sanitize_text_field(wp_unslash($_GET['export'])) !== 'csv') {
        return;
    }
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
    if ($form_id <= 0) {
        return;
    }

    $form = simplekitforms_get_form($form_id);
    if (!$form) {
        return;
    }

    $fields = json_decode($form->fields_json, true) ?: [];

    global $wpdb;
    $table = simplekitforms_get_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC",
        $table,
        $form_id
    ));

    // Headers devem ser enviados antes de qualquer saída
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_title($form->title) . '-responses.csv"');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Linha de cabeçalho
    $headers = ['ID'];
    foreach ($fields as $field) {
        $headers[] = $field['label'] ?? $field['name'] ?? '';
    }
    $headers[] = 'IP';
    $headers[] = 'Date/Time';
    fputcsv($output, $headers, ';');

    // Linhas de dados
    foreach ($entries as $entry) {
        $data = json_decode($entry->data_json, true) ?: [];
        $row = [$entry->id];
        foreach ($fields as $field) {
            $row[] = simplekitforms_format_field_value($data[$field['name'] ?? ''] ?? '', $field);
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
// AJAX: obter detalhes de uma resposta (para o modal)
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitforms_get_entry', 'simplekitforms_ajax_get_entry');

function simplekitforms_ajax_get_entry() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied.']);
    }
    check_ajax_referer('simplekitforms_nonce', 'nonce');

    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
    if ($entry_id <= 0) {
        wp_send_json_error(['message' => 'Invalid response.']);
    }

    global $wpdb;
    $table = simplekitforms_get_entries_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, $entry_id));

    if (!$entry) {
        wp_send_json_error(['message' => 'Response not found.']);
    }

    $form = simplekitforms_get_form($entry->form_id);
    $fields = $form ? (json_decode($form->fields_json, true) ?: []) : [];
    $data = json_decode($entry->data_json, true) ?: [];

    // Montar HTML detalhado
    $html = '<div style="padding:10px;font-family:sans-serif;">';
    $html .= '<h2 style="margin-top:0;">' . esc_html(sprintf('Response #%d', $entry->id)) . '</h2>';
    if ($form) {
        $html .= '<p><strong>Form:</strong> ' . esc_html($form->title) . '</p>';
    }
    $html .= '<table class="widefat striped" style="width:100%;border-collapse:collapse;">';
    $html .= '<tbody>';

    foreach ($fields as $field) {
        $label = $field['label'] ?? $field['name'] ?? '';
        $value = $data[$field['name'] ?? ''] ?? '';
        $html .= '<tr>';
        $html .= '<th style="text-align:left;padding:8px;width:30%;vertical-align:top;">' . esc_html($label) . '</th>';
        if (is_array($value)) {
            $formatted = implode('<br>', array_map('esc_html', $value));
        } else {
            $formatted = esc_html((string) $value);
        }
        $html .= '<td style="padding:8px;">' . $formatted . '</td>';
        $html .= '</tr>';
    }

    $html .= '<tr>';
    $html .= '<th style="text-align:left;padding:8px;">IP</th>';
    $html .= '<td style="padding:8px;">' . esc_html($entry->ip) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th style="text-align:left;padding:8px;">Date/Time</th>';
    $html .= '<td style="padding:8px;">' . esc_html($entry->created_at) . '</td>';
    $html .= '</tr>';

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '<p style="text-align:right;margin-top:15px;">';
    $html .= '<button type="button" class="button" onclick="tb_remove();">Close</button>';
    $html .= '</p>';
    $html .= '</div>';

    wp_send_json_success(['html' => $html]);
}

// ---------------------------------------------------------------------------
// Página: Lista de Respostas de um Formulário
// ---------------------------------------------------------------------------
function simplekitforms_page_entries() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied.');
    }

    // Garantir que Thickbox esteja disponível
    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page URL parameter, no nonce needed.
    $form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
    if ($form_id <= 0) {
        echo '<div class="wrap"><h1>Responses</h1>';
        echo '<p>Select a form to view responses.</p>';
        echo '</div>';
        return;
    }

    $form = simplekitforms_get_form($form_id);
    if (!$form) {
        echo '<div class="wrap"><h1>Responses</h1>';
        echo '<p>Form not found.</p>';
        echo '</div>';
        return;
    }

    $fields = json_decode($form->fields_json, true) ?: [];

    // Pagination
    $per_page = 15;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination parameter, no nonce needed.
    $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;

    global $wpdb;
    $table = simplekitforms_get_entries_table();

    // Get total count for pagination
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $total_entries = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE form_id = %d",
        $table,
        $form_id
    ));
    $total_pages = ceil($total_entries / $per_page);

    // Get paginated entries
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM %i WHERE form_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $table,
        $form_id,
        $per_page,
        $offset
    ));

    // Build pagination URLs
    $base_url = admin_url('admin.php?page=simplekitforms-entries&form_id=' . $form_id);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(sprintf('Responses: %s', $form->title)); ?></h1>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitforms')); ?>" class="button">Back to forms list</a>
            <a href="<?php echo esc_url(add_query_arg('export', 'csv')); ?>" class="button button-primary">Export CSV</a>
        </p>
        <hr class="wp-header-end">

        <?php if (empty($entries)) : ?>
            <p>No responses received for this form.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width:60px;">ID</th>
                        <?php foreach ($fields as $field) : ?>
                            <th scope="col"><?php echo esc_html($field['label'] ?? $field['name'] ?? ''); ?></th>
                        <?php endforeach; ?>
                        <th scope="col">IP</th>
                        <th scope="col">Date/Time</th>
                        <th scope="col" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry) : ?>
                        <?php $data = json_decode($entry->data_json, true) ?: []; ?>
                        <tr>
                            <td><?php echo (int) $entry->id; ?></td>
                            <?php foreach ($fields as $field) : ?>
                                <td><?php echo esc_html(simplekitforms_format_field_value($data[$field['name'] ?? ''] ?? '', $field)); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo esc_html($entry->ip); ?></td>
                            <td><?php echo esc_html($entry->created_at); ?></td>
                            <td>
                                <button type="button" class="button button-small simplekitforms-view-entry" data-entry-id="<?php echo (int) $entry->id; ?>">
                                    Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="tablenav bottom" style="margin-top:10px;">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo esc_html(sprintf('%d total responses', $total_entries)); ?></span>
                    <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php if ($current_page > 1) : ?>
                                <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">&laquo;</span></a>
                                <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">&lsaquo;</span></a>
                            <?php else : ?>
                                <span class="button disabled" aria-disabled="true">&laquo;</span>
                                <span class="button disabled" aria-disabled="true">&lsaquo;</span>
                            <?php endif; ?>

                            <span class="paging-input">
                                <span class="current-page"><?php echo (int) $current_page; ?></span>
                                of
                                <span class="total-pages"><?php echo (int) $total_pages; ?></span>
                            </span>

                            <?php if ($current_page < $total_pages) : ?>
                                <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">&rsaquo;</span></a>
                                <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">&raquo;</span></a>
                            <?php else : ?>
                                <span class="button disabled" aria-disabled="true">&rsaquo;</span>
                                <span class="button disabled" aria-disabled="true">&raquo;</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Área para o modal Thickbox (carregado via AJAX) -->
    <div id="simplekitforms-entry-detail" style="display:none;"></div>

    <script>
    (function($) {
        $(document).on('click', '.simplekitforms-view-entry', function() {
            var entryId = $(this).data('entry-id');
            var $detail = $('#simplekitforms-entry-detail');

            $detail.html('<p style="text-align:center;padding:40px;">Loading...</p>');

            $.post(ajaxurl, {
                action: 'simplekitforms_get_entry',
                entry_id: entryId,
                nonce: '<?php echo esc_js(wp_create_nonce('simplekitforms_nonce')); ?>'
            }, function(response) {
                if (response.success) {
                    $detail.html(response.data.html);
                    tb_show('Response details', '#TB_inline?width=640&height=500&inlineId=simplekitforms-entry-detail');
                } else {
                    alert(response.data.message || 'Error loading details.');
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// Formatar valor do campo para exibição
// ---------------------------------------------------------------------------
function simplekitforms_format_field_value($value, $field) {
    if (is_array($value)) {
        return implode(', ', array_map('esc_html', $value));
    }
    return esc_html((string) $value);
}
