<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// AJAX: Email signup via Gutenberg block (frontend)
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitmailing_collect', 'simplekitmailing_ajax_collect');
add_action('wp_ajax_nopriv_simplekitmailing_collect', 'simplekitmailing_ajax_collect');

function simplekitmailing_ajax_collect() {
    check_ajax_referer('simplekitmailing_collect_nonce', 'nonce');

    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $agree = isset($_POST['agree']) && $_POST['agree'] === '1';
    $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : 0;

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Please check the email address provided.', 'simplekitmailing')]);
    }

    if (!$agree) {
        wp_send_json_error(['message' => __('You need to agree to our terms to register your email.', 'simplekitmailing')]);
    }

    // If no list_id, use the first list
    if (!$list_id) {
        $lists = simplekitmailing_get_lists();
        $list_id = !empty($lists) ? (int) $lists[0]->id : 0;
    }

    // Validate additional fields according to configuration (per list)
    $collect_name  = simplekitmailing_get_collect_name($list_id);
    $collect_phone = simplekitmailing_get_collect_phone($list_id);

    $name  = '';
    $phone = '';

    if ($collect_name) {
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        if (empty($name)) {
            wp_send_json_error(['message' => __('Please fill in your name.', 'simplekitmailing')]);
        }
    }

    if ($collect_phone) {
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        if (empty($phone)) {
            wp_send_json_error(['message' => __('Please fill in your phone / WhatsApp.', 'simplekitmailing')]);
        }
    }

    // Validate protection (reCAPTCHA)
    $recaptcha_token = sanitize_text_field(wp_unslash($_POST['recaptcha_token'] ?? ''));
    $protection_check = simplekitmailing_validate_protection($list_id, $email, $name, $recaptcha_token);
    if (!$protection_check['valid']) {
        wp_send_json_error(['message' => $protection_check['error_message']]);
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';

    // Check if in removed list (for this specific list)
    // data must be retrieved at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $removed = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
            $table_removed, $list_id, $email
        )
    );
    if ($removed > 0) {
        wp_send_json_error(['message' => __('This email is in the removed list and cannot be registered again.', 'simplekitmailing')]);
    }

    // Check if already exists (in this list)
    // data must be retrieved at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE list_id = %d AND email = %s",
        $table_subscribers, $list_id, $email
    ));
    if ($exists > 0) {
        wp_send_json_error(['message' => __('This email is already registered in this list.', 'simplekitmailing')]);
    }

    // Only collect IP if the option is enabled for this list
    $ip = '';
    if (simplekitmailing_get_collect_ip($list_id)) {
        $ip = simplekitmailing_get_client_ip();
    }

    // Check if double opt-in is enabled for this list
    if (simplekitmailing_get_double_optin($list_id)) {
        // Use double opt-in flow: add to pending, send confirmation email
        $result = simplekitmailing_add_pending($list_id, $email, $name, $phone, $ip);

        if ($result === 'removed') {
            wp_send_json_error(['message' => __('This email is in the removed list and cannot be registered again.', 'simplekitmailing')]);
        } elseif ($result === 'subscribed') {
            wp_send_json_error(['message' => __('This email is already registered in this list.', 'simplekitmailing')]);
        } elseif ($result === 'email_failed') {
            wp_send_json_error(['message' => __('Registration recorded but the confirmation email could not be sent. Please try again.', 'simplekitmailing')]);
        } elseif ($result === 'pending') {
            $redirect_id = simplekitmailing_get_redirect_page_id($list_id);
            $redirect_url = $redirect_id ? get_permalink($redirect_id) : home_url();
            wp_send_json_success([
                'redirect' => $redirect_url,
                'message'  => __('A confirmation email has been sent to your email address. Please click the link in the email to confirm your subscription.', 'simplekitmailing'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error registering. Please try again.', 'simplekitmailing')]);
        }
        return;
    }

    // Standard flow (no double opt-in): insert directly
    // data must be inserted on custom plugin table
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $inserted = $wpdb->insert($table_subscribers, [
        'list_id' => $list_id,
        'email' => $email,
        'name'  => $name,
        'phone' => $phone,
        'ip'    => $ip,
    ]);

    if ($inserted) {
        $redirect_id = simplekitmailing_get_redirect_page_id($list_id);
        $redirect_url = $redirect_id ? get_permalink($redirect_id) : home_url();
        wp_send_json_success(['redirect' => $redirect_url]);
    } else {
        wp_send_json_error(['message' => __('Error registering. Please try again.', 'simplekitmailing')]);
    }
}

// ---------------------------------------------------------------------------
// Get client IP
// ---------------------------------------------------------------------------
function simplekitmailing_get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_raw = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $forwarded_for = explode(',', $forwarded_raw);
        return sanitize_text_field(trim($forwarded_for[0]));
    } else {
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}
