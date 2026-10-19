<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Messages attached to a response.
//
// Messages are permanent: they can be added from the dashboard and from the
// restricted evaluator area, but never edited or deleted individually. The
// author (name/e-mail) and the timestamp are always recorded.
// ---------------------------------------------------------------------------

/**
 * Return the messages of a response, in order of receipt.
 */
function simplekitregistration_get_entry_messages($entry_id) {
    global $wpdb;
    $table = simplekitregistration_messages_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return $wpdb->get_results($wpdb->prepare(
        'SELECT * FROM %i WHERE entry_id = %d ORDER BY created_at ASC, id ASC',
        $table,
        (int) $entry_id
    ));
}

/**
 * Store a new message.
 *
 * @param int    $entry_id
 * @param int    $form_id
 * @param string $author_type  'admin' or 'evaluator'.
 * @param string $author_name
 * @param string $author_email
 * @param string $message
 * @return int|false Message ID, or false on failure.
 */
function simplekitregistration_add_message($entry_id, $form_id, $author_type, $author_name, $author_email, $message) {
    $message = sanitize_textarea_field($message);
    if ($message === '') {
        return false;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $inserted = $wpdb->insert(
        simplekitregistration_messages_table(),
        [
            'entry_id'     => (int) $entry_id,
            'form_id'      => (int) $form_id,
            'author_type'  => ($author_type === 'evaluator') ? 'evaluator' : 'admin',
            'author_name'  => sanitize_text_field($author_name),
            'author_email' => sanitize_email($author_email),
            'message'      => $message,
        ],
        ['%d', '%d', '%s', '%s', '%s', '%s']
    );

    return $inserted ? (int) $wpdb->insert_id : false;
}

/**
 * Delete every message of a response (used when the response itself is removed).
 */
function simplekitregistration_delete_entry_messages($entry_id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return $wpdb->delete(simplekitregistration_messages_table(), ['entry_id' => (int) $entry_id], ['%d']);
}

/**
 * Delete every message of a form (used when the form itself is removed).
 */
function simplekitregistration_delete_form_messages($form_id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return $wpdb->delete(simplekitregistration_messages_table(), ['form_id' => (int) $form_id], ['%d']);
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------
/**
 * Render the messages list of a response (escaped HTML).
 */
function simplekitregistration_render_messages_list($entry_id, $use_custom_texts = false) {
    $messages = simplekitregistration_get_entry_messages($entry_id);
    $html     = '<div class="skr-message-list">';

    if (empty($messages)) {
        $empty_text = $use_custom_texts
            ? simplekitregistration_evaluator_text('messages_empty')
            : __('No messages yet.', 'simplekitregistration');
        $html .= '<p class="skr-message-empty">' . esc_html($empty_text) . '</p>';
    } else {
        foreach ($messages as $item) {
            $meta = $item->author_name !== '' ? $item->author_name : $item->author_email;
            if ($item->author_name !== '' && $item->author_email !== '' && $item->author_name !== $item->author_email) {
                $meta .= ' <' . $item->author_email . '>';
            }
            $html .= '<div class="skr-message-item">';
            $html .= '<p class="skr-message-meta"><strong>' . esc_html($meta) . '</strong> &middot; ' . esc_html($item->created_at) . '</p>';
            $html .= '<p class="skr-message-text">' . nl2br(esc_html($item->message)) . '</p>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    return $html;
}

/**
 * Render the evaluator-facing message form (posts to admin-post.php).
 */
function simplekitregistration_render_message_form($entry_id, $redirect_url) {
    $entry_id = (int) $entry_id;

    ob_start();
    ?>
    <div class="skr-message-form">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('simplekitregistration_message_' . $entry_id, 'skr_message_nonce'); ?>
            <input type="hidden" name="action" value="simplekitregistration_add_message">
            <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>">
            <input type="hidden" name="skr_redirect" value="<?php echo esc_url($redirect_url); ?>">
            <label for="skr-message-<?php echo esc_attr($entry_id); ?>"><?php echo esc_html(simplekitregistration_evaluator_text('message_write_label')); ?></label>
            <textarea id="skr-message-<?php echo esc_attr($entry_id); ?>" name="skr_message" rows="3" required></textarea>
            <p><button type="submit" class="skr-submit"><?php echo esc_html(simplekitregistration_evaluator_text('message_send_button')); ?></button></p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// AJAX: add message from the dashboard
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitregistration_add_message', 'simplekitregistration_ajax_add_message');

function simplekitregistration_ajax_add_message() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Access denied.', 'simplekitregistration')]);
    }
    check_ajax_referer('simplekitregistration_nonce', 'nonce');

    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
    $message  = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    $entry = $entry_id > 0 ? simplekitregistration_get_entry($entry_id) : null;
    if (!$entry) {
        wp_send_json_error(['message' => __('Response not found.', 'simplekitregistration')]);
    }

    if (trim((string) $message) === '') {
        wp_send_json_error(['message' => __('Please write a message before sending.', 'simplekitregistration')]);
    }

    $user         = wp_get_current_user();
    $author_name  = $user->display_name !== '' ? $user->display_name : $user->user_login;
    $author_email = $user->user_email;

    $added = simplekitregistration_add_message($entry->id, $entry->form_id, 'admin', $author_name, $author_email, $message);
    if (!$added) {
        wp_send_json_error(['message' => __('The message could not be saved.', 'simplekitregistration')]);
    }

    wp_send_json_success([
        'message' => __('Message sent.', 'simplekitregistration'),
        'html'    => simplekitregistration_render_messages_list($entry->id),
    ]);
}

// ---------------------------------------------------------------------------
// admin-post: add message from the restricted evaluator area
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_simplekitregistration_add_message', 'simplekitregistration_handle_add_message');
add_action('admin_post_simplekitregistration_add_message', 'simplekitregistration_handle_add_message');

function simplekitregistration_handle_add_message() {
    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;

    $redirect = isset($_POST['skr_redirect']) ? esc_url_raw(wp_unslash($_POST['skr_redirect'])) : home_url('/');
    $redirect = wp_validate_redirect($redirect, home_url('/'));

    $nonce = isset($_POST['skr_message_nonce']) ? sanitize_text_field(wp_unslash($_POST['skr_message_nonce'])) : '';
    if ($entry_id <= 0 || !wp_verify_nonce($nonce, 'simplekitregistration_message_' . $entry_id)) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'), '', ['response' => 403]);
    }

    $entry = simplekitregistration_get_entry($entry_id);
    if (!$entry) {
        wp_die(esc_html__('Response not found.', 'simplekitregistration'), '', ['response' => 404]);
    }

    $message = isset($_POST['skr_message']) ? sanitize_textarea_field(wp_unslash($_POST['skr_message'])) : '';
    if (trim((string) $message) === '') {
        wp_safe_redirect(add_query_arg('skr_message_error', '1', $redirect));
        exit;
    }

    // WordPress administrators may post from anywhere; evaluators must be
    // authenticated and allowed to see the form of this response.
    if (current_user_can('manage_options')) {
        $user         = wp_get_current_user();
        $author_name  = $user->display_name !== '' ? $user->display_name : $user->user_login;
        $author_email = $user->user_email;
        $author_type  = 'admin';
    } else {
        $evaluator = simplekitregistration_get_current_evaluator();
        if (!$evaluator || !simplekitregistration_evaluator_can_access($evaluator, $entry->form_id)) {
            wp_die(esc_html__('Access denied.', 'simplekitregistration'), '', ['response' => 403]);
        }
        $author_name  = $evaluator->email;
        $author_email = $evaluator->email;
        $author_type  = 'evaluator';
    }

    simplekitregistration_add_message($entry->id, $entry->form_id, $author_type, $author_name, $author_email, $message);

    wp_safe_redirect(add_query_arg('skr_message_added', '1', $redirect));
    exit;
}
