=== Simple Kit Registration ===
Contributors: lucasjunqueira
Tags: forms, registration, upload, evaluators, submissions
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate?token=v4gcsUlC7vfpl1Ah1hB22_b3vEKPPsimiFHbrFsqccIAUPdaYtAy_f_UkJWTT7uoeEfRfX2QRUd8sRaY

Collect registration data (including file uploads) through forms and let selected evaluators review the submissions on restricted pages of your site.

== Description ==

Simple Kit Registration is a self-hosted plugin to manage registrations such as applications to a public call (edital).

Main features:

* Create forms with text, e-mail, password, URL, number, phone, textarea, checkboxes, radio buttons, dropdown and **file** fields.
* Each form has a mandatory **form code** (alphanumeric, no spaces or special characters) that is used as the folder name for the uploaded files.
* File fields can define instructions, a maximum size in MB and a whitelist of allowed extensions. Files larger than the limit or with a disallowed extension are rejected on the server.
* Uploaded files are stored under `uploads/simplekitregistration/<form-code>/<response-id>/` with sanitized file names.
* Responses are listed in the WordPress dashboard with links to the uploaded files (downloads open in a new tab), CSV export and bulk deletion.
* Register **evaluators** (e-mail, encrypted password and the forms each one may access). Evaluators are not WordPress users.
* **Settings** page to customize every text shown on the evaluator-facing pages (login, form list, responses list, response details and messages) and every text written to the export spreadsheets (column headers and row labels of the responses CSV and of the form backup CSV). The configuration is global (it applies to all forms) and each field falls back to its default text when left empty.
* Restricted pages: add the "Simple Kit Registration Access" block to a page. Visitors see a login form; on success a signed cookie is set for one week and the block lists the forms the evaluator can access. Clicking a form shows its responses (15 per page) and clicking a response opens the full content, including download links, in a new tab.
* Messages: in the response details (dashboard and restricted page) it is possible to add comments to a response. Each message records the author name/e-mail and the date/time, is displayed below the response in the order it was received and is permanent (it cannot be edited or deleted). Messages are included in the backup.
* Backup as a ZIP file containing the plugin data (JSON, including messages) and the whole uploads folder. The same ZIP is generated on deactivation and restored on activation.
* Individual form backup: export a single form as a ZIP containing one CSV spreadsheet with all its responses and messages (ready to open in Excel) plus the complete upload folder with the files of every response. Export only.

== Installation ==

1. Upload the `simplekitregistration` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to SK Registration in the dashboard and create your first form.

== Frequently Asked Questions ==

= How do I display a form on the site? =

Use the "Simple Kit Registration Form" block, or the shortcode `[simplekitregistration code="my-form"]` (you can also use `[simplekitregistration id="1"]`).

= How do evaluators access the responses? =

Create a page and add the "Simple Kit Registration Access" block. Register the evaluator in SK Registration -> Evaluators, selecting the forms they may review. The evaluator logs in on that page with the registered e-mail and password.

= Where are the uploaded files stored? =

Under the WordPress uploads directory, following the layout `uploads/simplekitregistration/<form-code>/<response-id>/<file>`.

= Do evaluators need a WordPress account? =

No. Evaluators are managed by the plugin and log in through the restricted page.

== Changelog ==

= 1.3.2 =
* File handling now uses the WordPress APIs: uploads are stored with `wp_handle_upload()` (redirecting the destination through the `upload_dir` filter) instead of a direct `move_uploaded_file()` call, and the form backup CSV is built as a plain string instead of using stream functions.

= 1.3.1 =
* Security/validation review fixes: escaped the response ID in the message form, sanitized the message and password inputs, sanitized the form code before use, and the evaluator logout now requires a valid nonce.

= 1.3.0 =
* The Settings page now also manages the texts used in the export spreadsheets: the column headers of the responses CSV and the column headers and row labels of the individual form backup CSV.

= 1.2.0 =
* Added the individual (per form) backup on the Backup page: a ZIP with a CSV spreadsheet containing all the responses and messages of the form, plus the complete upload folder of that form. Export only.
* The full backup description now mentions that messages are included.

= 1.1.0 =
* Added the Settings page: all texts displayed to the evaluators (login, form list, responses list, response details and messages) can now be customized globally, with the default text used when a field is left empty.

= 1.0.1 =
* Added support for messages (comments) attached to responses, in the dashboard and in the restricted evaluator area. Messages record the author name/e-mail and the timestamp, are shown below the response and are permanent. They are also included in the backup.
* Frontend form submission moved to an enqueued script (fixes a JavaScript syntax error caused by content filters rewriting the inline script inside the block output).

= 1.0.0 =
* Initial release.
