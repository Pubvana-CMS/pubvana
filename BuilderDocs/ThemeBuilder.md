# ThemeBuilder — Pubvana Theme Development Guide

This document covers everything needed to build a Pubvana theme. It is self-contained — no other documents are required.

---

## 1. Theme Directory Structure

Each theme lives in its own folder under `themes/`. **No PHP files.** Every view is a `.tpl` file rendered by the template engine. The only non-`.tpl` files are `theme_info.json` and static assets.

```
themes/my_theme/
    theme_info.json
    assets/
        css/
            theme.css
        js/                     (optional)
        images/                 (optional)
        screenshot.png
    views/
        layout.tpl              outer HTML shell (head, nav, footer, JS)
        home.tpl                blog index / front page
        post.tpl                single post
        page.tpl                static page
        category.tpl            category archive
        tag.tpl                 tag archive
        archive.tpl             date archive
        search.tpl              search results
        profile.tpl             user account profile (logged-in users)
        partials/
            post-card.tpl       post listing card
            sidebar.tpl         sidebar wrapper
            pagination.tpl      pagination wrapper
            author-card.tpl     author bio card on posts
            comment-form.tpl    comment submission form
            comments-list.tpl   comment thread
            _comment.tpl        single comment (recursive for replies)
```

All 9 views and 7 partials should be present in a complete theme. Zero PHP files in the theme directory — any theme containing PHP will fail validation and cannot be activated.

---

## 2. theme_info.json Format

```json
{
    "name": "My Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "author_url": "https://yoursite.com",
    "support_url": "https://yoursite.com/support",
    "description": "One line description.",
    "screenshot": "screenshot.png",
    "free": true,
    "bundled": false,
    "css_framework": "Bootstrap",
    "css_frame_ver": "5.x",
    "icon_pack": "FontAwesome",
    "icon_pack_ver": "6.x",
    "js_framework": "Bootstrap",
    "js_framework_ver": "5.x",
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
    "update_url": "https://yoursite.com/api/update/check",
    "widget_areas": {
        "sidebar": "Main Sidebar",
        "footer-1": "Footer Column 1",
        "footer-2": "Footer Column 2",
        "footer-3": "Footer Column 3",
        "before-content": "Before Content"
    },
    "options": {
        "show_sidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": "1"
        },
        "footer_copyright": {
            "type": "text",
            "label": "Footer Copyright Text",
            "default": ""
        }
    }
}
```

### Required Fields

| Key | Required | Description |
|-----|----------|-------------|
| `name` | yes | Human-readable theme name |
| `version` | yes | Semantic version |
| `author` | yes | Author name |
| `css_framework` | yes | CSS framework name (e.g. `Bootstrap`, `DaisyUI`, `Tailwind`) |
| `css_frame_ver` | yes | CSS framework version (e.g. `5.x`) |
| `icon_pack` | yes | Icon library name (e.g. `FontAwesome`, `BootstrapIcons`, `Tabler`) |
| `icon_pack_ver` | yes | Icon library version (e.g. `6.x`) |
| `js_framework` | yes | JS framework name (e.g. `Bootstrap`, `AlpineJS`, `jQuery`, `none`) |
| `js_framework_ver` | yes | JS framework version (e.g. `5.x`, `3.x`). Empty string if `js_framework` is `none` |
| `author_url` | `""` | Author's website URL (linked in admin) |
| `support_url` | `""` | Support/contact URL shown in admin when theme is incompatible or has issues |
| `description` | `""` | Short description |
| `free` | `false` | Set to `true` if your theme is free. Free third-party themes activate without a license check. |
| `min_pubvana_version` | `""` | Minimum Pubvana version required |
| `max_pubvana_version` | `""` | Maximum Pubvana version this theme is compatible with |

### Optional Fields

| Key | Default | Description |
|-----|---------|-------------|
| `screenshot` | `""` | Filename relative to theme root, shown in admin |
| `bundled` | `false` | Reserved for Pubvana-authored addons that ship with Pubvana CMS. Third-party themes must not set this to `true`, this will break your updates. |
| `update_url` | `""` | API endpoint for update checks. Themes without this cannot be updated through admin. |
| `widget_areas` | `{}` | Object mapping area slugs to human labels. DB rows created on activation. |
| `css_class_mapping` | `{}` | Object mapping `cls_` variable names to CSS classes. Injected into all widgets. See Section 10. |
| `options` | `{}` | Admin-editable theme options. Types: `text`, `checkbox`, `textarea`, `select`, `color`, `number` |

### Third-Party Store & License Fields

These fields are only relevant if you sell your theme and run your own store/license API. See [ThirdPartyAddons.md](ThirdPartyAddons.md) for the full API protocol specification and pre-built Pubvana CMS Plugin.

| Field | Type | Description |
|-------|------|-------------|
| `license_validate_url` | string | Endpoint Pubvana CMS POSTs to when a site admin enters a license key for your theme. |
| `license_check_url` | string | Endpoint Pubvana CMS POSTs to for periodic license revalidation (90-day cycle). |
| `item_url` | string | Endpoint Pubvana CMS GETs to resolve your theme's numeric product ID (`{item_url}/{folder}`). |
| `store_url` | string | URL to your storefront page for this theme (linked in admin for license renewal). |

#### Future Pubvana release support

| Field | Type | Description |
|-------|------|-------------|
| `items_url` | string | Catalog listing endpoint (used by marketplace integrations). |
| `categories_url` | string | Category listing endpoint. |
| `categories_all_url` | string | Full category listing with products. |
| `category_url` | string | Single category endpoint. |
| `featured_url` | string | Featured products endpoint. |
| `update_check_url` | string | Alternative update check endpoint (if different from `update_url`). |
| `download_url` | string | Direct download endpoint for updates. |

> **For most third-party themes:** You only need `free`, `update_url`, `license_validate_url`, `license_check_url`, `item_url`, and `store_url`. The remaining URL fields are for full marketplace integrations. If your theme is free, only `free: true` and optionally `update_url` are needed if you provide updates.

**Notes:**
- `min_pubvana_version` / `max_pubvana_version` — Used by the update system to prevent incompatible core updates and to find the right release version.
- `update_url` — Pubvana CMS POSTs here when checking for updates. Pubvana-built themes use `https://pubvana.net/api/dstore/v1/update/check`. Third-party developers provide their own endpoint implementing the same protocol.
- `support_url` — Displayed in the admin UI.

Option values are stored in the `theme_options` table and available as variables in all theme views (see Section 6).

### Widget areas

Each slug declared in `widget_areas` becomes an assignable area in the admin Widgets page. To render an area in your theme, use the `{% widget_area %}` tag function with the matching slug:

```
{% widget_area 'sidebar' %}
{% widget_area 'footer-1' %}
{% widget_area 'before-content' %}
```

Areas are created in the database when the theme is activated. The admin can then drag widgets into each area and reorder them. If no widgets are assigned to an area, `{% widget_area %}` outputs nothing.

### Option type details

All option definitions support an optional `help` sub-key — rendered as hint text below the input.

**`select`** requires a `choices` object mapping values to display labels:

```json
"layout_style": {
    "type": "select",
    "label": "Layout Style",
    "choices": {
        "wide": "Wide",
        "boxed": "Boxed",
        "narrow": "Narrow"
    },
    "default": "wide"
}
```

**`color`** renders an HTML color picker:

```json
"accent_color": {
    "type": "color",
    "label": "Accent Color",
    "default": "#f59e0b",
    "help": "Used for links, buttons, and highlights"
}
```

**`number`** supports optional `min` and `max` constraints:

```json
"posts_per_page": {
    "type": "number",
    "label": "Posts Per Page",
    "default": "10",
    "min": 1,
    "max": 50
}
```

---

## 3. Asset Pipeline

Theme assets are copied from `themes/{name}/assets/` to `public/themes/{name}/` as real files.

`ThemeService::publishAssets()` runs on:
- Theme activation
- Admin themes page visit (`sync()`)
- Marketplace install

The copy is a clean replace: removes the existing target directory, then recursively copies fresh. `theme_url()` points at `public/themes/{name}/` — the URL path is unchanged, real files behind it.

**In your layout.tpl:**
```
<link rel="stylesheet" href="{% theme_url 'css/theme.css' %}">
```

A theme controls its own `layout.tpl` entirely and can load any CSS framework from CDN (Bootstrap, Tailwind, Bulma, none).

---

## 4. .tpl Syntax Reference

All theme views are `.tpl` files. No PHP allowed. The template engine provides:

### Output Tags

| Syntax | Description |
|--------|-------------|
| `{{ variable }}` | Escaped output (HTML entities) |
| `{! variable !}` | Raw output (no escaping) — use for trusted HTML only |
| `{{ obj.property }}` | Dot notation for object/array access |
| `{{ var \| filter }}` | Apply a filter |
| `{{ var \| filter1 \| filter2 }}` | Filter chain (left to right) |
| `{{ var \| filter('arg') }}` | Filter with arguments |

### Control Flow

```
{% if condition %}
    ...
{% elseif other_condition %}
    ...
{% else %}
    ...
{% endif %}

{% for item in collection %}
    {{ item.name }}
{% endfor %}
```

**Truthiness:** `null`, `false`, `''`, `0`, `[]` are falsy. Everything else is truthy.

### Operators

| Operator | Example |
|----------|---------|
| `==`, `!=`, `>`, `<`, `>=`, `<=` | `{% if count > 0 %}` |
| `and`, `or` | `{% if a and b %}` |
| `not` | `{% if not hidden %}` |

### Includes

```
{% include 'partials/post-card' %}
{% include 'partials/post-card' with {post: post, show_date: true} %}
```

Includes inherit the parent scope. `with {}` adds or overrides variables. Paths are relative to the theme's `views/` directory.

### Comments

```
{# This is a comment — stripped from output #}
```

---

## 5. Layout Inheritance — extends / block

**layout.tpl** defines the HTML shell with named blocks:

```
<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ page_title | default(site_name) }}</title>
    {% if seo.description %}
    <meta name="description" content="{{ seo.description }}">
    {% endif %}
    {% if seo.og_title %}
    <meta property="og:title" content="{{ seo.og_title }}">
    <meta property="og:description" content="{{ seo.og_description }}">
    {% endif %}
    {% if seo.og_image %}
    <meta property="og:image" content="{{ seo.og_image }}">
    {% endif %}
    <link rel="alternate" type="application/rss+xml" title="{{ site_name }} RSS Feed" href="{% site_url 'feed' %}">
    <link rel="alternate" type="application/atom+xml" title="{{ site_name }} Atom Feed" href="{% site_url 'atom' %}">
    <link rel="stylesheet" href="{% theme_url 'css/theme.css' %}">
    {% block head_extra %}{% endblock %}
    {% if json_ld %}
    <script type="application/ld+json">{! json_ld !}</script>
    {% endif %}
    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>
        window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
        gtag('js',new Date());gtag('config','{{ analytics_id | raw }}');
    </script>
    {% endif %}
</head>
<body>
    {% if preview_mode %}
        <div class="preview-banner">{% lang 'Blog.previewModeBanner' %}</div>
    {% endif %}

    <nav>
        <a href="{% site_url '' %}">{{ site_name }}</a>
        {% for item in primary_nav %}
            <a href="{{ item.url }}" target="{{ item.target }}">{{ item.label }}</a>
        {% endfor %}
        {# Auth icons — after search bar, right side of navbar #}
        {% if is_logged_in %}
            <a href="{% site_url 'accounts/profile' %}" title="{% lang 'Blog.profileTitle' %}"><i class="fas fa-user-pen"></i></a>
            {% if can_access_admin %}
            <a href="{% base_url 'admin' %}" title="{% lang 'Blog.adminPanel' %}"><i class="fas fa-user-gear"></i></a>
            {% endif %}
        {% else %}
            <a href="{% site_url 'login' %}" title="{% lang 'Blog.login' %}"><i class="fas fa-lock-open"></i></a>
        {% endif %}
    </nav>

    {% if flash_success %}
        <div class="alert-success">{{ flash_success }}</div>
    {% endif %}
    {% if flash_error %}
        <div class="alert-error">{{ flash_error }}</div>
    {% endif %}

    {% widget_area 'before-content' %}
    <main>
        {% block content %}{% endblock %}
    </main>

    <footer>
        {% widget_area 'footer-1' %}
        {% widget_area 'footer-2' %}
        {% widget_area 'footer-3' %}
        <p>{{ footer_copyright | default(site_name) }} &copy; {{ 'now' | date('Y') }}. {% lang 'Blog.allRightsReserved' %}</p>
    </footer>

</body>
</html>
```

**Page views** extend the layout and fill blocks:

```
{% extends 'layout' %}
{% block content %}
    <h1>{% lang 'Blog.latestPosts' %}</h1>
    {% for post in posts %}
        {% include 'partials/post-card' with {post: post} %}
    {% endfor %}
{% endblock %}
```

The engine loads the child, collects its block content, loads the parent (layout), and replaces `{% block %}` placeholders with the child's content.

---

## 6. Common Data — Variables Available in Every View

`ThemeService::buildCommonData()` injects these into every view automatically. Controllers do not need to pass them.

| Variable | Type | Description |
|----------|------|-------------|
| `page_title` | string | Browser tab title — "Post Title - Site Name" or just "Site Name" on homepage |
| `theme` | object or null | Active theme DB row |
| `site_name` | string | From `site_name()` helper |
| `site_tagline` | string | From `site_tagline()` helper |
| `locale` | string | Request locale (e.g. `en`, `es`) |
| `primary_nav` | array of objects | Nav items: `.url` (already locale-prefixed), `.label`, `.target` |
| `footer_nav` | array of objects | Nav items: `.url` (already locale-prefixed), `.label`, `.target` |
| `social_links` | array of objects | Social links: `.url`, `.icon` (FA class), `.platform` |
| `plugin_menu_items` | array | Plugin-contributed nav items |
| `is_logged_in` | bool | Whether a user is authenticated |
| `can_access_admin` | bool | Whether the logged-in user has `admin.access` permission (false when logged out) |
| `flash_success` | string or null | Session flash message (success) |
| `flash_error` | string or null | Session flash message (error) |
| `analytics_id` | string | Google Analytics tracking ID (empty if not set) |
| `sitemap_enabled` | bool | Whether sitemap is enabled |
| `comments_enabled` | bool | Whether comments are enabled site-wide |
| `comment_moderation` | bool | Whether new comments need approval |
| `hcaptcha_site_key` | string | hCaptcha site key (empty if not configured) |
| `csrf_field` | string | Pre-built CSRF hidden input tag — use `{! csrf_field !}` in forms |
| `csrf_token_name` | string | CSRF token field name (for manual form building) |
| `csrf_token_value` | string | CSRF token hash value (for manual form building) |
| `lang_switcher` | array | Language switcher data (`buttons`, `dropdown`, `ul` formats) |
| _(theme option keys)_ | string | All theme options from `theme_options` table (e.g. `show_sidebar`, `footer_copyright`) |

**Theme options** are available as top-level variables using their option key names. For example, if `theme_info.json` declares `show_sidebar` and `footer_copyright`, your `.tpl` can use `{{ show_sidebar }}` and `{{ footer_copyright }}` directly.

---

## 7. Page-Specific Variables per View

Controllers pass only page-specific data. These are merged with common data (Section 6) by ThemeService.

**`json_ld`** is passed by several controllers (post, category, tag, archive). Rather than rendering it in each page view, place the JSON-LD output in `layout.tpl` `<head>` — it's a no-op on pages that don't provide it:

```
{% if json_ld %}
<script type="application/ld+json">{! json_ld !}</script>
{% endif %}
```

### home.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `posts` | array of objects | Published posts, paginated, newest first |
| `pager_links` | string | Pre-rendered pagination HTML |
| `seo` | array | `title`, `description`, `og_title`, `og_description`, `og_image` |

### post.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `post` | object | `.title`, `.slug`, `.content`, `.body`, `.excerpt`, `.featured_image`, `.published_at`, `.views`, `.author_id`, `.is_premium`, `.content_type` |
| `comments` | array | Nested tree, each has `.children` |
| `author_profile` | object or null | `.display_name`, `.bio`, `.avatar`, `.website`, `.twitter`, `.facebook`, `.linkedin`, `.email`, `.username` |
| `seo` | array | SEO metadata |
| `json_ld` | string | Pre-encoded JSON-LD Article markup |
| `paywall` | bool | True when post is premium and user not logged in |
| `reading_time` | int | Estimated minutes to read |
| `preview_mode` | bool | True when accessed via preview token |

### page.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `page` | object | `.title`, `.content`, `.content_type` |
| `seo` | array | SEO metadata |

### category.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `category` | object | `.name`, `.slug`, `.description` |
| `posts` | array of objects | Paginated posts in this category |
| `pager_links` | string | Pre-rendered pagination HTML |
| `seo` | array | SEO metadata |
| `json_ld` | string | BreadcrumbList JSON-LD |

### tag.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `tag` | object | `.name`, `.slug` |
| `posts` | array of objects | Paginated posts with this tag |
| `pager_links` | string | Pre-rendered pagination HTML |
| `seo` | array | SEO metadata |
| `json_ld` | string | BreadcrumbList JSON-LD |

### archive.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `year` | int | Archive year |
| `month` | int | Archive month |
| `archive` | object | `.title` (e.g. "March 2026") |
| `posts` | array of objects | Paginated posts in this period |
| `pager_links` | string | Pre-rendered pagination HTML |
| `seo` | array | SEO metadata |
| `json_ld` | string | BreadcrumbList JSON-LD |

### search.tpl

| Variable | Type | Description |
|----------|------|-------------|
| `query` | string | The search query (from `?q=`) |
| `posts` | array of objects | Matching posts, or empty if no query |
| `pager_links` | string or null | Pre-rendered pagination HTML |
| `seo` | array | SEO metadata |

### profile.tpl

User account profile page, shown at `/accounts/profile` for logged-in users. All users see basic fields (username, email, password). Authors and above also see the full author profile fields.

| Variable | Type | Description |
|----------|------|-------------|
| `user` | object | Shield user object (`user.username`, `user.id`) |
| `email` | string | Current email address |
| `profile` | object or null | Author profile (display_name, bio, avatar, website, twitter, facebook, linkedin) — null for non-authors |
| `is_author` | bool | True if user is author, editor, admin, or superadmin |
| `seo` | array | SEO metadata |

The avatar upload uses a separate form (POST to `/accounts/avatar` with `enctype="multipart/form-data"`). The profile form POSTs to `/accounts/profile`.

---

## 8. Tag Functions — Complete Reference

Tag functions output strings directly into the template. Arguments are space-separated. Quoted strings are literal values; unquoted identifiers are variable references.

| Function | Signature | Description |
|----------|-----------|-------------|
| `lang` | `{% lang 'Blog.key' %}` | Localized string lookup |
| `lang` | `{% lang 'Blog.key' arg1 arg2 %}` | With placeholder substitution (`{0}`, `{1}`) |
| `site_url` | `{% site_url 'path' %}` | Locale-aware URL — prepends locale prefix for non-default locales (e.g. `/es/path`) |
| `base_url` | `{% base_url 'path' %}` | Plain base URL + path — **no locale prefix**. Use for assets, hreflang tags, and lang switcher URLs only |
| `theme_url` | `{% theme_url 'css/theme.css' %}` | Active theme's asset URL |
| `post_url` | `{% post_url slug_var %}` | Locale-aware blog post URL: `/blog/{slug}` or `/es/blog/{slug}` |
| `category_url` | `{% category_url slug_var %}` | Locale-aware category URL: `/category/{slug}` or `/es/category/{slug}` |
| `tag_url` | `{% tag_url slug_var %}` | Locale-aware tag URL: `/tag/{slug}` or `/es/tag/{slug}` |
| `widget_area` | `{% widget_area 'sidebar' %}` | Render all widgets assigned to this area |
| `render_content` | `{% render_content entity_var %}` | Render entity's content (Markdown or HTML based on `.content_type`) |

### Examples

```
{% lang 'Blog.readMore' %}
{% lang 'Blog.views' post.views|number_format %}
{% site_url 'login' %}                    {# locale-aware route URL #}
{% base_url post.featured_image %}        {# asset path — no locale prefix #}
{% theme_url 'images/logo.png' %}
{% post_url post.slug %}
{% widget_area 'sidebar' %}
{% render_content post %}
```

### `site_url` vs `base_url`

This give your theme language/locale support.

Use `{% site_url %}` for **all route paths** (pages, feeds, login, search, etc.). It automatically prepends the locale prefix when the request is in a non-default language (e.g. `/es/blog` for Spanish).

Use `{% base_url %}` only for:  
- **Asset paths** — `{% base_url post.featured_image %}`, `{% base_url author_profile.avatar %}`
- **hreflang tags** — `{% base_url btn.url %}` (lang switcher URLs are already locale-prefixed)
- **hreflang x-default** — `{% base_url '' %}` (intentionally the bare site root)

---

## 9. Filters — Complete Reference

Filters transform values. Chain with `|`. Arguments in parentheses.

| Filter | Signature | Description |
|--------|-----------|-------------|
| `date` | `value \| date('F j, Y')` | Format a date string or timestamp |
| `number_format` | `value \| number_format` or `value \| number_format(2)` | Format number with thousands separator |
| `nl2br` | `value \| nl2br` | Convert newlines to `<br>` tags |
| `md5` | `value \| md5` | MD5 hash (useful for Gravatar URLs) |
| `count` | `value \| count` | Count array/countable items |
| `excerpt` | `value \| excerpt(150)` | Strip tags and truncate with ellipsis |
| `default` | `value \| default('fallback')` | Fallback if value is null, empty, or false. Argument can be a variable. |
| `raw` | `value \| raw` | Skip HTML auto-escaping. **Must be the last filter in a chain.** |
| `strtolower` | `value \| strtolower` | Convert to lowercase |
| `strip_tags` | `value \| strip_tags` | Remove HTML tags |

### The `raw` filter

`{{ }}` auto-escapes by default. The `raw` filter tells the engine to skip escaping. Use only for trusted content. Must be last in a chain:

```
{{ profile.bio | nl2br | raw }}
{{ analytics_id | raw }}
```

For untrusted HTML, use `{! variable !}` raw output tags (but prefer `{{ }}` with escaping).

---

## 10. Widget Styling from Themes — The `cls_` Pattern

Widgets, plugins, and pagination output HTML with semantic CSS class defaults. Themes override these by declaring a `css_class_mapping` object in `theme_info.json`. These values are injected automatically at render time.

### How It Works

Every CSS class in a widget `.tpl` is a variable with a `default()` fallback:

```
<div class="{{ cls_widget | default('widget widget-categories') }}">
```

If the theme provides `cls_widget` via `css_class_mapping`, that value is used. If not, the semantic default applies.

### Declaring `css_class_mapping` in theme_info.json

```json
{
    "name": "My Theme",
    "css_class_mapping": {
        "cls_list": "list-group list-group-flush",
        "cls_list_item": "list-group-item d-flex justify-content-between align-items-center",
        "cls_button": "btn btn-sm btn-primary",
        "cls_tag": "badge bg-light text-dark text-decoration-none"
    }
}
```

Only override the classes you need. Any `cls_` variable not in `css_class_mapping` falls back to its semantic default, which your theme's `theme.css` should style.

### Two Layers

1. **`css_class_mapping` in JSON** — injects your framework's utility classes (Bootstrap, DaisyUI, Tailwind, etc.) directly into widget markup
2. **`theme.css`** — styles the semantic defaults (`.widget-list`, `.widget-title`, etc.) as a base layer

Both work together. `css_class_mapping` values take priority over semantic defaults.

### Naming Convention

All class variables use the `cls_` prefix. There are three tiers:

| Prefix | Scope | Example |
|--------|-------|---------|
| `cls_*` | Standard — available to all widgets, plugins, and theme templates | `cls_card`, `cls_table`, `cls_mb_3` |
| `cls_{plugin}_*` | Plugin-specific — namespaced to a single plugin | `cls_dstore_cart_item` |
| `cls_{widget}_*` | Widget-specific — namespaced to a single widget | `cls_toc_nav` |

Standard `cls_*` names are framework-agnostic. The *values* map to the theme's chosen CSS framework. Variable names use `left`/`right` (not `start`/`end`).

### Complete `cls_*` Standard Reference

See **[CssClassReference.md](CssClassReference.md)** for the full vocabulary of all standard `cls_*` variables with semantic defaults, BS5 examples, and Tailwind examples.

---

## 11. Icon Pack Support

Themes declare which icon library they use via the `icon_pack` and `icon_pack_ver` fields in `theme_info.json`. Pubvana CMS uses this declaration to keep social link icons, widget icons, and any other icon references in sync with the active theme's icon library — automatically, with no manual effort from the site admin.

### What Happens When a Theme is Activated

When an admin activates a theme, Pubvana CMS reads the new theme's `icon_pack` and `icon_pack_ver` from `theme_info.json` and automatically converts all social link icons stored in the database to the correct CSS classes for that icon pack. For example, if social links were saved with Font Awesome 6 classes (`fa-brands fa-facebook`) and the admin switches to a theme using Bootstrap Icons, the stored icon classes are updated to `bi bi-facebook` on activation.

This means theme developers do not need to worry about what icon classes were stored before their theme was activated — Pubvana CMS handles the translation.

### Supported Icon Packs

Pubvana CMS ships with built-in support for these icon libraries. The `icon_pack` value in `theme_info.json` must match one of the names below (matching is case-insensitive and ignores spaces, hyphens, and underscores — so `"FontAwesome"`, `"Font Awesome"`, and `"font-awesome"` all work). The `icon_pack_ver` value only needs the major version number (e.g. `"6.x"`, `"6"`, or `"6.2.14"` all resolve to major version `6`).

| `icon_pack` | Supported Versions | Example Class |
|-------------|-------------------|---------------|
| `FontAwesome` | 5, 6, 7 | `fa-brands fa-facebook` (v6/7), `fab fa-facebook` (v5) |
| `BootstrapIcons` | 1 | `bi bi-facebook` |
| `RemixIcon` | 4 | `ri-facebook-fill` |
| `Boxicons` | 2 | `bx bxl-facebook` |
| `TablerIcons` | 3 | `ti ti-brand-facebook` |
| `PhosphorIcons` | 2 | `ph ph-facebook-logo` |
| `Lineicons` | 4 | `lni lni-facebook` |

Each pack includes mappings for 29 brand platforms (Facebook, X/Twitter, Instagram, YouTube, LinkedIn, Pinterest, TikTok, Snapchat, Reddit, Discord, Twitch, GitHub, WhatsApp, Telegram, Mastodon, Tumblr, Vimeo, Flickr, Dribbble, Behance, Medium, Spotify, SoundCloud, Slack, Skype, Steam, Patreon, PayPal, Messenger) plus generic icons like `website` (globe icon).

### Icon Variables in Widget Templates (`icon_*`)

When a widget is rendered, Pubvana CMS automatically injects `icon_*` template variables based on the active theme's icon pack. These variables let widget templates display the correct icon class without hardcoding any specific icon library.

Available variables follow the pattern `icon_{platform_key}`:

**Brand icons:** `icon_facebook`, `icon_messenger`, `icon_x`, `icon_instagram`, `icon_youtube`, `icon_linkedin`, `icon_pinterest`, `icon_tiktok`, `icon_snapchat`, `icon_reddit`, `icon_discord`, `icon_twitch`, `icon_github`, `icon_whatsapp`, `icon_telegram`, `icon_mastodon`, `icon_tumblr`, `icon_vimeo`, `icon_flickr`, `icon_dribbble`, `icon_behance`, `icon_medium`, `icon_spotify`, `icon_soundcloud`, `icon_slack`, `icon_skype`, `icon_steam`, `icon_patreon`, `icon_paypal`

**Generic icons:** `icon_website`

Use these in widget `.tpl` files with a `default()` fallback so the widget still works if no theme is active or the theme doesn't declare an icon pack:

```
<i class="{{ icon_facebook | default('fab fa-facebook') }}"></i>
<i class="{{ icon_website | default('fas fa-globe') }}"></i>
<i class="{{ icon_x | default('fab fa-twitter') }}"></i>
```

The `default()` value should be a reasonable Font Awesome 5/6 class since that's the most commonly used icon library. See the **AuthorBio** widget for a real-world example of `icon_*` usage alongside `cls_*` class variables.

### How It Works Together

The `cls_*` pattern (Section 10) controls **structural CSS** — layout, spacing, typography classes from whatever CSS framework the theme uses. The `icon_*` pattern controls **icon CSS** — the correct icon library class for a given platform. Together they make widgets fully portable across themes:

```
{# cls_ for structural styling, icon_ for icon library #}
<a href="{{ profile.website }}" class="{{ cls_social_link | default('widget-social-link') }}">
    <i class="{{ icon_website | default('fas fa-globe') }}"></i>
</a>
```

A Bootstrap + Font Awesome 6 theme and a DaisyUI + Bootstrap Icons theme will both render this widget correctly, with no template changes.

---

## 12. User-Visible Strings — `{% lang %}` Requirement

**All user-visible text in `.tpl` files must use `{% lang %}` tags.** No hardcoded English.

Available `Blog.*` keys organized by view:

**All views (layout):** `home`, `blog`, `search`, `searchPlaceholder`, `rssFeed`, `sitemap`, `allRightsReserved`, `language`, `previewModeBanner`, `login`, `adminPanel`, `profileTitle`

**home.tpl:** `latestPosts`, `readMore`, `viewAll`, `noPostsYet`

**post.tpl:** `postedOn`, `views`, `readingTime`, `publishedBy`, `inCategory`, `tags`, `commentsHeading`, `commentFormTitle`, `commentLabel`, `commentPostBtn`, `commentModerated`, `commentLoginRequired`, `commentLoginLink`, `commentAwaitModeration`, `commentPosted`, `commentLoginToComment`, `commentTooFast`

**post.tpl (paywall):** `paywallTitle`, `paywallMessage`, `paywallSignIn`, `paywallCreateAccount`

**post.tpl (author card):** `authorCardLabel`, `unknownAuthor`

**page.tpl:** (uses `render_content`, minimal text needed)

**category.tpl:** `categoryHeading`, `noPostsInCategory`

**tag.tpl:** `tagHeading`, `noPostsWithTag`

**archive.tpl:** `archiveHeading`, `noPostsInPeriod`

**search.tpl:** `searchResultsHeading`, `searchShowingFor`, `searchNoResults`, `searchPostsPlaceholder`

**profile.tpl:** `profileTitle`, `profileBasicInfo`, `profileUsername`, `profileEmail`, `profilePassword`, `profilePasswordConfirm`, `profilePasswordHelp`, `profileSave`, `profileAuthorInfo`, `profileDisplayName`, `profileBio`, `profileAvatar`, `profileAvatarChange`, `profileWebsite`, `profileTwitter`, `profileFacebook`, `profileLinkedin`

**pagination (partial):** `pageNavLabel`, `prevPage`, `nextPage`

**contact form:** `contactTitle`, `contactName`, `contactEmail`, `contactMessage`, `contactSendBtn`, `contactSent`, `contactCaptchaFail`, `contactSubject`

**404 page:** `pageNotFound`, `pageNotFoundTitle`

**maintenance page:** `maintenanceTitle`, `maintenanceBody`

**Parameterized keys** use `{0}`, `{1}` placeholders:
```
{% lang 'Blog.views' post.views|number_format %}
{# → "1,234 views" #}

{% lang 'Blog.readingTime' reading_time %}
{# → "5 min read" #}

{% lang 'Blog.commentsHeading' comments|count %}
{# → "Comments (3)" #}

{% lang 'Blog.categoryHeading' category.name %}
{# → "Posts in Technology" #}

{% lang 'Blog.searchNoResults' query %}
{# → 'No posts found for "example".' #}
```

For the full key list, see `app/Language/en/Blog.php`. Translations exist for Multiple Languages.

---

## 13. Multi-Language Support

### Dynamic `<html lang>` Attribute

```
<html lang="{{ locale }}">
```

Do not hardcode `<html lang="en">`.

### hreflang Tags

In `layout.tpl` `<head>`:

```
{% if lang_switcher.buttons %}
    {% for btn in lang_switcher.buttons %}
        <link rel="alternate" hreflang="{{ btn.code }}" href="{% base_url btn.url %}">
    {% endfor %}
    <link rel="alternate" hreflang="x-default" href="{% base_url '' %}">
{% endif %}
```

### Language Switcher UI

`lang_switcher` provides `buttons`, `dropdown`, and `ul` formats. Each item has: `code`, `name`, `native_name`, `url`, `direction` (ltr/rtl), `active` (bool), `css`.

```
{% if lang_switcher.ul %}
<nav>
    <ul>
        {% for item in lang_switcher.ul %}
        <li>
            <a href="{% base_url item.url %}"
               {% if item.active %}aria-current="true"{% endif %}>
                {{ item.native_name }}
            </a>
        </li>
        {% endfor %}
    </ul>
</nav>
{% endif %}
```

Alternatively, the **LanguagePicker** widget provides a drop-in language switcher with configurable style (buttons, dropdown, or list). Assign it to any widget area — no manual markup needed.

---

## 14. Dark Mode

Dark mode is entirely a theme-level frontend concern. Pubvana CMS has no server-side dark mode setting, middleware, or database flag — it serves the same HTML regardless. All dark mode logic lives in the theme's CSS and (optionally) JavaScript.

### Approaches

**No dark mode** — One color scheme, no switching logic. Simplest to build.

**System-preference only** — The user's OS controls light/dark. No toggle in the theme UI. CSS handles everything:

```css
:root {
    --bg: #ffffff;
    --text: #111111;
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg: #1a1a1a;
        --text: #e0e0e0;
    }
}
```

Zero JavaScript required. The theme just defines two sets of CSS custom property values.

**User toggle** — A visible switch in the theme UI (nav, footer, etc.) lets the visitor choose. This requires:

1. **CSS custom properties** for both palettes, switched via a class or data attribute on `<html>`:
   ```css
   :root { --bg: #ffffff; --text: #111111; }
   [data-theme="dark"] { --bg: #1a1a1a; --text: #e0e0e0; }
   ```
2. **Toggle JS** in the theme's assets directory that:
   - Flips the attribute on click
   - Persists the choice to `localStorage`
3. **Inline JS snippet** in `layout.tpl` `<head>` that reads `localStorage` and applies the attribute *before* paint — prevents a flash of the wrong theme on page load
4. **Toggle UI element** in `layout.tpl` (button/icon)

All of this lives in `theme.css`, `assets/js/`, and `layout.tpl`. Nothing touches Pubvana CMS.

**Always dark** — The theme's base design is dark. No light mode, no switching. Same as "no dark mode" but the single palette is dark.

### What the Theme Builder Needs to Know

The chosen approach determines:
- Whether `theme.css` needs CSS custom properties and a second palette
- Whether the theme ships JS (toggle + localStorage persistence)
- Whether `layout.tpl` needs an inline `<script>` in `<head>` and a toggle button
- Whether the `<html>` tag needs a conditional `data-theme` attribute

---

## 15. Theme Validation

`ThemeService::validateTheme()` runs on every admin themes page load. It recursively scans every file in the theme directory for PHP.

Themes that fail: activate button is disabled, warning shown in the admin theme card.

---

## 16. Key Path Constants

```
THEMES_PATH   = ROOTPATH . 'themes/'       e.g. /var/www/html2/themes/
WIDGETS_PATH  = ROOTPATH . 'widgets/'      e.g. /var/www/html2/widgets/
FCPATH        = public/index.php dir        e.g. /var/www/html2/public/
```

`theme_url('css/theme.css')` resolves to `{base_url}themes/{folder}/css/theme.css` — pointing at the copied files in `FCPATH`.

---

## 17. Complete Example — Building a Minimal Theme

A minimal but complete theme demonstrating all patterns.

### themes/minimal/theme_info.json

```json
{
    "name": "Minimal",
    "version": "1.0.0",
    "author": "Example",
    "author_url": "https://example.com",
    "support_url": "https://example.com/support",
    "description": "A bare-bones starter theme.",
    "screenshot": "screenshot.png",
    "free": true,
    "bundled": false,
    "css_framework": "none",
    "css_frame_ver": "",
    "icon_pack": "FontAwesome",
    "icon_pack_ver": "6.x",
    "js_framework": "none",
    "js_framework_ver": "",
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
    "widget_areas": {
        "sidebar": "Sidebar",
        "footer-1": "Footer"
    },
    "options": {
        "show_sidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": "1"
        }
    }
}
```

### themes/minimal/views/layout.tpl

```
<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ page_title | default(site_name) }}</title>
    {% if seo.description %}
    <meta name="description" content="{{ seo.description }}">
    {% endif %}
    {% if seo.og_title %}
    <meta property="og:title" content="{{ seo.og_title }}">
    <meta property="og:description" content="{{ seo.og_description }}">
    {% endif %}
    {% if seo.og_image %}
    <meta property="og:image" content="{{ seo.og_image }}">
    {% endif %}
    {% if lang_switcher.buttons %}
        {% for btn in lang_switcher.buttons %}
        <link rel="alternate" hreflang="{{ btn.code }}" href="{% base_url btn.url %}">
        {% endfor %}
        <link rel="alternate" hreflang="x-default" href="{% base_url '' %}">
    {% endif %}
    <link rel="alternate" type="application/rss+xml" title="{{ site_name }} RSS Feed" href="{% site_url 'feed' %}">
    <link rel="alternate" type="application/atom+xml" title="{{ site_name }} Atom Feed" href="{% site_url 'atom' %}">
    <link rel="stylesheet" href="{% theme_url 'css/theme.css' %}">
    {% block head_extra %}{% endblock %}
    {% if json_ld %}
    <script type="application/ld+json">{! json_ld !}</script>
    {% endif %}
    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>
        window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
        gtag('js',new Date());gtag('config','{{ analytics_id | raw }}');
    </script>
    {% endif %}
</head>
<body>
    {% if preview_mode %}
        <div class="preview-banner">{% lang 'Blog.previewModeBanner' %}</div>
    {% endif %}

    <header>
        <a href="{% site_url '' %}">{{ site_name }}</a>
        <span>{{ site_tagline }}</span>
        <nav>
            <a href="{% site_url '' %}">{% lang 'Blog.home' %}</a>
            <a href="{% site_url 'blog' %}">{% lang 'Blog.blog' %}</a>
            {% for item in primary_nav %}
                <a href="{{ item.url }}" target="{{ item.target }}">{{ item.label }}</a>
            {% endfor %}
        </nav>
    </header>

    {% if flash_success %}
        <div class="flash flash-success">{{ flash_success }}</div>
    {% endif %}
    {% if flash_error %}
        <div class="flash flash-error">{{ flash_error }}</div>
    {% endif %}

    <div class="container">
        {% widget_area 'before-content' %}
        <main>
            {% block content %}{% endblock %}
        </main>
        {% if show_sidebar %}
        <aside>
            {% widget_area 'sidebar' %}
        </aside>
        {% endif %}
    </div>

    <footer>
        {% widget_area 'footer-1' %}
        {% if footer_nav %}
        <nav>
            {% for item in footer_nav %}
                <a href="{{ item.url }}" target="{{ item.target }}">{{ item.label }}</a>
            {% endfor %}
        </nav>
        {% endif %}
        <p>{{ site_name }} &copy; {{ 'now' | date('Y') }}. {% lang 'Blog.allRightsReserved' %}</p>
        {% if sitemap_enabled %}
            <a href="{% site_url 'sitemap.xml' %}">{% lang 'Blog.sitemap' %}</a>
        {% endif %}
        <a href="{% site_url 'feed' %}">{% lang 'Blog.rssFeed' %}</a>
    </footer>
</body>
</html>
```

### themes/minimal/views/home.tpl

```
{% extends 'layout' %}
{% block content %}
    <h1>{% lang 'Blog.latestPosts' %}</h1>
    {% if posts %}
        {% for post in posts %}
            {% include 'partials/post-card' with {post: post} %}
        {% endfor %}
        {% if pager_links %}
            {! pager_links !}
        {% endif %}
    {% else %}
        <p>{% lang 'Blog.noPostsYet' %}</p>
    {% endif %}
{% endblock %}
```

### themes/minimal/views/post.tpl

```
{% extends 'layout' %}
{% block content %}
    <article>
        <h1>{{ post.title }}</h1>
        <div class="meta">
            {% lang 'Blog.postedOn' %} {{ post.published_at | date('F j, Y') }}
            &middot; {% lang 'Blog.readingTime' reading_time %}
            &middot; {% lang 'Blog.views' post.views|number_format %}
        </div>

        {% if post.featured_image %}
            <img src="{% base_url post.featured_image %}" alt="{{ post.title }}">
        {% endif %}

        {% if paywall %}
            {{ post.excerpt | excerpt(300) }}
            {# The Paywall widget can be assigned to the before-content area
               to display a styled sign-in CTA on premium posts #}
        {% else %}
            {% render_content post %}
        {% endif %}
    </article>

    {% if not paywall and comments_enabled %}
        {% include 'partials/comments-list' with {comments: comments} %}
        {% if is_logged_in %}
            {% include 'partials/comment-form' %}
        {% else %}
            <p>{% lang 'Blog.commentLoginRequired' %} <a href="{% site_url 'login' %}">{% lang 'Blog.commentLoginLink' %}</a></p>
        {% endif %}
    {% endif %}
{% endblock %}
```

### themes/minimal/views/partials/post-card.tpl

```
<article class="post-card">
    {% if post.featured_image %}
        <img src="{% base_url post.featured_image %}" alt="{{ post.title }}">
    {% endif %}
    <h2><a href="{% post_url post.slug %}">{{ post.title }}</a></h2>
    <span class="meta">{{ post.published_at | date('M j, Y') }}</span>
    {% if post.excerpt %}
        <p>{{ post.excerpt | excerpt(150) }}</p>
    {% endif %}
    <a href="{% post_url post.slug %}">{% lang 'Blog.readMore' %}</a>
</article>
```

### themes/minimal/views/category.tpl

```
{% extends 'layout' %}
{% block content %}
    <h1>{% lang 'Blog.categoryHeading' category.name %}</h1>
    {% if posts %}
        {% for post in posts %}
            {% include 'partials/post-card' with {post: post} %}
        {% endfor %}
        {% if pager_links %}
            {! pager_links !}
        {% endif %}
    {% else %}
        <p>{% lang 'Blog.noPostsInCategory' %}</p>
    {% endif %}
{% endblock %}
```

### themes/minimal/views/search.tpl

```
{% extends 'layout' %}
{% block content %}
    <h1>{% lang 'Blog.searchResultsHeading' %}</h1>
    {% if query %}
        <p>{% lang 'Blog.searchShowingFor' query %}</p>
    {% endif %}
    {% if posts %}
        {% for post in posts %}
            {% include 'partials/post-card' with {post: post} %}
        {% endfor %}
        {% if pager_links %}
            {! pager_links !}
        {% endif %}
    {% else %}
        {% if query %}
            <p>{% lang 'Blog.searchNoResults' query %}</p>
        {% endif %}
    {% endif %}
{% endblock %}
```

The remaining views (`page.tpl`, `tag.tpl`, `archive.tpl`) follow the same pattern — extend layout, fill the content block, use the page-specific variables from Section 7.

---

## 18. Vetting

Themes are checked against the Pubvana vetting service during discovery. This is a non-blocking background check -- themes that have not been vetted can still be activated, but the admin sees a status badge next to each theme in the management UI.

### Four States

| Status | Badge | Meaning |
|--------|-------|---------|
| `unknown` | Gray "Unknown" | Theme has not been checked yet (new install or network error) |
| `safe` | Green "Safe" | Theme is in the Pubvana registry and has been reviewed |
| `known` | Green "Safe" + yellow warning | Theme is in the registry but has a noted limitation or caution |
| `malicious` | Red "Not Safe" | Theme has been flagged as harmful -- a warning is displayed prominently |

### Author Normalization

The `author` field in `theme_info.json` is normalized before it is sent to the vetting service: converted to lowercase and spaces replaced with underscores. For example, `"Pubvana Team"` becomes `pubvana_team`. Use a consistent author name across all your themes so the vetting registry can group them correctly.

### Submitting for Vetting

To have your theme reviewed and listed in the Pubvana registry, submit it at **pubvana.net/vetted/submit**. You will need to provide a ZIP containing a valid `theme_info.json`. The Pubvana team will review the code and notify you by email when a decision is made.
