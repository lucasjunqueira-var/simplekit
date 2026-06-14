<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Unsubscribe endpoint via link in email
// ---------------------------------------------------------------------------
add_action('init', 'simplekitmailing_unsubscribe_handler');

function simplekitmailing_unsubscribe_handler() {
    if (!isset($_GET['sm_unsubscribe']) || $_GET['sm_unsubscribe'] !== '1') {
        return;
    }

    $email   = sanitize_email(wp_unslash($_GET['email'] ?? ''));
    $nonce   = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
    $list_id = isset($_GET['list_id']) ? absint($_GET['list_id']) : 0;

    if (!is_email($email) || !wp_verify_nonce($nonce, 'sm_unsubscribe_' . $email)) {
        wp_die(esc_html__('Invalid or expired unsubscribe link.', 'simplekitmailing'), esc_html__('Error', 'simplekitmailing'), ['response' => 403]);
    }

    global $wpdb;
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';
    $table_removed     = $wpdb->prefix . 'sm_removed';

    if ($list_id) {
        // Remove only from the specific list
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete($table_subscribers, ['email' => $email, 'list_id' => $list_id]);
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->replace($table_removed, ['list_id' => $list_id, 'email' => $email]);
    } else {
        // Remove from all lists (fallback)
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $subs = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT list_id FROM %i WHERE email = %s", $table_subscribers, $email));
        foreach ($subs as $s) {
            $lid = $s->list_id ?: 0;
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete($table_subscribers, ['email' => $email, 'list_id' => $lid]);
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->replace($table_removed, ['list_id' => $lid, 'email' => $email]);
        }
    }

    // Display confirmation message
    $message = __('Your email has been removed from our mailing list. You will no longer receive our messages.', 'simplekitmailing');

    // Use the page template if available, otherwise show a simple message
    $page_id = simplekitmailing_get_redirect_page_id($list_id);
    if ($page_id) {
        $page_url = get_permalink($page_id);
        wp_safe_redirect(add_query_arg('sm_unsubscribed', '1', $page_url));
        exit;
    }

    wp_die(
        '<h2>' . esc_html__('Unsubscribed', 'simplekitmailing') . '</h2>' .
        '<p>' . esc_html($message) . '</p>' .
        '<p><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Back to home', 'simplekitmailing') . '</a></p>',
        esc_html__('Unsubscribe', 'simplekitmailing')
    );
}
