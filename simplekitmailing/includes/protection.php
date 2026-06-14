<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Protection setting helpers (per list)
// ---------------------------------------------------------------------------

/**
 * Get the protection type for a list.
 *
 * Values: 'none', 'akismet', 'recaptcha'
 */
function simplekitmailing_get_protection($list_id) {
    return simplekitmailing_get_list_setting($list_id, 'protection', '', 'none');
}

/**
 * Get reCAPTCHA site key for a list (with global fallback).
 */
function simplekitmailing_get_recaptcha_site_key($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'recaptcha_site_key', 'simplekitmailing_recaptcha_site_key', '');
}

/**
 * Get reCAPTCHA secret key for a list (with global fallback).
 */
function simplekitmailing_get_recaptcha_secret_key($list_id = 0) {
    return simplekitmailing_get_list_setting($list_id, 'recaptcha_secret_key', 'simplekitmailing_recaptcha_secret_key', '');
}

// ---------------------------------------------------------------------------
// Akismet validation
// ---------------------------------------------------------------------------

/**
 * Check if Akismet is active and available.
 */
function simplekitmailing_akismet_available() {
    return function_exists('akismet_http_post') || function_exists('akismet_check_spam');
}

/**
 * Validate a submission against Akismet.
 *
 * @param string $email      Submitter email.
 * @param string $name       Submitter name (optional).
 * @param string $content    Additional content / comment to check (optional).
 * @param string $ip         Submitter IP address (optional).
 * @return bool              True if marked as spam, false if ham.
 */
function simplekitmailing_akismet_check($email, $name = '', $content = '', $ip = '') {
    // If Akismet is not available, skip the check
    if (!simplekitmailing_akismet_available()) {
        return false;
    }

    $blog_url = get_home_url();
    $blog_lang = get_bloginfo('language');

    $data = array(
        'blog'                 => $blog_url,
        'blog_lang'            => $blog_lang,
        'blog_charset'         => get_bloginfo('charset'),
        'user_ip'              => $ip ?: simplekitmailing_get_client_ip(),
        'user_agent'           => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'referrer'             => sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')),
        'comment_type'         => 'contact-form',
        'comment_author'       => $name,
        'comment_author_email' => $email,
        'comment_content'      => $content ?: __('Newsletter signup from', 'simplekitmailing') . ' ' . $blog_url,
        'permalink'            => sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? $blog_url)),
    );

    $data_string = '';
    foreach ($data as $key => $value) {
        $data_string .= $key . '=' . rawurlencode(wp_strip_all_tags($value ?? '')) . '&';
    }
    $data_string = rtrim($data_string, '&');

    // Use WordPress Akismet API if available
    if (function_exists('akismet_http_post')) {
        $response = akismet_http_post($data_string, 'comment-check');
        if (isset($response[1]) && $response[1] === 'true') {
            return true; // Spam
        }
    }

    return false; // Ham
}

// ---------------------------------------------------------------------------
// reCAPTCHA v3 (Invisible) validation
// ---------------------------------------------------------------------------

/**
 * Verify a reCAPTCHA v3 token with Google's API.
 *
 * @param string $token     The reCAPTCHA response token from the frontend.
 * @param string $secret    The reCAPTCHA secret key.
 * @param float  $threshold Minimum score threshold (0.0 to 1.0). Default 0.5.
 * @return bool             True if verification passes, false otherwise.
 */
function simplekitmailing_recaptcha_verify($token, $secret, $threshold = 0.5) {
    if (empty($token) || empty($secret)) {
        return false;
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'method' => 'POST',
        'body'   => array(
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => simplekitmailing_get_client_ip(),
        ),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!isset($result['success']) || $result['success'] !== true) {
        return false;
    }

    // Check score (for reCAPTCHA v3)
    if (isset($result['score'])) {
        return (float) $result['score'] >= $threshold;
    }

    // For v2, success alone is enough
    return true;
}

// ---------------------------------------------------------------------------
// Unified validation entry point
// ---------------------------------------------------------------------------

/**
 * Validate form submission against the list's protection setting.
 *
 * Returns an array with:
 *   - 'valid' => bool
 *   - 'error_message' => string (if invalid)
 *
 * @param int    $list_id          The mailing list ID.
 * @param string $email            The submitter's email.
 * @param string $name             The submitter's name (optional).
 * @param string $recaptcha_token  The reCAPTCHA token (optional, passed from the AJAX handler).
 * @return array
 */
function simplekitmailing_validate_protection($list_id, $email, $name = '', $recaptcha_token = '') {
    $protection = simplekitmailing_get_protection($list_id);

    if ($protection === 'none' || empty($protection)) {
        return array('valid' => true);
    }

    if ($protection === 'akismet') {
        // Akismet check
        $is_spam = simplekitmailing_akismet_check($email, $name, '', '');
        if ($is_spam) {
            return array(
                'valid'         => false,
                'error_message' => __('Your submission has been flagged as spam. Please try again.', 'simplekitmailing'),
            );
        }
        return array('valid' => true);
    }

    if ($protection === 'recaptcha') {
        $secret_key = simplekitmailing_get_recaptcha_secret_key($list_id);

        if (empty($recaptcha_token)) {
            return array(
                'valid'         => false,
                'error_message' => __('reCAPTCHA verification failed. Please try again.', 'simplekitmailing'),
            );
        }

        $verified = simplekitmailing_recaptcha_verify($recaptcha_token, $secret_key);
        if (!$verified) {
            return array(
                'valid'         => false,
                'error_message' => __('reCAPTCHA verification failed. Please try again.', 'simplekitmailing'),
            );
        }

        return array('valid' => true);
    }

    // Unknown protection type – allow through
    return array('valid' => true);
}
