<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Statistics page and CSV export
// ---------------------------------------------------------------------------

function simplekitdownloads_page_statistics() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
    $download_id = isset($_GET['download']) ? absint($_GET['download']) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
    $download_name = isset($_GET['name']) ? sanitize_text_field(wp_unslash($_GET['name'])) : '';

    if ($download_id > 0 || $download_name !== '') {
        simplekitdownloads_render_statistics_detail($download_id, $download_name);
        return;
    }

    simplekitdownloads_render_statistics_overview();
}

/**
 * URL for the CSV export, protected by a nonce.
 */
function simplekitdownloads_stats_export_url($args = []) {
    $url = add_query_arg(
        array_merge(['action' => 'simplekitdownloads_export_stats'], $args),
        admin_url('admin-post.php')
    );
    return wp_nonce_url($url, 'simplekitdownloads_export_stats');
}

/**
 * Grouped statistics by download (including deleted ones).
 */
function simplekitdownloads_get_statistics_groups() {
    global $wpdb;
    $log_table = simplekitdownloads_table('log');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT download_id,
                MAX(download_name) AS download_name,
                COUNT(*) AS total,
                MIN(created_at) AS first_download,
                MAX(created_at) AS last_download
         FROM %i
         GROUP BY download_id
         ORDER BY download_name ASC",
        $log_table
    ));

    return is_array($rows) ? $rows : [];
}

function simplekitdownloads_render_statistics_overview() {
    global $wpdb;
    $log_table = simplekitdownloads_table('log');

    $downloads = simplekitdownloads_get_downloads();
    $groups    = simplekitdownloads_get_statistics_groups();

    $live = [];
    foreach ($downloads as $download) {
        $live[(int) $download->id] = [
            'id'             => (int) $download->id,
            'name'           => $download->name,
            'total'          => 0,
            'first_download' => '',
            'last_download'  => '',
            'deleted'        => false,
        ];
    }

    $deleted = [];
    foreach ($groups as $group) {
        $id = (int) $group->download_id;
        if ($id > 0 && isset($live[$id])) {
            $live[$id]['total']          = (int) $group->total;
            $live[$id]['first_download'] = (string) $group->first_download;
            $live[$id]['last_download']  = (string) $group->last_download;
            if ($group->download_name !== '') {
                $live[$id]['name'] = $group->download_name;
            }
        } else {
            $deleted[] = [
                'id'             => $id,
                'name'           => (string) $group->download_name,
                'total'          => (int) $group->total,
                'first_download' => (string) $group->first_download,
                'last_download'  => (string) $group->last_download,
                'deleted'        => true,
            ];
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $overall = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) AS total, MIN(created_at) AS first_download, MAX(created_at) AS last_download FROM %i",
        $log_table
    ));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- read-only counter of the statistics screen.
    $versions_total = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i",
        simplekitdownloads_table('versions')
    ));
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Statistics', 'simplekitdownloads'); ?></h1>
        <hr class="wp-header-end" />

        <div class="skd-cards">
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html((string) ($overall ? (int) $overall->total : 0)); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Total downloads', 'simplekitdownloads'); ?></span>
            </div>
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html((string) count($downloads)); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Downloads registered', 'simplekitdownloads'); ?></span>
            </div>
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html((string) $versions_total); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Versions registered', 'simplekitdownloads'); ?></span>
            </div>
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html($overall && $overall->last_download ? $overall->last_download : '—'); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Last download', 'simplekitdownloads'); ?></span>
            </div>
        </div>

        <p>
            <a class="button" href="<?php echo esc_url(simplekitdownloads_stats_export_url(['view' => 'overview'])); ?>">
                <?php esc_html_e('Export CSV', 'simplekitdownloads'); ?>
            </a>
            <a class="button" href="<?php echo esc_url(simplekitdownloads_stats_export_url(['view' => 'overview', 'log' => '1'])); ?>">
                <?php esc_html_e('Export full log (CSV)', 'simplekitdownloads'); ?>
            </a>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Download', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Downloads', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Oldest download', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Newest download', 'simplekitdownloads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($live)) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No downloads registered yet.', 'simplekitdownloads'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($live as $row) : ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'simplekitdownloads-stats', 'download' => $row['id']], admin_url('admin.php'))); ?>">
                                        <?php echo esc_html($row['name']); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html((string) $row['total']); ?></td>
                            <td><?php echo $row['first_download'] !== '' ? esc_html($row['first_download']) : '—'; ?></td>
                            <td><?php echo $row['last_download'] !== '' ? esc_html($row['last_download']) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($deleted)) : ?>
            <h2><?php esc_html_e('Deleted downloads', 'simplekitdownloads'); ?></h2>
            <p class="description">
                <?php esc_html_e('These records were kept after the download was removed.', 'simplekitdownloads'); ?>
            </p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Download', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Downloads', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Oldest download', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Newest download', 'simplekitdownloads'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted as $row) : ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php
                                    $detail_args = ['page' => 'simplekitdownloads-stats'];
                                    if ($row['id'] > 0) {
                                        $detail_args['download'] = $row['id'];
                                    } else {
                                        $detail_args['name'] = $row['name'];
                                    }
                                    ?>
                                    <a href="<?php echo esc_url(add_query_arg($detail_args, admin_url('admin.php'))); ?>">
                                        <?php echo esc_html($row['name'] !== '' ? $row['name'] : __('(unnamed)', 'simplekitdownloads')); ?>
                                    </a>
                                </strong>
                                <br /><span class="skd-missing"><?php esc_html_e('Deleted', 'simplekitdownloads'); ?></span>
                            </td>
                            <td><?php echo esc_html((string) $row['total']); ?></td>
                            <td><?php echo $row['first_download'] !== '' ? esc_html($row['first_download']) : '—'; ?></td>
                            <td><?php echo $row['last_download'] !== '' ? esc_html($row['last_download']) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Per-version statistics of one download (live or deleted).
 */
function simplekitdownloads_get_statistics_versions($download_id, $download_name) {
    global $wpdb;
    $log_table = simplekitdownloads_table('log');

    if ($download_id > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare(
            "SELECT version_label,
                    MAX(version_sort) AS version_sort,
                    COUNT(*) AS total,
                    MIN(created_at) AS first_download,
                    MAX(created_at) AS last_download
             FROM %i
             WHERE download_id = %d
             GROUP BY version_label
             ORDER BY version_sort DESC",
            $log_table,
            $download_id
        ));
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results($wpdb->prepare(
        "SELECT version_label,
                MAX(version_sort) AS version_sort,
                COUNT(*) AS total,
                MIN(created_at) AS first_download,
                MAX(created_at) AS last_download
         FROM %i
         WHERE download_name = %s
         GROUP BY version_label
         ORDER BY version_sort DESC",
        $log_table,
        $download_name
    ));
}

function simplekitdownloads_render_statistics_detail($download_id, $download_name) {
    $download = $download_id > 0 ? simplekitdownloads_get_download($download_id) : null;
    $name     = $download ? $download->name : $download_name;

    // Deleted download addressed by ID: recover the name from the log snapshot.
    if (!$download && $download_id > 0) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $name = (string) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(download_name) FROM %i WHERE download_id = %d",
            simplekitdownloads_table('log'),
            $download_id
        ));
    }

    if (!$download && $name === '') {
        echo '<div class="wrap"><div class="notice notice-error"><p>'
            . esc_html__('Download not found.', 'simplekitdownloads')
            . '</p></div></div>';
        return;
    }

    $rows  = simplekitdownloads_get_statistics_versions($download_id, $name);
    $total = 0;
    foreach ((array) $rows as $row) {
        $total += (int) $row->total;
    }

    $export_args = ['view' => 'detail'];
    if ($download_id > 0) {
        $export_args['download'] = $download_id;
    } else {
        $export_args['name'] = $name;
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php
            /* translators: %s: download name. */
            echo esc_html(sprintf(__('Statistics: %s', 'simplekitdownloads'), $name));
            ?>
        </h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'simplekitdownloads-stats'], admin_url('admin.php'))); ?>" class="page-title-action">
            <?php esc_html_e('Back to Statistics', 'simplekitdownloads'); ?>
        </a>
        <hr class="wp-header-end" />

        <?php if (!$download) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('This download was deleted. Its statistics are kept below.', 'simplekitdownloads'); ?></p>
            </div>
        <?php endif; ?>

        <div class="skd-cards">
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html((string) $total); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Downloads', 'simplekitdownloads'); ?></span>
            </div>
            <div class="skd-card-stat">
                <span class="skd-stat-value"><?php echo esc_html((string) count((array) $rows)); ?></span>
                <span class="skd-stat-label"><?php esc_html_e('Versions with downloads', 'simplekitdownloads'); ?></span>
            </div>
        </div>

        <p>
            <a class="button" href="<?php echo esc_url(simplekitdownloads_stats_export_url($export_args)); ?>">
                <?php esc_html_e('Export CSV', 'simplekitdownloads'); ?>
            </a>
            <a class="button" href="<?php echo esc_url(simplekitdownloads_stats_export_url(array_merge($export_args, ['log' => '1']))); ?>">
                <?php esc_html_e('Export full log (CSV)', 'simplekitdownloads'); ?>
            </a>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Version', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Downloads', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Oldest download', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Newest download', 'simplekitdownloads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No downloads recorded for this download.', 'simplekitdownloads'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($row->version_label); ?></strong></td>
                            <td><?php echo esc_html((string) (int) $row->total); ?></td>
                            <td><?php echo $row->first_download !== '' ? esc_html($row->first_download) : '—'; ?></td>
                            <td><?php echo $row->last_download !== '' ? esc_html($row->last_download) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// CSV export
// ---------------------------------------------------------------------------

/**
 * Neutralize spreadsheet formula injection.
 */
function simplekitdownloads_csv_cell($value) {
    $value = (string) $value;
    if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
        $value = "'" . $value;
    }
    return $value;
}

add_action('admin_post_simplekitdownloads_export_stats', 'simplekitdownloads_handle_export_stats');

function simplekitdownloads_handle_export_stats() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_export_stats');

    $view          = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : 'overview';
    $download_id   = isset($_GET['download']) ? absint($_GET['download']) : 0;
    $download_name = isset($_GET['name']) ? sanitize_text_field(wp_unslash($_GET['name'])) : '';
    $full_log      = isset($_GET['log']) && sanitize_text_field(wp_unslash($_GET['log'])) === '1';

    $filename = 'simplekitdownloads-statistics';
    if ($view === 'detail') {
        $label    = $download_name;
        if ($download_id > 0) {
            $download = simplekitdownloads_get_download($download_id);
            if ($download) {
                $label = $download->name;
            }
        }
        $slug     = sanitize_title($label);
        $filename = $slug !== '' ? $slug . '-statistics' : $filename;
    }
    if ($full_log) {
        $filename .= '-log';
    }

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $output = fopen('php://output', 'w');
    if (!$output) {
        exit;
    }

    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    if ($full_log) {
        simplekitdownloads_export_full_log($output, $download_id, $download_name);
    } elseif ($view === 'detail') {
        simplekitdownloads_export_detail_csv($output, $download_id, $download_name);
    } else {
        simplekitdownloads_export_overview_csv($output);
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($output);
    exit;
}

function simplekitdownloads_export_overview_csv($output) {
    fputcsv($output, ['Download', 'Downloads', 'Oldest download', 'Newest download'], ';', '"', '');

    $downloads = simplekitdownloads_get_downloads();
    $groups    = simplekitdownloads_get_statistics_groups();

    $by_id = [];
    foreach ($groups as $group) {
        $by_id[(int) $group->download_id] = $group;
    }

    $seen = [];
    foreach ($downloads as $download) {
        $id    = (int) $download->id;
        $group = isset($by_id[$id]) ? $by_id[$id] : null;
        $seen[$id] = true;

        fputcsv($output, [
            simplekitdownloads_csv_cell($download->name),
            $group ? (int) $group->total : 0,
            $group ? simplekitdownloads_csv_cell($group->first_download) : '',
            $group ? simplekitdownloads_csv_cell($group->last_download) : '',
        ], ';', '"', '');
    }

    foreach ($groups as $group) {
        $id = (int) $group->download_id;
        if (isset($seen[$id])) {
            continue;
        }
        fputcsv($output, [
            simplekitdownloads_csv_cell($group->download_name),
            (int) $group->total,
            simplekitdownloads_csv_cell($group->first_download),
            simplekitdownloads_csv_cell($group->last_download),
        ], ';', '"', '');
    }
}

function simplekitdownloads_export_detail_csv($output, $download_id, $download_name) {
    fputcsv($output, ['Version', 'Downloads', 'Oldest download', 'Newest download'], ';', '"', '');

    $rows = simplekitdownloads_get_statistics_versions($download_id, $download_name);
    foreach ((array) $rows as $row) {
        fputcsv($output, [
            simplekitdownloads_csv_cell($row->version_label),
            (int) $row->total,
            simplekitdownloads_csv_cell($row->first_download),
            simplekitdownloads_csv_cell($row->last_download),
        ], ';', '"', '');
    }
}

function simplekitdownloads_export_full_log($output, $download_id = 0, $download_name = '') {
    global $wpdb;
    $log_table = simplekitdownloads_table('log');

    fputcsv($output, ['ID', 'Download', 'Version', 'IP', 'Date/Time'], ';', '"', '');

    $limit  = 1000;
    $offset = 0;

    do {
        if ($download_id > 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, download_name, version_label, ip, created_at FROM %i WHERE download_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
                $log_table,
                $download_id,
                $limit,
                $offset
            ));
        } elseif ($download_name !== '') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, download_name, version_label, ip, created_at FROM %i WHERE download_name = %s ORDER BY id ASC LIMIT %d OFFSET %d",
                $log_table,
                $download_name,
                $limit,
                $offset
            ));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, download_name, version_label, ip, created_at FROM %i ORDER BY id ASC LIMIT %d OFFSET %d",
                $log_table,
                $limit,
                $offset
            ));
        }

        foreach ((array) $rows as $row) {
            fputcsv($output, [
                (int) $row->id,
                simplekitdownloads_csv_cell($row->download_name),
                simplekitdownloads_csv_cell($row->version_label),
                simplekitdownloads_csv_cell($row->ip),
                simplekitdownloads_csv_cell($row->created_at),
            ], ';', '"', '');
        }

        $count   = is_array($rows) ? count($rows) : 0;
        $offset += $limit;
    } while ($count === $limit);
}
