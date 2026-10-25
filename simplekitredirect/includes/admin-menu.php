<?php
/**
 * Admin menu and assets.
 *
 * @package SimpleKitRedirect
 */

defined('ABSPATH') or exit;

add_action('admin_menu', 'simplekitredirect_admin_menu');

/**
 * Register the Help (main) and Donate pages.
 *
 * @return void
 */
function simplekitredirect_admin_menu() {
    add_menu_page(
        __('Simple Kit Redirect', 'simplekitredirect'),
        __('SK Redirect', 'simplekitredirect'),
        'manage_options',
        'simplekitredirect',
        'simplekitredirect_page_help',
        'dashicons-external',
        31
    );

    // Reuse the parent slug so WordPress does not add a duplicate entry.
    add_submenu_page(
        'simplekitredirect',
        __('Help', 'simplekitredirect'),
        __('Help', 'simplekitredirect'),
        'manage_options',
        'simplekitredirect',
        'simplekitredirect_page_help'
    );

    add_submenu_page(
        'simplekitredirect',
        __('Donate', 'simplekitredirect'),
        __('Donate', 'simplekitredirect'),
        'manage_options',
        'simplekitredirect-donate',
        'simplekitredirect_page_donate'
    );
}

add_action('admin_enqueue_scripts', 'simplekitredirect_admin_assets');

/**
 * Load the plugin stylesheet on its pages and on the post editor.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function simplekitredirect_admin_assets($hook) {
    $is_plugin_page = (false !== strpos($hook, 'simplekitredirect'));
    $is_post_edit   = in_array($hook, array('post.php', 'post-new.php'), true);

    if (!$is_plugin_page && !$is_post_edit) {
        return;
    }

    wp_enqueue_style(
        'simplekitredirect-admin',
        SIMPLEKITREDIRECT_PLUGIN_URL . 'assets/admin.css',
        array(),
        SIMPLEKITREDIRECT_VERSION
    );
}
