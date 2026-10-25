<?php
/**
 * Frontend redirect handler.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

add_action('template_redirect', 'simplekitredirect_maybe_redirect', 1);

/**
 * Send visitors to the configured address before any output is produced.
 *
 * @return void
 */
function simplekitredirect_maybe_redirect() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    if (defined('WP_CLI') && WP_CLI) {
        return;
    }

    if (is_preview() || is_customize_preview()) {
        return;
    }

    if (is_feed() || is_robots() || is_trackback() || is_404()) {
        return;
    }

    if (!is_singular()) {
        return;
    }

    $post_id = get_queried_object_id();

    if (!$post_id) {
        return;
    }

    $post = get_post($post_id);

    if (!$post) {
        return;
    }

    if (!in_array($post->post_type, simplekitredirect_post_types(), true)) {
        return;
    }

    // Never bypass the password form.
    if (post_password_required($post)) {
        return;
    }

    if (simplekitredirect_is_editor_bypass($post_id)) {
        return;
    }

    if (!apply_filters('simplekitredirect_should_redirect', true, $post_id, $post)) {
        return;
    }

    $redirect = simplekitredirect_get_redirect($post_id);

    if (!$redirect) {
        return;
    }

    if (simplekitredirect_is_self_target($redirect['url'], $post_id)) {
        return;
    }

    $target = simplekitredirect_preserve_request_query($redirect['url']);
    $target = apply_filters('simplekitredirect_target_url', $target, $post_id, $redirect);
    $target = simplekitredirect_normalize_url($target);

    if (is_wp_error($target)) {
        return;
    }

    $status = (301 === $redirect['status']) ? 301 : 302;

    // External destinations are a documented feature, so wp_safe_redirect()
    // cannot be used. The target is validated above; wp_redirect() emits the
    // Location header requested by the spec.
    // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- See comment above.
    wp_redirect($target, $status);
    exit;
}

/**
 * Whether an editor explicitly asked to see the original content.
 *
 * The switch is ignored for visitors who cannot edit the post, so it does not
 * create a public bypass.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function simplekitredirect_is_editor_bypass($post_id) {
    if (!current_user_can('edit_post', $post_id)) {
        return false;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only bypass switch.
    $bypass = isset($_GET['simplekitredirect']) ? sanitize_key(wp_unslash($_GET['simplekitredirect'])) : '';

    return in_array($bypass, array('0', 'no', 'off', 'bypass'), true);
}

/**
 * Append the original query string when the destination has none.
 *
 * @param string $url Validated target URL.
 * @return string
 */
function simplekitredirect_preserve_request_query($url) {
    if (!apply_filters('simplekitredirect_preserve_query', true)) {
        return $url;
    }

    if (empty($_SERVER['REQUEST_URI'])) {
        return $url;
    }

    $request_uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
    $query       = wp_parse_url($request_uri, PHP_URL_QUERY);

    if (!is_string($query) || '' === $query) {
        return $url;
    }

    if ('' !== (string) wp_parse_url($url, PHP_URL_QUERY)) {
        return $url;
    }

    $args = array();
    wp_parse_str($query, $args);

    if (empty($args)) {
        return $url;
    }

    return add_query_arg($args, $url);
}
