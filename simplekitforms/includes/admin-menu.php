<?php
defined('ABSPATH') or exit;

add_action('admin_menu', 'simplekitforms_admin_menu');

function simplekitforms_admin_menu() {
    add_menu_page(
        'SK Forms',
        'SK Forms',
        'manage_options',
        'simplekitforms',
        'simplekitforms_page_forms_list',
        'dashicons-feedback',
        26
    );

    add_submenu_page(
        'simplekitforms',
        'Forms',
        'Forms',
        'manage_options',
        'simplekitforms',
        'simplekitforms_page_forms_list'
    );

    add_submenu_page(
        'simplekitforms',
        'Create form',
        'Create form',
        'manage_options',
        'simplekitforms-create',
        'simplekitforms_page_form_editor'
    );

    add_submenu_page(
        'simplekitforms',
        'Settings',
        'Settings',
        'manage_options',
        'simplekitforms-settings',
        'simplekitforms_page_settings'
    );

    add_submenu_page(
        'simplekitforms',
        'Help',
        'Help',
        'manage_options',
        'simplekitforms-help',
        'simplekitforms_page_help'
    );

    // Backup page (second-to-last)
    add_submenu_page(
        'simplekitforms',
        'Backup',
        'Backup',
        'manage_options',
        'simplekitforms-backup',
        'simplekitforms_page_backup'
    );

    // Donate page (last)
    add_submenu_page(
        'simplekitforms',
        'Donate',
        'Donate',
        'manage_options',
        'simplekitforms-donate',
        'simplekitforms_page_donate'
    );
}

// ---------------------------------------------------------------------------
// Assets (CSS/JS) for admin pages
// ---------------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'simplekitforms_admin_assets');

function simplekitforms_admin_assets($hook) {
    if (strpos($hook, 'simplekitforms') === false) {
        return;
    }

    // jQuery UI Sortable (for form builder)
    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_style('simplekitforms-admin', SIMPLEKITFORMS_PLUGIN_URL . 'assets/admin.css', [], SIMPLEKITFORMS_VERSION);
    wp_enqueue_script('simplekitforms-admin', SIMPLEKITFORMS_PLUGIN_URL . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], SIMPLEKITFORMS_VERSION, true);

    // List of forms for the builder
    $forms = simplekitforms_get_all_forms();
    $forms_data = [];
    foreach ($forms as $f) {
        $forms_data[] = [
            'id'    => (int) $f->id,
            'title' => esc_html($f->title),
        ];
    }

    wp_localize_script('simplekitforms-admin', 'simplekitforms_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('simplekitforms_nonce'),
        'forms'    => $forms_data,
        'strings'  => [
            'confirmDelete'    => 'Are you sure you want to delete this form?',
            'fieldLabel'       => 'Label',
            'fieldPlaceholder' => 'Placeholder',
            'fieldRequired'    => 'Required',
            'fieldOptions'     => 'Options',
            'addOption'        => 'Add option',
            'text'             => 'Text',
            'email'            => 'E-mail',
            'password'         => 'Password',
            'url'              => 'URL',
            'number'           => 'Number',
            'tel'              => 'Phone',
            'textarea'         => 'Textarea',
            'checkboxes'       => 'Checkboxes',
            'radio'            => 'Radio buttons',
            'select'           => 'Dropdown',
        ],
    ]);
}
