<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Public download endpoint.
//
// The endpoint is public on purpose: downloads must work for anonymous
// visitors and the URLs must remain valid inside cached pages. Statistics are
// recorded as soon as the delivery starts.
// ---------------------------------------------------------------------------

add_action('admin_post_nopriv_simplekitdownloads_file', 'simplekitdownloads_handle_file_request');
add_action('admin_post_simplekitdownloads_file', 'simplekitdownloads_handle_file_request');

function simplekitdownloads_handle_file_request() {
    $download_id = isset($_GET['d']) ? absint($_GET['d']) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public download link.
    $requested = isset($_GET['v']) ? sanitize_text_field(wp_unslash($_GET['v'])) : 'current';

    $download = $download_id > 0 ? simplekitdownloads_get_download($download_id) : null;
    if (!$download) {
        simplekitdownloads_file_not_found();
    }

    if ($requested === '' || $requested === 'current') {
        $version = simplekitdownloads_get_latest_version($download_id);
    } else {
        $version = simplekitdownloads_get_version_by_label($download_id, $requested);
    }

    if (!$version) {
        simplekitdownloads_file_not_found();
    }

    if ($version->source === 'url') {
        // Re-validate at click time so an old record cannot become an open redirect.
        $url = simplekitdownloads_validate_internal_url($version->file_url);
        if (is_wp_error($url)) {
            simplekitdownloads_file_not_found();
        }

        // Record the statistic when the delivery starts (Q4).
        simplekitdownloads_log_download($download, $version);

        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);
        wp_redirect($url, 302);
        exit;
    }

    $path = simplekitdownloads_version_file_path($version);

    // Self-heal records stored in the default uploads folder by older versions.
    if ($path === '' || !is_file($path)) {
        if (simplekitdownloads_repair_version_file($version)) {
            $version = simplekitdownloads_get_version((int) $version->id);
            $path    = simplekitdownloads_version_file_path($version);
        }
    }

    if ($path === '' || !is_file($path)) {
        simplekitdownloads_file_not_found();
    }

    // Record the statistic when the delivery starts (Q4).
    simplekitdownloads_log_download($download, $version);

    simplekitdownloads_stream_file($path, $version->file_name);
    exit;
}

function simplekitdownloads_file_not_found() {
    status_header(404);
    nocache_headers();
    wp_die(
        esc_html__('The requested download was not found.', 'simplekitdownloads'),
        '',
        ['response' => 404]
    );
}

/**
 * Store one row in the statistics log.
 *
 * The download and version names are snapshotted so the history survives
 * deletions. Download/version IDs are also kept (without foreign keys) to keep
 * the record attributable.
 */
function simplekitdownloads_log_download($download, $version) {
    global $wpdb;

    $ip = '';
    if (simplekitdownloads_record_ip() && isset($_SERVER['REMOTE_ADDR'])) {
        $candidate = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(simplekitdownloads_table('log'), [
        'download_id'   => (int) $download->id,
        'version_id'    => (int) $version->id,
        'download_name' => (string) $download->name,
        'version_label' => (string) $version->version,
        'version_sort'  => (string) $version->version_sort,
        'ip'            => $ip,
        'created_at'    => current_time('mysql'),
    ]);
}
