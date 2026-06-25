# Simple Kit QRCode

This plugin allows for the quick creation of QR codes that link to posts or pages on your website. Furthermore, it tracks the usage of these codes, keeping an updated list of how many times each one has been used.

## Highlights

- Simple and quick generation of QR codes.
- Monitoring code usage statistics.

## How the plugin works

After activating the plugin, access the "generate QR code" page from the "SK QRCode" menu. Here, you will see a list of posts and pages published on your website. Simply select one and click the "generate QR code" button.

<img width="1920" height="973" alt="qrcode01" src="https://github.com/user-attachments/assets/cfc7075e-f8bd-4358-9adc-c04deed0b672" />

The code will be generated, and you can download the image for printing.

<img width="1920" height="973" alt="qrcode02" src="https://github.com/user-attachments/assets/5ce857c1-4130-4bf5-858f-7f58e2e73b8b" />

On the statistics page you can see all the generated chords, the number of times they have been scanned, and the last time someone used them, as well as delete the data for any of them.

<img width="1920" height="973" alt="qrcode03" src="https://github.com/user-attachments/assets/7792c3e1-da8e-4a28-ac38-8719dd774c70" />

## Frequently asked questions

**Does QR code generation use any external service or API?**

No, all image generation is done by the plugin itself. For this, it's important that your web server has the PHP GD2 extension enabled (which is common).

**I lost the QR code image for a page, how do I download it again?**

Simply generate the code for the same page again. The usage statistics for this new code will be added to those you already have recorded for it.
