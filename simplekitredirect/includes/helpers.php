<?php
/**
 * URL helpers shared by the meta box and the frontend redirect.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

/**
 * Post types that can be redirected.
 *
 * @return string[]
 */
function simplekitredirect_post_types() {
    $post_types = apply_filters('simplekitredirect_post_types', array('post', 'page'));

    if (!is_array($post_types)) {
        return array('post', 'page');
    }

    $clean = array();

    foreach ($post_types as $post_type) {
        if (!is_string($post_type) || '' === $post_type) {
            continue;
        }

        $clean[] = sanitize_key($post_type);
    }

    $clean = array_filter($clean);

    return empty($clean) ? array('post', 'page') : array_values(array_unique($clean));
}

/**
 * Validate and normalize a redirect target.
 *
 * Accepts absolute http(s) URLs, protocol-relative URLs and root-relative
 * paths. Returns an absolute URL or a WP_Error describing the problem.
 *
 * @param mixed $raw Raw target value.
 * @return string|WP_Error
 */
function simplekitredirect_normalize_url($raw) {
    if (!is_string($raw)) {
        return new WP_Error(
            'simplekitredirect_invalid_url',
            __('The redirect target must be a URL.', 'simplekitredirect')
        );
    }

    $url = trim($raw);

    if ('' === $url) {
        return new WP_Error(
            'simplekitredirect_empty_url',
            __('The redirect target cannot be empty.', 'simplekitredirect')
        );
    }

    // Control characters could be used for header injection.
    if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return new WP_Error(
            'simplekitredirect_invalid_characters',
            __('The redirect target contains invalid characters.', 'simplekitredirect')
        );
    }

    if (0 === strpos($url, '//')) {
        $url = (is_ssl() ? 'https:' : 'http:') . $url;
    } elseif (0 === strpos($url, '/')) {
        $url = home_url($url);
    }

    $parts = wp_parse_url($url);
    $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
    $host   = isset($parts['host']) ? $parts['host'] : '';

    if ('' === $scheme || '' === $host) {
        return new WP_Error(
            'simplekitredirect_invalid_url',
            __('The redirect target must be a valid URL.', 'simplekitredirect')
        );
    }

    if (!in_array($scheme, array('http', 'https'), true)) {
        return new WP_Error(
            'simplekitredirect_invalid_scheme',
            __('Only http and https addresses are allowed.', 'simplekitredirect')
        );
    }

    if (!empty($parts['user']) || !empty($parts['pass'])) {
        return new WP_Error(
            'simplekitredirect_credentials',
            __('Addresses with a username or password are not allowed.', 'simplekitredirect')
        );
    }

    $url   = esc_url_raw($url, array('http', 'https'));
    $check = wp_parse_url($url);

    if (empty($check['host'])) {
        return new WP_Error(
            'simplekitredirect_invalid_url',
            __('The redirect target must be a valid URL.', 'simplekitredirect')
        );
    }

    return $url;
}

/**
 * Compare two URLs ignoring scheme port defaults, a leading www. and trailing
 * slashes. Fragments are ignored because they never reach the server.
 *
 * @param string $url_a First URL.
 * @param string $url_b Second URL.
 * @return bool
 */
function simplekitredirect_same_url($url_a, $url_b) {
    $url_a = simplekitredirect_normalize_url($url_a);
    $url_b = simplekitredirect_normalize_url($url_b);

    if (is_wp_error($url_a) || is_wp_error($url_b)) {
        return false;
    }

    $parts_a = wp_parse_url($url_a);
    $parts_b = wp_parse_url($url_b);

    if (!$parts_a || !$parts_b) {
        return false;
    }

    $scheme_a = isset($parts_a['scheme']) ? strtolower($parts_a['scheme']) : '';
    $scheme_b = isset($parts_b['scheme']) ? strtolower($parts_b['scheme']) : '';

    $host_a = isset($parts_a['host']) ? preg_replace('/^www\./i', '', strtolower($parts_a['host'])) : '';
    $host_b = isset($parts_b['host']) ? preg_replace('/^www\./i', '', strtolower($parts_b['host'])) : '';

    if ($scheme_a !== $scheme_b || $host_a !== $host_b) {
        return false;
    }

    $port_a = isset($parts_a['port']) ? (int) $parts_a['port'] : 0;
    $port_b = isset($parts_b['port']) ? (int) $parts_b['port'] : 0;

    if (('https' === $scheme_a && 443 === $port_a) || ('http' === $scheme_a && 80 === $port_a)) {
        $port_a = 0;
    }

    if (('https' === $scheme_b && 443 === $port_b) || ('http' === $scheme_b && 80 === $port_b)) {
        $port_b = 0;
    }

    if ($port_a !== $port_b) {
        return false;
    }

    $path_a = isset($parts_a['path']) ? rawurldecode($parts_a['path']) : '/';
    $path_b = isset($parts_b['path']) ? rawurldecode($parts_b['path']) : '/';

    $path_a = '/' . trim($path_a, '/');
    $path_b = '/' . trim($path_b, '/');

    if ($path_a !== $path_b) {
        return false;
    }

    $query_a = isset($parts_a['query']) ? $parts_a['query'] : '';
    $query_b = isset($parts_b['query']) ? $parts_b['query'] : '';

    return $query_a === $query_b;
}

/**
 * Current request URL, used by the loop guard.
 *
 * @return string Empty string when the server variables are missing.
 */
function simplekitredirect_current_url() {
    if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
        return '';
    }

    $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
    $uri  = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));

    if ('' === $host || '' === $uri) {
        return '';
    }

    return (is_ssl() ? 'https://' : 'http://') . $host . $uri;
}

/**
 * Whether the target would send the visitor to the current address again.
 *
 * @param string $target  Redirect target.
 * @param int    $post_id Post ID being requested.
 * @return bool
 */
function simplekitredirect_is_self_target($target, $post_id) {
    $permalink = get_permalink($post_id);

    if ($permalink && simplekitredirect_same_url($target, $permalink)) {
        return true;
    }

    $current = simplekitredirect_current_url();

    return ('' !== $current && simplekitredirect_same_url($target, $current));
}

/**
 * Read the validated redirect configured for a post.
 *
 * @param int $post_id Post ID.
 * @return array|null Array with url and status, or null when there is none.
 */
function simplekitredirect_get_redirect($post_id) {
    $post_id = absint($post_id);

    if (!$post_id) {
        return null;
    }

    $raw = get_post_meta($post_id, '_simplekitredirect_url', true);

    if (!is_string($raw) || '' === trim($raw)) {
        return null;
    }

    $url = simplekitredirect_normalize_url($raw);

    if (is_wp_error($url)) {
        return null;
    }

    $status = get_post_meta($post_id, '_simplekitredirect_status', true);

    return array(
        'url'    => $url,
        'status' => ('301' === (string) $status) ? 301 : 302,
    );
}
