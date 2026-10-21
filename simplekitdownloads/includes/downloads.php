<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Downloads and versions: data access
// ---------------------------------------------------------------------------

function simplekitdownloads_get_download($download_id) {
    global $wpdb;
    $table = simplekitdownloads_table('downloads');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, (int) $download_id));
}

function simplekitdownloads_get_downloads($search = '') {
    global $wpdb;
    $table = simplekitdownloads_table('downloads');

    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE name LIKE %s ORDER BY name ASC", $table, $like));
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY name ASC", $table));
}

function simplekitdownloads_get_versions($download_id) {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM %i WHERE download_id = %d ORDER BY version_sort DESC, id DESC",
        $table,
        (int) $download_id
    ));
}

function simplekitdownloads_get_version($version_id) {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table, (int) $version_id));
}

function simplekitdownloads_get_version_by_label($download_id, $label) {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM %i WHERE download_id = %d AND version = %s LIMIT 1",
        $table,
        (int) $download_id,
        (string) $label
    ));
}

function simplekitdownloads_get_latest_version($download_id) {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM %i WHERE download_id = %d ORDER BY version_sort DESC, id DESC LIMIT 1",
        $table,
        (int) $download_id
    ));
}

function simplekitdownloads_get_version_counts() {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $rows = $wpdb->get_results($wpdb->prepare("SELECT download_id, COUNT(*) AS total FROM %i GROUP BY download_id", $table));

    $counts = [];
    foreach ((array) $rows as $row) {
        $counts[(int) $row->download_id] = (int) $row->total;
    }
    return $counts;
}

function simplekitdownloads_get_latest_version_map() {
    global $wpdb;
    $table = simplekitdownloads_table('versions');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT download_id, version FROM %i ORDER BY version_sort DESC, id DESC",
        $table
    ));

    $map = [];
    foreach ((array) $rows as $row) {
        $id = (int) $row->download_id;
        if (!isset($map[$id])) {
            $map[$id] = $row->version;
        }
    }
    return $map;
}

function simplekitdownloads_get_download_totals() {
    global $wpdb;
    $table = simplekitdownloads_table('log');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT download_id, COUNT(*) AS total, MAX(created_at) AS last_download
         FROM %i WHERE download_id IS NOT NULL GROUP BY download_id",
        $table
    ));

    $totals = [];
    foreach ((array) $rows as $row) {
        $totals[(int) $row->download_id] = [
            'total'         => (int) $row->total,
            'last_download' => $row->last_download,
        ];
    }
    return $totals;
}

function simplekitdownloads_get_download_total($download_id) {
    global $wpdb;
    $table = simplekitdownloads_table('log');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE download_id = %d",
        $table,
        (int) $download_id
    ));
}

function simplekitdownloads_update_log_download_name($download_id, $name) {
    global $wpdb;
    $table = simplekitdownloads_table('log');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->update($table, ['download_name' => $name], ['download_id' => (int) $download_id]);
}

/**
 * Public URL of the download endpoint. Version "current" is resolved at click
 * time, so cached pages always deliver the latest version.
 */
function simplekitdownloads_get_download_url($download_id, $version = 'current') {
    $version = ($version === null || $version === '') ? 'current' : (string) $version;

    return add_query_arg([
        'action' => 'simplekitdownloads_file',
        'd'      => (int) $download_id,
        'v'      => $version,
    ], admin_url('admin-post.php'));
}

/**
 * Unique slug for a download name.
 */
function simplekitdownloads_unique_slug($name, $exclude_id = 0) {
    global $wpdb;
    $table = simplekitdownloads_table('downloads');

    $base = sanitize_title($name);
    if ($base === '') {
        $base = 'download';
    }

    $slug    = $base;
    $counter = 2;
    while (true) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM %i WHERE slug = %s AND id <> %d LIMIT 1",
            $table,
            $slug,
            (int) $exclude_id
        ));
        if (!$exists) {
            return $slug;
        }
        $slug = $base . '-' . $counter;
        $counter++;
    }
}

// ---------------------------------------------------------------------------
// Redirect helpers
// ---------------------------------------------------------------------------
function simplekitdownloads_admin_url($args = []) {
    return add_query_arg(array_merge(['page' => 'simplekitdownloads'], $args), admin_url('admin.php'));
}

function simplekitdownloads_redirect($url, $message = '', $type = 'success') {
    if ($message !== '') {
        $url = add_query_arg([
            'skd_message' => $message,
            'skd_type'    => ($type === 'error') ? 'error' : 'success',
        ], $url);
    }
    wp_safe_redirect($url);
    exit;
}

function simplekitdownloads_admin_notice() {
    if (!isset($_GET['skd_message'])) {
        return;
    }
    $message = sanitize_text_field(wp_unslash($_GET['skd_message']));
    $type    = isset($_GET['skd_type']) ? sanitize_text_field(wp_unslash($_GET['skd_type'])) : 'success';
    $class   = ($type === 'error') ? 'notice-error' : 'notice-success';

    echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
}

// ---------------------------------------------------------------------------
// Admin action: create/update a download
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_save_download', 'simplekitdownloads_handle_save_download');

function simplekitdownloads_handle_save_download() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_save_download');

    global $wpdb;
    $table       = simplekitdownloads_table('downloads');
    $download_id = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
    $name        = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';

    if ($name === '') {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Please enter a name for the download.', 'simplekitdownloads'), 'error');
    }

    if ($download_id > 0) {
        if (!simplekitdownloads_get_download($download_id)) {
            simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Download not found.', 'simplekitdownloads'), 'error');
        }

        $wpdb->update(
            $table,
            [
                'name' => $name,
                'slug' => simplekitdownloads_unique_slug($name, $download_id),
            ],
            ['id' => $download_id]
        );

        // Keep the statistics in sync with the current name (Q3).
        simplekitdownloads_update_log_download_name($download_id, $name);

        simplekitdownloads_redirect(
            simplekitdownloads_admin_url(['action' => 'edit', 'id' => $download_id]),
            __('Download updated.', 'simplekitdownloads')
        );
    }

    $inserted = $wpdb->insert($table, [
        'name' => $name,
        'slug' => simplekitdownloads_unique_slug($name),
    ]);

    if (!$inserted) {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(['action' => 'new']), __('The download could not be created.', 'simplekitdownloads'), 'error');
    }

    simplekitdownloads_redirect(
        simplekitdownloads_admin_url(['action' => 'edit', 'id' => (int) $wpdb->insert_id]),
        __('Download created. Now add a version to it.', 'simplekitdownloads')
    );
}

// ---------------------------------------------------------------------------
// Admin action: delete a download (statistics are kept)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_delete_download', 'simplekitdownloads_handle_delete_download');

function simplekitdownloads_handle_delete_download() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_delete_download');

    global $wpdb;
    $download_id = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
    $download    = simplekitdownloads_get_download($download_id);

    if (!$download) {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Download not found.', 'simplekitdownloads'), 'error');
    }

    // Remove version rows and their files; log rows keep the snapshot name.
    $wpdb->delete(simplekitdownloads_table('versions'), ['download_id' => $download_id]);
    simplekitdownloads_delete_download_files($download_id);
    $wpdb->delete(simplekitdownloads_table('downloads'), ['id' => $download_id]);

    simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Download deleted. Its statistics were kept.', 'simplekitdownloads'));
}

// ---------------------------------------------------------------------------
// Admin action: add a version
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_add_version', 'simplekitdownloads_handle_add_version');

function simplekitdownloads_handle_add_version() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_add_version');

    global $wpdb;
    $versions_table = simplekitdownloads_table('versions');
    $download_id    = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
    $download       = simplekitdownloads_get_download($download_id);
    $edit_url       = simplekitdownloads_admin_url(['action' => 'edit', 'id' => $download_id]);

    if (!$download) {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Download not found.', 'simplekitdownloads'), 'error');
    }

    $label = simplekitdownloads_validate_version_label(isset($_POST['version']) ? wp_unslash($_POST['version']) : '');
    if (is_wp_error($label)) {
        simplekitdownloads_redirect($edit_url, $label->get_error_message(), 'error');
    }

    if (simplekitdownloads_get_version_by_label($download_id, $label)) {
        simplekitdownloads_redirect($edit_url, __('This version already exists for this download.', 'simplekitdownloads'), 'error');
    }

    $source = (isset($_POST['source']) && sanitize_text_field(wp_unslash($_POST['source'])) === 'url') ? 'url' : 'upload';

    $inserted = $wpdb->insert($versions_table, [
        'download_id'  => $download_id,
        'version'      => $label,
        'version_sort' => simplekitdownloads_version_sort_key($label),
        'source'       => $source,
    ]);

    if (!$inserted) {
        simplekitdownloads_redirect($edit_url, __('The version could not be created. It may already exist.', 'simplekitdownloads'), 'error');
    }

    $version_id = (int) $wpdb->insert_id;

    if ($source === 'url') {
        $url = simplekitdownloads_validate_internal_url(isset($_POST['url']) ? wp_unslash($_POST['url']) : '');
        if (is_wp_error($url)) {
            $wpdb->delete($versions_table, ['id' => $version_id]);
            simplekitdownloads_redirect($edit_url, $url->get_error_message(), 'error');
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $wpdb->update($versions_table, [
            'file_url'  => $url,
            'file_name' => $path ? basename($path) : '',
            'file_size' => 0,
        ], ['id' => $version_id]);

        simplekitdownloads_redirect($edit_url, __('Version added.', 'simplekitdownloads'));
    }

    if (empty($_FILES['file']) || empty($_FILES['file']['name'])) {
        $wpdb->delete($versions_table, ['id' => $version_id]);
        simplekitdownloads_redirect($edit_url, __('Please select a file to upload.', 'simplekitdownloads'), 'error');
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- file array handled by wp_handle_upload.
    $uploaded = simplekitdownloads_handle_upload($_FILES['file'], $download_id, $version_id);
    if (is_wp_error($uploaded)) {
        $wpdb->delete($versions_table, ['id' => $version_id]);
        simplekitdownloads_delete_version_files($download_id, $version_id);
        simplekitdownloads_redirect($edit_url, $uploaded->get_error_message(), 'error');
    }

    $wpdb->update($versions_table, [
        'file_path' => $uploaded['file_path'],
        'file_name' => $uploaded['file_name'],
        'file_size' => $uploaded['file_size'],
    ], ['id' => $version_id]);

    simplekitdownloads_redirect($edit_url, __('Version added.', 'simplekitdownloads'));
}

// ---------------------------------------------------------------------------
// Admin action: update a version (replace file or switch source)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_update_version', 'simplekitdownloads_handle_update_version');

function simplekitdownloads_handle_update_version() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_update_version');

    global $wpdb;
    $versions_table = simplekitdownloads_table('versions');
    $version_id     = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
    $version        = simplekitdownloads_get_version($version_id);

    if (!$version) {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Version not found.', 'simplekitdownloads'), 'error');
    }

    $download_id = (int) $version->download_id;
    $edit_url    = simplekitdownloads_admin_url(['action' => 'edit', 'id' => $download_id, 'version' => $version_id]);
    $source      = (isset($_POST['source']) && sanitize_text_field(wp_unslash($_POST['source'])) === 'url') ? 'url' : 'upload';
    $old_path    = simplekitdownloads_version_file_path($version);
    $old_source  = (string) $version->source;

    if ($source === 'url') {
        $url = simplekitdownloads_validate_internal_url(isset($_POST['url']) ? wp_unslash($_POST['url']) : '');
        if (is_wp_error($url)) {
            simplekitdownloads_redirect($edit_url, $url->get_error_message(), 'error');
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $wpdb->update($versions_table, [
            'source'    => 'url',
            'file_url'  => $url,
            'file_path' => '',
            'file_name' => $path ? basename($path) : '',
            'file_size' => 0,
        ], ['id' => $version_id]);

        if ($old_source === 'upload' && $old_path !== '') {
            simplekitdownloads_delete_version_files($download_id, $version_id);
        }

        simplekitdownloads_redirect($edit_url, __('Version updated.', 'simplekitdownloads'));
    }

    $has_new_file = !empty($_FILES['file']) && !empty($_FILES['file']['name']);

    if (!$has_new_file) {
        if ($old_source === 'upload') {
            $wpdb->update($versions_table, ['source' => 'upload'], ['id' => $version_id]);
            simplekitdownloads_redirect($edit_url, __('No new file was selected. The current file was kept.', 'simplekitdownloads'));
        }
        simplekitdownloads_redirect($edit_url, __('Please select a file to upload.', 'simplekitdownloads'), 'error');
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- file array handled by wp_handle_upload.
    $uploaded = simplekitdownloads_handle_upload($_FILES['file'], $download_id, $version_id);
    if (is_wp_error($uploaded)) {
        simplekitdownloads_redirect($edit_url, $uploaded->get_error_message(), 'error');
    }

    $wpdb->update($versions_table, [
        'source'    => 'upload',
        'file_path' => $uploaded['file_path'],
        'file_url'  => '',
        'file_name' => $uploaded['file_name'],
        'file_size' => $uploaded['file_size'],
    ], ['id' => $version_id]);

    // Discard the previous binary when it is different from the new one.
    if ($old_path !== '' && $old_path !== $uploaded['file']) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        @unlink($old_path);
    }

    simplekitdownloads_redirect($edit_url, __('Version updated.', 'simplekitdownloads'));
}

// ---------------------------------------------------------------------------
// Admin action: delete a version (statistics are kept)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitdownloads_delete_version', 'simplekitdownloads_handle_delete_version');

function simplekitdownloads_handle_delete_version() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }
    check_admin_referer('simplekitdownloads_delete_version');

    global $wpdb;
    $versions_table = simplekitdownloads_table('versions');
    $version_id     = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
    $version        = simplekitdownloads_get_version($version_id);

    if (!$version) {
        simplekitdownloads_redirect(simplekitdownloads_admin_url(), __('Version not found.', 'simplekitdownloads'), 'error');
    }

    $download_id = (int) $version->download_id;

    $wpdb->delete($versions_table, ['id' => $version_id]);
    simplekitdownloads_delete_version_files($download_id, $version_id);

    simplekitdownloads_redirect(
        simplekitdownloads_admin_url(['action' => 'edit', 'id' => $download_id]),
        __('Version deleted. Its statistics were kept.', 'simplekitdownloads')
    );
}

// ---------------------------------------------------------------------------
// Page: Files
// ---------------------------------------------------------------------------
function simplekitdownloads_page_files() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitdownloads'));
    }

    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    $id     = isset($_GET['id']) ? absint($_GET['id']) : 0;

    simplekitdownloads_admin_notice();

    if ($action === 'new') {
        simplekitdownloads_render_download_editor(0);
        return;
    }
    if ($action === 'edit' && $id > 0) {
        simplekitdownloads_render_download_editor($id);
        return;
    }

    simplekitdownloads_render_downloads_list();
}

function simplekitdownloads_render_downloads_list() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search.
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

    $downloads     = simplekitdownloads_get_downloads($search);
    $version_count = simplekitdownloads_get_version_counts();
    $latest        = simplekitdownloads_get_latest_version_map();
    $totals        = simplekitdownloads_get_download_totals();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Files', 'simplekitdownloads'); ?></h1>
        <a href="<?php echo esc_url(simplekitdownloads_admin_url(['action' => 'new'])); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'simplekitdownloads'); ?>
        </a>
        <hr class="wp-header-end" />

        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="search-form">
            <input type="hidden" name="page" value="simplekitdownloads" />
            <label for="simplekitdownloads-search" class="screen-reader-text">
                <?php esc_html_e('Search downloads', 'simplekitdownloads'); ?>
            </label>
            <input type="search" id="simplekitdownloads-search" name="s"
                   value="<?php echo esc_attr($search); ?>"
                   placeholder="<?php esc_attr_e('Search downloads', 'simplekitdownloads'); ?>" />
            <button type="submit" class="button"><?php esc_html_e('Search', 'simplekitdownloads'); ?></button>
            <?php if ($search !== '') : ?>
                <a href="<?php echo esc_url(simplekitdownloads_admin_url()); ?>" class="button">
                    <?php esc_html_e('Clear', 'simplekitdownloads'); ?>
                </a>
            <?php endif; ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Name', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Versions', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Downloads', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Last download', 'simplekitdownloads'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'simplekitdownloads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($downloads)) : ?>
                    <tr>
                        <td colspan="5">
                            <?php echo $search !== ''
                                ? esc_html__('No downloads found for this search.', 'simplekitdownloads')
                                : esc_html__('No downloads yet. Use "Add New" to create the first one.', 'simplekitdownloads'); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($downloads as $download) : ?>
                        <?php
                        $download_id = (int) $download->id;
                        $versions    = isset($version_count[$download_id]) ? (int) $version_count[$download_id] : 0;
                        $total       = isset($totals[$download_id]) ? (int) $totals[$download_id]['total'] : 0;
                        $last        = isset($totals[$download_id]) ? $totals[$download_id]['last_download'] : '';
                        $edit_url    = simplekitdownloads_admin_url(['action' => 'edit', 'id' => $download_id]);
                        $stats_url   = add_query_arg(
                            ['page' => 'simplekitdownloads-stats', 'download' => $download_id],
                            admin_url('admin.php')
                        );
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($download->name); ?></a></strong>
                            </td>
                            <td>
                                <?php echo esc_html((string) $versions); ?>
                                <?php if ($versions > 0 && isset($latest[$download_id])) : ?>
                                    <br /><span class="description">
                                        <?php
                                        /* translators: %s: version label. */
                                        echo esc_html(sprintf(__('latest: %s', 'simplekitdownloads'), $latest[$download_id]));
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($stats_url); ?>"><?php echo esc_html((string) $total); ?></a>
                            </td>
                            <td><?php echo $last !== '' ? esc_html($last) : '&mdash;'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'simplekitdownloads'); ?>
                                </a>
                                <a href="<?php echo esc_url($stats_url); ?>" class="button button-small">
                                    <?php esc_html_e('Statistics', 'simplekitdownloads'); ?>
                                </a>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                      style="display:inline-block;margin:0;"
                                      onsubmit="return confirm('<?php echo esc_js(__('Delete this download? The statistics will be kept.', 'simplekitdownloads')); ?>');">
                                    <input type="hidden" name="action" value="simplekitdownloads_delete_download" />
                                    <input type="hidden" name="download_id" value="<?php echo esc_attr($download_id); ?>" />
                                    <?php wp_nonce_field('simplekitdownloads_delete_download'); ?>
                                    <button type="submit" class="button button-small button-link-delete">
                                        <?php esc_html_e('Delete', 'simplekitdownloads'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function simplekitdownloads_render_download_editor($download_id) {
    $download = $download_id > 0 ? simplekitdownloads_get_download($download_id) : null;

    if ($download_id > 0 && !$download) {
        echo '<div class="wrap"><div class="notice notice-error"><p>'
            . esc_html__('Download not found.', 'simplekitdownloads')
            . '</p></div></div>';
        return;
    }

    $version_id      = isset($_GET['version']) ? absint($_GET['version']) : 0;
    $editing_version = $version_id > 0 ? simplekitdownloads_get_version($version_id) : null;
    if ($editing_version && (int) $editing_version->download_id !== (int) $download_id) {
        $editing_version = null;
    }

    $versions = $download ? simplekitdownloads_get_versions($download_id) : [];
    ?>
    <div class="wrap">
        <h1>
            <?php echo $download
                ? esc_html__('Edit download', 'simplekitdownloads')
                : esc_html__('Add new download', 'simplekitdownloads'); ?>
        </h1>
        <a href="<?php echo esc_url(simplekitdownloads_admin_url()); ?>">&larr; <?php esc_html_e('Back to Files', 'simplekitdownloads'); ?></a>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="skd-card">
            <input type="hidden" name="action" value="simplekitdownloads_save_download" />
            <input type="hidden" name="download_id" value="<?php echo esc_attr((int) $download_id); ?>" />
            <?php wp_nonce_field('simplekitdownloads_save_download'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="skd-download-name"><?php esc_html_e('Name', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" id="skd-download-name" name="name" required
                               value="<?php echo esc_attr($download ? $download->name : ''); ?>"
                               placeholder="<?php esc_attr_e('e.g. Product catalog', 'simplekitdownloads'); ?>" />
                        <p class="description">
                            <?php esc_html_e('Renaming a download also updates the name shown in the statistics.', 'simplekitdownloads'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button($download ? __('Save changes', 'simplekitdownloads') : __('Create download', 'simplekitdownloads')); ?>
        </form>

        <?php if ($download) : ?>
            <?php simplekitdownloads_render_version_editor($download, $editing_version); ?>
            <?php simplekitdownloads_render_versions_table($download, $versions); ?>
        <?php endif; ?>
    </div>
    <?php
}

function simplekitdownloads_render_version_editor($download, $version = null) {
    $is_edit = ($version !== null);
    $source  = $is_edit ? (string) $version->source : 'upload';
    $title   = $is_edit
        ? sprintf(
            /* translators: %s: version label. */
            __('Edit version %s', 'simplekitdownloads'),
            $version->version
        )
        : __('Add version', 'simplekitdownloads');
    ?>
    <div class="skd-card">
        <h2><?php echo esc_html($title); ?></h2>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              enctype="multipart/form-data" class="skd-version-form"
              data-mode="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <input type="hidden" name="action"
                   value="<?php echo $is_edit ? 'simplekitdownloads_update_version' : 'simplekitdownloads_add_version'; ?>" />
            <input type="hidden" name="download_id" value="<?php echo esc_attr((int) $download->id); ?>" />
            <?php if ($is_edit) : ?>
                <input type="hidden" name="version_id" value="<?php echo esc_attr((int) $version->id); ?>" />
                <?php wp_nonce_field('simplekitdownloads_update_version'); ?>
            <?php else : ?>
                <?php wp_nonce_field('simplekitdownloads_add_version'); ?>
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <?php if (!$is_edit) : ?>
                    <tr>
                        <th scope="row">
                            <label for="skd-version"><?php esc_html_e('Version', 'simplekitdownloads'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="skd-version" name="version" required
                                   placeholder="<?php esc_attr_e('e.g. 25.1', 'simplekitdownloads'); ?>" />
                            <p class="description">
                                <?php esc_html_e('Numeric version, optionally with dots: 25, 25.1 or 25.0.1. The highest version is used by "current".', 'simplekitdownloads'); ?>
                            </p>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <th scope="row">
                        <label for="skd-source"><?php esc_html_e('Source', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <select id="skd-source" name="source" class="skd-source">
                            <option value="upload" <?php selected($source, 'upload'); ?>>
                                <?php esc_html_e('Upload a file', 'simplekitdownloads'); ?>
                            </option>
                            <option value="url" <?php selected($source, 'url'); ?>>
                                <?php esc_html_e('URL on this site', 'simplekitdownloads'); ?>
                            </option>
                        </select>
                    </td>
                </tr>

                <tr class="skd-upload-row">
                    <th scope="row">
                        <label for="skd-file"><?php esc_html_e('File', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="skd-file" name="file" />
                        <?php if ($is_edit && $source === 'upload' && $version->file_name !== '') : ?>
                            <p class="description">
                                <?php
                                /* translators: %s: file name. */
                                echo esc_html(sprintf(__('Current file: %s. Select a new file to replace it.', 'simplekitdownloads'), $version->file_name));
                                ?>
                            </p>
                        <?php endif; ?>
                        <p class="description">
                            <?php echo esc_html(sprintf(
                                /* translators: %s: maximum upload size. */
                                __('Maximum upload size allowed by the server: %s.', 'simplekitdownloads'),
                                size_format(wp_max_upload_size())
                            )); ?>
                        </p>
                    </td>
                </tr>

                <tr class="skd-url-row">
                    <th scope="row">
                        <label for="skd-url"><?php esc_html_e('URL', 'simplekitdownloads'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="large-text" id="skd-url" name="url"
                               value="<?php echo esc_attr($is_edit && $source === 'url' ? $version->file_url : ''); ?>"
                               placeholder="https://example.com/files/archive.zip" />
                        <p class="description">
                            <?php esc_html_e('The file is downloaded from its original location. Only URLs on this site domain, or paths starting with /, are accepted.', 'simplekitdownloads'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button($is_edit ? __('Save version', 'simplekitdownloads') : __('Add version', 'simplekitdownloads')); ?>

            <?php if ($is_edit) : ?>
                <p>
                    <a href="<?php echo esc_url(simplekitdownloads_admin_url(['action' => 'edit', 'id' => (int) $download->id])); ?>" class="button">
                        <?php esc_html_e('Cancel', 'simplekitdownloads'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </form>
    </div>
    <?php
}

function simplekitdownloads_render_versions_table($download, $versions) {
    ?>
    <div class="skd-card">
        <h2><?php esc_html_e('Versions', 'simplekitdownloads'); ?></h2>

        <?php if (empty($versions)) : ?>
            <p><?php esc_html_e('This download has no versions yet. Add the first version above.', 'simplekitdownloads'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Version', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Source', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('File', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Size', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Added', 'simplekitdownloads'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'simplekitdownloads'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versions as $version) : ?>
                        <?php
                        $version_id = (int) $version->id;
                        $edit_url   = simplekitdownloads_admin_url([
                            'action'  => 'edit',
                            'id'      => (int) $download->id,
                            'version' => $version_id,
                        ]);
                        $missing = false;
                        if ($version->source === 'upload') {
                            // Fix records created before the custom upload folder worked.
                            simplekitdownloads_repair_version_file($version);
                            $version = simplekitdownloads_get_version($version_id);
                            $path    = simplekitdownloads_version_file_path($version);
                            $missing = ($path === '' || !is_file($path));
                        }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($version->version); ?></strong></td>
                            <td>
                                <?php echo $version->source === 'url'
                                    ? esc_html__('URL', 'simplekitdownloads')
                                    : esc_html__('Upload', 'simplekitdownloads'); ?>
                            </td>
                            <td>
                                <?php if ($version->source === 'url') : ?>
                                    <a href="<?php echo esc_url($version->file_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($version->file_url); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($version->file_name); ?>
                                    <?php if ($missing) : ?>
                                        <br /><span class="skd-missing"><?php esc_html_e('File missing', 'simplekitdownloads'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo (int) $version->file_size > 0 ? esc_html(size_format((int) $version->file_size)) : '&mdash;'; ?>
                            </td>
                            <td><?php echo esc_html($version->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'simplekitdownloads'); ?>
                                </a>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                      style="display:inline-block;margin:0;"
                                      onsubmit="return confirm('<?php echo esc_js(__('Delete this version? The statistics will be kept.', 'simplekitdownloads')); ?>');">
                                    <input type="hidden" name="action" value="simplekitdownloads_delete_version" />
                                    <input type="hidden" name="version_id" value="<?php echo esc_attr($version_id); ?>" />
                                    <?php wp_nonce_field('simplekitdownloads_delete_version'); ?>
                                    <button type="submit" class="button button-small button-link-delete">
                                        <?php esc_html_e('Delete', 'simplekitdownloads'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
