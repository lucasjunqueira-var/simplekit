<?php
defined('ABSPATH') or exit;

add_action('admin_menu', 'simplekitmailing_admin_menu');

function simplekitmailing_admin_menu() {
    add_menu_page(
        __('Simple Kit Mailing', 'simplekitmailing'),
        __('SK Mailing', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing',
        'simplekitmailing_page_subscribers',
        'dashicons-email-alt',
        25
    );

    add_submenu_page(
        'simplekitmailing',
        __('Subscribers', 'simplekitmailing'),
        __('Subscribers', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing',
        'simplekitmailing_page_subscribers'
    );

    add_submenu_page(
        'simplekitmailing',
        __('Mailing Lists', 'simplekitmailing'),
        __('Mailing Lists', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-lists',
        'simplekitmailing_page_lists'
    );

    add_submenu_page(
        'simplekitmailing',
        __('Create Message', 'simplekitmailing'),
        __('Create Message', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-message',
        'simplekitmailing_page_message'
    );

    add_submenu_page(
        'simplekitmailing',
        __('Messages', 'simplekitmailing'),
        __('Messages', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-sends',
        'simplekitmailing_page_sends'
    );

    add_submenu_page(
        'simplekitmailing',
        __('Settings', 'simplekitmailing'),
        __('Settings', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-settings',
        'simplekitmailing_page_settings'
    );

    add_submenu_page(
        'simplekitmailing',
        __('Help', 'simplekitmailing'),
        __('Help', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-help',
        'simplekitmailing_page_help'
    );

    // Backup page (second-to-last)
    add_submenu_page(
        'simplekitmailing',
        __('Backup', 'simplekitmailing'),
        __('Backup', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-backup',
        'simplekitmailing_page_backup'
    );

    // Donate page (last)
    add_submenu_page(
        'simplekitmailing',
        __('Donate', 'simplekitmailing'),
        __('Donate', 'simplekitmailing'),
        'manage_options',
        'simplekitmailing-donate',
        'simplekitmailing_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Assets (CSS/JS) for admin pages
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitmailing_admin_assets');

function simplekitmailing_admin_assets($hook) {
    if (strpos($hook, 'simplekitmailing') === false) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('simplekitmailing-admin', SIMPLEKITMAILING_PLUGIN_URL . 'assets/admin.css', [], SIMPLEKITMAILING_VERSION);
    wp_enqueue_script('simplekitmailing-admin', SIMPLEKITMAILING_PLUGIN_URL . 'assets/admin.js', ['jquery', 'wp-color-picker'], SIMPLEKITMAILING_VERSION, true);
    wp_localize_script('simplekitmailing-admin', 'simplekitmailing_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('simplekitmailing_nonce'),
    ]);
}
