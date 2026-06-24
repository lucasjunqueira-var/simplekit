<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitsharing_admin_menu');

function simplekitsharing_admin_menu() {
    add_menu_page(
        __('Simple Kit Sharing', 'simplekitsharing'),
        __('SK Sharing', 'simplekitsharing'),
        'manage_options',
        'simplekitsharing',
        'simplekitsharing_page_settings',
        'dashicons-share',
        27
    );

    // First submenu (same slug as parent to override the auto-generated duplicate)
    add_submenu_page(
        'simplekitsharing',
        __('Simple Kit Sharing', 'simplekitsharing'),
        __('Global', 'simplekitsharing'),
        'manage_options',
        'simplekitsharing',
        'simplekitsharing_page_settings'
    );

    // Help submenu (second-to-last)
    add_submenu_page(
        'simplekitsharing',
        __('Help', 'simplekitsharing'),
        __('Help', 'simplekitsharing'),
        'manage_options',
        'simplekitsharing-help',
        'simplekitsharing_page_help'
    );

    // Donate submenu (last)
    add_submenu_page(
        'simplekitsharing',
        __('Donate', 'simplekitsharing'),
        __('Donate', 'simplekitsharing'),
        'manage_options',
        'simplekitsharing-donate',
        'simplekitsharing_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Admin assets (CSS / JS)
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitsharing_admin_assets');

function simplekitsharing_admin_assets($hook) {
    // Load on plugin settings page OR on post/page edit screens (meta box)
    $enabled_hooks = ['post.php', 'post-new.php'];
    $is_plugin_page = strpos($hook, 'simplekitsharing') !== false;
    $is_post_edit   = in_array($hook, $enabled_hooks, true);

    if (!$is_plugin_page && !$is_post_edit) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style('simplekitsharing-admin', SIMPLEKITSHARING_PLUGIN_URL . 'assets/admin.css', [], SIMPLEKITSHARING_VERSION);
    wp_enqueue_script('simplekitsharing-admin', SIMPLEKITSHARING_PLUGIN_URL . 'assets/admin.js', ['jquery'], SIMPLEKITSHARING_VERSION, true);

    wp_localize_script('simplekitsharing-admin', 'simplekitsharing_admin', [
        'media_title'  => __('Select or Upload Image', 'simplekitsharing'),
        'media_button' => __('Use this image', 'simplekitsharing'),
    ]);
}
