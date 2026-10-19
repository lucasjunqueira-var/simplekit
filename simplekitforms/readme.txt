=== Simple Kit Forms ===
Contributors: chokito76
Tags: forms, contact form, form builder
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create forms and manage responses directly from WordPress.

== Description ==

Simple Kit Forms is a streamlined plugin for creating forms and managing responses directly from your WordPress installation. It manages its own database tables for forms and entries — all data stays on your server.

Forms can include text, e-mail, textarea, checkboxes, radio buttons, dropdown and **file upload** fields. A file field can define instructions, a maximum size in MB and an optional list of allowed extensions. Uploaded files are stored in the WordPress uploads directory (`uploads/simplekitforms/<form-id>/<response-id>/`) and are downloaded from the dashboard through a permission-checked link that opens in a new tab.

== Changelog ==

= 1.3.0 =
* Added the "File" field type to the form builder: a form can now contain one or more file upload fields, each with instructions, a maximum size (MB) and an optional list of allowed extensions.
* Uploaded files are stored under the WordPress uploads directory and downloaded from the dashboard through a permission-checked endpoint (links open in a new tab). Files are removed when the response or the form is deleted.
* The notification e-mail lists the file name for file fields.

= 1.2.0 =
* Added per-form SMTP settings (server, port, username, password and encryption), mirroring the per-list settings of Simple Kit Mailing.
* Notification e-mails are now sent through the form's SMTP settings using PHPMailer, falling back to the WordPress mailer when no SMTP server is configured.
* The forms table now stores the SMTP settings; existing installations are migrated automatically.

= 1.1.0 =
* Added a per-form notification e-mail: all received responses are now also sent to the configured address while still being stored in the dashboard.
* When the form contains one or more e-mail fields, the first one is used as the "Reply-To" of the notification message.

= 1.0.0 =
* Initial release.
