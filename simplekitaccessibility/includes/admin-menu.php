<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitaccessibility_admin_menu');

function simplekitaccessibility_admin_menu() {
    add_menu_page(
        __('Simple Kit Accessibility', 'simplekitaccessibility'),
        __('SK Accessibility', 'simplekitaccessibility'),
        'manage_options',
        'simplekitaccessibility',
        'simplekitaccessibility_page_setup',
        'dashicons-universal-access',
        29
    );

    // First submenu uses the same slug as the parent (Setup).
    add_submenu_page(
        'simplekitaccessibility',
        __('Setup', 'simplekitaccessibility'),
        __('Setup', 'simplekitaccessibility'),
        'manage_options',
        'simplekitaccessibility',
        'simplekitaccessibility_page_setup'
    );

    add_submenu_page(
        'simplekitaccessibility',
        __('Help', 'simplekitaccessibility'),
        __('Help', 'simplekitaccessibility'),
        'manage_options',
        'simplekitaccessibility-help',
        'simplekitaccessibility_page_help'
    );

    add_submenu_page(
        'simplekitaccessibility',
        __('Donate', 'simplekitaccessibility'),
        __('Donate', 'simplekitaccessibility'),
        'manage_options',
        'simplekitaccessibility-donate',
        'simplekitaccessibility_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Admin assets
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitaccessibility_admin_assets');

function simplekitaccessibility_admin_assets($hook) {
    if (strpos((string) $hook, 'simplekitaccessibility') === false) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style(
        'simplekitaccessibility-admin',
        SIMPLEKITACCESSIBILITY_PLUGIN_URL . 'assets/admin.css',
        ['wp-color-picker'],
        SIMPLEKITACCESSIBILITY_VERSION
    );
    wp_enqueue_script(
        'simplekitaccessibility-admin',
        SIMPLEKITACCESSIBILITY_PLUGIN_URL . 'assets/admin.js',
        ['jquery', 'wp-color-picker'],
        SIMPLEKITACCESSIBILITY_VERSION,
        true
    );

    wp_localize_script('simplekitaccessibility-admin', 'simplekitaccessibility_admin', [
        'mediaTitle'  => __('Select an image', 'simplekitaccessibility'),
        'mediaButton' => __('Use this image', 'simplekitaccessibility'),
        'removeImage' => __('Remove image', 'simplekitaccessibility'),
    ]);
}
