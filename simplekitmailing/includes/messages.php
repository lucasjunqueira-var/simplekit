<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// "Create Message" page
// ---------------------------------------------------------------------------
function simplekitmailing_page_message() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    global $wpdb;
    $table_messages = $wpdb->prefix . 'sm_messages';

    $saved_subject = '';
    $saved_content = '';
    $message_id    = 0;

    // If redirected after registering a send
    if (isset($_GET['message_id'])) {
        $message_id = absint($_GET['message_id']);
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $msg_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table_messages, $message_id));
        if ($msg_data) {
            $saved_subject = $msg_data->subject;
            $saved_content = $msg_data->content;
        }
    }

    // Preserve subject and content after test/send redirect via transient
    $preserve_key = isset($_GET['sm_preserve']) ? preg_replace('/[^a-f0-9]/', '', sanitize_text_field(wp_unslash($_GET['sm_preserve']))) : '';
    if ($preserve_key) {
        $preserved = get_transient('sm_preserve_' . $preserve_key);
        if (is_array($preserved)) {
            $saved_subject = isset($preserved['subject']) ? $preserved['subject'] : $saved_subject;
            $saved_content = isset($preserved['content']) ? $preserved['content'] : $saved_content;
        }
        delete_transient('sm_preserve_' . $preserve_key);
    }

    $lists = simplekitmailing_get_lists();

    // Build a map of list_id => editor CSS for dynamic switching
    $list_styles = array();
    foreach ($lists as $list) {
        $list_styles[(int) $list->id] = simplekitmailing_editor_color_style((int) $list->id);
    }
    $selected_list_id = !empty($lists) ? (int) $lists[0]->id : 0;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Create Message', 'simplekitmailing'); ?></h1>

        <?php if (isset($_GET['msg'])) :
            $msg_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : 'success';
            $msg_text = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : '';
        ?>
            <div class="notice notice-<?php echo esc_attr($msg_type); ?> is-dismissible">
                <p><?php echo esc_html($msg_text); ?></p>
            </div>
        <?php endif; ?>

        <form id="simplekitmailing-message-form" method="post" action="">
            <?php wp_nonce_field('simplekitmailing_message_action', 'simplekitmailing_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="msg_subject"><?php esc_html_e('Subject', 'simplekitmailing'); ?></label></th>
                    <td><input type="text" id="msg_subject" name="msg_subject" value="<?php echo esc_attr($saved_subject); ?>" class="regular-text" required style="width:100%;" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="msg_content"><?php esc_html_e('Email content', 'simplekitmailing'); ?></label></th>
                    <td>
                        <?php
                        // Use colors from the selected list (or first list) as preview reference
                        $msg_config = simplekitmailing_editor_config($selected_list_id);
                        $msg_config['textarea_name'] = 'msg_content';
                        wp_editor($saved_content, 'msg_content', $msg_config);
                        ?>
                    </td>
                </tr>
                <!-- test mail -->
                <tr>
                    <th scope="row"><label for="test_email"><?php esc_html_e('Send test', 'simplekitmailing'); ?></label></th>
                    <td>
                        <input type="email" id="test_email" name="test_email" placeholder="email@example.com" class="regular-text" />
                        <input type="submit" class="button" name="send_test" value="<?php esc_attr_e('Send test', 'simplekitmailing'); ?>" />
                    </td>
                </tr>
                <!-- mailing list select -->
                <tr>
                    <th scope="row"><label for="msg_list_id"><?php esc_html_e('Send to mailing list', 'simplekitmailing'); ?></label></th>
                    <td>
                        <p class="description"><?php esc_html_e('Select which mailing list this message will be sent to.', 'simplekitmailing'); ?></p>
                        <select id="msg_list_id" name="msg_list_id" required>
                            <option value=""><?php esc_html_e('— Select a list —', 'simplekitmailing'); ?></option>
                            <?php foreach ($lists as $list) : ?>
                                <option value="<?php echo (int) $list->id; ?>">
                                    <?php echo esc_html($list->name); ?>
                                    (<?php echo (int) simplekitmailing_get_list_subscriber_count($list->id); ?> <?php esc_html_e('subscribers', 'simplekitmailing'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button button-primary" name="send_to_all" value="<?php esc_attr_e('Send to all list subscribers', 'simplekitmailing'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to send this message to all subscribers of the selected list?', 'simplekitmailing'); ?>');" />
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var listStyles = <?php echo wp_json_encode($list_styles); ?>;

        function smApplyListStyle(listId) {
            if (!listId || !listStyles[listId]) return;

            var editor = tinymce.get('msg_content');
            if (!editor) return;

            var doc = editor.getDoc();
            if (!doc) return;

            var styleEl = doc.getElementById('sm-list-style');
            if (!styleEl) {
                styleEl = doc.createElement('style');
                styleEl.id = 'sm-list-style';
                doc.head.appendChild(styleEl);
            }
            styleEl.innerHTML = listStyles[listId];
        }

        // Apply style for the initially selected list
        var initialListId = parseInt($('#msg_list_id').val(), 10) || <?php echo (int) $selected_list_id; ?>;
        if (initialListId && listStyles[initialListId]) {
            // Wait a bit for TinyMCE to fully initialize
            var checkEditor = setInterval(function() {
                if (tinymce.get('msg_content')) {
                    clearInterval(checkEditor);
                    setTimeout(function() { smApplyListStyle(initialListId); }, 300);
                }
            }, 200);
        }

        // Apply style on list change
        $('#msg_list_id').on('change', function() {
            var listId = parseInt($(this).val(), 10);
            smApplyListStyle(listId);
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// Process message page actions
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitmailing_handle_message_actions');

function simplekitmailing_handle_message_actions() {
    if (!isset($_POST['simplekitmailing_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['simplekitmailing_nonce']));

    // Detect which button was clicked
    $is_test    = isset($_POST['send_test']);
    $is_sendall = isset($_POST['send_to_all']);

    global $wpdb;
    $table_messages     = $wpdb->prefix . 'sm_messages';
    $table_subscribers  = $wpdb->prefix . 'sm_subscribers';

    // Helper to preserve subject/content on redirect via transient
    $preserve_fields = function ($raw_subject, $raw_content, $extra = []) {
        $key = wp_hash(wp_rand() . time() . $raw_subject . $raw_content);
        $key = substr($key, 0, 16);
        set_transient('sm_preserve_' . $key, [
            'subject' => $raw_subject,
            'content' => $raw_content,
        ], 300); // 5 minutes
        $args = array_merge($extra, [
            'sm_preserve' => $key,
        ]);
        return $args;
    };

    // Send test
    if ($is_test && wp_verify_nonce($nonce, 'simplekitmailing_message_action')) {
        $test_email = sanitize_email(wp_unslash($_POST['test_email'] ?? ''));
        $subject    = sanitize_text_field(wp_unslash($_POST['msg_subject'] ?? ''));
        $content    = wp_kses_post(wp_unslash($_POST['msg_content'] ?? ''));

        if (empty($test_email) || !is_email($test_email)) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('Invalid test email.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
            exit;
        }

        if (empty($content)) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('The message content is empty. Write something before sending.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
            exit;
        }

        $list_id = isset($_POST['msg_list_id']) ? absint($_POST['msg_list_id']) : 0;
        $result = simplekitmailing_send_email($test_email, $subject, $content, $list_id);

        if ($result === true) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('Test sent successfully!', 'simplekitmailing'),
                    'type' => 'success',
                ]),
                admin_url('admin.php')
            ));
        } elseif (is_string($result)) {
            $error_detail = sanitize_text_field($result);
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => 'SMTP ERROR: ' . $error_detail,
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
        } else {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('Failed to send test. Check your SMTP settings.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
        }
        exit;
    }

    // Send to all subscribers of the selected list
    if ($is_sendall && wp_verify_nonce($nonce, 'simplekitmailing_message_action')) {
        $subject = sanitize_text_field(wp_unslash($_POST['msg_subject'] ?? ''));
        $content = wp_kses_post(wp_unslash($_POST['msg_content'] ?? ''));
        $list_id = isset($_POST['msg_list_id']) ? absint($_POST['msg_list_id']) : 0;

        if (empty($subject) || empty($content)) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('Fill in the subject and content before sending.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
            exit;
        }

        if (empty($list_id)) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('Select a target list.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
            exit;
        }

        // Count only subscribers of the selected list
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE list_id = %d",
            $table_subscribers, $list_id
        ));

        if ($total === 0) {
            wp_safe_redirect(add_query_arg(
                $preserve_fields($subject, $content, [
                    'page' => 'simplekitmailing-message',
                    'msg'  => __('The selected list has no subscribers.', 'simplekitmailing'),
                    'type' => 'error',
                ]),
                admin_url('admin.php')
            ));
            exit;
        }

        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert($table_messages, [
            'list_id' => $list_id,
            'subject' => $subject,
            'content' => $content,
            'status'  => 'active',
            'total'   => $total,
            'sent'    => 0,
        ]);
        $send_id = $wpdb->insert_id;

        // Remove old tasks, keeping only the 20 most recent
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM %i WHERE status != 'draft' ORDER BY created_at DESC",
                $table_messages
            )
        );
        if (count($ids) > 20) {
            $keep = array_slice($ids, 0, 20);
            $delete_ids = array_slice($ids, 20);
            foreach ($delete_ids as $delete_id) {
                // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->delete($table_messages, ['id' => $delete_id]);
            }
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'simplekitmailing-sends',
            'msg'  => __('Send registered. Dispatching will begin shortly.', 'simplekitmailing'),
            'type' => 'success',
        ], admin_url('admin.php')));
        exit;
    }
}
