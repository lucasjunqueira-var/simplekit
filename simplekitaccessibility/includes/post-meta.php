<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Per post / per page override
//
// The meta box is added to every public post type that uses the editor. The
// value is stored as a single meta key so the frontend check is cheap.
// ---------------------------------------------------------------------------
add_action('add_meta_boxes', 'simplekitaccessibility_add_meta_box');

function simplekitaccessibility_add_meta_box() {
    $post_types = get_post_types([
        'public'  => true,
        'show_ui' => true,
    ], 'names');

    unset($post_types['attachment']);

    foreach ($post_types as $post_type) {
        add_meta_box(
            'simplekitaccessibility_meta_box',
            __('Simple Kit Accessibility', 'simplekitaccessibility'),
            'simplekitaccessibility_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

function simplekitaccessibility_render_meta_box($post) {
    wp_nonce_field('simplekitaccessibility_meta_box', 'simplekitaccessibility_meta_nonce');

    $disabled = get_post_meta($post->ID, '_simplekitaccessibility_disabled', true) === '1';
    ?>
    <p>
        <input type="hidden" name="simplekitaccessibility_disabled" value="0" />
        <label>
            <input type="checkbox" name="simplekitaccessibility_disabled" value="1" <?php checked($disabled); ?> />
            <?php esc_html_e('Hide the accessibility button on this content', 'simplekitaccessibility'); ?>
        </label>
    </p>
    <p class="description">
        <?php esc_html_e('The reading tools stay available for custom triggers on this page. Only the floating button is hidden.', 'simplekitaccessibility'); ?>
    </p>
    <?php
}

add_action('save_post', 'simplekitaccessibility_save_meta_box');

function simplekitaccessibility_save_meta_box($post_id) {
    if (!isset($_POST['simplekitaccessibility_meta_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['simplekitaccessibility_meta_nonce']));
    if (!wp_verify_nonce($nonce, 'simplekitaccessibility_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $value = isset($_POST['simplekitaccessibility_disabled'])
        ? sanitize_text_field(wp_unslash($_POST['simplekitaccessibility_disabled']))
        : '0';

    if ($value === '1') {
        update_post_meta($post_id, '_simplekitaccessibility_disabled', '1');
    } else {
        delete_post_meta($post_id, '_simplekitaccessibility_disabled');
    }
}
