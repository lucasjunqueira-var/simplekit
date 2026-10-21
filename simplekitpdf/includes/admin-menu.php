<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Admin menu: Help (main page) and Donate
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitpdf_add_admin_menu');

function simplekitpdf_add_admin_menu() {
    add_menu_page(
        __('Simple Kit PDF', 'simplekitpdf'),
        __('SK PDF', 'simplekitpdf'),
        'manage_options',
        'simplekitpdf',
        'simplekitpdf_page_help',
        'dashicons-media-document',
        30
    );

    add_submenu_page(
        'simplekitpdf',
        __('Help', 'simplekitpdf'),
        __('Help', 'simplekitpdf'),
        'manage_options',
        'simplekitpdf',
        'simplekitpdf_page_help'
    );

    add_submenu_page(
        'simplekitpdf',
        __('Donate', 'simplekitpdf'),
        __('Donate', 'simplekitpdf'),
        'manage_options',
        'simplekitpdf-donate',
        'simplekitpdf_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Admin assets (plugin pages only)
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitpdf_enqueue_admin_assets');

function simplekitpdf_enqueue_admin_assets($hook) {
    if (strpos((string) $hook, 'simplekitpdf') === false) {
        return;
    }

    wp_enqueue_style(
        'simplekitpdf-admin',
        SIMPLEKITPDF_PLUGIN_URL . 'assets/admin.css',
        array(),
        SIMPLEKITPDF_VERSION
    );
}
