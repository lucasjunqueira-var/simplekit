<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Protection setting helpers (global options)
// ---------------------------------------------------------------------------

/**
 * Get the global protection type.
 *
 * Values: 'none', 'akismet', 'recaptcha'
 */
function simplekitforms_get_protection() {
    return get_option('simplekitforms_protection', 'none');
}

/**
 * Get reCAPTCHA site key.
 */
function simplekitforms_get_recaptcha_site_key() {
    return get_option('simplekitforms_recaptcha_site_key', '');
}

/**
 * Get reCAPTCHA secret key.
 */
function simplekitforms_get_recaptcha_secret_key() {
    return get_option('simplekitforms_recaptcha_secret_key', '');
}

// ---------------------------------------------------------------------------
// Akismet validation
// ---------------------------------------------------------------------------

/**
 * Check if Akismet is active and available.
 */
function simplekitforms_akismet_available() {
    return function_exists('akismet_http_post') || function_exists('akismet_check_spam');
}

/**
 * Validate a submission against Akismet.
 *
 * @param string $email      Submitter email.
 * @param string $name       Submitter name (optional).
 * @param string $content    Additional content to check (optional).
 * @param string $ip         Submitter IP address (optional).
 * @return bool              True if marked as spam, false if ham.
 */
function simplekitforms_akismet_check($email, $name = '', $content = '', $ip = '') {
    if (!simplekitforms_akismet_available()) {
        return false;
    }

    $blog_url  = get_home_url();
    $blog_lang = get_bloginfo('language');

    $data = array(
        'blog'                 => $blog_url,
        'blog_lang'            => $blog_lang,
        'blog_charset'         => get_bloginfo('charset'),
        'user_ip'              => $ip ?: simplekitforms_get_client_ip(),
        'user_agent'           => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'referrer'             => sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')),
        'comment_type'         => 'contact-form',
        'comment_author'       => $name,
        'comment_author_email' => $email,
        'comment_content'      => $content ?: 'Form submission from ' . $blog_url,
        'permalink'            => sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? $blog_url)),
    );

    $data_string = '';
    foreach ($data as $key => $value) {
        $data_string .= $key . '=' . rawurlencode(wp_strip_all_tags($value ?? '')) . '&';
    }
    $data_string = rtrim($data_string, '&');

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
function simplekitforms_recaptcha_verify($token, $secret, $threshold = 0.5) {
    if (empty($token) || empty($secret)) {
        return false;
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'method' => 'POST',
        'body'   => array(
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => simplekitforms_get_client_ip(),
        ),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $body   = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!isset($result['success']) || $result['success'] !== true) {
        return false;
    }

    if (isset($result['score'])) {
        return (float) $result['score'] >= $threshold;
    }

    return true;
}

// ---------------------------------------------------------------------------
// Unified validation entry point
// ---------------------------------------------------------------------------

/**
 * Validate form submission against the global protection setting.
 *
 * Returns an array with:
 *   - 'valid' => bool
 *   - 'error_message' => string (if invalid)
 *
 * @param string $email            The submitter's email (from any email field).
 * @param string $name             The submitter's name (optional).
 * @param string $recaptcha_token  The reCAPTCHA token (passed from the AJAX handler).
 * @return array
 */
function simplekitforms_validate_protection($email, $name = '', $recaptcha_token = '') {
    $protection = simplekitforms_get_protection();

    if ($protection === 'none' || empty($protection)) {
        return array('valid' => true);
    }

    if ($protection === 'akismet') {
        $is_spam = simplekitforms_akismet_check($email, $name);
        if ($is_spam) {
            return array(
                'valid'         => false,
                'error_message' => 'Your submission has been flagged as spam. Please try again.',
            );
        }
        return array('valid' => true);
    }

    if ($protection === 'recaptcha') {
        $secret_key = simplekitforms_get_recaptcha_secret_key();

        if (empty($recaptcha_token)) {
            return array(
                'valid'         => false,
                'error_message' => 'reCAPTCHA verification failed. Please try again.',
            );
        }

        $verified = simplekitforms_recaptcha_verify($recaptcha_token, $secret_key);
        if (!$verified) {
            return array(
                'valid'         => false,
                'error_message' => 'reCAPTCHA verification failed. Please try again.',
            );
        }

        return array('valid' => true);
    }

    return array('valid' => true);
}
