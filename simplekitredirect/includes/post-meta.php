<?php
/**
 * Redirect meta box for posts and pages.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

add_action('add_meta_boxes', 'simplekitredirect_add_meta_box');

/**
 * Register the meta box on every supported post type.
 *
 * @return void
 */
function simplekitredirect_add_meta_box() {
    foreach (simplekitredirect_post_types() as $post_type) {
        add_meta_box(
            'simplekitredirect_meta_box',
            __('Simple Kit Redirect', 'simplekitredirect'),
            'simplekitredirect_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Render the meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function simplekitredirect_render_meta_box($post) {
    wp_nonce_field('simplekitredirect_meta_box', 'simplekitredirect_meta_nonce');

    $url    = get_post_meta($post->ID, '_simplekitredirect_url', true);
    $status = get_post_meta($post->ID, '_simplekitredirect_status', true);

    if ('301' !== $status) {
        $status = '302';
    }

    $permalink = get_permalink($post->ID);
    ?>
    <p>
        <label for="simplekitredirect_url_meta">
            <strong><?php esc_html_e('Redirect to', 'simplekitredirect'); ?></strong>
        </label>
        <input
            type="text"
            id="simplekitredirect_url_meta"
            name="simplekitredirect_url_meta"
            value="<?php echo esc_attr(is_string($url) ? $url : ''); ?>"
            class="widefat code"
            placeholder="https://example.com/new-page"
            autocomplete="off"
        />
    </p>
    <p>
        <label for="simplekitredirect_status_meta">
            <?php esc_html_e('Redirect type', 'simplekitredirect'); ?>
        </label>
        <select id="simplekitredirect_status_meta" name="simplekitredirect_status_meta" class="widefat">
            <option value="302" <?php selected($status, '302'); ?>>
                <?php esc_html_e('302 - Temporary (recommended)', 'simplekitredirect'); ?>
            </option>
            <option value="301" <?php selected($status, '301'); ?>>
                <?php esc_html_e('301 - Permanent', 'simplekitredirect'); ?>
            </option>
        </select>
    </p>
    <p class="description">
        <?php esc_html_e('Leave empty to display this content normally. Accepted formats: http(s) addresses or a path starting with "/".', 'simplekitredirect'); ?>
    </p>
    <p class="description simplekitredirect-warning">
        <?php esc_html_e('Permanent redirects are cached by browsers. Use 301 only when the new address will not change.', 'simplekitredirect'); ?>
    </p>
    <p class="description">
        <?php esc_html_e('Editors can open this content by adding ?simplekitredirect=0 to the address.', 'simplekitredirect'); ?>
    </p>
    <?php if ($permalink) : ?>
        <p class="description simplekitredirect-current">
            <?php esc_html_e('Current address:', 'simplekitredirect'); ?><br />
            <code><?php echo esc_html($permalink); ?></code>
        </p>
    <?php endif; ?>
    <?php
}

add_action('save_post', 'simplekitredirect_save_meta_box');

/**
 * Save the redirect settings.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function simplekitredirect_save_meta_box($post_id) {
    if (!isset($_POST['simplekitredirect_meta_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['simplekitredirect_meta_nonce']));

    if (!wp_verify_nonce($nonce, 'simplekitredirect_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = '';

    if (isset($_POST['simplekitredirect_url_meta'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated and sanitized by simplekitredirect_normalize_url().
        $submitted = wp_unslash($_POST['simplekitredirect_url_meta']);

        if (is_string($submitted)) {
            $raw = trim($submitted);
        }
    }

    if ('' === $raw) {
        delete_post_meta($post_id, '_simplekitredirect_url');
        delete_post_meta($post_id, '_simplekitredirect_status');
        return;
    }

    $url = simplekitredirect_normalize_url($raw);

    if (is_wp_error($url)) {
        // Keep the previous valid value and explain the problem on the next
        // admin page load.
        set_transient(
            'simplekitredirect_meta_notice_' . get_current_user_id(),
            $url->get_error_message(),
            60
        );
        return;
    }

    $status = '302';

    if (isset($_POST['simplekitredirect_status_meta'])) {
        $submitted_status = sanitize_text_field(wp_unslash($_POST['simplekitredirect_status_meta']));

        if ('301' === $submitted_status) {
            $status = '301';
        }
    }

    update_post_meta($post_id, '_simplekitredirect_url', $url);
    update_post_meta($post_id, '_simplekitredirect_status', $status);
}

add_action('admin_notices', 'simplekitredirect_meta_notice');

/**
 * Show the validation error from the last save attempt.
 *
 * @return void
 */
function simplekitredirect_meta_notice() {
    $user_id = get_current_user_id();

    if (!$user_id) {
        return;
    }

    $key     = 'simplekitredirect_meta_notice_' . $user_id;
    $message = get_transient($key);

    if (!is_string($message) || '' === $message) {
        return;
    }

    delete_transient($key);

    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
}
