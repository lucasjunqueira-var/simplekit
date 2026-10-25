# Simple Kit Accessibility

Add reading tools to the pages and posts of your website: text size, line spacing, letter spacing and high contrast, controlled from a small floating panel.

## Highlights

- Four adjustments with increase, decrease and reset: text size, line spacing and letter spacing, plus a high contrast toggle.
- Floating button in eight positions, with configurable size, shape, colors, optional image and optional text.
- Works with themes that declare sizes in pixels, not only with themes based on rem or em.
- Optional per browser memory, and a per content switch to hide the button.

## How the plugin works

After activating the plugin, open the Setup page in the SK Accessibility menu and choose where the button appears.

[]

Adjust the button: optional text, accessible name, optional image, size, shape, background color, text color and hover color. When neither an image nor a text is provided, the plugin uses the universal access icon.

[]

Configure the panel: background, text and border colors, font size, width and corner radius.

[]

Replace the texts shown in the panel, from its title to the labels of each action.

[]

Fine tune the position. Positions anchored to a side use the edge distances; positions centered on an axis use the center shift, where negative values move the button up or to the left.

[]

Visitors click the button to open the panel and adjust the page. By default nothing is saved between pages; the Setup page can remember the choices in the browser or only during the visit.

[]

To hide the button on a specific page, post or custom post type, uncheck the option in the "Simple Kit Accessibility" box of the editor.

[]

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

## Text size methods

The default method applies a multiplier to a curated list of text containers. The measured method reads the size of every text element in the browser and applies the factor over the original size, which keeps the typographic hierarchy of themes that use pixels and of pages built with page builders. The method is selected on the Setup page.

## Frequently asked questions

**Does the plugin change the theme files?**

No. The adjustments are applied by classes on the `html` element and, in the measured method, by inline font sizes that are restored when the visitor returns to the original state.

**Are the visitor choices sent to the server?**

No. When the "Remember choices" option is enabled, the state is stored only in the browser, using local or session storage.

**Why does the text size not change on some themes?**

The default method multiplies the size inherited from the parent element. Themes that declare sizes in pixels keep working, but the original size of each element is not preserved. On those sites, switch the text size method to "Measured per element". A few very specific cases may still need rules in the Custom CSS field.

**Can I use the plugin with a page builder?**

Yes. The measured method was designed for pages with inline sizes, which is the usual output of builders such as Elementor and Divi. The text containers used by the adjustments can also be extended with the `simplekitaccessibility_text_selectors` filter.
