=== Simple Kit Downloads ===
Contributors: lucasjunqueira
Tags: downloads, download manager, statistics, files, gutenberg
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate?token=v4gcsUlC7vfpl1Ah1hB22_b3vEKPPsimiFHbrFsqccIAUPdaYtAy_f_UkJWTT7uoeEfRfX2QRUd8sRaY

Create download buttons for files hosted on your site and track download statistics per version.

== Description ==

Simple Kit Downloads registers downloads identified by a name. Each download can contain any number of versions, numbered with a single number or with dotted numbers such as 25, 25.1 or 25.0.1.

Each version can be:

* A file uploaded through the plugin, stored in `wp-content/uploads/simplekitdownloads/`.
* A URL on the same domain of the site. In this case the file is served from its original location and is not copied to the uploads folder.

Downloads are exposed through a Gutenberg block or the `[simplekitdownloads]` shortcode. The button can point to a specific version or to "current", which always resolves to the latest version when the button is clicked. This makes the button work correctly even on cached pages.

Features:

* Files page with search, version management and file replacement.
* Statistics page with totals and the oldest/newest download per download and per version.
* CSV export of the current view and an optional full log export with one line per download.
* Statistics records are preserved when a download or version is deleted.
* Setup page with button texts, global CSS and an option to record the visitor IP address.
* JSON backup including settings, downloads, versions and statistics.

== Frequently Asked Questions ==

= How does "current" work? =

The download URL stores the word `current`. When the button is clicked, the plugin resolves the highest version available at that moment. Publishing a new version immediately updates every existing "current" button, even on cached pages.

= What happens to statistics when a download is deleted? =

The statistics are kept. Download and version names are copied into each statistics record, so the history remains available on the Statistics page and in the CSV exports.

= Are uploaded files included in the backup? =

No. The JSON backup contains settings, downloads, versions and statistics. Versions registered by URL keep working because only the URL is stored. Uploaded files must be backed up separately from the uploads folder.

= Can the download URL point to another site? =

No. Only URLs on the same domain of the site are accepted, in order to avoid open redirects and traffic hijacking.

= Which files can be uploaded? =

Server executable file types such as PHP, CGI and shell scripts are blocked. All other files are accepted, limited by the maximum upload size configured on the server.

== Changelog ==

= 1.4.0 =
* Simple Kit plugins normalization

= 1.0.0 =
* Initial release.
