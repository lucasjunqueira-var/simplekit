=== Simple Kit Accessibility ===
Contributors: lucasjunqueira
Tags: accessibility, contrast, font size, reading
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate?token=v4gcsUlC7vfpl1Ah1hB22_b3vEKPPsimiFHbrFsqccIAUPdaYtAy_f_UkJWTT7uoeEfRfX2QRUd8sRaY

Add reading tools to your pages: text size, line spacing, letter spacing and high contrast.

== Description ==

Simple Kit Accessibility adds a small floating button to the site. When opened, the button shows a panel with four independent adjustments:

* Text size, increase, decrease and reset.
* Line spacing, increase, decrease and reset.
* Letter spacing, increase, decrease and reset.
* High contrast, on and off.

The button can be placed in eight positions, with configurable size, shape, colors, optional image, optional text and fully editable labels. The panel has its own colors, font size, width and corner radius.

The adjustments are applied by classes on the `html` element, so nothing is reloaded and no request is made to the server.

== Installation ==

1. Upload the `simplekitaccessibility` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open the Setup page and choose where the button should appear.

== Frequently Asked Questions ==

= Are the visitor choices saved? =

By default no. Every page load starts from the original state. The Setup page has an option to remember the choices in the browser or only during the visit. The data never leaves the browser.

= Can I create my own buttons? =

Yes. Any element with the `data-skacc-action` attribute drives the adjustments, for example `<button data-skacc-action="font-up">A+</button>`. The `[simplekitaccessibility]` shortcode prints such an element.

= Can I use the adjustments from my own JavaScript? =

Yes. `window.simplekitaccessibility` exposes `increaseFont()`, `decreaseFont()`, `resetFont()`, the line and letter equivalents, `setContrast()`, `toggleContrast()`, `resetAll()`, `getState()` and `subscribe()`. The API is loaded whenever the plugin is enabled, even on pages where the floating button is hidden.

= How do I hide the button on a specific page? =

Open the content in the editor and uncheck "Hide the accessibility button on this content" in the Simple Kit Accessibility box.

= Why does the text size not change on some themes? =

The default method applies a multiplier to a curated list of text containers. Themes that declare sizes in pixels keep working, but the multiplier is relative to the parent element, so the original size of each element is not preserved.

On those sites switch "Text size method" to "Measured per element" on the Setup page. The plugin reads the size of every text element before scaling it, which keeps the typographic hierarchy of the theme. A few very specific cases may still need rules in the Custom CSS field.

= What does the high contrast filter do? =

The default method overrides colors with a high contrast palette and never changes layout. The alternative method applies a CSS contrast filter to the page; it affects fixed and sticky elements as a side effect.

== Changelog ==

= 1.0.0 =
* Initial release.
