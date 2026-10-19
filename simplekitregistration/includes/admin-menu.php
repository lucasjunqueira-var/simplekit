<?php
defined('ABSPATH') or exit;

add_action('admin_menu', 'simplekitregistration_admin_menu');

function simplekitregistration_admin_menu() {
    add_menu_page(
        'SK Registration',
        'SK Registration',
        'manage_options',
        'simplekitregistration',
        'simplekitregistration_page_forms_list',
        'dashicons-clipboard',
        27
    );

    add_submenu_page(
        'simplekitregistration',
        'Forms',
        'Forms',
        'manage_options',
        'simplekitregistration',
        'simplekitregistration_page_forms_list'
    );

    add_submenu_page(
        'simplekitregistration',
        'Create form',
        'Create form',
        'manage_options',
        'simplekitregistration-create',
        'simplekitregistration_page_form_editor'
    );

    add_submenu_page(
        'simplekitregistration',
        'Evaluators',
        'Evaluators',
        'manage_options',
        'simplekitregistration-evaluators',
        'simplekitregistration_page_evaluators'
    );

    add_submenu_page(
        'simplekitregistration',
        'Settings',
        'Settings',
        'manage_options',
        'simplekitregistration-settings',
        'simplekitregistration_page_settings'
    );

    add_submenu_page(
        'simplekitregistration',
        'Help',
        'Help',
        'manage_options',
        'simplekitregistration-help',
        'simplekitregistration_page_help'
    );

    add_submenu_page(
        'simplekitregistration',
        'Backup',
        'Backup',
        'manage_options',
        'simplekitregistration-backup',
        'simplekitregistration_page_backup'
    );

    add_submenu_page(
        'simplekitregistration',
        'Donate',
        'Donate',
        'manage_options',
        'simplekitregistration-donate',
        'simplekitregistration_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Admin assets (form builder)
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitregistration_admin_assets');

function simplekitregistration_admin_assets($hook) {
    if (strpos($hook, 'simplekitregistration') === false) {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_style('simplekitregistration-admin', SIMPLEKITREGISTRATION_PLUGIN_URL . 'assets/admin.css', [], SIMPLEKITREGISTRATION_VERSION);
    wp_enqueue_script('simplekitregistration-admin', SIMPLEKITREGISTRATION_PLUGIN_URL . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], SIMPLEKITREGISTRATION_VERSION, true);

    wp_localize_script('simplekitregistration-admin', 'simplekitregistration_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('simplekitregistration_nonce'),
        'strings'  => [
            'confirmDelete'    => __('Remove this field?', 'simplekitregistration'),
            'fieldLabel'       => __('Label', 'simplekitregistration'),
            'fieldPlaceholder' => __('Placeholder', 'simplekitregistration'),
            'fieldRequired'    => __('Required', 'simplekitregistration'),
            'fieldOptions'     => __('Options', 'simplekitregistration'),
            'addOption'        => __('Add option', 'simplekitregistration'),
            'removeOption'     => __('Remove', 'simplekitregistration'),
            'noLabel'          => __('(no label)', 'simplekitregistration'),
            'configure'        => __('Configure', 'simplekitregistration'),
            'remove'           => __('Remove', 'simplekitregistration'),
            'text'             => __('Text', 'simplekitregistration'),
            'email'            => __('E-mail', 'simplekitregistration'),
            'password'         => __('Password', 'simplekitregistration'),
            'url'              => __('URL', 'simplekitregistration'),
            'number'           => __('Number', 'simplekitregistration'),
            'tel'              => __('Phone', 'simplekitregistration'),
            'textarea'         => __('Textarea', 'simplekitregistration'),
            'checkboxes'       => __('Checkboxes', 'simplekitregistration'),
            'radio'            => __('Radio buttons', 'simplekitregistration'),
            'select'           => __('Dropdown', 'simplekitregistration'),
            'file'             => __('File', 'simplekitregistration'),
            'fileDescription'  => __('Description / instructions', 'simplekitregistration'),
            'fileMaxSize'      => __('Maximum size (MB)', 'simplekitregistration'),
            'fileAllowedExt'   => __('Allowed extensions', 'simplekitregistration'),
            'fileAllowedHint'  => __('Comma separated list. Leave empty to accept any file type.', 'simplekitregistration'),
        ],
    ]);
}
