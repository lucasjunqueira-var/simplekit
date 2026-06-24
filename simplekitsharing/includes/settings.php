<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Default values helper
// ---------------------------------------------------------------------------
function simplekitsharing_default_share_text() {
    return get_bloginfo('description');
}

// ---------------------------------------------------------------------------
// Get all general settings as an array
// ---------------------------------------------------------------------------
function simplekitsharing_get_general_settings() {
    return [
        'icon'        => get_option('simplekitsharing_icon', ''),
        'share_text'  => get_option('simplekitsharing_share_text', simplekitsharing_default_share_text()),
        'share_image' => get_option('simplekitsharing_share_image', ''),
        'tags'        => get_option('simplekitsharing_tags', ''),
    ];
}

// ---------------------------------------------------------------------------
// Register settings
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitsharing_register_settings');

function simplekitsharing_register_settings() {
    register_setting('simplekitsharing_settings', 'simplekitsharing_icon', 'sanitize_text_field');
    register_setting('simplekitsharing_settings', 'simplekitsharing_share_text', 'sanitize_textarea_field');
    register_setting('simplekitsharing_settings', 'simplekitsharing_share_image', 'sanitize_text_field');
    register_setting('simplekitsharing_settings', 'simplekitsharing_tags', 'sanitize_text_field');
}

// ---------------------------------------------------------------------------
// Admin page: General Sharing Settings
// ---------------------------------------------------------------------------
function simplekitsharing_page_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitsharing'));
    }

    $settings = simplekitsharing_get_general_settings();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Global', 'simplekitsharing'); ?></h1>
        <p><?php esc_html_e('These settings define the default social sharing meta tags for your site. If a page or post has its own sharing settings, those will override these defaults.', 'simplekitsharing'); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields('simplekitsharing_settings'); ?>
            <?php do_settings_sections('simplekitsharing_settings'); ?>

            <h2><?php esc_html_e('Sharing Defaults', 'simplekitsharing'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="simplekitsharing_icon"><?php esc_html_e('Icon (Favicon)', 'simplekitsharing'); ?></label></th>
                    <td>
                        <div class="simplesharing-image-wrapper">
                            <input type="text" id="simplekitsharing_icon" name="simplekitsharing_icon" value="<?php echo esc_attr($settings['icon']); ?>" class="regular-text simplesharing-image-input" />
                            <button type="button" class="button simplesharing-upload-btn" data-target="simplekitsharing_icon"><?php esc_html_e('Upload', 'simplekitsharing'); ?></button>
                            <?php if (!empty($settings['icon'])) : ?>
                                <br><img src="<?php echo esc_url($settings['icon']); ?>" style="max-width:64px; max-height:64px; margin-top:8px; border:1px solid #ddd; border-radius:4px;" />
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php esc_html_e('URL of the icon to be used as favicon / share icon.', 'simplekitsharing'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="simplekitsharing_share_text"><?php esc_html_e('Share Text', 'simplekitsharing'); ?></label></th>
                    <td>
                        <textarea id="simplekitsharing_share_text" name="simplekitsharing_share_text" rows="3" class="large-text"><?php echo esc_textarea($settings['share_text']); ?></textarea>
                        <p class="description"><?php esc_html_e('Default description used in Open Graph / Twitter Card meta tags (e.g., og:description).', 'simplekitsharing'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="simplekitsharing_share_image"><?php esc_html_e('Share Image', 'simplekitsharing'); ?></label></th>
                    <td>
                        <div class="simplesharing-image-wrapper">
                            <input type="text" id="simplekitsharing_share_image" name="simplekitsharing_share_image" value="<?php echo esc_attr($settings['share_image']); ?>" class="regular-text simplesharing-image-input" />
                            <button type="button" class="button simplesharing-upload-btn" data-target="simplekitsharing_share_image"><?php esc_html_e('Upload', 'simplekitsharing'); ?></button>
                            <?php if (!empty($settings['share_image'])) : ?>
                                <br><img src="<?php echo esc_url($settings['share_image']); ?>" style="max-width:200px; margin-top:8px; border:1px solid #ddd; border-radius:4px;" />
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php esc_html_e('Default image used in Open Graph / Twitter Card meta tags (e.g., og:image).', 'simplekitsharing'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="simplekitsharing_tags"><?php esc_html_e('Tags (for articles)', 'simplekitsharing'); ?></label></th>
                    <td>
                        <input type="text" id="simplekitsharing_tags" name="simplekitsharing_tags" value="<?php echo esc_attr($settings['tags']); ?>" class="regular-text" style="width:100%; max-width:500px;" />
                        <p class="description"><?php esc_html_e('Comma-separated tags used in the article:tag meta property (e.g., wordpress, plugin, sharing).', 'simplekitsharing'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Changes', 'simplekitsharing')); ?>
        </form>
    </div>
    <?php
}
