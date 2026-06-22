<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Protection setting helpers (per list)
// ---------------------------------------------------------------------------

/**
 * Get the protection type for a list.
 *
 * Values: 'none', 'recaptcha'
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
