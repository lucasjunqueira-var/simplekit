=== Simple Kit Mailing ===
Contributors: simplekitmailing
Tags: newsletter, email, mailing, subscribers, smtp
Requires at least: 6.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The "Simple Kit Mailing" plugin is part of the "Simple Kit" suite and offers a simplified way to manage both contact registration in mailing lists and sending messages to limited numbers of recipients, directly from your dashboard.

== Description ==

Simple Kit Mailing allows you to manage mailing lists, collect subscribers via Gutenberg blocks, and send email messages directly from your WordPress site.

= Features =

* Multiple mailing lists support
* Gutenberg block for email collection
* Unsubscribe management
* Double opt-in confirmation
* CSV import/export
* SMTP integration
* reCAPTCHA and Akismet protection
* WP-Cron based gradual sending
* Backup and restore

== Installation ==

1. Upload the `simplekitmailing` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your SMTP settings and mailing lists under Simple Kit Mailing → Settings.

== Frequently Asked Questions ==

= How do I collect emails? =

Use the "Simple Kit Mailing Collect" Gutenberg block on any page or post. Visitors can subscribe by entering their email address.

= How do I send a message? =

Go to Simple Kit Mailing → Create Message, write your email content, select a target list, and click "Send to all list subscribers".

= How do I configure SMTP? =

Go to Simple Kit Mailing → Settings, select a list, and configure your SMTP server details under the SMTP Settings section.

== Changelog ==

= 2.0.0 =
* Multiple mailing lists support
* Per-list SMTP settings
* Double opt-in confirmation
* Improved email template customization
* CSV import/export

= 1.0.0 =
* Initial release
