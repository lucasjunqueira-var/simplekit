<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Meta box for posts and pages
// ---------------------------------------------------------------------------
add_action('add_meta_boxes', 'simplekitsharing_add_meta_box');

function simplekitsharing_add_meta_box() {
    $post_types = ['post', 'page'];
    foreach ($post_types as $pt) {
        add_meta_box(
            'simplekitsharing_meta_box',
            __('Simple Kit Sharing', 'simplekitsharing'),
            'simplekitsharing_render_meta_box',
            $pt,
            'side',
            'default'
        );
    }
}

function simplekitsharing_render_meta_box($post) {
    wp_nonce_field('simplekitsharing_meta_box', 'simplekitsharing_meta_nonce');

    $icon        = get_post_meta($post->ID, '_simplekitsharing_icon', true);
    $share_text  = get_post_meta($post->ID, '_simplekitsharing_share_text', true);
    $share_image = get_post_meta($post->ID, '_simplekitsharing_share_image', true);
    $tags        = get_post_meta($post->ID, '_simplekitsharing_tags', true);
    ?>
    <p>
        <label for="simplekitsharing_icon_meta"><?php esc_html_e('Icon (Favicon)', 'simplekitsharing'); ?></label><br>
        <input type="text" id="simplekitsharing_icon_meta" name="simplekitsharing_icon_meta" value="<?php echo esc_attr($icon); ?>" class="widefat simplesharing-image-input" />
        <button type="button" class="button simplesharing-upload-btn" data-target="simplekitsharing_icon_meta" style="margin-top:4px; width:100%;"><?php esc_html_e('Upload', 'simplekitsharing'); ?></button>
        <?php if (!empty($icon)) : ?>
            <br><img src="<?php echo esc_url($icon); ?>" style="max-width:48px; max-height:48px; margin-top:6px; border:1px solid #ddd; border-radius:4px;" />
        <?php endif; ?>
    </p>
    <p>
        <label for="simplekitsharing_share_text_meta"><?php esc_html_e('Share Text', 'simplekitsharing'); ?></label><br>
        <textarea id="simplekitsharing_share_text_meta" name="simplekitsharing_share_text_meta" rows="3" class="widefat"><?php echo esc_textarea($share_text); ?></textarea>
    </p>
    <p>
        <label for="simplekitsharing_share_image_meta"><?php esc_html_e('Share Image', 'simplekitsharing'); ?></label><br>
        <input type="text" id="simplekitsharing_share_image_meta" name="simplekitsharing_share_image_meta" value="<?php echo esc_attr($share_image); ?>" class="widefat simplesharing-image-input" />
        <button type="button" class="button simplesharing-upload-btn" data-target="simplekitsharing_share_image_meta" style="margin-top:4px; width:100%;"><?php esc_html_e('Upload', 'simplekitsharing'); ?></button>
        <?php if (!empty($share_image)) : ?>
            <br><img src="<?php echo esc_url($share_image); ?>" style="max-width:180px; margin-top:6px; border:1px solid #ddd; border-radius:4px;" />
        <?php endif; ?>
    </p>
    <p>
        <label for="simplekitsharing_tags_meta"><?php esc_html_e('Tags (for articles)', 'simplekitsharing'); ?></label><br>
        <input type="text" id="simplekitsharing_tags_meta" name="simplekitsharing_tags_meta" value="<?php echo esc_attr($tags); ?>" class="widefat" />
    </p>
    <p class="description"><?php esc_html_e('Leave fields empty to use the general sharing settings.', 'simplekitsharing'); ?></p>
    <?php
}

// ---------------------------------------------------------------------------
// Save meta box data
// ---------------------------------------------------------------------------
add_action('save_post', 'simplekitsharing_save_meta_box');

function simplekitsharing_save_meta_box($post_id) {
    if (!isset($_POST['simplekitsharing_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simplekitsharing_meta_nonce'])), 'simplekitsharing_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'simplekitsharing_icon_meta'        => '_simplekitsharing_icon',
        'simplekitsharing_share_text_meta'  => '_simplekitsharing_share_text',
        'simplekitsharing_share_image_meta' => '_simplekitsharing_share_image',
        'simplekitsharing_tags_meta'        => '_simplekitsharing_tags',
    ];

    foreach ($fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $value = sanitize_text_field(wp_unslash($_POST[$post_key]));
            if (!empty($value)) {
                update_post_meta($post_id, $meta_key, $value);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}
