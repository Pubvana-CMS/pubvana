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
