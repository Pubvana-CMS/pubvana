# Theme Engine Redesign Spec

**Date:** 2026-03-26
**Status:** Approved design, plan written — ready for implementation

## Problem Statement

The current theme system has three fundamental issues:

1. **Security** — Themes execute arbitrary PHP via `extract() + include`. Theme views contain direct database queries, service calls, session access, and environment variable reads. Any third-party theme has full server access.
2. **Portability** — Theme assets and media uploads rely on filesystem symlinks (`symlinkAssets()`), requiring Apache's `+FollowSymlinks`, correct ownership/permissions, and OS-level symlink support. This breaks on Windows, restrictive shared hosting, and Nginx without explicit config.
3. **Framework coupling** — Widget views are hardcoded to Bootstrap classes. A theme using any other CSS framework gets unstyled widget output.

## Solution Overview

Three interconnected changes:

1. **Asset pipeline** — Replace symlinks with file copies to `FCPATH`. Move media uploads to write directly to `FCPATH`.
2. **Template engine** — Custom `.tpl` engine with zero PHP allowed. Runtime interpreter with page-level output caching via CI4's cache library.
3. **Widget standardization** — Framework-agnostic semantic classes. Widget views become `.tpl` files.

---

## 1. Asset Pipeline

### Theme Assets

Theme assets are copied — not symlinked — from `themes/{name}/assets/` to `FCPATH . 'themes/{name}/'` as real files.

**Triggers:**

- `sync()` — copies all discovered themes (admin themes page visit)
- `activate()` — copies the activated theme
- Marketplace install — copies the newly installed theme

The copy is a clean replace: delete the target directory if it exists, then copy fresh. `symlinkAssets()` is removed entirely. `+FollowSymlinks` is no longer required.

`theme_url()` continues to point at `themes/{name}/` — the URL path is unchanged. The difference is real files behind it instead of symlinks.

### Media Uploads

User-uploaded media writes directly to `FCPATH . 'uploads/'` instead of `WRITEPATH . 'uploads/'`.

The `public/writable/uploads` symlink and `public/writable/` directory are removed.

Stored paths in the database become full web-root-relative (e.g., `/uploads/2026/03/abc123.webp`). URL building becomes `base_url($path)` everywhere — no `writable/` prefix assembly at render time.

All existing render points that prepend `writable/` get updated:
- `MediaService::upload()` return value
- `app/Views/admin/media/index.php` image src
- `app/Views/admin/users/profile.php` avatar src (two references)
- `themes/default/views/partials/author-card.php` avatar src (rewritten to `.tpl` regardless)

`BackupService` currently backs up from `WRITEPATH . 'uploads/'` — updated to back up from `FCPATH . 'uploads/'`. Backup documentation text in `admin/backup/index.php` updated accordingly.

### Documentation

README steps 9 (Theme Assets Symlink) and 10 (Media Uploads Symlink) are rewritten to reflect the new approach. ThemeBuilder.md updated for the new asset path.

---

## 2. Template Engine

### Core Design

A new `TemplateEngine` class that:

- Loads `.tpl` files from the theme's `views/` directory
- Parses and interprets at runtime (no compile-to-PHP step)
- Page-level output caching via CI4's cache library with a configurable TTL (default 2 minutes), can be turned off
- Theme validation handled by `sync()` on admin themes page load (see Section 3)

### Syntax

| Construct | Syntax |
|---|---|
| Output (auto-escaped) | `{{ variable }}` |
| Raw/unescaped output | `{! variable !}` |
| Property/key access | `{{ post.title }}`, `{{ seo.title }}` |
| Conditionals | `{% if condition %}...{% elseif %}...{% else %}...{% endif %}` |
| Loops | `{% for post in posts %}...{% endfor %}` |
| Includes | `{% include 'partials/post-card' with {post: post} %}` — inherits parent scope; `with {}` adds/overrides variables |
| Layout inheritance | `{% extends 'layout' %}` / `{% block name %}...{% endblock %}` |
| Tag functions | `{% lang 'Blog.readMore' %}`, `{% widget_area 'sidebar' %}`, `{% theme_url 'css/theme.css' %}` |
| Filters | `{{ post.published_at \| date('F j, Y') }}`, `{{ post.views \| number_format }}` |
| Default values | `{{ seo.title \| default(site_name) }}` |
| Comparisons | `==`, `!=`, `>`, `<`, `>=`, `<=` |
| Boolean logic | `and`, `or`, `not` |
| Comments | `{# this is a comment #}` |

Unknown variables or filters silently output nothing.

### Whitelisted Tag Functions

- `lang` — localized string lookup
- `theme_url` — URL to theme asset
- `base_url` — CI4 base URL helper
- `widget_area` — render all widgets in a named area
- `post_url` — URL to a blog post
- `category_url` — URL to a category archive
- `tag_url` — URL to a tag archive
- `render_content` — renders markdown or HTML based on content_type

### Whitelisted Filters

- `date` — date formatting (e.g., `date('F j, Y')`)
- `number_format` — number formatting
- `nl2br` — newlines to `<br>`
- `md5` — hash (for Gravatar URLs)
- `count` — array/collection count
- `excerpt` — strip tags and truncate
- `default` — fallback value
- `raw` — bypass auto-escaping (must be the last filter in the chain)
- `strtolower` — lowercase
- `strip_tags` — remove HTML tags

### Data Flow

`ThemeService` owns the full data bag. When a controller calls `$this->themeService->view('home', $pageData)`:

1. ThemeService loads **common data** internally — nav, social links, theme options, site name/tagline, locale, auth state, flash messages, settings (analytics, captcha, comments enabled, etc.)
2. ThemeService merges in the **page-specific data** passed by the controller (posts, pager, seo, json_ld, etc.)
3. ThemeService passes the complete bag to the template engine
4. Engine loads the `.tpl` file, interprets it, returns HTML
5. ThemeService caches the output via CI4's cache library

Controllers become thinner — they pass only page-specific data. `BaseController` no longer builds the common `$this->data` array; ThemeService handles all of that internally.

### Layout Inheritance (extends/block)

Replaces the current `ob_start()` / `theme_view(theme_layout())` two-phase render pattern.

`layout.tpl` defines the HTML shell with named blocks:

```
<head>...{% block head_extra %}{% endblock %}...</head>
<body>
    <main>{% block content %}{% endblock %}</main>
</body>
```

Page views declare which layout they extend and fill the blocks:

```
{% extends 'layout' %}
{% block content %}
    ...page HTML...
{% endblock %}
```

The engine sees `extends`, loads the layout, and replaces block placeholders with the child's block content. No manual output buffering required.

---

## 3. Theme Validation & Security

Zero PHP files allowed in a theme, period.

- `theme_info.php` becomes `theme_info.json`
- View files use `.tpl` extension
- Static assets (CSS, JS, images) are unaffected
- `sync()` runs on every admin themes page load and scans every file in each theme directory for `<?php`, `<?=`, and `<%`
- Themes that fail validation: activate button is disabled, warning shown in the theme's card
- Since `sync()` runs on every page load, modified theme files are caught on the next visit

---

## 4. Widget Standardization

### Framework-Agnostic Output

Widgets output HTML using CSS class variables with semantic defaults. Widget `.tpl` files use `{{ cls_list | default('widget-list') }}`, `{{ cls_badge | default('widget-badge') }}`, etc. Themes can inject their own CSS classes (e.g. Bootstrap's `list-group`, `badge`) through the widget rendering context. If the theme doesn't provide overrides, the semantic defaults apply.

The standard class variable names and their defaults are defined by converting the existing 12 widgets — fix them first, document what emerges.

### Widget Architecture

- Widget PHP class (e.g., `RecentPostsWidget.php`) remains PHP — it is the data provider
- Widget views become `.tpl` files rendered by the template engine
- `BaseWidget::view()` changes internally to use the template engine instead of `extract() + include`
- `widget_info.php` becomes `widget_info.json`
- Widget `.tpl` files can contain `<script>` tags for client-side JS — the `<?` restriction blocks PHP tags only
- No asset registry for now — widgets include JS inline in their `.tpl`

### Paywall Widget

The paywall CTA currently lives at `app/Views/partials/paywall.php` and is called via CI4's native `view()`. It becomes a proper widget with standardized semantic markup, a `.tpl` view, and `widget_info.json`. It is assigned to a widget area (e.g. `before-content`) via the admin UI and rendered through `{% widget_area %}` like every other widget.

---

## 5. Plugin Frontend

Plugins use `.tpl` for any output that renders within the active theme's layout. Same engine, same rules — no PHP in template files.

Plugin backend (routes, migrations, controllers, DB access) stays as PHP — plugins need full server-side capability. The architecture for how plugins register routes, migrations, and config is deferred to a separate spec.

`plugin_info.php` becomes `plugin_info.json` for consistency.

---

## 6. Theme Structure

A complete theme:

```
themes/my_theme/
    theme_info.json
    assets/
        css/theme.css
        js/                    (optional)
        images/                (optional)
        screenshot.png
    views/
        layout.tpl
        home.tpl
        post.tpl
        page.tpl
        category.tpl
        tag.tpl
        archive.tpl
        search.tpl
        partials/
            post-card.tpl
            sidebar.tpl
            pagination.tpl
            author-card.tpl
            comment-form.tpl
            comments-list.tpl
            _comment.tpl
```

Every theme is standalone — no parent/child inheritance. Full set of `.tpl` files required. Zero PHP files in the theme directory.

---

## 7. What Gets Removed

- `symlinkAssets()` method and all calls to it
- `public/themes/` symlink directory (replaced by real copied files)
- `public/writable/uploads` symlink and `public/writable/` directory
- `+FollowSymlinks` requirement from `.htaccess`
- Parent/child theme support (`parent` key in manifests, fallback logic in `ThemeService::view()`)
- `extract() + include` rendering in `ThemeService::view()` and `BaseWidget::view()`
- `ob_start()` / `theme_view(theme_layout())` boilerplate from all theme views
- Direct DB queries from theme view files
- `theme_info.php`, `widget_info.php`, `plugin_info.php` (replaced by `.json`)
- Common data building from `BaseController::initController()` (moves into ThemeService)
- `view('partials/paywall')` call from theme post views (becomes a widget assigned to a widget area)
- README steps 9 and 10 (symlink setup)

---

## 8. What Gets Updated

- `ThemeService` — owns full data bag, loads theme options, runs engine, handles page caching, `publishAssets()` replaces `symlinkAssets()`, `discover()` reads `theme_info.json`
- `BaseWidget` — `view()` uses engine instead of `extract() + include`, reads `widget_info.json`
- `WidgetService` — `discover()` reads `widget_info.json`
- `MediaService` — writes to `FCPATH . 'uploads/'`, stores full web-root-relative paths
- `theme_url()` helper — unchanged URL structure, now points at real files instead of symlinks
- Three media render points — use `base_url($path)` directly, no `writable/` prepend
- `BackupService` — backs up from `FCPATH . 'uploads/'` instead of `WRITEPATH . 'uploads/'`
- `app/Views/admin/backup/index.php` — documentation text updated for new upload path
- `MarketplaceService` — calls `publishAssets()` instead of `symlinkAssets()`
- All 12 widget views — converted to `.tpl` with standardized semantic classes
- All theme views (default, ember, cyborg, darkly, flatly, lux, sandstone, slate) — converted to standalone `.tpl` themes with full view sets. All 8 themes get full `lang()` support — default and others were not brought along when i18n was added
- `ThemeBuilder.md` — rewritten for `.tpl` syntax, `theme_info.json`, new asset pipeline, standardized widget classes
- `WidgetBuilder.md` — new document covering widget structure, `widget_info.json`, data provider pattern, `.tpl` views, standardized semantic classes, JS guidelines
- `README.md` — symlink steps removed, updated for new setup

---

## 9. What Gets Created

- `TemplateEngine` class — runtime interpreter: lexer, parser, evaluator, block/extends handling, filter/tag dispatch
- Whitelisted tag functions — `lang`, `theme_url`, `base_url`, `widget_area`, `post_url`, `category_url`, `tag_url`, `render_content`
- Whitelisted filters — `date`, `number_format`, `nl2br`, `md5`, `count`, `excerpt`, `default`, `raw`, `strtolower`, `strip_tags`
- Theme validation logic in `sync()` — scans theme files for `<?` / `<%` on every admin themes page load
- `publishAssets()` method — recursive directory copy to `FCPATH . 'themes/{name}/'`
- Paywall widget — new widget with standardized markup, `.tpl` view, `widget_info.json`
- Standardized widget CSS class vocabulary — documented after converting the 12 widgets

---

## 10. Out of Scope (Deferred)

- Admin views — decision pending
- Plugin backend architecture (routes, migrations, config discovery)
- Widget/plugin asset registry — revisit if a vulnerability is identified
