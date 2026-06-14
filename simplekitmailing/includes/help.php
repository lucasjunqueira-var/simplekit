<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Help page
// ---------------------------------------------------------------------------
function simplekitmailing_page_help() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help & Documentation', 'simplekitmailing'); ?></h1>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('The Simple Kit Mailing Plugin', 'simplekitmailing'); ?></h2>
            <p><?php esc_html_e('Simple Kit Mailing is a streamlined plugin designed for collecting email addresses and sending messages directly from your WordPress installation. Its purpose is to serve small contact lists and occasional mailings without relying on external email marketing services.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('Unlike dedicated email platforms (Mailchimp, SendGrid, Amazon SES, etc.), Simple Kit Mailing operates entirely within your own WordPress environment. This makes it ideal for website owners who need a straightforward, self-hosted solution for contacting their users — such as sending newsletters to a few dozen subscribers, notifying members about updates, or managing opt-in lists for small communities.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The plugin manages its own database tables for subscribers, mailing lists, messages, and double opt-in confirmations. All data stays on your server, giving you full control and privacy.', 'simplekitmailing'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Limitations', 'simplekitmailing'); ?></h3>
            <p><?php esc_html_e('Before using Simple Kit Mailing, please be aware of the following constraints:', 'simplekitmailing'); ?></p>
            <p>
                <strong><?php esc_html_e('Dependency on your own email service', 'simplekitmailing'); ?></strong>
                <br />
                <?php esc_html_e('Simple Kit Mailing does not include a built-in email delivery service. You must configure an SMTP account linked to your own domain (such as the one provided by your hosting company or a transactional email service). Without proper SMTP configuration, emails may not be delivered or may be flagged as spam.', 'simplekitmailing'); ?>    
            </p>
            <p>
                <strong><?php esc_html_e('Slow sending — the send page must remain open throughout the process', 'simplekitmailing'); ?></strong>
                <br />
                <?php esc_html_e('Dispatching messages is performed gradually via WordPress cron and AJAX calls while the "Messages" admin page is open. The sending process is intentionally paced to avoid overloading your server. This means:', 'simplekitmailing'); ?>
                <br />
                <?php esc_html_e('1. Sending a message to a large list can take a considerable amount of time;', 'simplekitmailing'); ?>
                <br />
                <?php esc_html_e('2. The admin page must stay open in your browser during the entire sending process; closing it will pause the dispatch.', 'simplekitmailing'); ?>
                <br />
                <?php esc_html_e('3. This approach is suitable for small volumes only.', 'simplekitmailing'); ?>
            </p>
        </div></div>

        <div style="margin-top:20px;"><div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Style', 'simplekitmailing'); ?></h2>
            <p><?php esc_html_e('To adjust the appearance of the blocks created by the Simple Kit Mailing plugin in your posts and pages, use the following CSS styles as a reference.', 'simplekitmailing'); ?></p>
            <textarea id="simplekitmailing-cssreference" style="width:100%; height:250px;" readonly>/* ===================================================
   Simple Kit Mailing — Default Block Styles
   Use these styles as a starting point to customize
   the appearance of plugin blocks in your theme.
   =================================================== */

/* -------------------------------------------------
   Collect Block  .simplekitmailing-collect-block
   ------------------------------------------------- */
.simplekitmailing-collect-block {
    max-width: 400px;
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.simplekitmailing-collect-block h3 {
    margin-top: 0;
}

.simplekitmailing-collect-block .sm-field {
    margin-bottom: 12px;
}

.simplekitmailing-collect-block .sm-field input[type="email"],
.simplekitmailing-collect-block .sm-field input[type="text"],
.simplekitmailing-collect-block .sm-field input[type="tel"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

.simplekitmailing-collect-block .sm-field label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 14px;
    cursor: pointer;
}

.simplekitmailing-collect-block .sm-field label input[type="checkbox"] {
    margin-top: 2px;
}

.simplekitmailing-collect-block .sm-submit {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.simplekitmailing-collect-block .sm-submit:hover {
    background: #005a87;
}

.simplekitmailing-collect-block .sm-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.simplekitmailing-collect-block .sm-message {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    display: none;
}

.simplekitmailing-collect-block .sm-message.error {
    display: block;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.simplekitmailing-collect-block .sm-message.success {
    display: block;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.simplekitmailing-collect-block .g-recaptcha {
    margin-bottom: 12px;
}

/* -------------------------------------------------
   Unsubscribe Block  .simplekitmailing-unsubscribe-block
   ------------------------------------------------- */
.simplekitmailing-unsubscribe-block {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    text-align: center;
}

.simplekitmailing-unsubscribe-block h2 {
    margin-top: 0;
    color: #333;
}

.simplekitmailing-unsubscribe-block .sm-unsubscribed {
    color: #155724;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}

.simplekitmailing-unsubscribe-block .sm-no-email {
    color: #856404;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}

/* -------------------------------------------------
   Confirm Block  .simplekitmailing-confirm-block
   ------------------------------------------------- */
.simplekitmailing-confirm-block {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    text-align: center;
}

.simplekitmailing-confirm-block h2 {
    margin-top: 0;
    color: #333;
}

.simplekitmailing-confirm-block .sm-confirm-success {
    color: #155724;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}

.simplekitmailing-confirm-block .sm-confirm-error {
    color: #721c24;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 4px;
    margin-top: 20px;
}</textarea>
        </div></div>


        <div style="margin-top:20px;">
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:30px;line-height:1.8;">
                <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Domain Authentication for Email Deliverability', 'simplekitmailing'); ?></h2>
                <p><?php esc_html_e('When sending marketing emails, one of the biggest challenges is ensuring that your messages actually reach the recipient\'s inbox instead of being flagged as spam. Internet Service Providers (ISPs) and email platforms rely heavily on authentication protocols to verify that the sender is legitimate. Configuring SPF, DKIM, and DMARC is essential to build trust, protect your brand, and improve deliverability rates.', 'simplekitmailing'); ?></p>
                <h3 style="color:#1d2327;"><?php esc_html_e('SPF (Sender Policy Framework)', 'simplekitmailing'); ?></h3>
                <p><?php esc_html_e('Defines which mail servers are authorized to send emails on behalf of your domain.', 'simplekitmailing'); ?></p>
                <h3 style="color:#1d2327;"><?php esc_html_e('DKIM (DomainKeys Identified Mail)', 'simplekitmailing'); ?></h3>
                <p><?php esc_html_e('Adds a digital signature to your emails, proving that the content hasn\'t been altered in transit.', 'simplekitmailing'); ?></p>
                <h3 style="color:#1d2327;"><?php esc_html_e('DMARC (Domain-based Message Authentication, Reporting, and Conformance)', 'simplekitmailing'); ?></h3>
                <p><?php esc_html_e('Aligns SPF and DKIM results and provides instructions to ISPs on how to handle unauthenticated emails. It also generates reports so you can monitor suspicious activity.', 'simplekitmailing'); ?></p>
                <p style="margin-bottom:0;"><?php esc_html_e('Without these records, your emails are more likely to be rejected, marked as spam, or even exploited by attackers through spoofing.', 'simplekitmailing'); ?></p>
            </div>
        </div>
    </div>
    <?php
}
