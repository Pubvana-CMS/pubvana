# ThemeBuilder — Pubvana Theme Development Guide

This document covers everything needed to build a Pubvana theme. It is self-contained — no other documents are required.

---

## 1. Theme Directory Structure

Each theme lives in its own folder under `themes/`. **No PHP files.** Every view is a `.tpl` file rendered by the template engine. The only non-`.tpl` files are `theme_info.json` (manifest) and static assets.

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
        partials/
            post-card.tpl       post listing card
            sidebar.tpl         sidebar wrapper
            pagination.tpl      pagination wrapper
            author-card.tpl     author bio card on posts
            comment-form.tpl    comment submission form
            comments-list.tpl   comment thread
            _comment.tpl        single comment (recursive for replies)
```

All 8 views and 7 partials should be present in a complete theme. Zero PHP files in the theme directory — any theme containing `<?php`, `<?=`, or `<%` will fail validation and cannot be activated.

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
    "premium": false,
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

| Key | Required | Description |
|-----|----------|-------------|
| `name` | yes | Human-readable theme name |
| `version` | yes | Semantic version |
| `author` | no | Author name |
| `author_url` | no | Author's website URL (linked in admin) |
| `support_url` | no | Support/contact URL (linked in admin) |
| `description` | no | Short description |
| `screenshot` | no | Filename relative to theme root, shown in admin |
| `premium` | no | Boolean flag for paid themes |
| `widget_areas` | no | Object mapping area slugs to human labels. DB rows created on activation. |
| `options` | no | Admin-editable theme options. Types: `text`, `checkbox`, `textarea`, `number` |

Option values are stored in the `theme_options` table and available as variables in all theme views (see Section 6).

---

## 3. Asset Pipeline

Theme assets are **copied** — not symlinked — from `themes/{name}/assets/` to `public/themes/{name}/` as real files.

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

Replaces the old `ob_start()` / `theme_view(theme_layout())` pattern.

**layout.tpl** defines the HTML shell with named blocks:

```
<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ seo.title | default(site_name) }}</title>
    <meta name="description" content="{{ seo.description }}">
    {% if seo.og_title %}
    <meta property="og:title" content="{{ seo.og_title }}">
    <meta property="og:description" content="{{ seo.og_description }}">
    {% endif %}
    {% if seo.og_image %}
    <meta property="og:image" content="{{ seo.og_image }}">
    {% endif %}
    <link rel="stylesheet" href="{% theme_url 'css/theme.css' %}">
    {% block head_extra %}{% endblock %}
</head>
<body>
    <nav>
        <a href="{% base_url '' %}">{{ site_name }}</a>
        {% for item in primary_nav %}
            <a href="{{ item.url }}" target="{{ item.target }}">{{ item.label }}</a>
        {% endfor %}
    </nav>

    {% if flash_success %}
        <div class="alert-success">{{ flash_success }}</div>
    {% endif %}
    {% if flash_error %}
        <div class="alert-error">{{ flash_error }}</div>
    {% endif %}

    <main>
        {% block content %}{% endblock %}
    </main>

    <footer>
        {% widget_area 'footer-1' %}
        {% widget_area 'footer-2' %}
        {% widget_area 'footer-3' %}
        <p>{{ footer_copyright | default(site_name) }} &copy; {{ 'now' | date('Y') }}. {% lang 'Blog.allRightsReserved' %}</p>
    </footer>

    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ analytics_id | raw }}');
    </script>
    {% endif %}
</body>
</html>
```

**Page views** extend the layout and fill blocks:

```
{% extends 'layout' %}
{% block content %}
    <h1>{{ seo.title }}</h1>
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
| `theme` | object or null | Active theme DB row |
| `site_name` | string | From `site_name()` helper |
| `site_tagline` | string | From `site_tagline()` helper |
| `locale` | string | Request locale (e.g. `en`, `es`) |
| `primary_nav` | array of objects | Nav items: `.url`, `.label`, `.target` |
| `footer_nav` | array of objects | Nav items: `.url`, `.label`, `.target` |
| `social_links` | array of objects | Social links: `.url`, `.icon` (FA class), `.platform` |
| `plugin_menu_items` | array | Plugin-contributed nav items |
| `is_logged_in` | bool | Whether a user is authenticated |
| `flash_success` | string or null | Session flash message (success) |
| `flash_error` | string or null | Session flash message (error) |
| `analytics_id` | string | Google Analytics tracking ID (empty if not set) |
| `sitemap_enabled` | bool | Whether sitemap is enabled |
| `comments_enabled` | bool | Whether comments are enabled site-wide |
| `comment_moderation` | bool | Whether new comments need approval |
| `hcaptcha_site_key` | string | hCaptcha site key (empty if not configured) |
| `lang_switcher` | array | Language switcher data (`buttons`, `dropdown`, `ul` formats) |
| _(theme option keys)_ | string | All theme options from `theme_options` table (e.g. `show_sidebar`, `footer_copyright`) |

**Theme options** are available as top-level variables using their option key names. For example, if `theme_info.json` declares `show_sidebar` and `footer_copyright`, your `.tpl` can use `{{ show_sidebar }}` and `{{ footer_copyright }}` directly.

---

## 7. Page-Specific Variables per View

Controllers pass only page-specific data. These are merged with common data (Section 6) by ThemeService.

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

---

## 8. Tag Functions — Complete Reference

Tag functions output strings directly into the template. Arguments are space-separated. Quoted strings are literal values; unquoted identifiers are variable references.

| Function | Signature | Description |
|----------|-----------|-------------|
| `lang` | `{% lang 'Blog.key' %}` | Localized string lookup |
| `lang` | `{% lang 'Blog.key' arg1 arg2 %}` | With placeholder substitution (`{0}`, `{1}`) |
| `base_url` | `{% base_url 'path' %}` | Site base URL + path |
| `theme_url` | `{% theme_url 'css/theme.css' %}` | Active theme's asset URL |
| `post_url` | `{% post_url slug_var %}` | Blog post URL: `/blog/{slug}` |
| `category_url` | `{% category_url slug_var %}` | Category URL: `/category/{slug}` |
| `tag_url` | `{% tag_url slug_var %}` | Tag URL: `/tag/{slug}` |
| `widget_area` | `{% widget_area 'sidebar' %}` | Render all widgets assigned to this area |
| `render_content` | `{% render_content entity_var %}` | Render entity's content (Markdown or HTML based on `.content_type`) |

### Examples

```
{% lang 'Blog.readMore' %}
{% lang 'Blog.views' post.views|number_format %}
{% base_url 'login' %}
{% theme_url 'images/logo.png' %}
{% post_url post.slug %}
{% widget_area 'sidebar' %}
{% render_content post %}
```

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

Widgets output HTML with semantic CSS class defaults. Themes can override these by passing `cls_` variables into widget areas.

Every CSS class in a widget `.tpl` is a variable with a `default()` fallback:

```
<div class="{{ cls_widget | default('widget widget-categories') }}">
```

If the theme doesn't pass `cls_widget`, the semantic default applies. If the theme passes its own value (e.g. Bootstrap classes), that replaces the default.

See **WidgetBuilder.md Section 5** for the complete `cls_` vocabulary with all standard variables and their defaults.

---

## 11. User-Visible Strings — `{% lang %}` Requirement

**All user-visible text in `.tpl` files must use `{% lang %}` tags.** No hardcoded English.

Available `Blog.*` keys organized by view:

**All views (layout):** `home`, `blog`, `search`, `searchPlaceholder`, `rssFeed`, `sitemap`, `allRightsReserved`

**home.tpl:** `latestPosts`, `readMore`, `noPostsYet`

**post.tpl:** `postedOn`, `views`, `readingTime`, `publishedBy`, `inCategory`, `tags`, `commentsHeading`, `commentFormTitle`, `commentLabel`, `commentPostBtn`, `commentModerated`, `commentLoginRequired`, `commentLoginLink`, `previewModeBanner`

**page.tpl:** (uses `render_content`, minimal text needed)

**category.tpl:** `categoryHeading`, `noPostsInCategory`

**tag.tpl:** `tagHeading`, `noPostsWithTag`

**archive.tpl:** `archiveHeading`, `noPostsInPeriod`

**search.tpl:** `searchResultsHeading`, `searchShowingFor`, `searchNoResults`

**Parameterized keys** use `{0}`, `{1}` placeholders:
```
{% lang 'Blog.views' post.views|number_format %}
{# → "1,234 views" #}

{% lang 'Blog.categoryHeading' category.name %}
{# → "Posts in Technology" #}
```

For the full key list, see `app/Language/en/Blog.php`. Translations exist for: `en`, `es`, `fr`, `id`, `pt`, `sk`.

---

## 12. Multi-Language Support

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

---

## 13. Theme Validation

`ThemeService::validateTheme()` runs on every admin themes page load. It recursively scans every file in the theme directory (skipping binary formats) for `<?php`, `<?=`, and `<%`.

Themes that fail: activate button is disabled, warning shown in the admin theme card.

---

## 14. Key Path Constants

```
THEMES_PATH   = ROOTPATH . 'themes/'       e.g. /var/www/html2/themes/
WIDGETS_PATH  = ROOTPATH . 'widgets/'      e.g. /var/www/html2/widgets/
FCPATH        = public/index.php dir        e.g. /var/www/html2/public/
```

`theme_url('css/theme.css')` resolves to `{base_url}themes/{folder}/css/theme.css` — pointing at the copied files in `FCPATH`.

---

## 15. Complete Example — Building a Minimal Theme

A minimal but complete theme demonstrating all patterns.

### themes/minimal/theme_info.json

```json
{
    "name": "Minimal",
    "version": "1.0.0",
    "author": "Example",
    "description": "A bare-bones starter theme.",
    "screenshot": "screenshot.png",
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
    <title>{{ seo.title | default(site_name) }}</title>
    <meta name="description" content="{{ seo.description }}">
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
    <link rel="stylesheet" href="{% theme_url 'css/theme.css' %}">
    {% block head_extra %}{% endblock %}
</head>
<body>
    <header>
        <a href="{% base_url '' %}">{{ site_name }}</a>
        <span>{{ site_tagline }}</span>
        <nav>
            <a href="{% base_url '' %}">{% lang 'Blog.home' %}</a>
            <a href="{% base_url 'blog' %}">{% lang 'Blog.blog' %}</a>
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
        <p>{{ site_name }} &copy; {{ 'now' | date('Y') }}. {% lang 'Blog.allRightsReserved' %}</p>
        {% if sitemap_enabled %}
            <a href="{% base_url 'sitemap.xml' %}">{% lang 'Blog.sitemap' %}</a>
        {% endif %}
        <a href="{% base_url 'blog/feed' %}">{% lang 'Blog.rssFeed' %}</a>
    </footer>

    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>
        window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
        gtag('js',new Date());gtag('config','{{ analytics_id | raw }}');
    </script>
    {% endif %}
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
    {% if preview_mode %}
        <div class="preview-banner">{% lang 'Blog.previewModeBanner' %}</div>
    {% endif %}

    <article>
        <h1>{{ post.title }}</h1>
        <div class="meta">
            {% lang 'Blog.postedOn' %} {{ post.published_at | date('F j, Y') }}
            &middot; {% lang 'Blog.readingTime' %} {{ reading_time }}
            &middot; {{ post.views | number_format }} {% lang 'Blog.views' post.views|number_format %}
        </div>

        {% if post.featured_image %}
            <img src="{% base_url post.featured_image %}" alt="{{ post.title }}">
        {% endif %}

        {% if paywall %}
            {{ post.excerpt | excerpt(300) }}
            {% widget_area 'before-content' %}
        {% else %}
            {% render_content post %}
        {% endif %}
    </article>

    {% if not paywall and comments_enabled %}
        {% include 'partials/comments-list' with {comments: comments} %}
        {% if is_logged_in %}
            {% include 'partials/comment-form' %}
        {% else %}
            <p>{% lang 'Blog.commentLoginRequired' %} <a href="{% base_url 'login' %}">{% lang 'Blog.commentLoginLink' %}</a></p>
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
            <p>{% lang 'Blog.searchNoResults' %}</p>
        {% endif %}
    {% endif %}
{% endblock %}
```

The remaining views (`page.tpl`, `tag.tpl`, `archive.tpl`) follow the same pattern — extend layout, fill the content block, use the page-specific variables from Section 7.
