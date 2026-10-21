<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------
add_action('admin_menu', 'simplekitdownloads_admin_menu');

function simplekitdownloads_admin_menu() {
    add_menu_page(
        __('Simple Kit Downloads', 'simplekitdownloads'),
        __('SK Downloads', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads',
        'simplekitdownloads_page_files',
        'dashicons-download',
        27
    );

    // First submenu uses the same slug as the parent (Files).
    add_submenu_page(
        'simplekitdownloads',
        __('Files', 'simplekitdownloads'),
        __('Files', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads',
        'simplekitdownloads_page_files'
    );

    add_submenu_page(
        'simplekitdownloads',
        __('Statistics', 'simplekitdownloads'),
        __('Statistics', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads-stats',
        'simplekitdownloads_page_statistics'
    );

    add_submenu_page(
        'simplekitdownloads',
        __('Setup', 'simplekitdownloads'),
        __('Setup', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads-setup',
        'simplekitdownloads_page_setup'
    );

    add_submenu_page(
        'simplekitdownloads',
        __('Backup', 'simplekitdownloads'),
        __('Backup', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads-backup',
        'simplekitdownloads_page_backup'
    );

    add_submenu_page(
        'simplekitdownloads',
        __('Help', 'simplekitdownloads'),
        __('Help', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads-help',
        'simplekitdownloads_page_help'
    );

    add_submenu_page(
        'simplekitdownloads',
        __('Donate', 'simplekitdownloads'),
        __('Donate', 'simplekitdownloads'),
        'manage_options',
        'simplekitdownloads-donate',
        'simplekitdownloads_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Admin assets
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitdownloads_admin_assets');

function simplekitdownloads_admin_assets($hook) {
    if (strpos($hook, 'simplekitdownloads') === false) {
        return;
    }

    wp_enqueue_style(
        'simplekitdownloads-admin',
        SIMPLEKITDOWNLOADS_PLUGIN_URL . 'assets/admin.css',
        [],
        SIMPLEKITDOWNLOADS_VERSION
    );

    wp_enqueue_script(
        'simplekitdownloads-admin',
        SIMPLEKITDOWNLOADS_PLUGIN_URL . 'assets/admin.js',
        ['jquery'],
        SIMPLEKITDOWNLOADS_VERSION,
        true
    );
}
