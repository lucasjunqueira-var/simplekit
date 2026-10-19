<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Evaluator session
//
// Evaluators are not WordPress users, so wp_set_auth_cookie() must not be used.
// Instead the plugin issues a signed cookie (HMAC over the e-mail address and
// the expiration timestamp) which is validated on every request.
// ---------------------------------------------------------------------------

function simplekitregistration_cookie_name() {
    return 'skr_evaluator';
}

function simplekitregistration_evaluator_signature($email, $expires) {
    return hash_hmac('sha256', $email . '|' . $expires, wp_salt('simplekitregistration_evaluator'));
}

function simplekitregistration_build_evaluator_token($email, $expires) {
    $signature = simplekitregistration_evaluator_signature($email, $expires);
    return base64_encode($email . '|' . $expires . '|' . $signature);
}

/**
 * Parse and validate a cookie token. Returns the e-mail address or an empty
 * string when the token is invalid or expired.
 */
function simplekitregistration_parse_evaluator_token($token) {
    $token = (string) $token;
    if ($token === '') {
        return '';
    }

    $decoded = base64_decode($token, true);
    if ($decoded === false) {
        return '';
    }

    $parts = explode('|', $decoded);
    if (count($parts) !== 3) {
        return '';
    }

    list($email, $expires, $signature) = $parts;
    if (!is_email($email)) {
        return '';
    }

    if ((int) $expires < time()) {
        return '';
    }

    $expected = simplekitregistration_evaluator_signature($email, $expires);
    if (!hash_equals($expected, (string) $signature)) {
        return '';
    }

    return sanitize_email($email);
}

function simplekitregistration_set_evaluator_cookie($email) {
    $expires = time() + WEEK_IN_SECONDS;
    $token   = simplekitregistration_build_evaluator_token($email, $expires);

    $path   = (defined('COOKIEPATH') && COOKIEPATH !== '') ? COOKIEPATH : '/';
    $domain = (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

    setcookie(simplekitregistration_cookie_name(), $token, [
        'expires'  => $expires,
        'path'     => $path,
        'domain'   => $domain,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Make the cookie available during the current request as well.
    $_COOKIE[simplekitregistration_cookie_name()] = $token;
}

function simplekitregistration_clear_evaluator_cookie() {
    $path   = (defined('COOKIEPATH') && COOKIEPATH !== '') ? COOKIEPATH : '/';
    $domain = (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';

    setcookie(simplekitregistration_cookie_name(), '', [
        'expires'  => time() - YEAR_IN_SECONDS,
        'path'     => $path,
        'domain'   => $domain,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE[simplekitregistration_cookie_name()]);
}

/**
 * Return the currently authenticated evaluator row, or null.
 */
function simplekitregistration_get_current_evaluator() {
    static $evaluator = false;

    if ($evaluator !== false) {
        return $evaluator;
    }

    $evaluator = null;
    $cookie    = isset($_COOKIE[simplekitregistration_cookie_name()])
        ? sanitize_text_field(wp_unslash($_COOKIE[simplekitregistration_cookie_name()]))
        : '';

    $email = simplekitregistration_parse_evaluator_token($cookie);
    if ($email === '') {
        return $evaluator;
    }

    $row = simplekitregistration_get_evaluator_by_email($email);
    if ($row) {
        $evaluator = $row;
    }

    return $evaluator;
}

/**
 * Check whether an evaluator is allowed to see a given form.
 */
function simplekitregistration_evaluator_can_access($evaluator, $form_id) {
    if (!$evaluator) {
        return false;
    }
    $forms = json_decode($evaluator->forms_json ?? '[]', true);
    if (!is_array($forms)) {
        return false;
    }
    $forms = array_map('intval', $forms);
    return in_array((int) $form_id, $forms, true);
}

// ---------------------------------------------------------------------------
// Login throttling
// ---------------------------------------------------------------------------
function simplekitregistration_login_throttle_key() {
    return 'skr_login_' . md5(simplekitregistration_get_client_ip());
}

function simplekitregistration_login_is_locked() {
    return (int) get_transient(simplekitregistration_login_throttle_key()) >= 5;
}

function simplekitregistration_register_failed_login() {
    $key   = simplekitregistration_login_throttle_key();
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, 15 * MINUTE_IN_SECONDS);
}

function simplekitregistration_clear_failed_logins() {
    delete_transient(simplekitregistration_login_throttle_key());
}

// ---------------------------------------------------------------------------
// Login / logout handlers (public, logged-out visitors included)
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_simplekitregistration_login', 'simplekitregistration_handle_login');
add_action('admin_post_simplekitregistration_login', 'simplekitregistration_handle_login');

function simplekitregistration_handle_login() {
    if (!isset($_POST['skr_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['skr_login_nonce'])), 'simplekitregistration_login')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'), '', ['response' => 403]);
    }

    $redirect = isset($_POST['skr_redirect']) ? esc_url_raw(wp_unslash($_POST['skr_redirect'])) : home_url('/');
    $redirect = wp_validate_redirect($redirect, home_url('/'));

    $email    = sanitize_email(wp_unslash($_POST['skr_email'] ?? ''));
    $password = sanitize_text_field(wp_unslash($_POST['skr_password'] ?? ''));

    if (simplekitregistration_login_is_locked() || !is_email($email) || $password === '') {
        simplekitregistration_register_failed_login();
        wp_safe_redirect(add_query_arg('skr_login_error', '1', $redirect));
        exit;
    }

    $evaluator = simplekitregistration_get_evaluator_by_email($email);
    if (!$evaluator || !wp_check_password($password, $evaluator->password_hash)) {
        simplekitregistration_register_failed_login();
        wp_safe_redirect(add_query_arg('skr_login_error', '1', $redirect));
        exit;
    }

    simplekitregistration_clear_failed_logins();
    simplekitregistration_set_evaluator_cookie($email);

    wp_safe_redirect(remove_query_arg('skr_login_error', $redirect));
    exit;
}

add_action('init', 'simplekitregistration_maybe_logout');

function simplekitregistration_maybe_logout() {
    // Logging out is a state change, so it requires a valid nonce.
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'simplekitregistration_logout')) {
        return;
    }

    simplekitregistration_clear_evaluator_cookie();

    $redirect = wp_get_referer();
    if (!$redirect) {
        $redirect = home_url('/');
    }
    $redirect = remove_query_arg(['skr_logout', '_wpnonce', 'skr_form', 'skr_entry', 'skr_page'], $redirect);

    wp_safe_redirect($redirect);
    exit;
}
