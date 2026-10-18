# Accessibility

Pubvana targets **WCAG 2.2 level AA** for the admin UI and the default theme.

## Covered

- Admin UI: every page under `/admin`.
- Default theme: `themes/default`, every public page it renders.
- Public forms that come with the plugins: comments, Forms, Profiles,
  Search.

## Not Covered

- Other themes: A theme is markup the site owner installs. Pubvana
  documents it in `themes/default/README.md`.
- Author content: What someone typed into the editor. Sanitizing keeps the
  attributes (see `app/Services/HtmlPurifierFactory.php`). It cannot add alt
  text or a heading structure.
- Debug bar: (on development sites) It renders on `APP_ENV=development` and fails the target
  size rule.

## How it was verified

**Method:** source review plus axe-core 4.14.0 in a browser, against the
`wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, and `wcag22aa` tags.

**Pages:** `/`, a single post, `/search`, `/author` archives, a public profile,
`/auth/login`, `/auth/register`, `/auth/forgot`, and the admin pages for
dashboard, blog, pages, media, comments, forms, navigation, themes, theme
regions, users, plugins, and settings.

The contrast ratios in `themes/default/assets/css/pubvana.css` and
`public/admin-assets/css/admin.css` come from measurement in the browser.

### Running the audit

Open the page and its developer console, then paste:

```js
const s = document.createElement('script');
s.src = 'https://cdn.jsdelivr.net/npm/axe-core@4.14.0/axe.min.js';
s.onload = async () => {
  const r = await axe.run(document, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] }
  });
  console.table(r.violations.flatMap(v => v.nodes.map(n => ({
    rule: v.id, impact: v.impact, target: n.target.join(' '),
    detail: (n.any[0] && n.any[0].message) || ''
  }))));
};
document.head.appendChild(s);
```

The Content-Security-Policy allows `cdn.jsdelivr.net`, so the browser will fetch
the script. Hard refresh first: AssetService sends a one day cache header for
theme CSS.

Admin pages need a signed-in session. Sign in, then run this.

## Known limitations

1. **Page language is fixed at `en`.** Both layouts hardcode `<html lang="en">`,
   and no setting exists to change it. A site in another language fails 3.1.1
   until one does. Locale/Language support is planned.
2. **Block titles are `<h3>`.** A block placed in `before-content` or the sidebar
   sits above the page `<h1>` in the document, so the outline skips a level.
3. **Region ordering is drag only.** `admin/themes/regions` reorders placements
   with HTML5 drag events and no keyboard path, which is 2.5.7 Dragging
   Movements. The fix is an up and down button pair.
4. **Settings tab URLs render a fragment.** Requesting `/admin/settings/general`
   returns the tab body with no `<html lang>` and no `<title>`. The correct
   entry point is `/admin/settings`.
5. **CAPTCHA.** hCaptcha and reCAPTCHA both render an audio alternative inside
   their widget, which is what 3.3.8 asks for. The site owner chooses the
   provider.

## Statement for site owners

Paste this into a page, fill in the bracketed parts, and link it from the
footer. Delete any line that is not true for your site.

```markdown
# Accessibility statement

[SITE NAME] targets the Web Content Accessibility Guidelines (WCAG) 2.2
level AA.

## Site Software

The site runs Pubvana CMS with the [THEME NAME] theme. Pubvana CMS targets
WCAG 2.2 AA for the administration area, its own default theme, and other themes it produces.
[SITE NAME] is responsible for the theme in use here and for the content
published on it.

## Feedback

Use the [contact form](/page/contact) to report a barrier. Include the page address
and what went wrong. We reply within [N] working days.

## Known issues

- Content entered through the editor depends on who created it. Images need
  alternative text, and headings need to be in order.

This statement was last reviewed on [DATE].
```
