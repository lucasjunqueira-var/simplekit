<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Backup: export all plugin data to array
// ---------------------------------------------------------------------------
function simplekitmailing_export_backup() {
    global $wpdb;

    $table_lists       = $wpdb->prefix . 'sm_lists';
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';
    $table_messages    = $wpdb->prefix . 'sm_messages';
    $table_pending     = $wpdb->prefix . 'sm_pending';

    $data = [
        'version'     => SIMPLEKITMAILING_VERSION,
        'exported_at' => current_time('mysql'),
        'namespace'   => 'simplekitmailing',
        'tables'      => [],
    ];

    $tables = [
        'sm_lists'       => $table_lists,
        'sm_subscribers' => $table_subscribers,
        'sm_removed'     => $table_removed,
        'sm_messages'    => $table_messages,
        'sm_pending'     => $table_pending,
    ];

    foreach ($tables as $key => $table) {
        // data is being exported from the plugin tables, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i", $table),
            ARRAY_A
        );
        $data['tables'][$key] = is_array($rows) ? $rows : [];
    }

    return $data;
}

// ---------------------------------------------------------------------------
// Backup: validate backup data structure
// ---------------------------------------------------------------------------
function simplekitmailing_validate_backup($data) {
    if (!is_array($data)) {
        return false;
    }
    if (!isset($data['namespace']) || $data['namespace'] !== 'simplekitmailing') {
        return false;
    }
    if (!isset($data['tables']) || !is_array($data['tables'])) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------
// Backup: import data from a validated backup array
// ---------------------------------------------------------------------------
function simplekitmailing_import_backup($data) {
    global $wpdb;

    if (!simplekitmailing_validate_backup($data)) {
        return false;
    }

    $tables = [
        'sm_lists'       => $wpdb->prefix . 'sm_lists',
        'sm_subscribers' => $wpdb->prefix . 'sm_subscribers',
        'sm_removed'     => $wpdb->prefix . 'sm_removed',
        'sm_messages'    => $wpdb->prefix . 'sm_messages',
        'sm_pending'     => $wpdb->prefix . 'sm_pending',
    ];

    foreach ($tables as $key => $table) {
        if (!isset($data['tables'][$key]) || !is_array($data['tables'][$key])) {
            continue;
        }

        // Clear existing data
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table));

        // Insert backup rows
        foreach ($data['tables'][$key] as $row) {
            // data must be inserted on custom plugin table
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table, $row);
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// Backup: get uploads directory path
// ---------------------------------------------------------------------------
function simplekitmailing_backup_file_path() {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'simplekitmailing-bakcup.json';
}

// ---------------------------------------------------------------------------
// Page: Backup
// ---------------------------------------------------------------------------
function simplekitmailing_page_backup() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    $msg  = '';
    $type = 'success';
    $download_url = '';

    // Handle export action (POST form submission)
    if (isset($_POST['action']) && $_POST['action'] === 'export_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitmailing_backup_export')
    ) {
        $data    = simplekitmailing_export_backup();
        $json    = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filepath = simplekitmailing_backup_file_path();

        $written = file_put_contents($filepath, $json);
        if ($written === false) {
            $msg  = __('Failed to write backup file to the uploads directory.', 'simplekitmailing');
            $type = 'error';
        } else {
            // Build download URL via admin-post.php
            $download_url = wp_nonce_url(
                admin_url('admin-post.php?action=simplekitmailing_download_backup'),
                'simplekitmailing_download_backup'
            );
            $msg = __('Backup file generated successfully.', 'simplekitmailing');
        }
    }

    // Handle import action (POST form submission)
    if (isset($_POST['action']) && $_POST['action'] === 'import_backup'
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitmailing_backup_import')
    ) {
        if (!isset($_FILES['backup_file']['error']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $msg  = __('Error uploading the backup file.', 'simplekitmailing');
            $type = 'error';
        } else {
            $tmp_path = isset($_FILES['backup_file']['tmp_name']) ? sanitize_text_field(wp_unslash($_FILES['backup_file']['tmp_name'])) : '';
            $contents = file_get_contents($tmp_path);
            $data     = json_decode($contents, true);

            if (!simplekitmailing_validate_backup($data)) {
                $msg  = __('Invalid backup file. The file structure does not match the expected format.', 'simplekitmailing');
                $type = 'error';
            } else {
                $imported = simplekitmailing_import_backup($data);
                if ($imported) {
                    $msg  = __('Backup imported successfully. All previous data has been replaced.', 'simplekitmailing');
                    $type = 'success';
                } else {
                    $msg  = __('Failed to import the backup file.', 'simplekitmailing');
                    $type = 'error';
                }
            }
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Backup', 'simplekitmailing'); ?></h1>

        <?php if ($msg) : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($msg); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Export Backup', 'simplekitmailing'); ?></h2>
        <p><?php esc_html_e('Download a JSON backup file containing all mailing lists, subscribers, removed emails, and messages. The file will also be saved to the WordPress uploads directory.', 'simplekitmailing'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitmailing_backup_export', '_wpnonce'); ?>
            <input type="hidden" name="action" value="export_backup" />
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Export Backup', 'simplekitmailing'); ?>" />
        </form>

        <?php if ($download_url) : ?>
        <p>
            <a href="<?php echo esc_url($download_url); ?>" class="button button-primary" id="sm-download-backup-link">
                <?php esc_html_e('Download backup file', 'simplekitmailing'); ?>
            </a>
        </p>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            window.location.href = $('#sm-download-backup-link').attr('href');
        });
        </script>
        <?php endif; ?>

        <hr />

        <h2><?php esc_html_e('Import Backup', 'simplekitmailing'); ?></h2>
        <p><?php esc_html_e('Upload a previously exported JSON backup file to restore your data.', 'simplekitmailing'); ?></p>
        <p><strong><?php esc_html_e('Warning:', 'simplekitmailing'); ?></strong> <?php esc_html_e('All current data will be replaced by the data from the backup file. This action cannot be undone.', 'simplekitmailing'); ?></p>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return confirm('<?php esc_attr_e('Are you sure? All current data will be replaced by the backup data.', 'simplekitmailing'); ?>');">
            <?php wp_nonce_field('simplekitmailing_backup_import', '_wpnonce'); ?>
            <input type="hidden" name="action" value="import_backup" />
            <input type="file" name="backup_file" accept=".json" required />
            <br /><br />
            <input type="submit" class="button" value="<?php esc_attr_e('Import Backup', 'simplekitmailing'); ?>" />
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Page: Donate
// ---------------------------------------------------------------------------
function simplekitmailing_page_donate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Donate', 'simplekitmailing'); ?></h1>

        <div class="notice notice-info">
            <p><?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator, Lucas Junqueira. To do so, use the form below to donate via PayPal.', 'simplekitmailing'); ?></p>
        </div>

        <div style="margin-top:30px;max-width:400px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="chokito76@gmail.com" />
                <input type="hidden" name="item_name" value="Donation to Lucas Santos Junqueira" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="hidden" name="no_note" value="0" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="<?php esc_attr_e('Donate with PayPal button', 'simplekitmailing'); ?>" />
            </form>
        </div>

        <hr />

        <p><?php esc_html_e('Thank you for your support!', 'simplekitmailing'); ?></p>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Donation dismissible notice (shown on main plugin page after activation)
// ---------------------------------------------------------------------------
add_action('admin_notices', 'simplekitmailing_donation_notice');
function simplekitmailing_donation_notice() {
    // Only show on Simple Kit Mailing admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'simplekitmailing') === false) {
        return;
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitmailing_display')) {
        // Silent nonce check: allow fallback to default when nonce absent
    }

    // Only show on the main page (not sub-pages)
    if (!isset($_GET['page']) || $_GET['page'] !== 'simplekitmailing') {
        return;
    }

    // Check if user has dismissed the notice
    $user_id = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'simplekitmailing_dismiss_donation', true);
    if ($dismissed) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" id="simplekitmailing-donation-notice">
        <p>
            <?php esc_html_e('This is a free plugin. If you find its features useful, please consider making a donation of any amount to its creator. To do so, Please access the Donate sub menu.', 'simplekitmailing'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplekitmailing-donate')); ?>" class="button button-small" style="margin-left:10px;">
                <?php esc_html_e('Donate', 'simplekitmailing'); ?>
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '#simplekitmailing-donation-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'simplekitmailing_dismiss_donation',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitmailing_dismiss_donation')); ?>'
            });
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// AJAX: dismiss donation notice permanently
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitmailing_dismiss_donation', 'simplekitmailing_ajax_dismiss_donation');
function simplekitmailing_ajax_dismiss_donation() {
    check_ajax_referer('simplekitmailing_dismiss_donation');
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'simplekitmailing_dismiss_donation', 1);
    wp_send_json_success();
}


// ---------------------------------------------------------------------------
// Download backup file via admin-post.php
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitmailing_download_backup', 'simplekitmailing_download_backup_handler');
function simplekitmailing_download_backup_handler() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitmailing_download_backup')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    $filepath = simplekitmailing_backup_file_path();

    if (!file_exists($filepath)) {
        wp_die(esc_html__('Backup file not found. Please generate a new backup.', 'simplekitmailing'));
    }

    $json = file_get_contents($filepath);

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="simplekitmailing-bakcup.json"');
    header('Content-Length: ' . strlen($json));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo wp_json_encode(json_decode($json, true));
    exit;
}
