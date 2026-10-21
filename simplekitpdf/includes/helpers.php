<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Site host helpers
// ---------------------------------------------------------------------------

/**
 * Normalizes a host so comparisons ignore case and a leading "www.".
 *
 * @param string $host Host name.
 * @return string
 */
function simplekitpdf_normalize_host($host) {
    $host = strtolower(trim((string) $host));
    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }
    return $host;
}

/**
 * Returns the normalized host of this site.
 *
 * @return string
 */
function simplekitpdf_get_site_host() {
    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    return simplekitpdf_normalize_host(is_string($host) ? $host : '');
}

/**
 * Returns the scheme://host[:port] origin of this site.
 *
 * @return string
 */
function simplekitpdf_get_site_origin() {
    $parts = wp_parse_url(home_url('/'));
    if (!is_array($parts) || empty($parts['host'])) {
        return '';
    }
    $scheme = !empty($parts['scheme']) ? $parts['scheme'] : (is_ssl() ? 'https' : 'http');
    $origin = $scheme . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $origin .= ':' . (int) $parts['port'];
    }
    return $origin;
}

/**
 * Resolves a possibly relative value into an absolute URL on this site.
 *
 * @param string $url Raw URL or path.
 * @return string
 */
function simplekitpdf_resolve_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    // Protocol relative value, e.g. //example.com/file.pdf
    if (strpos($url, '//') === 0) {
        $url = (is_ssl() ? 'https:' : 'http:') . $url;
    }

    if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
        // Absolute URL with a scheme but no "//" (for example javascript: or data:).
        // Keep it untouched so the validation below can reject it.
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) {
            return $url;
        }

        $origin = simplekitpdf_get_site_origin();
        if ($origin === '') {
            return '';
        }
        // A root relative path always points to the domain root, even when
        // WordPress is installed in a subdirectory.
        $url = $origin . '/' . ltrim($url, '/');
    }

    return $url;
}

/**
 * Checks that a URL points to a file served from this same site.
 *
 * @param string $url Raw URL or path.
 * @return bool
 */
function simplekitpdf_is_allowed_pdf_url($url) {
    $url = simplekitpdf_resolve_url($url);
    if ($url === '') {
        return false;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host']) || empty($parts['path']) || $parts['path'] === '/') {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    if ($scheme !== 'http' && $scheme !== 'https') {
        return false;
    }

    $site_host = simplekitpdf_get_site_host();
    if ($site_host === '') {
        return false;
    }

    return simplekitpdf_normalize_host($parts['host']) === $site_host;
}

/**
 * Sanitizes and resolves a PDF URL.
 *
 * @param string $url Raw URL or path.
 * @return string
 */
function simplekitpdf_sanitize_pdf_url($url) {
    $url = simplekitpdf_resolve_url($url);
    if ($url === '') {
        return '';
    }
    return esc_url_raw($url);
}

// ---------------------------------------------------------------------------
// Attribute helpers
// ---------------------------------------------------------------------------

/**
 * Converts block/shortcode values into a boolean.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function simplekitpdf_to_bool($value) {
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return $value !== 0;
    }
    $value = strtolower(trim((string) $value));
    return in_array($value, array('1', 'true', 'yes', 'on'), true);
}

/**
 * Normalizes block attributes into a predictable internal shape.
 *
 * @param array $attributes Raw block attributes.
 * @return array
 */
function simplekitpdf_normalize_attributes($attributes) {
    $attributes = is_array($attributes) ? $attributes : array();

    $height = isset($attributes['height']) ? (int) $attributes['height'] : SIMPLEKITPDF_DEFAULT_HEIGHT;
    if ($height <= 0) {
        $height = SIMPLEKITPDF_DEFAULT_HEIGHT;
    }
    $height = max(SIMPLEKITPDF_MIN_HEIGHT, min(SIMPLEKITPDF_MAX_HEIGHT, $height));

    $source = isset($attributes['source']) ? (string) $attributes['source'] : 'media';
    if (!in_array($source, array('media', 'url'), true)) {
        $source = 'media';
    }

    $height_mode = isset($attributes['heightMode']) ? (string) $attributes['heightMode'] : 'fixed';
    if (!in_array($height_mode, array('fixed', 'first-page', 'full-document'), true)) {
        $height_mode = 'fixed';
    }

    $show_toolbar  = simplekitpdf_to_bool(isset($attributes['showToolbar']) ? $attributes['showToolbar'] : false);
    $allow_download = simplekitpdf_to_bool(isset($attributes['allowDownload']) ? $attributes['allowDownload'] : false);

    // Download only exists inside the toolbar, so it cannot be enabled without it.
    if (!$show_toolbar) {
        $allow_download = false;
    }

    return array(
        'source'         => $source,
        'attachment_id'  => isset($attributes['attachmentId']) ? (int) $attributes['attachmentId'] : 0,
        'url'            => isset($attributes['pdfUrl']) ? simplekitpdf_sanitize_pdf_url($attributes['pdfUrl']) : '',
        'file_name'      => isset($attributes['fileName']) ? sanitize_text_field((string) $attributes['fileName']) : '',
        'height'         => $height,
        'height_mode'    => $height_mode,
        'show_toolbar'   => $show_toolbar,
        'allow_download' => $allow_download,
    );
}

/**
 * Resolves the PDF URL and a display name from normalized attributes.
 *
 * @param array $attributes Normalized attributes.
 * @return array{url:string,file_name:string}
 */
function simplekitpdf_get_pdf_source($attributes) {
    $url  = '';
    $name = '';

    if ($attributes['source'] === 'media' && $attributes['attachment_id'] > 0) {
        $attachment_url = wp_get_attachment_url($attributes['attachment_id']);
        if ($attachment_url) {
            $url = simplekitpdf_sanitize_pdf_url($attachment_url);
        }
        $file = get_attached_file($attributes['attachment_id']);
        if ($file) {
            $name = wp_basename($file);
        }
    }

    if ($url === '' && $attributes['url'] !== '') {
        $url = $attributes['url'];
    }

    if ($name === '' && $url !== '') {
        $path = wp_parse_url($url, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $name = wp_basename($path);
        }
    }

    return array(
        'url'       => $url,
        'file_name' => $name,
    );
}
