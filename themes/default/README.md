# Default Theme

The default Pubvana theme. Bootstrap 5 Bootswatch Flatly, with a navbar, optional hero, breadcrumbs, a sidebar region, a multi-column footer, and a page template for every public content type.

## Overview

- Renders public content through one master layout: navbar, hero, breadcrumbs, page content, sidebar blocks, footer.
- Provides page templates for the blog, static pages, search, categories, tags, and user profiles.
- Renders error pages, including 404, in the theme.
- Exposes a **sidebar** region and three **footer column** regions for blocks.
- Serves its own Bootstrap 5 assets at `/assets/theme/default/...`.
- Overrides the block templates for all core and plugin blocks
- Shows flash messages on the public side.

## Theme Options

Manage options in **Admin > Appearance > Themes > Options**. The admin form groups them. The group name affects the form layout only.

| Group | Option | Type | Default | Purpose |
|-------|--------|------|---------|---------|
| Layout | Sidebar Location | select | `sidebar-right` | Which side the sidebar renders on: `sidebar-right`, `sidebar-left` |
| Layout | Show Sidebar On | select | `not_home` | Pages that show the sidebar region: `all`, `not_home`, `home`, `none` |
| Breadcrumbs | Show Breadcrumbs | toggle | on | Breadcrumb trail on subpages |
| Hero | Show Hero | toggle | off | Hero section below the navbar |
| Hero | Background Image | media | (none) | Hero background image |
| Hero | Title | input | (none) | Hero title text |
| Footer Bottom | Footer Bottom | toggle | on | Bottom strip of the footer with the copyright line and `<hr>` |
| Footer Bottom | Footer Text | input | (none) | Copyright or site text, falls back to the site copyright setting when blank |

### Layout behavior

- **Sidebar Location** sets the sidebar side: left or right.
- **Show Sidebar On** decides whether the sidebar renders: all pages, all pages except home, home only, or nowhere. Static pages follow this option like any other page.

## Regions

Place blocks in regions under **Admin > Appearance > Themes > Regions**.

| Region ID | Label | Where it renders |
|-----------|-------|------------------|
| `sidebar` | Sidebar | Column next to the page content. Shown per the Show Sidebar On option |
| `footer-col-1` | Footer Column 1 | First column of the footer |
| `footer-col-2` | Footer Column 2 | Second column of the footer |
| `footer-col-3` | Footer Column 3 | Third column of the footer |

The theme also uses the platform regions (`before-content`, `after-content`, `footer`) from the core.

## Templates

The theme includes a template for every public view and block. Templates are Vision `.tpl` files and can't execute PHP. `layout.tpl` is the main file that must be used. Other page templates hold content only, and `PublicController` injects their output as `content`.

## Minimum Files

A bare minimum theme can be created with only the two required files: `layout.tpl` and `pubvana.json`. 

For further help creating themes you should find the [Theme Docs](https://pubvanacms.com/docs/dev/v/3.0/dev-for-pubvana/themes) helpful.



## Assets

- CSS: `assets/css/bootstrap.min.css`, `assets/css/pubvana.css`
- JS: `assets/js/bootstrap.bundle.min.js`
- Icon: `icon.svg` (shown in the admin theme picker)

The AssetService serves these at `/assets/theme/default/{path}`. It reads them from the theme's `assets/` folder.

## Accessibility

The theme targets WCAG 2.2 AA. Anything a replacement theme swaps out has to
hold that line. This is what the theme supplies and what a replacement must
keep. See `ACCESSIBILITY.md` for the audit and the limits of the claim.

### Shell

- `layout.tpl` opens with a skip link to `#main`. The `<main>` element carries
  `id="main"` and `tabindex="-1"` so the link has somewhere to land.
- The navbar is `<nav aria-label="Main">`. The mobile toggler carries
  `aria-label`, `aria-controls`, and `aria-expanded`.
- The sidebar column is `<aside aria-label="Sidebar">`.
- The footer sets its own surface through `footer.bg-dark`. White text on it
  measures 11.1:1.
- `<html lang="en">` is hardcoded. There is no locale setting to read, so a
  theme in another language has to change that line itself.

### Headings

- One `<h1>` per page, owned by the content template: `post.tpl`, `page.tpl`,
  `search.tpl`, `archive.tpl`, `categories.tpl`, `tags.tpl`, `profile.tpl`.
- `home.tpl` prints a visually hidden `<h1>` because the design has no visible
  page title.
- `partials/hero.tpl` prints a `<p>`, not a heading. An `<h1>` there gives every
  hero page two of them.
- Block titles are `<h3>`. A block in `before-content` or the sidebar sits above
  the page `<h1>`, so the outline skips a level there.

### Colour

Every ratio in `pubvana.css` is measured against the surface it actually sits
on, and the comment beside the rule says which. The short version: Flatly's link
colour measures 2.41:1 on white and its secondary grey 2.55:1, so both are
overridden. Re-measure before changing a colour.

### Focus

Every focus indicator is a white outline plus a dark outer ring, so it stays
visible on the white page and on the dark navbar and footer. The rules use
`:focus-visible`, so a mouse click does not draw a ring.

### Forms

- Every field carries a real `<label for>`. A placeholder is not a label.
- The guest comment fields carry `autocomplete` tokens.
- Input borders are `#767676`, which measures 4.54:1. Flatly's `#dee2e6`
  measures 1.30:1 and misses the 3:1 that 1.4.11 asks for.
- The captcha widget prints a visible instruction and wraps the provider's div
  in a labelled `role="group"`.

