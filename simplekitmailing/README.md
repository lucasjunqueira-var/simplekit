# Simple Kit Mailing

This plugin was created to simplify the management of small contact lists, from collecting email addresses to sending messages. It is designed for very small lists (only a few dozen) with infrequent mailings. The entire process takes place on your own website, using your domain's SMTP service, without the need for other external services.

## Highlights

- Manage mailing lists directly from your dashboard without using external services.
- Capture contacts from multiple lists.
- Simplified message sending.

## How the plugin works

Once the plugin is activated, go to the "mailing lists" page in the SK Mailing menu. Here you will see some pre-created lists that you can delete or edit. Adjust the lists to your liking, setting their names and descriptions.

<img width="1920" height="973" alt="mail01" src="https://github.com/user-attachments/assets/8afcdaa7-6f3d-4160-aa7e-bac12a329390" />

Next, go to "settings" to adjust the configuration for each list individually. The first adjustment to be made is to specify the sending method. The plugin depends on your SMTP server to send messages, so you will need to specify its name, password, SMTP server/port, and encryption method. Each list can have a different sending configuration.

<img width="1920" height="973" alt="mail02" src="https://github.com/user-attachments/assets/4ae27079-1e40-4ff8-8aa8-19d4c3dc1c78" />

The next adjustment to be made is to indicate the page where subscribers to your lists will be directed after registering their addresses (a thank-you page, for example). In addition, also indicate a page that will manage unsubscription – a link to it will be incorporated into all sent messages so that subscribers can cancel their subscription. Also indicate what information will be collected in addition to the email address.

<img width="1920" height="973" alt="mail03" src="https://github.com/user-attachments/assets/543d1ac7-b740-434b-b9ff-0cd761239fd6" />

Now, adjust the appearance and common information of the messages sent to the list, such as colors, header, and footer.

<img width="1920" height="973" alt="mail04" src="https://github.com/user-attachments/assets/713b133d-b721-4732-8e2b-02eab086a674" />

The text displayed in the blocks created by the plugin on your pages, such as the registration form, removal form, and confirmation form, is the next adjustment to be made.

<img width="1920" height="973" alt="mail05" src="https://github.com/user-attachments/assets/fb8f4b95-d0c0-4de5-b324-8730f90b53af" />

The final settings are very important! First, indicate how many messages will be sent every 10 seconds at the time of sending. Some SMTP services may limit your sending capacity, so it's important to adjust accordingly. Next, we have the protection of the address registration form. It is recommended to choose reCaptcha and register your configuration keys here (which you can get for free on the service's website). Finally, you can activate "double opt-in" during registration, which is recommended. With this setting active, when registering an address, the visitor will receive a message asking them to confirm the subscription - the email will only be added if they click on the address received in this confirmation message, which leads to the page indicated in this configuration.

<img width="1920" height="973" alt="mail06" src="https://github.com/user-attachments/assets/c889ec39-a5cc-4cba-afec-5767bc44aa81" />

With the list configured, it's time to adjust the website pages. The first is the registration page. Add a "Simple Kit Mailing Collect" block and adjust some of its options: first, the title to be displayed (remember that all other displayed texts are adjusted in the list configuration). Also indicate which of your lists it is related to. Finally, you can adjust the visual properties of this block in "custom CSS".

<img width="1920" height="973" alt="mail07" src="https://github.com/user-attachments/assets/2edcbb40-0783-4bb9-9be6-30ca1593fbe8" />

The page redirected after registration doesn't need any special block – it's just a regular page with your post-registration message, which could be a thank you and/or instructions about double opt-in. The unsubscribe page, however, needs to include the "Simple Kit Mailing Unsubscribe" block, with similar settings to the previous one.

<img width="1920" height="973" alt="mail08" src="https://github.com/user-attachments/assets/9266b3b7-922c-4ee1-99f6-f51ca1ff73bc" />

The same applies to the "Simple Kit Mailing Confirm" block, which should be placed on the double opt-in link page.

<img width="1920" height="973" alt="mail09" src="https://github.com/user-attachments/assets/53fa3fb8-6ff9-45cf-aae2-b3ad9191ef3d" />

With the pages prepared, back in the plugin menu, you will find the list of subscribers on the "subscribers" page. From here you can even export the registered contacts as a spreadsheet.

<img width="1920" height="973" alt="mail10" src="https://github.com/user-attachments/assets/ca82ba07-944b-4cf6-98b0-d0fdfdc9db59" />

In the "create message" menu, you can write your messages. Here, define the list to which the message will be sent and create your message. It is recommended that you always send test messages to confirm that everything is correct. Once the process is finished, click on "send to all list subscribers".

<img width="1920" height="973" alt="mail11" src="https://github.com/user-attachments/assets/4b3ff2be-7c05-4697-a638-1531323e2439" />

You will be taken to the "messages" menu, which contains the sending process. It is necessary to keep this page open and active for all messages to be sent at the rate you set in the list settings. You can pause or even cancel the sending of remaining messages at any time.

<img width="1920" height="973" alt="mail12" src="https://github.com/user-attachments/assets/ef23af7c-ced9-4db0-8a44-8b7e9bbec026" />

If you need to back up your list settings and registered emails, access the "backup" menu. Here, in addition to exporting the settings as a JSON file, you can re-import it, which is useful for transferring your mailings from one server to another, for example.

<img width="1920" height="973" alt="mail13" src="https://github.com/user-attachments/assets/e9efd866-58e0-4dc3-9997-60a480d7d076" />

## Frequently asked questions

**How is the sending done?**

You configure, individually for each list, the SMTP service associated with the sender's address, as you would with a traditional email program. The plugin will use this service to send the messages.

**Are there limits per send?**

This plugin was created with small lists and few sends in mind. The biggest limitation is the restrictions that are normally imposed by SMTP services. Adjust your list settings accordingly.

**Can I include images in the messages?**

Yes! They will be embedded in the text, but remember that, firstly, many message receiving services hide images until the reader confirms their viewing and, secondly, that including many images in a message increases the chances of it going to a spam folder.

**My messages are going to recipients' spam folders. What's happening?**

Several factors can influence this, such as the number of images embedded in the messages or a high frequency of sends. One way to alleviate this problem is to correctly configure the SPF/DKIM/DMARC settings for your website's domain. Check with your hosting service provider for the best way to do this in your case.
