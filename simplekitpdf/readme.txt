=== Simple Kit PDF ===
Contributors: chokito76
Tags: pdf, pdf.js, embed, document viewer, gutenberg
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate?token=v4gcsUlC7vfpl1Ah1hB22_b3vEKPPsimiFHbrFsqccIAUPdaYtAy_f_UkJWTT7uoeEfRfX2QRUd8sRaY

Embed PDF files in posts and pages with pdf.js, with an optional toolbar and download control.

== Description ==

Simple Kit PDF adds the "Simple Kit PDF" block to the WordPress editor. The block embeds a PDF file
that is rendered in the visitor browser by pdf.js, the open source PDF viewer maintained by Mozilla.
No external service is contacted and the file is never uploaded anywhere else.

The PDF can come from the Media Library or from a URL pointing to a file hosted on the same site. URLs
from other domains are refused.

Block settings:

* Height mode: fixed height in pixels (default 600, limited to 100-2000), first page only, or the full
  document measured from every page plus the spacing between them.
* Show toolbar (disabled by default): previous/next page, page number, zoom, fit width.
* Allow download (disabled by default, requires the toolbar): adds a download button.

The block always occupies the full width available to it. pdf.js is only loaded on pages that contain
the block, and only when the block approaches the viewport.

== Frequently Asked Questions ==

= Does the plugin send the PDF to an external server? =

No. The PDF file is fetched from your own site and rendered locally by the visitor browser. pdf.js is
bundled with the plugin, so no CDN or third-party request is involved.

= Can I embed a PDF hosted on another domain? =

No. Only files hosted on the same domain as the site can be embedded. This is validated in the block
editor and again when the page is rendered.

= Does disabling the download button protect the file? =

No. The download control is a display policy: the button and the no-JavaScript fallback link are not
printed, but a determined visitor can still read the file URL from the page source. Use a membership or
protected-files solution if the document must be restricted.

= Why are some characters missing in a PDF? =

The bundled pdf.js build leaves out the optional CJK character maps (cmaps) and standard font
fallbacks to keep the plugin small. PDFs that depend on them may show substituted glyphs. Embedding the
fonts in the PDF avoids the issue.

= The block keeps showing "Loading PDF...". =

pdf.js is loaded as a JavaScript module and uses a module worker. Some security plugins and strict
Content-Security-Policy rules block module workers. Check the browser console and allow the plugin files
to be loaded, especially assets/pdfjs/build/pdf.worker.mjs.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin through the Plugins screen.
3. Add the "Simple Kit PDF" block to a post or page.

== Changelog ==

= 1.0.0 =
* Initial release.
* Simple Kit PDF block with Media Library and same-domain URL sources.
* Optional pdf.js toolbar with page navigation, zoom and fit width.
* Optional download button.

== Credits ==

This plugin bundles pdf.js 4.10.38, developed by Mozilla and contributors.
Project: https://mozilla.github.io/pdf.js/
License: Apache License 2.0 - https://www.apache.org/licenses/LICENSE-2.0
The bundled library is distributed unmodified in assets/pdfjs/, together with its LICENSE file.
