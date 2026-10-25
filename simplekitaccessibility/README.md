# Simple Kit Accessibility

Include accessibility tools for the text on your website.

## Highlights

- Four adjustments with increase, decrease and reset: text size, line spacing and letter spacing, plus a high contrast toggle.
- Optional floating button in eight positions, with configurable size, shape, colors, optional image and optional text.
- Works with themes that declare sizes in pixels, not only with themes based on rem or em.
- Implements JavaScript methods so that your theme can use the accessibility features, even if the floating button is not enabled.

## How the plugin works

After activating the plugin, open the settings page in the SK Accessibility menu and choose whether and where the button should appear.

<img width="1920" height="973" alt="acc1" src="https://github.com/user-attachments/assets/53084592-15de-4e58-9228-1943ce52c49b" />

Adjust the button: optional text, accessible name, optional image, size, shape, background color, text color and hover color. When neither an image nor a text is provided, the plugin uses the universal access icon.

<img width="1920" height="973" alt="acc2" src="https://github.com/user-attachments/assets/38a6a4b4-a6c2-4991-81d0-8d69e17bae26" />

Configure the panel: background, text and border colors, font size, width and corner radius. You can also replace the texts shown in the panel, from its title to the labels of each action. 

Even if you use the floating button by default on your site, it is possible to prevent it from being displayed on an individual page or post basis.

<img width="1920" height="973" alt="acc3" src="https://github.com/user-attachments/assets/fabfc7cf-f56a-451a-b02e-df92deeae927" />

## Custom triggers

Any element of the page can drive the adjustments through the `data-skacc-action` attribute:

```html
<button data-skacc-action="font-up">A+</button>
<button data-skacc-action="contrast">High contrast</button>
```

The same result is available as a shortcode:

```
[simplekitaccessibility action="font-up" label="A+"]
```

Available actions: `font-up`, `font-down`, `font-reset`, `line-up`, `line-down`, `line-reset`, `letter-up`, `letter-down`, `letter-reset`, `contrast`, `reset-all` and `panel`.

## Frequently asked questions

**Does the plugin change the theme files?**

No. The adjustments are applied by classes on the `html` element and, in the measured method, by inline font sizes that are restored when the visitor returns to the original state.

**Are the visitor choices sent to the server?**

No. When the "Remember choices" option is enabled, the state is stored only in the browser, using local or session storage.

**Why does the text size not change on some themes?**

The default method multiplies the size inherited from the parent element. Themes that declare sizes in pixels keep working, but the original size of each element is not preserved. On those sites, switch the text size method to "Measured per element". A few very specific cases may still need rules in the Custom CSS field.

**Can I use the plugin with a page builder?**

Yes. The measured method was designed for pages with inline sizes, which is the usual output of builders such as Elementor and Divi. The text containers used by the adjustments can also be extended with the `simplekitaccessibility_text_selectors` filter.
