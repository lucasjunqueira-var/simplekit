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
            <h2 style="margin-top:0;color:#1d2327;"><?php esc_html_e('Simple Kit Mailing', 'simplekitmailing'); ?></h2>
            <p><?php esc_html_e('This plugin was created to simplify the management of small contact lists, from collecting email addresses to sending messages. It is designed for very small lists (only a few dozen) with infrequent mailings. The entire process takes place on your own website, using your domain\'s SMTP service, without the need for other external services.', 'simplekitmailing'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Highlights', 'simplekitmailing'); ?></h3>
            <ul>
                <li><?php esc_html_e('Manage mailing lists directly from your dashboard without using external services.', 'simplekitmailing'); ?></li>
                <li><?php esc_html_e('Capture contacts from multiple lists.', 'simplekitmailing'); ?></li>
                <li><?php esc_html_e('Simplified message sending.', 'simplekitmailing'); ?></li>
            </ul>

            <h3 style="color:#1d2327;"><?php esc_html_e('How the plugin works', 'simplekitmailing'); ?></h3>
            <p><?php esc_html_e('Once the plugin is activated, go to the "mailing lists" page in the SK Mailing menu. Here you will see some pre-created lists that you can delete or edit. Adjust the lists to your liking, setting their names and descriptions.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('Next, go to "settings" to adjust the configuration for each list individually. The first adjustment to be made is to specify the sending method. The plugin depends on your SMTP server to send messages, so you will need to specify its name, password, SMTP server/port, and encryption method. Each list can have a different sending configuration.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The next adjustment to be made is to indicate the page where subscribers to your lists will be directed after registering their addresses (a thank-you page, for example). In addition, also indicate a page that will manage unsubscription – a link to it will be incorporated into all sent messages so that subscribers can cancel their subscription. Also indicate what information will be collected in addition to the email address.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('Now, adjust the appearance and common information of the messages sent to the list, such as colors, header, and footer.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The text displayed in the blocks created by the plugin on your pages, such as the registration form, removal form, and confirmation form, is the next adjustment to be made.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The final settings are very important! First, indicate how many messages will be sent every 10 seconds at the time of sending. Some SMTP services may limit your sending capacity, so it\'s important to adjust accordingly. Next, we have the protection of the address registration form. It is recommended to choose reCaptcha and register your configuration keys here (which you can get for free on the service\'s website). Finally, you can activate "double opt-in" during registration, which is recommended. With this setting active, when registering an address, the visitor will receive a message asking them to confirm the subscription - the email will only be added if they click on the address received in this confirmation message, which leads to the page indicated in this configuration.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('With the list configured, it\'s time to adjust the website pages. The first is the registration page. Add a "Simple Kit Mailing Collect" block and adjust some of its options: first, the title to be displayed (remember that all other displayed texts are adjusted in the list configuration). Also indicate which of your lists it is related to. Finally, you can adjust the visual properties of this block in "custom CSS".', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The page redirected after registration doesn\'t need any special block – it\'s just a regular page with your post-registration message, which could be a thank you and/or instructions about double opt-in. The unsubscribe page, however, needs to include the "Simple Kit Mailing Unsubscribe" block, with similar settings to the previous one.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('The same applies to the "Simple Kit Mailing Confirm" block, which should be placed on the double opt-in link page.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('With the pages prepared, back in the plugin menu, you will find the list of subscribers on the "subscribers" page. From here you can even export the registered contacts as a spreadsheet.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('In the "create message" menu, you can write your messages. Here, define the list to which the message will be sent and create your message. It is recommended that you always send test messages to confirm that everything is correct. Once the process is finished, click on "send to all list subscribers".', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('You will be taken to the "messages" menu, which contains the sending process. It is necessary to keep this page open and active for all messages to be sent at the rate you set in the list settings. You can pause or even cancel the sending of remaining messages at any time.', 'simplekitmailing'); ?></p>
            <p><?php esc_html_e('If you need to back up your list settings and registered emails, access the "backup" menu. Here, in addition to exporting the settings as a JSON file, you can re-import it, which is useful for transferring your mailings from one server to another, for example.', 'simplekitmailing'); ?></p>

            <h3 style="color:#1d2327;"><?php esc_html_e('Frequently asked questions', 'simplekitmailing'); ?></h3>
            <p>
                <strong><?php esc_html_e('How is the sending done?', 'simplekitmailing'); ?></strong><br />
                <?php esc_html_e('You configure, individually for each list, the SMTP service associated with the sender\'s address, as you would with a traditional email program. The plugin will use this service to send the messages.', 'simplekitmailing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Are there limits per send?', 'simplekitmailing'); ?></strong><br />
                <?php esc_html_e('This plugin was created with small lists and few sends in mind. The biggest limitation is the restrictions that are normally imposed by SMTP services. Adjust your list settings accordingly.', 'simplekitmailing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Can I include images in the messages?', 'simplekitmailing'); ?></strong><br />
                <?php esc_html_e('Yes! They will be embedded in the text, but remember that, firstly, many message receiving services hide images until the reader confirms their viewing and, secondly, that including many images in a message increases the chances of it going to a spam folder.', 'simplekitmailing'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('My messages are going to recipients\' spam folders. What\'s happening?', 'simplekitmailing'); ?></strong><br />
                <?php esc_html_e('Several factors can influence this, such as the number of images embedded in the messages or a high frequency of sends. One way to alleviate this problem is to correctly configure the SPF/DKIM/DMARC settings for your website\'s domain. Check with your hosting service provider for the best way to do this in your case.', 'simplekitmailing'); ?>
            </p>
        </div></div>
    </div>
    <?php
}
