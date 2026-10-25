# Simple Kit Redirect

Send posts and pages to a new address, configured individually in the WordPress editor.

## Requirements

- WordPress 6.8 or later
- PHP 7.4 or later

## Installation

1. Copy the `simplekitredirect` folder to `wp-content/plugins/`.
2. Activate **Simple Kit Redirect** in the WordPress admin.
3. Edit a post or page and use the **Simple Kit Redirect** box in the sidebar.

## How it works

The target and the redirect type are stored as post meta:

| Meta key | Values |
|----------|--------|
| `_simplekitredirect_url` | Absolute `http`/`https` URL |
| `_simplekitredirect_status` | `301` or `302` |

On the frontend, `template_redirect` reads the meta, validates the URL and sends the
visitor with `wp_redirect()`. The redirect applies to logged-in editors as well, so
the frontend matches what visitors see. Previews, REST requests, feeds, robots.txt,
sitemaps, 404 requests and password-protected content are never redirected. Editors
can open the original content by appending `?simplekitredirect=0` to the address.

### Accepted targets

- Absolute URLs, including other domains: `https://example.com/new-page`
- Root-relative paths: `/new-page` (resolved against `home_url()`)
- Protocol-relative URLs: `//example.com/new-page`

`javascript:`, `data:`, `mailto:`, `ftp:`, credentials in the URL and control
characters are rejected.

## Filters

| Filter | Default | Purpose |
|--------|---------|---------|
| `simplekitredirect_post_types` | `['post', 'page']` | Post types that can be redirected |
| `simplekitredirect_should_redirect` | `true` | Decides at request time whether the redirect happens |
| `simplekitredirect_preserve_query` | `true` | Appends the request query string when the target has none |
| `simplekitredirect_target_url` | Target URL | Changes the final destination before validation |

## Uninstall

Uninstalling deletes `_simplekitredirect_url` and `_simplekitredirect_status` from
`wp_postmeta`. Deactivation does not modify any data.

## License

GPLv2 or later. See [LICENSE](../LICENSE).
