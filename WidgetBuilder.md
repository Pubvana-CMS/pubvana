# WidgetBuilder — Pubvana Widget Development Guide

This document covers everything needed to build a Pubvana widget. It is self-contained — no other documents are required.

---

## 1. Widget Directory Structure

Each widget lives in its own folder under `widgets/`. **No PHP files.** A widget is JSON + templates only.

```
widgets/RecentPosts/
    widget_info.json        # Manifest: metadata + admin form + data providers
    views/
        widget.tpl          # Frontend template (rendered by the engine)
```

**Naming convention:**
- Folder: capitalized, no underscores (e.g. `RecentPosts`, `TagCloud`, `CategoriesList`)

---

## 2. widget_info.json Format

The manifest has three top-level sections: metadata, `admin`, and `output`.

```json
{
    "name": "Recent Posts",
    "description": "Displays a list of the most recent published posts.",
    "version": "1.0.0",
    "admin": {
        "notice": "",
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Recent Posts"
            },
            "count": {
                "type": "number",
                "label": "Number of Posts",
                "default": "5"
            },
            "show_date": {
                "type": "checkbox",
                "label": "Show Date",
                "default": "1"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "posts": {
                "provider": "PostModel.getRecent",
                "params": {
                    "limit": "count"
                }
            }
        }
    }
}
```

### Metadata (top-level)

| Key | Required | Description |
|-----|----------|-------------|
| `name` | yes | Human-readable widget name |
| `description` | yes | Short description |
| `version` | yes | Semantic version |

### `admin` Section

Drives the admin configure form. `WidgetService::renderAdminForm()` auto-generates HTML form fields from this section — no per-widget form file needed.

| Key | Description |
|-----|-------------|
| `notice` | Optional info message shown above the form (e.g. "Managed in Admin → Social Links") |
| `options` | Object of configurable options. Each key becomes a form field and a template variable. |

**Option types:** `text`, `number`, `checkbox`, `textarea`, `select`

- `default` is always a string (even for numbers/booleans)
- `select` type requires a `choices` object: `{"value": "Label", ...}`
- Widgets with no configurable settings use `"options": {}`
- Saved option values are stored in `widget_instances.options_json` in the database

### `output` Section

Drives the public render.

| Key | Description |
|-----|-------------|
| `template` | The `.tpl` file in `views/` to render (typically `"widget.tpl"`) |
| `providers` | Object mapping template variable names to data providers. Optional — omit for widgets that don't need data. |

### Data Providers

Each provider entry declares a whitelisted `Model.method` call:

```json
"providers": {
    "template_var_name": {
        "provider": "ModelName.methodName",
        "params": {
            "param_name": "option_key"
        }
    }
}
```

- `provider` — a whitelisted `Model.method` string (e.g. `PostModel.getRecent`). `WidgetDataService` validates against its whitelist before calling.
- `params` — maps parameter names to option keys. The saved option value is substituted at runtime. Use `{}` if no params needed.
- The model method is called by `WidgetDataService`, which is a service layer — widgets never touch models directly.
- If a provider isn't whitelisted or the model method doesn't exist, the template variable receives `null`.

### Registered Providers

| Provider | Description |
|----------|-------------|
| `CategoryModel.getWithPostCount` | All categories with post counts |
| `PostModel.getRecent` | Recent published posts. Params: `limit` |
| `TagModel.getWithPostCount` | All tags with post counts. Params: `limit` |
| `SocialModel.getActive` | Active social links |
| `PostModel.getArchiveList` | Monthly/yearly archive list. Params: `format` |
| `CommentModel.getRecentApproved` | Recent approved comments. Params: `limit` |
| `PostModel.getRelated` | Posts related to current post. Params: `limit` |
| `AuthorProfileModel.getForCurrentPost` | Author profile for the current post page |

---

## 3. Render Flow

There are no PHP widget classes. `WidgetService` handles the entire lifecycle:

1. `WidgetService::renderArea($slug)` finds widget instances assigned to the area
2. For each instance, reads `widget_info.json` from the widget folder
3. Reads defaults from `admin.options`, loads saved values from `widget_instances.options_json` in the DB, merges saved over defaults
4. Reads `output.providers`, passes to `WidgetDataService::resolve()` with merged options
5. `WidgetDataService` validates each provider against its whitelist, calls `Model.method` with resolved params, returns data keyed by template variable name
6. Merges options + provider results into one data array
7. Engine renders `output.template` with that data
8. HTML returned and concatenated into the page

---

## 4. .tpl View Syntax Reference

Widget views use `.tpl` files rendered by Pubvana's template engine. **No PHP is allowed in .tpl files.**

### Output Tags

| Syntax | Description |
|--------|-------------|
| `{{ variable }}` | Escaped output (HTML entities) |
| `{! variable !}` | Raw output (no escaping) — use for trusted HTML |
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

### Tag Functions

```
{% lang 'Blog.readMore' %}
{% base_url 'path' %}
{% theme_url 'css/style.css' %}
{% post_url slug_variable %}
{% category_url slug_variable %}
{% tag_url slug_variable %}
{% widget_area 'sidebar' %}
{% render_content entity_variable %}
```

### Includes

```
{% include 'partials/card' %}
{% include 'partials/card' with {post: post, show_date: show_date} %}
```

### Comments

```
{# This is a comment — stripped from output #}
```

### Layout Inheritance (themes only, not typically used in widgets)

```
{% extends 'layout' %}
{% block content %}...{% endblock %}
```

---

## 5. CSS Class Variables — The `cls_` Pattern

Widgets use **semantic CSS class defaults** that themes can override. This makes widgets framework-agnostic.

### How It Works

Every CSS class in a widget `.tpl` is a variable with a semantic default:

```
<div class="{{ cls_widget | default('widget widget-categories') }}">
```

- If the theme passes `cls_widget`, that value is used
- If not, the default `'widget widget-categories'` applies

Themes inject their own classes (e.g. Bootstrap) by passing `cls_` variables when rendering the widget area.

### Standard `cls_` Variables

| Variable | Default | Used For |
|----------|---------|----------|
| `cls_widget` | `widget widget-{type}` | Outermost wrapper |
| `cls_title` | `widget-title` | Widget heading (h4) |
| `cls_list` | `widget-list` | List container (ul) |
| `cls_list_item` | `widget-list-item` | List item (li) |
| `cls_link` | `widget-list-link` | Links within list items |
| `cls_badge` | `widget-badge` | Count badges |
| `cls_meta` | `widget-meta` | Metadata text (dates, counts) |
| `cls_empty` | `widget-empty` | Empty state message |
| `cls_content` | `widget-content` | Content container |
| `cls_form` | `widget-form` | Form wrapper |
| `cls_input` | `widget-form-input` | Form inputs |
| `cls_button` | `widget-form-button` | Form buttons |
| `cls_tags` | `widget-tags` | Tag cloud container |
| `cls_tag` | `widget-tag` | Individual tag link |
| `cls_card` | `widget-card` | Card container |
| `cls_card_image` | `widget-card-image` | Card image |
| `cls_card_body` | `widget-card-body` | Card body |
| `cls_card_title` | `widget-card-title` | Card title |
| `cls_card_text` | `widget-card-text` | Card text |
| `cls_thumbnail` | `widget-thumbnail` | Thumbnail image |
| `cls_social_links` | `widget-social-links` / `widget-social-links-list` | Social links container |
| `cls_social_link` | `widget-social-link` | Individual social link |
| `cls_toc_nav` | `widget-toc-nav` | Table of contents nav |
| `cls_toc_list` | `widget-toc-list` | TOC list |
| `cls_toc_item` | `widget-toc-item` | TOC list item |
| `cls_toc_link` | `widget-toc-link` | TOC link |
| `cls_fade` | `widget-paywall-fade` | Paywall fade overlay |
| `cls_cta` | `widget-paywall-cta` | Paywall CTA container |
| `cls_icon` | `widget-paywall-icon` | Paywall icon |
| `cls_paywall_title` | `widget-paywall-title` | Paywall heading |
| `cls_message` | `widget-paywall-message` | Paywall message |
| `cls_btn_primary` | `widget-paywall-button widget-paywall-button-primary` | Primary button |
| `cls_btn_secondary` | `widget-paywall-button widget-paywall-button-secondary` | Secondary button |

### Naming Convention

- All CSS class variables start with `cls_`
- Use `cls_` + descriptive noun: `cls_list`, `cls_badge`, `cls_card_body`
- Keep defaults semantic, not framework-specific

---

## 6. Available Tag Functions

| Function | Signature | Description |
|----------|-----------|-------------|
| `lang` | `{% lang 'Blog.key' %}` or `{% lang 'Blog.key' arg1 %}` | Localized string. Supports `{0}`, `{1}` placeholders. |
| `base_url` | `{% base_url 'path' %}` | Site base URL + path |
| `theme_url` | `{% theme_url 'css/theme.css' %}` | Active theme's asset URL |
| `post_url` | `{% post_url slug %}` | Blog post URL: `/blog/{slug}` |
| `category_url` | `{% category_url slug %}` | Category URL: `/category/{slug}` |
| `tag_url` | `{% tag_url slug %}` | Tag URL: `/tag/{slug}` |
| `widget_area` | `{% widget_area 'slug' %}` | Render all widgets in an area |
| `render_content` | `{% render_content entity %}` | Render entity content (Markdown or HTML) |

### Tag Function Arguments

Arguments are space-separated. Quoted strings are literal values; unquoted identifiers are variable references.

```
{% lang 'Blog.views' post.views|number_format %}
```
This calls `lang('Blog.views', [number_format(post.views)])`.

---

## 7. Available Filters

| Filter | Signature | Description |
|--------|-----------|-------------|
| `date` | `value \| date('F j, Y')` | Format a date string or timestamp |
| `number_format` | `value \| number_format` or `value \| number_format(2)` | Format number with thousands separator |
| `nl2br` | `value \| nl2br` | Convert newlines to `<br>` tags |
| `md5` | `value \| md5` | MD5 hash (useful for Gravatar URLs) |
| `count` | `value \| count` | Count array/countable items |
| `excerpt` | `value \| excerpt(150)` | Strip tags and truncate with ellipsis |
| `default` | `value \| default('fallback')` | Use fallback if value is null, empty, or false |
| `raw` | `value \| raw` | Mark as safe (skip HTML escaping). **Must be the last filter in a chain.** |
| `strtolower` | `value \| strtolower` | Convert to lowercase |
| `strip_tags` | `value \| strip_tags` | Remove HTML tags |

### Important: `raw` Filter

The `raw` filter is a marker — it tells the engine to skip auto-escaping on `{{ }}` output. Use it only for trusted content. It must be the **last** filter in a chain:

```
{{ profile.bio | nl2br | raw }}
```

For untrusted content, use `{! !}` raw output tags instead (but prefer `{{ }}` with escaping whenever possible).

---

## 8. JavaScript in Widgets

Some widgets (like Table of Contents) need client-side JavaScript. Since `.tpl` files cannot contain PHP, JavaScript is embedded directly in the template:

```
<script>
(function () {
    var clsList = "{{ cls_toc_list | default('widget-toc-list') }}";
    var clsItem = "{{ cls_toc_item | default('widget-toc-item') }}";
    // ... JS logic using CSS class variables
}());
</script>
```

**Key points:**
- Wrap in an IIFE to avoid polluting global scope
- Use `cls_` variables for CSS class names so themes can override them
- Use `{{ var | default('fallback') | raw }}` for values injected into JS (numbers, strings)
- The `DOMContentLoaded` event ensures DOM is ready

---

## 9. User-Visible Strings — `{% lang %}` Requirement

**All user-visible text in `.tpl` files must use `{% lang %}` tags.** No hardcoded English.

```
{% lang 'Blog.noPostsYet' %}
{% lang 'Blog.search' %}
{% lang 'Blog.categoryHeading' category.name %}
```

### Available Language Keys (app/Language/en/Blog.php)

**Common:** `home`, `blog`, `readMore`, `viewAll`, `noPostsYet`, `search`, `searchPlaceholder`

**Feeds/Footer:** `rssFeed`, `sitemap`, `allRightsReserved`

**Post Detail:** `postedOn`, `views`, `readingTime`, `publishedBy`, `inCategory`, `tags`

**Paywall:** `paywallTitle`, `paywallMessage`, `paywallSignIn`, `paywallCreateAccount`

**Author:** `authorCardLabel`, `unknownAuthor`

**Category/Tag/Archive:** `categoryHeading`, `tagHeading`, `archiveHeading`, `noPostsInCategory`, `noPostsWithTag`, `noPostsInPeriod`

**Search:** `searchResultsHeading`, `searchShowingFor`, `searchNoResults`

**Comments:** `commentsHeading`, `commentFormTitle`, `commentLabel`, `commentPostBtn`, `commentModerated`, `commentLoginRequired`, `commentLoginLink`

**Pagination:** `pageNavLabel`, `prevPage`, `nextPage`

Parameterized keys use `{0}`, `{1}` placeholders:
```
{% lang 'Blog.views' post.views|number_format %}
{# Renders: "1,234 views" #}
```

---

## 10. Complete Example — Building a Widget from Scratch

Here is a complete "Popular Tags" widget that displays the most-used tags with post counts.

### widgets/PopularTags/widget_info.json

```json
{
    "name": "Popular Tags",
    "description": "Displays the most popular tags by post count.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Popular Tags"
            },
            "max_tags": {
                "type": "number",
                "label": "Maximum Tags",
                "default": "10"
            },
            "show_count": {
                "type": "checkbox",
                "label": "Show Post Count",
                "default": "1"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "tags": {
                "provider": "TagModel.getWithPostCount",
                "params": {
                    "limit": "max_tags"
                }
            }
        }
    }
}
```

### widgets/PopularTags/views/widget.tpl

```
<div class="{{ cls_widget | default('widget widget-popular-tags') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    {% if tags %}
    <div class="{{ cls_tags | default('widget-tags') }}">
        {% for tag in tags %}
            <a href="{% tag_url tag.slug %}" class="{{ cls_tag | default('widget-tag') }}">
                {{ tag.name }}
                {% if show_count %}
                    <span class="{{ cls_badge | default('widget-badge') }}">{{ tag.post_count }}</span>
                {% endif %}
            </a>
        {% endfor %}
    </div>
    {% else %}
        <p class="{{ cls_empty | default('widget-empty') }}">{% lang 'Blog.noPostsYet' %}</p>
    {% endif %}
</div>
```

### How It Works

1. Admin assigns "Popular Tags" to a widget area (e.g. sidebar)
2. Admin configures options (title, max tags, show count) via the auto-generated admin form
3. On page load, `WidgetService::renderArea('sidebar')` finds the instance
4. Reads `widget_info.json`, merges saved options over defaults
5. `WidgetDataService` calls `TagModel.getWithPostCount` with `limit` from saved `max_tags` option
6. Engine renders `views/widget.tpl` with merged options + provider data
7. The engine resolves `cls_` variables (from theme or defaults), `{% tag_url %}` calls, `{% lang %}` strings
8. Final HTML is returned and inserted into the page
