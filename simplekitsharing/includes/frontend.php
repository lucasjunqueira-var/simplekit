<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Helper: resolve an uploaded image URL to a favicon-sized version
// ---------------------------------------------------------------------------
function simplekitsharing_resolve_favicon_url($url) {
    if (empty($url)) {
        return '';
    }

    // Try to resolve the URL to an attachment ID
    $attachment_id = attachment_url_to_postid($url);
    if ($attachment_id) {
        // Get a properly sized cropped version suitable for favicon use
        $sized = wp_get_attachment_image_src($attachment_id, [32, 32], true);
        if ($sized && !empty($sized[0])) {
            return $sized[0];
        }

        // Fallback: try thumbnail (150x150 cropped)
        $thumb = wp_get_attachment_image_src($attachment_id, 'thumbnail', true);
        if ($thumb && !empty($thumb[0])) {
            return $thumb[0];
        }
    }

    // External or non-attachment URL – return as-is
    return $url;
}

// ---------------------------------------------------------------------------
// Get sharing data for current page / post
// ---------------------------------------------------------------------------
function simplekitsharing_get_current_data() {
    $general = simplekitsharing_get_general_settings();
    $data    = $general;

    if (is_singular(['post', 'page'])) {
        $post_id = get_queried_object_id();

        $post_icon        = get_post_meta($post_id, '_simplekitsharing_icon', true);
        $post_share_text  = get_post_meta($post_id, '_simplekitsharing_share_text', true);
        $post_share_image = get_post_meta($post_id, '_simplekitsharing_share_image', true);
        $post_tags        = get_post_meta($post_id, '_simplekitsharing_tags', true);

        if (!empty($post_icon)) {
            $data['icon'] = $post_icon;
        }
        if (!empty($post_share_text)) {
            $data['share_text'] = $post_share_text;
        }
        if (!empty($post_share_image)) {
            $data['share_image'] = $post_share_image;
        }
        if (!empty($post_tags)) {
            $data['tags'] = $post_tags;
        }
    }

    return $data;
}

// ---------------------------------------------------------------------------
// Get the share title
// ---------------------------------------------------------------------------
function simplekitsharing_get_share_title() {
    if (is_front_page() || is_home()) {
        return get_bloginfo('name');
    }

    if (is_singular()) {
        return get_the_title(get_queried_object_id());
    }

    return wp_get_document_title();
}

// ---------------------------------------------------------------------------
// Get the current page URL
// ---------------------------------------------------------------------------
function simplekitsharing_get_current_url() {
    global $wp;
    return home_url(add_query_arg([], $wp->request));
}

// ---------------------------------------------------------------------------
// Output social sharing meta tags in <head>
// ---------------------------------------------------------------------------
add_action('wp_head', 'simplekitsharing_output_meta_tags', 1);

function simplekitsharing_output_meta_tags() {
    $data  = simplekitsharing_get_current_data();
    $title = simplekitsharing_get_share_title();
    $url   = simplekitsharing_get_current_url();

    $share_text  = !empty($data['share_text']) ? $data['share_text'] : get_bloginfo('description');
    $share_image = !empty($data['share_image']) ? $data['share_image'] : '';
    $icon        = !empty($data['icon']) ? $data['icon'] : '';
    $tags        = !empty($data['tags']) ? $data['tags'] : '';
    $site_name   = get_bloginfo('name');

    $og_type = is_singular(['post', 'page']) ? 'article' : 'website';

    echo "\n<!-- Simple Kit Sharing Meta Tags -->\n";

    // Favicon / Icon – resolve to a properly sized version for browser tabs
    if (!empty($icon)) {
        $favicon_url = simplekitsharing_resolve_favicon_url($icon);
        echo '<link rel="icon" href="' . esc_url($favicon_url) . '" sizes="32x32" />' . "\n";
        echo '<link rel="shortcut icon" href="' . esc_url($favicon_url) . '" />' . "\n";
    }

    // Article tags – all tags in a single article:tag meta element, comma-separated
    if (!empty($tags)) {
        echo '<meta property="article:tag" content="' . esc_attr($tags) . '" />' . "\n";
    }

    // Open Graph
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($share_text) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";

    if (!empty($share_image)) {
        echo '<meta property="og:image" content="' . esc_url($share_image) . '" />' . "\n";
    }

    // Twitter Cards
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($share_text) . '" />' . "\n";

    if (!empty($share_image)) {
        echo '<meta name="twitter:image" content="' . esc_url($share_image) . '" />' . "\n";
    }

    echo "<!-- / Simple Kit Sharing Meta Tags -->\n\n";
}
