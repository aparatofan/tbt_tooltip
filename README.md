# TBT Tooltip

WordPress plugin for inline definition tooltips in The Blue Tree English lessons.
Replaces the old Divi Hacks + jQuery + Magnific Popup mechanism with a small,
self-contained system: pure-CSS hover popovers with a tiny vanilla-JS layer for
tablet tap support and viewport-edge repositioning. No jQuery, no external
libraries, no dependency on Divi's asset framework.

## How it works

1. Install and activate the plugin (`tbt-tooltip/` folder).
2. Go to **Tools → TBT Tooltip** in wp-admin.
3. Paste the lesson text (each line becomes a paragraph).
4. Select the words/phrases that need a tooltip with the mouse.
5. Type the tooltip text for each selected fragment.
6. Click **Generate HTML**, test in the live preview, copy the result.
7. Paste the HTML into a Code block (e.g. Divi Code module / Custom HTML block)
   on the lesson page.

The generated markup looks like:

```html
<p class="tbt-tooltip-paragraph">
  ...text...
  <span class="tbt-tooltip-trigger">
    <span class="tbt-tooltip-trigger-text">taken off</span>
    <span class="tbt-tooltip-bubble">become suddenly popular/successful</span>
  </span>
  ...text...
</p>
```

## Frontend behavior

- **Desktop:** bubble shows on hover — pure CSS, works even if JS fails to load.
- **Tablet:** tap toggles the bubble (`.is-active`); only one bubble open at a
  time; tapping elsewhere closes it.
- Bubbles near the viewport edge are shifted on-screen while the arrow keeps
  pointing at the trigger word.
- Assets (`tbt-tooltip-css`, `tbt-tooltip-js` handles) are enqueued on every
  public page on purpose — there is no shortcode to detect and content scans
  miss Divi Theme Builder layouts. This is what guarantees tooltips never
  silently break on a specific category/template combination.

## Files

```
tbt-tooltip/
├── tbt-tooltip.php                    # plugin bootstrap + frontend enqueue
├── admin/tbt-tooltip-admin.php        # Tools → TBT Tooltip generator page
└── assets/
    ├── css/tbt-tooltip.css            # frontend bubble styles
    ├── css/tbt-tooltip-admin.css      # generator page styles
    ├── js/tbt-tooltip.js              # tap support + edge repositioning
    └── js/tbt-tooltip-admin.js        # generator logic (all client-side)
```

Nothing is stored in the database; the generator is purely client-side.
