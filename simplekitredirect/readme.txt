=== Simple Kit Redirect ===
Contributors: chokito76
Tags: redirect, seo, posts, pages
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate?token=v4gcsUlC7vfpl1Ah1hB22_b3vEKPPsimiFHbrFsqccIAUPdaYtAy_f_UkJWTT7uoeEfRfX2QRUd8sRaY

Send posts and pages to a new address without leaving the WordPress dashboard.

== Description ==

Simple Kit Redirect adds one field to the post and page editor. Fill in a destination and every visit to that content is sent there. Leave the field empty and the content is displayed normally.

= Features =

* Per-post and per-page destination
* 302 temporary (default) or 301 permanent redirect
* Absolute, protocol-relative and root-relative targets
* External domains supported
* Editors, previews, REST requests, feeds, robots.txt, sitemaps and 404 requests are never redirected
* Password-protected content keeps showing its password form
* Loop guard for targets that point back to the same address
* Optional query string preservation
* No custom database tables and no external requests

== Installation ==

1. Upload the `simplekitredirect` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Edit a post or page and use the **Simple Kit Redirect** box in the sidebar.

== Frequently Asked Questions ==

= Does it work with custom post types? =

Not by default. Add the post type with the simplekitredirect_post_types filter:

add_filter( 'simplekitredirect_post_types', function ( $types ) { $types[] = 'book'; return $types; } );

= Can I redirect to another domain? =

Yes. Absolute http and https addresses are accepted. Other schemes, such as javascript:, data:, mailto: and ftp:, are rejected.

= Why do I still see the page after setting a redirect? =

Check that the field was saved, that the target is a valid http(s) address or a path starting with a slash, and that the target is not the same address as the content. If you are logged in and can edit the content, the redirect also applies; append ?simplekitredirect=0 to the address to view the original content.

= Can I view the original content while testing? =

Yes, if you can edit the content. Append ?simplekitredirect=0 to the address. The switch has no effect for anonymous visitors.

= Why does a 301 keep redirecting after I remove it? =

Browsers and search engines cache permanent redirects. Clear the browser cache or test in a private window. Prefer 302 while testing.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

Initial release.
