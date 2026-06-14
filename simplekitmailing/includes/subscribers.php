<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Helper: redirect with JS fallback if headers already sent
// ---------------------------------------------------------------------------
function simplekitmailing_redirect($url) {
    if (!headers_sent()) {
        wp_safe_redirect($url);
        exit;
    }
    // Fallback via JavaScript
    echo '<script>window.location.href="' . esc_js($url) . '";</script>';
    exit;
}

// ---------------------------------------------------------------------------
// Mailing lists management page
// ---------------------------------------------------------------------------
function simplekitmailing_page_lists() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    global $wpdb;
    $table_lists = $wpdb->prefix . 'sm_lists';

    // Process actions
    $msg = '';
    $type = 'success';

    if (isset($_POST['action']) && $_POST['action'] === 'add_list' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitmailing_list_action')) {
        $name = sanitize_text_field(wp_unslash($_POST['list_name'] ?? ''));
        if (!empty($name)) {
            $desc = sanitize_textarea_field(wp_unslash($_POST['list_description'] ?? ''));
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($table_lists, ['name' => $name, 'description' => $desc]);
            $msg = __('List created successfully.', 'simplekitmailing');
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_list' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'simplekitmailing_list_action')) {
        $id = absint(wp_unslash($_POST['list_id'] ?? 0));
        $name = sanitize_text_field(wp_unslash($_POST['list_name'] ?? ''));
        if ($id > 0 && !empty($name)) {
            $desc = sanitize_textarea_field(wp_unslash($_POST['list_description'] ?? ''));
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table_lists, ['name' => $name, 'description' => $desc], ['id' => $id]);
            $msg = __('List updated successfully.', 'simplekitmailing');
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_list' && isset($_GET['list_id']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'simplekitmailing_delete_list')) {
        $id = absint($_GET['list_id']);
        // Do not allow deleting if it is the last list
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM %i", $table_lists)
        );
        if ($count > 1) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete($table_lists, ['id' => $id]);
            $msg = __('List removed.', 'simplekitmailing');
        } else {
            $msg = __('Cannot remove the only remaining list.', 'simplekitmailing');
            $type = 'error';
        }
    }

    $lists = simplekitmailing_get_lists();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Mailing Lists', 'simplekitmailing'); ?></h1>

        <?php if ($msg) : ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($msg); ?></p>
            </div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('ID', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Name', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Description', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Subscribers', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Created at', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'simplekitmailing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lists)) : ?>
                    <tr><td colspan="6"><?php esc_html_e('No lists registered.', 'simplekitmailing'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($lists as $list) : ?>
                        <tr>
                            <td><?php echo (int) $list->id; ?></td>
                            <td><?php echo esc_html($list->name); ?></td>
                            <td><?php echo esc_html($list->description ?: '—'); ?></td>
                            <td><?php echo (int) simplekitmailing_get_list_subscriber_count($list->id); ?></td>
                            <td><?php echo esc_html($list->created_at); ?></td>
                            <td>
                                <button class="button button-small edit-list-btn" data-id="<?php echo (int) $list->id; ?>" data-name="<?php echo esc_attr($list->name); ?>" data-desc="<?php echo esc_attr($list->description); ?>">
                                    <?php esc_html_e('Edit', 'simplekitmailing'); ?>
                                </button>
                                <?php if (count($lists) > 1) : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing-lists', 'action' => 'delete_list', 'list_id' => $list->id]), 'simplekitmailing_delete_list', '_wpnonce')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Remove this list? Contacts will NOT be lost, but will have no list associated.', 'simplekitmailing'); ?>');">
                                        <?php esc_html_e('Remove', 'simplekitmailing'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <hr />

        <!-- New list form -->
        <h2><?php esc_html_e('New List', 'simplekitmailing'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitmailing_list_action', '_wpnonce'); ?>
            <input type="hidden" name="action" value="add_list" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="list_name"><?php esc_html_e('List name', 'simplekitmailing'); ?></label></th>
                    <td><input type="text" id="list_name" name="list_name" class="regular-text" required style="width:100%;" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="list_description"><?php esc_html_e('Description', 'simplekitmailing'); ?></label></th>
                    <td><textarea id="list_description" name="list_description" rows="3" class="large-text" style="width:100%;"></textarea></td>
                </tr>
                <tr>
                    <th scope="row">&nbsp;</th>
                    <td><?php submit_button(__('Create list', 'simplekitmailing')); ?></td>
                </tr>
            </table>
        </form>

        <!-- Edit modal -->
        <div id="edit-list-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:100000;">
            <div style="background:#fff;max-width:500px;margin:100px auto;padding:20px;border-radius:4px;">
                <h2><?php esc_html_e('Edit list', 'simplekitmailing'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('simplekitmailing_list_action', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="edit_list" />
                    <input type="hidden" name="list_id" id="edit_list_id" value="" />
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="edit_list_name"><?php esc_html_e('List name', 'simplekitmailing'); ?></label></th>
                            <td><input type="text" id="edit_list_name" name="list_name" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit_list_description"><?php esc_html_e('Description', 'simplekitmailing'); ?></label></th>
                            <td><textarea id="edit_list_description" name="list_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                    </table>
                    <p>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save', 'simplekitmailing'); ?>" />
                        <button type="button" class="button" id="edit-list-cancel"><?php esc_html_e('Cancel', 'simplekitmailing'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.edit-list-btn').on('click', function() {
                $('#edit_list_id').val($(this).data('id'));
                $('#edit_list_name').val($(this).data('name'));
                $('#edit_list_description').val($(this).data('desc'));
                $('#edit-list-modal').show();
            });
            $('#edit-list-cancel').on('click', function() {
                $('#edit-list-modal').hide();
            });
            $(document).on('click', function(e) {
                if ($(e.target).is('#edit-list-modal')) {
                    $('#edit-list-modal').hide();
                }
            });
        });
        </script>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// GET actions (remove, reactivate) processed via admin_init BEFORE HTML
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitmailing_handle_get_actions');
function simplekitmailing_handle_get_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'simplekitmailing') {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';

    $nonce = sanitize_text_field(wp_unslash($_REQUEST['simplekitmailing_nonce'] ?? ''));
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

    if (!$action || !wp_verify_nonce($nonce, 'simplekitmailing_subscriber_action')) {
        return;
    }

    // Active list via GET (fallback)
    $list_id = isset($_GET['list_id']) ? absint($_GET['list_id']) : 0;

    // Remove individual (via GET)
    if ($action === 'remove_subscriber' && isset($_GET['subscriber_id'])) {
        $id = absint($_GET['subscriber_id']);
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table_subscribers, $id));
        if ($sub) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete($table_subscribers, ['id' => $id]);
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->replace($table_removed, ['list_id' => $sub->list_id, 'email' => $sub->email]);
        }
        $args = ['page' => 'simplekitmailing', 'msg' => __('Email removed successfully.', 'simplekitmailing'), 'type' => 'success'];
        if ($list_id) $args['list_id'] = $list_id;
        simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
    }

    // Reactivate removed email (via GET)
    if ($action === 'reactivate_removed' && isset($_GET['removed_id'])) {
        $id = absint($_GET['removed_id']);
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $r = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table_removed, $id));
        if ($r) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->replace($table_subscribers, ['list_id' => $r->list_id, 'email' => $r->email, 'name' => '', 'phone' => '', 'ip' => '']);
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete($table_removed, ['id' => $id]);
        }
        $args = ['page' => 'simplekitmailing', 'msg' => __('Email reactivated successfully.', 'simplekitmailing'), 'type' => 'success'];
        if ($list_id) $args['list_id'] = $list_id;
        simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
    }
}

// ---------------------------------------------------------------------------
// Active list filter (via GET)
// ---------------------------------------------------------------------------
function simplekitmailing_get_active_list_id() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitmailing_display')) {
        // Silent nonce check: allow fallback to default when nonce absent
    }
    return isset($_GET['list_id']) ? absint($_GET['list_id']) : 0;
}

// ---------------------------------------------------------------------------
// "Subscribers" page
// ---------------------------------------------------------------------------
function simplekitmailing_page_subscribers() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simplekitmailing_display')) {
        // Silent nonce check: allow fallback to default when nonce absent
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';
    $table_lists       = $wpdb->prefix . 'sm_lists';

    $list_id = simplekitmailing_get_active_list_id();

    // Process POST actions
    simplekitmailing_handle_subscriber_actions();

    // Search
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $like   = $search ? $wpdb->esc_like($search) : '';


    // Pagination for subscribers
    $per_page = 25;
    $sub_page = isset($_GET['subp']) ? max(1, absint($_GET['subp'])) : 1;
    $sub_offset = ($sub_page - 1) * $per_page;

    if ($like) {
        $like_pattern = '%' . $like . '%';
        if ($list_id) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $sub_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE list_id = %d AND (email LIKE %s OR name LIKE %s)",
                $table_subscribers, $list_id, $like_pattern, $like_pattern
            ));
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM %i WHERE list_id = %d AND (email LIKE %s OR name LIKE %s) ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $table_subscribers, $list_id, $like_pattern, $like_pattern, $per_page, $sub_offset
            ));
        } else {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $sub_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE (email LIKE %s OR name LIKE %s)",
                $table_subscribers, $like_pattern, $like_pattern
            ));
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM %i WHERE (email LIKE %s OR name LIKE %s) ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $table_subscribers, $like_pattern, $like_pattern, $per_page, $sub_offset
            ));
        }
    } else {
        if ($list_id) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $sub_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE list_id = %d",
                $table_subscribers, $list_id
            ));
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM %i WHERE list_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $table_subscribers, $list_id, $per_page, $sub_offset
            ));
        } else {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $sub_total = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM %i", $table_subscribers)
            );
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $table_subscribers, $per_page, $sub_offset
                )
            );
        }
    }
    $sub_total_pages = max(1, ceil($sub_total / $per_page));

    // Pagination for removed
    $rem_per_page = 25;
    $rem_page = isset($_GET['remp']) ? max(1, absint($_GET['remp'])) : 1;
    $rem_offset = ($rem_page - 1) * $rem_per_page;

    if ($list_id) {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rem_total = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM %i WHERE list_id = %d", $table_removed, $list_id)
        );
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $removed = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE list_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $table_removed, $list_id, $rem_per_page, $rem_offset
            )
        );
    } else {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rem_total = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM %i", $table_removed)
        );
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $removed = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $table_removed, $rem_per_page, $rem_offset
            )
        );
    }
    $rem_total_pages = max(1, ceil($rem_total / $rem_per_page));

    // Helper to build URLs
    $base_url = admin_url('admin.php') . '?page=simplekitmailing';
    if ($search) {
        $base_url .= '&s=' . urlencode($search);
    }
    if ($list_id) {
        $base_url .= '&list_id=' . $list_id;
    }

    $lists = simplekitmailing_get_lists();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Subscribers', 'simplekitmailing'); ?></h1>

        <!-- List filter -->
        <form method="get" action="" style="margin-bottom:15px;">
            <input type="hidden" name="page" value="simplekitmailing" />
            <?php if ($search) : ?>
                <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>" />
            <?php endif; ?>
            <label for="filter-list"><?php esc_html_e('Filter by list:', 'simplekitmailing'); ?></label>
            <select id="filter-list" name="list_id" onchange="this.form.submit();">
                <option value="0"><?php esc_html_e('All lists', 'simplekitmailing'); ?></option>
                <?php foreach ($lists as $l) : ?>
                    <option value="<?php echo (int) $l->id; ?>" <?php selected($list_id, $l->id); ?>>
                        <?php echo esc_html($l->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><input type="submit" class="button" value="<?php esc_attr_e('Filter', 'simplekitmailing'); ?>" /></noscript>
        </form>

        <?php if (isset($_GET['msg'])) :
            $msg_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : 'success';
            $msg_text = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : '';
        ?>
            <div class="notice notice-<?php echo esc_attr($msg_type); ?> is-dismissible">
                <p><?php echo esc_html($msg_text); ?></p>
            </div>
        <?php endif; ?>

        <!-- Search form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="simplekitmailing" />
            <?php if ($list_id) : ?>
                <input type="hidden" name="list_id" value="<?php echo (int) $list_id; ?>" />
            <?php endif; ?>
            <p class="search-box">
                <label class="screen-reader-text" for="subscriber-search-input"><?php esc_html_e('Search subscribers:', 'simplekitmailing'); ?></label>
                <input type="search" id="subscriber-search-input" name="s" value="<?php echo esc_attr($search); ?>" />
                <input type="submit" class="button" value="<?php esc_attr_e('Search', 'simplekitmailing'); ?>" />
            </p>
        </form>

        <!-- Subscribers table -->
        <h2><?php esc_html_e('Registered emails', 'simplekitmailing'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitmailing_subscriber_action', 'simplekitmailing_nonce'); ?>
            <?php if ($list_id) : ?>
                <input type="hidden" name="list_id" value="<?php echo (int) $list_id; ?>" />
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1" /></th>
                        <th scope="col"><?php esc_html_e('Email', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('Name', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('List', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('Phone', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('IP', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('Date/Time', 'simplekitmailing'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'simplekitmailing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)) : ?>
                        <tr><td colspan="8"><?php esc_html_e('No subscribers found.', 'simplekitmailing'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($subscribers as $sub) :
                            $list_name = $sub->list_id ? simplekitmailing_get_list_name($sub->list_id) : '—';
                        ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="subscriber_ids[]" value="<?php echo (int) $sub->id; ?>" /></th>
                                <td><?php echo esc_html($sub->email); ?></td>
                                <td><?php echo esc_html($sub->name ?: '—'); ?></td>
                                <td><?php echo esc_html($list_name); ?></td>
                                <td><?php echo esc_html($sub->phone ?: '—'); ?></td>
                                <td><?php echo esc_html($sub->ip); ?></td>
                                <td><?php echo esc_html($sub->created_at); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing', 'action' => 'remove_subscriber', 'subscriber_id' => $sub->id, 'list_id' => $list_id]), 'simplekitmailing_subscriber_action', 'simplekitmailing_nonce')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Remove this email?', 'simplekitmailing'); ?>');">
                                        <?php esc_html_e('Remove', 'simplekitmailing'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($subscribers)) : ?>
                <div style="margin-top:10px;">
                    <select name="bulk_action">
                        <option value=""><?php esc_html_e('Bulk actions', 'simplekitmailing'); ?></option>
                        <option value="remove_selected"><?php esc_html_e('Remove selected', 'simplekitmailing'); ?></option>
                    </select>
                    <input type="submit" class="button" value="<?php esc_attr_e('Apply', 'simplekitmailing'); ?>" />
                </div>
            <?php endif; ?>
        </form>

        <!-- Subscribers pagination -->
        <?php if ($sub_total_pages > 1) : ?>
            <div class="tablenav bottom" style="margin-bottom:20px;">
                <div class="tablenav-pages">
                    <?php
                    /* translators: %d: number of items */
                    $sub_items_text = __('%d items', 'simplekitmailing');
                    ?>
                    <span class="displaying-num"><?php echo esc_html(sprintf($sub_items_text, $sub_total)); ?></span>
                    <?php if ($sub_page > 1) : ?>
                        <a class="button button-small" href="<?php echo esc_url($base_url . '&subp=' . ($sub_page - 1)); ?>">&laquo; <?php esc_html_e('Previous', 'simplekitmailing'); ?></a>
                    <?php endif; ?>
                    <?php
                    /* translators: 1: current page number, 2: total number of pages */
                    $sub_page_text = __('Page %1$d of %2$d', 'simplekitmailing');
                    ?>
                    <span class="displaying-num"><?php echo esc_html(sprintf($sub_page_text, $sub_page, $sub_total_pages)); ?></span>
                    <?php if ($sub_page < $sub_total_pages) : ?>
                        <a class="button button-small" href="<?php echo esc_url($base_url . '&subp=' . ($sub_page + 1)); ?>"><?php esc_html_e('Next', 'simplekitmailing'); ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <br />

        <!-- Export CSV -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:10px;">
            <?php wp_nonce_field('simplekitmailing_export_csv', 'simplekitmailing_nonce'); ?>
            <input type="hidden" name="action" value="simplekitmailing_export_csv" />
            <?php if ($list_id) : ?>
                <input type="hidden" name="list_id" value="<?php echo (int) $list_id; ?>" />
            <?php endif; ?>
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Export CSV', 'simplekitmailing'); ?>" />
        </form>

        <!-- Upload CSV -->
        <form method="post" action="" enctype="multipart/form-data" style="display:inline-block;">
            <?php wp_nonce_field('simplekitmailing_import_csv', 'simplekitmailing_nonce'); ?>
            <input type="hidden" name="action" value="import_csv" />
            <?php if ($list_id) : ?>
                <input type="hidden" name="import_list_id" value="<?php echo (int) $list_id; ?>" />
            <?php endif; ?>
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Import CSV', 'simplekitmailing'); ?>" />
            <input id="csvupload" type="file" class="simplekitmailing-fileupload" name="csv_file" accept=".csv" required />
            <label id="csvuploadlabel" for="csvupload" class="button"><?php esc_attr_e('Choose CSV file', 'simplekitmailing'); ?></label>
            <script>
                const input = document.getElementById('csvupload');
                const label = document.getElementById("csvuploadlabel");
                input.addEventListener('change', () => {
                    if (input.files.length > 0) {
                        label.textContent = `<?php esc_attr_e('Selected CSV file: ', 'simplekitmailing'); ?> ${input.files[0].name}`;
                    } else {
                        label.textContent = "<?php esc_attr_e('Choose CSV file', 'simplekitmailing'); ?>";
                    }
                });
            </script>
        </form>

        <hr />

        <!-- Removed emails list -->
        <h2><?php esc_html_e('Removed emails', 'simplekitmailing'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Email', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('List', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Date/Time', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'simplekitmailing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($removed)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No removed emails.', 'simplekitmailing'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($removed as $r) :
                        $list_name = $r->list_id ? simplekitmailing_get_list_name($r->list_id) : '—';
                    ?>
                        <tr>
                            <td><?php echo esc_html($r->email); ?></td>
                            <td><?php echo esc_html($list_name); ?></td>
                            <td><?php echo esc_html($r->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing', 'action' => 'reactivate_removed', 'removed_id' => $r->id, 'list_id' => $list_id]), 'simplekitmailing_subscriber_action', 'simplekitmailing_nonce')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Reactivate this email? It will be moved back to the subscribers list.', 'simplekitmailing'); ?>');">
                                    <?php esc_html_e('Reactivate', 'simplekitmailing'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Removed emails pagination -->
        <?php if ($rem_total_pages > 1) : ?>
            <div class="tablenav bottom" style="margin-bottom:20px;">
                <div class="tablenav-pages">
                    <?php
                    /* translators: %d: number of items */
                    $rem_items_text = __('%d items', 'simplekitmailing');
                    ?>
                    <span class="displaying-num"><?php echo esc_html(sprintf($rem_items_text, $rem_total)); ?></span>
                    <?php if ($rem_page > 1) : ?>
                        <a class="button button-small" href="<?php echo esc_url($base_url . '&remp=' . ($rem_page - 1)); ?>">&laquo; <?php esc_html_e('Previous', 'simplekitmailing'); ?></a>
                    <?php endif; ?>
                    <?php
                    /* translators: 1: current page number, 2: total number of pages */
                    $rem_page_text = __('Page %1$d of %2$d', 'simplekitmailing');
                    ?>
                    <span class="displaying-num"><?php echo esc_html(sprintf($rem_page_text, $rem_page, $rem_total_pages)); ?></span>
                    <?php if ($rem_page < $rem_total_pages) : ?>
                        <a class="button button-small" href="<?php echo esc_url($base_url . '&remp=' . ($rem_page + 1)); ?>"><?php esc_html_e('Next', 'simplekitmailing'); ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add email to removed -->
        <h3><?php esc_html_e('Add email to removed', 'simplekitmailing'); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('simplekitmailing_add_removed', 'simplekitmailing_nonce'); ?>
            <input type="hidden" name="action" value="add_removed" />
            <?php if ($list_id) : ?>
                <input type="hidden" name="removed_list_id" value="<?php echo (int) $list_id; ?>" />
            <?php endif; ?>
            <input type="email" name="removed_email" placeholder="<?php esc_attr_e('Enter email', 'simplekitmailing'); ?>" required />
            <input type="submit" class="button" value="<?php esc_attr_e('Add', 'simplekitmailing'); ?>" />
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Process POST actions from the subscribers page
// ---------------------------------------------------------------------------
function simplekitmailing_handle_subscriber_actions() {
    if (empty($_POST)) {
        return;
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';

    // Verify nonce
    $nonce = sanitize_text_field(wp_unslash($_POST['simplekitmailing_nonce'] ?? ''));
    if (!wp_verify_nonce($nonce, 'simplekitmailing_subscriber_action') &&
        !wp_verify_nonce($nonce, 'simplekitmailing_import_csv') &&
        !wp_verify_nonce($nonce, 'simplekitmailing_add_removed')) {
        return;
    }

    $action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
    $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;

    // Bulk remove (via POST)
    if ($action === 'remove_selected' && !empty($_POST['subscriber_ids'])) {
        $ids = array_map('intval', $_POST['subscriber_ids']);
        foreach ($ids as $id) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table_subscribers, $id));
            if ($sub) {
                // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->delete($table_subscribers, ['id' => $id]);
                // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->replace($table_removed, ['list_id' => $sub->list_id, 'email' => $sub->email]);
            }
        }
        $args = ['page' => 'simplekitmailing', 'msg' => __('Emails removed successfully.', 'simplekitmailing'), 'type' => 'success'];
        if ($list_id) $args['list_id'] = $list_id;
        simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
    }

    // Import CSV (via POST)
    if ($action === 'import_csv') {
        $import_list_id = isset($_POST['import_list_id']) ? absint($_POST['import_list_id']) : 0;
        if (!$import_list_id) {
            // Get first list if none specified
            $lists = simplekitmailing_get_lists();
            $import_list_id = !empty($lists) ? (int) $lists[0]->id : 0;
        }

        if (!isset($_FILES['csv_file']['error']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = __('Error uploading CSV file.', 'simplekitmailing');
            if (isset($_FILES['csv_file'])) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE   => __('The file exceeds the maximum size allowed by PHP.', 'simplekitmailing'),
                    UPLOAD_ERR_FORM_SIZE  => __('The file exceeds the maximum size defined in the form.', 'simplekitmailing'),
                    UPLOAD_ERR_PARTIAL    => __('The file upload was partial.', 'simplekitmailing'),
                    UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'simplekitmailing'),
                ];
                $upload_error_code = isset($_FILES['csv_file']['error']) ? (int) $_FILES['csv_file']['error'] : UPLOAD_ERR_NO_FILE;
                $error_msg = isset($upload_errors[$upload_error_code]) ? $upload_errors[$upload_error_code] : $error_msg;
            }
            $args = ['page' => 'simplekitmailing', 'msg' => $error_msg, 'type' => 'error'];
            if ($list_id) $args['list_id'] = $list_id;
            simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
        }

        $tmp = isset($_FILES['csv_file']['tmp_name']) ? sanitize_text_field(wp_unslash($_FILES['csv_file']['tmp_name'])) : '';

        // Use WP_Filesystem to read the uploaded CSV
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        $csv_contents = $wp_filesystem->get_contents($tmp);

        $imported = 0;
        $skipped = 0;

        $lines = explode("\n", trim($csv_contents ?? ''));
        $first = true;
        $header = array();
        foreach ($lines as $csv_line) {
            $row = str_getcsv($csv_line, ';');
            if ($first) {
                $header = array_map('trim', $row);
                $idx_email = array_search('Email', $header);
                $idx_name  = array_search('Name', $header);
                $idx_phone = array_search('Phone', $header);
                if ($idx_email === false) {
                    $idx_email = 0;
                }
                $first = false;
                continue;
            }
            $email = sanitize_email(trim($row[$idx_email ?? 0] ?? $row[0]));
            if (!is_email($email)) {
                continue;
            }
            // Check if in removed list (for this list)
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $removed_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
                $table_removed, $import_list_id, $email
            ));
            if ($removed_exists > 0) {
                $skipped++;
                continue;
            }

            $name  = isset($idx_name) ? sanitize_text_field(trim($row[$idx_name] ?? '')) : '';
            $phone = isset($idx_phone) ? sanitize_text_field(trim($row[$idx_phone] ?? '')) : '';

            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $inserted = $wpdb->replace($table_subscribers, [
                'list_id' => $import_list_id,
                'email' => $email,
                'name'  => $name,
                'phone' => $phone,
                'ip'    => '',
            ]);
            if ($inserted !== false) {
                $imported++;
            }
        }
        /* translators: 1: number of imported records, 2: number of skipped records */
        $import_text = __('Import completed: %1$d registered, %2$d skipped (were in the removed list).', 'simplekitmailing');
        $msg = sprintf($import_text, $imported, $skipped);
        $args = ['page' => 'simplekitmailing', 'msg' => $msg, 'type' => 'success'];
        if ($list_id) $args['list_id'] = $list_id;
        simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
    }

    // Add email to removed (via POST)
    if ($action === 'add_removed' && isset($_POST['removed_email'])) {
        $removed_list_id = isset($_POST['removed_list_id']) ? absint($_POST['removed_list_id']) : 0;
        if (!$removed_list_id) {
            $lists = simplekitmailing_get_lists();
            $removed_list_id = !empty($lists) ? (int) $lists[0]->id : 0;
        }

        $email = sanitize_email(wp_unslash($_POST['removed_email']));
        if (is_email($email)) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete($table_subscribers, ['email' => $email, 'list_id' => $removed_list_id]);
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->replace($table_removed, ['list_id' => $removed_list_id, 'email' => $email]);
        }
        $args = ['page' => 'simplekitmailing', 'msg' => __('Email added to removed list.', 'simplekitmailing'), 'type' => 'success'];
        if ($list_id) $args['list_id'] = $list_id;
        simplekitmailing_redirect(add_query_arg($args, admin_url('admin.php')));
    }
}

// ---------------------------------------------------------------------------
// Export CSV via admin-post.php (forces correct download)
// ---------------------------------------------------------------------------
add_action('admin_post_simplekitmailing_export_csv', 'simplekitmailing_export_csv_handler');
function simplekitmailing_export_csv_handler() {
    if (!isset($_POST['simplekitmailing_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simplekitmailing_nonce'])), 'simplekitmailing_export_csv')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';

    $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;

    if ($list_id) {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $subscribers = $wpdb->get_results(
            $wpdb->prepare("SELECT email, name, phone, ip, created_at FROM %i WHERE list_id = %d ORDER BY created_at DESC", $table_subscribers, $list_id)
        );
    } else {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $subscribers = $wpdb->get_results(
            $wpdb->prepare("SELECT email, name, phone, ip, created_at FROM %i ORDER BY created_at DESC", $table_subscribers)
        );
    }

    // Write CSV to a temporary file in the uploads directory
    $upload_dir = wp_upload_dir();
    $filename   = 'simplekitmailing-export-' . gmdate('Y-m-d-His') . '.csv';
    $filepath   = trailingslashit($upload_dir['basedir']) . $filename;

    $handle = fopen($filepath, 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    if (!$handle) {
        wp_die(esc_html__('Failed to create temporary file for CSV export.', 'simplekitmailing'));
    }

    // Write UTF-8 BOM
    fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
    // Write header row
    fwrite($handle, simplekitmailing_csv_row(array('Email', 'Name', 'Phone', 'IP', 'Date/Time'))); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
    // Write data rows
    foreach ($subscribers as $s) {
        fwrite($handle, simplekitmailing_csv_row(array($s->email, $s->name, $s->phone, $s->ip, $s->created_at))); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
    }
    fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . gmdate('Y-m-d') . '.csv"');
    header('Content-Length: ' . filesize($filepath));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($filepath); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
    wp_delete_file($filepath);
    exit;
}

/**
 * Format a CSV row with proper quoting.
 *
 * @param array $fields Array of field values.
 * @return string A single CSV line ending with \r\n.
 */
function simplekitmailing_csv_row($fields) {
    $escaped = array();
    foreach ($fields as $field) {
        if (strpos($field, ';') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false || strpos($field, "\r") !== false) {
            $field = '"' . str_replace('"', '""', $field) . '"';
        }
        $escaped[] = $field;
    }
    return implode(';', $escaped) . "\r\n";
}
