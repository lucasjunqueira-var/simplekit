<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Helpers: double opt-in settings per list
// ---------------------------------------------------------------------------
function simplekitmailing_get_double_optin($list_id) {
    return (bool) simplekitmailing_get_list_setting($list_id, 'double_optin', '', false);
}

function simplekitmailing_get_confirm_page_id($list_id) {
    return (int) simplekitmailing_get_list_setting($list_id, 'confirm_page_id', '', 0);
}

function simplekitmailing_get_confirm_email_subject($list_id) {
    return simplekitmailing_get_list_setting($list_id, 'confirm_email_subject', '', __('Please confirm your subscription', 'simplekitmailing'));
}

function simplekitmailing_get_confirm_email_content($list_id) {
    $default = __("Hello, this email address has just been added to our mailing list. To complete the registration process, please access the link below.<br><br>{confirm_link}<br><br><em>If you did not request to be added to our list, you can ignore this message or, if you prefer, indicate that you do not want your email to be used in future subscription attempts by accessing the link {unsubscribe_link}</em>", 'simplekitmailing');
    return simplekitmailing_get_list_setting($list_id, 'confirm_email_content', '', $default);
}

// ---------------------------------------------------------------------------
// Generate activation code
// ---------------------------------------------------------------------------
function simplekitmailing_generate_activation_code() {
    return wp_hash(wp_rand() . time() . uniqid('sm_', true));
}

// ---------------------------------------------------------------------------
// Ensure the sm_pending table exists (for installations before the upgrade)
// ---------------------------------------------------------------------------
function simplekitmailing_ensure_pending_table() {
    global $wpdb;
    $table_pending = $wpdb->prefix . 'sm_pending';

    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $table_exists = $wpdb->get_var(
        $wpdb->prepare("SHOW TABLES LIKE %s", $table_pending)
    );
    if ($table_exists) {
        return;
    }

    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_pending (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        list_id BIGINT UNSIGNED DEFAULT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT '',
        phone VARCHAR(50) DEFAULT '',
        ip VARCHAR(45) DEFAULT '',
        activation_code VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_list_email (list_id, email),
        KEY idx_activation_code (activation_code)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// ---------------------------------------------------------------------------
// Add email to pending list and send confirmation
// ---------------------------------------------------------------------------
function simplekitmailing_add_pending($list_id, $email, $name = '', $phone = '', $ip = '') {
    global $wpdb;

    // Ensure the pending table exists
    simplekitmailing_ensure_pending_table();

    $table_pending = $wpdb->prefix . 'sm_pending';

    // Check if already in the removed list
    $table_removed = $wpdb->prefix . 'sm_removed';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $removed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
        $table_removed, $list_id, $email
    ));
    if ($removed > 0) {
        return 'removed';
    }

    // Check if already a subscriber
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $subscribed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
        $table_subscribers, $list_id, $email
    ));
    if ($subscribed > 0) {
        return 'subscribed';
    }

    // Check if already pending (re-registration: generate new code and resend)
    $code = simplekitmailing_generate_activation_code();
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM %i WHERE list_id = %d AND email = %s",
        $table_pending, $list_id, $email
    ));

    if ($existing) {
        // Update existing pending entry with new code and timestamp
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            $table_pending,
            [
                'activation_code' => $code,
                'name'            => $name,
                'phone'           => $phone,
                'ip'              => $ip,
                'created_at'      => current_time('mysql'),
            ],
            ['id' => $existing]
        );
        $pending_id = $existing;
    } else {
        // Insert new pending entry
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert($table_pending, [
            'list_id'         => $list_id,
            'email'           => $email,
            'name'            => $name,
            'phone'           => $phone,
            'ip'              => $ip,
            'activation_code' => $code,
            'created_at'      => current_time('mysql'),
        ]);
        $pending_id = $wpdb->insert_id;
    }

    if (!$pending_id) {
        return false;
    }

    // Send confirmation email
    $sent = simplekitmailing_send_confirmation_email($pending_id, $list_id, $email, $code);

    return $sent ? 'pending' : 'email_failed';
}

// ---------------------------------------------------------------------------
// Send confirmation email
// ---------------------------------------------------------------------------
function simplekitmailing_send_confirmation_email($pending_id, $list_id, $email, $code) {
    $subject = simplekitmailing_get_confirm_email_subject($list_id);
    $content = simplekitmailing_get_confirm_email_content($list_id);

    // Build confirmation link
    $confirm_page_id = simplekitmailing_get_confirm_page_id($list_id);
    if ($confirm_page_id) {
        $page_url = get_permalink($confirm_page_id);
        if (!$page_url) {
            $page_url = home_url('/');
        }
    } else {
        $page_url = home_url('/');
    }

    $confirm_url = add_query_arg([
        'sm_email' => urlencode($email),
        'sm_code'  => $code,
        'list_id'  => $list_id,
    ], $page_url);

    // Build unsubscribe link
    $unsub_page_id = simplekitmailing_get_unsubscribe_page_id($list_id);
    if ($unsub_page_id) {
        $unsub_page_url = get_permalink($unsub_page_id);
        if (!$unsub_page_url) {
            $unsub_page_url = home_url('/');
        }
    } else {
        $unsub_page_url = home_url('/');
    }

    $unsubscribe_url = add_query_arg([
        'em'      => urlencode($email),
        'list_id' => $list_id,
    ], $unsub_page_url);

    // Replace placeholders in content
    $content = str_replace('{confirm_link}', '<a href="' . esc_url($confirm_url) . '">' . esc_url($confirm_url) . '</a>', $content);
    $content = str_replace('{unsubscribe_link}', '<a href="' . esc_url($unsubscribe_url) . '">' . esc_url($unsubscribe_url) . '</a>', $content);

    $result = simplekitmailing_send_email($email, $subject, $content, $list_id);

    return ($result === true);
}

// ---------------------------------------------------------------------------
// Confirm a pending subscription
// ---------------------------------------------------------------------------
function simplekitmailing_confirm_subscription($email, $code, $list_id = 0) {
    global $wpdb;

    // Ensure the pending table exists
    simplekitmailing_ensure_pending_table();

    $table_pending     = $wpdb->prefix . 'sm_pending';
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';

    // Find the pending entry
    if ($list_id) {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE email = %s AND activation_code = %s AND list_id = %d",
            $table_pending, $email, $code, $list_id
        ));
    } else {
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE email = %s AND activation_code = %s",
            $table_pending, $email, $code
        ));
    }

    if (!$pending) {
        return 'invalid';
    }

    // Check if email is already in the removed list
    $table_removed = $wpdb->prefix . 'sm_removed';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $removed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
        $table_removed, $pending->list_id, $email
    ));
    if ($removed > 0) {
        // Remove from pending since it's blocked
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($table_pending, ['id' => $pending->id]);
        return 'removed';
    }

    // Check if already subscribed (shouldn't happen, but guard)
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $subscribed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
        $table_subscribers, $pending->list_id, $email
    ));
    if ($subscribed > 0) {
        // Clean up pending and return success
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($table_pending, ['id' => $pending->id]);
        return 'confirmed';
    }

    // Move from pending to subscribers
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $inserted = $wpdb->insert($table_subscribers, [
        'list_id' => $pending->list_id,
        'email'   => $pending->email,
        'name'    => $pending->name,
        'phone'   => $pending->phone,
        'ip'      => $pending->ip,
    ]);

    if ($inserted) {
        // Remove from pending
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($table_pending, ['id' => $pending->id]);
        return 'confirmed';
    }

    return 'error';
}

// ---------------------------------------------------------------------------
// Cleanup expired pending entries (older than 7 days)
// ---------------------------------------------------------------------------
function simplekitmailing_cleanup_pending() {
    global $wpdb;
    $table_pending = $wpdb->prefix . 'sm_pending';

    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM %i WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $table_pending
        )
    );
}

// ---------------------------------------------------------------------------
// Cron: schedule pending cleanup daily
// ---------------------------------------------------------------------------
add_action('wp', 'simplekitmailing_schedule_pending_cleanup');
function simplekitmailing_schedule_pending_cleanup() {
    if (!wp_next_scheduled('simplekitmailing_cleanup_pending_cron')) {
        wp_schedule_event(time(), 'daily', 'simplekitmailing_cleanup_pending_cron');
    }
}

add_action('simplekitmailing_cleanup_pending_cron', 'simplekitmailing_cleanup_pending');

// ---------------------------------------------------------------------------
// Helper: get pending count for a list
// ---------------------------------------------------------------------------
function simplekitmailing_get_pending_count($list_id) {
    global $wpdb;
    $table_pending = $wpdb->prefix . 'sm_pending';
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    return (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM %i WHERE list_id = %d", $table_pending, $list_id)
    );
}
