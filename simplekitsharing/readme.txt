=== Simple Kit Sharing ===
Contributors: simplekitsharing
Tags: sharing, social sharing, open graph, twitter cards, meta tags
Requires at least: 6.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage social sharing meta tags (Open Graph, Twitter Cards) for pages and posts.

== Description ==

Simple Kit Sharing automatically inserts Open Graph and Twitter Card meta tags into the head section of your pages, ensuring proper link previews when shared on social media platforms.

= Features =

* Open Graph (og:) meta tags for title, description, image, and more
* Twitter Card meta tags for rich link previews
* Per-page override via meta box in the post/page editor
* Custom favicon / icon support with automatic size optimization
* Article tag support for content categorization
* Lightweight — no external dependencies

== Installation ==

1. Upload the `simplekitsharing` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to SK Sharing → Settings to configure your default sharing settings.

== Frequently Asked Questions ==

= How do I set default sharing values? =

Go to SK Sharing → Settings and configure the default share text, share image, icon, and article tags. These values are used globally on every page.

= How do I override settings for a specific page or post? =

When editing a post or page, look for the "Simple Kit Sharing" meta box in the sidebar. Fill in the fields you want to customize — any field left empty will use the global default.

= My favicon doesn't appear in the browser tab =

The plugin automatically generates a properly sized 32x32 pixel version of your uploaded image for browser tab display. Clear your browser cache and reload the page.

== Changelog ==

= 1.0.0 =
* Initial release
