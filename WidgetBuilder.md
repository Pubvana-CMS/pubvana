# WidgetBuilder — Pubvana Widget Development Guide

This document covers everything needed to build a Pubvana widget. It is self-contained — no other documents are required.

---

## 1. Widget Directory Structure

Each widget lives in its own folder under `widgets/`. **No PHP files.** A widget is JSON + templates only.

```
widgets/RecentPosts/
    widget_info.json        # Required — metadata, admin form, data providers
    views/
        widget.tpl          # Frontend template (rendered by the engine)
```

**Naming convention:**
- Folder: capitalized, no underscores (e.g. `RecentPosts`, `TagCloud`, `CategoriesList`)

### Validation Rules

`WidgetService` enforces these rules when scanning widget directories. Widgets that fail validation are skipped and logged as warnings:

1. **Required fields** — `widget_info.json` must have non-empty `name`, `description`, `version`, and `author`. Missing any of these causes the widget to be disabled.
2. **No PHP files** — The widget directory must not contain any `.php` files. Widgets are JSON + templates only. If a `.php` file is found in the widget's root directory, the widget is skipped entirely. This is a security measure — all data access goes through the whitelisted provider system (see Section 2), never through arbitrary PHP.

---

## 2. widget_info.json Format

`widget_info.json` has three top-level sections: metadata, `admin`, and `output`.

```json
{
    "name": "Recent Posts",
    "description": "Displays a list of the most recent published posts.",
    "version": "1.0.0",
    "author": "Your Name",
    "author_url": "https://example.com",
    "support_url": "https://example.com/support",
    "free": true,
    "bundled": false,
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
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

#### Required Fields

| Key | Required | Description |
|-----|----------|-------------|
| `name` | yes | Human-readable widget name |
| `description` | yes | Short description |
| `version` | yes | Semantic version |
| `author` | yes | Widget author name |

#### Optional Fields

| Key | Default | Description |
|-----|---------|-------------|
| `author_url` | `""` | Author's website URL (linked in admin) |
| `support_url` | `""` | Support/contact URL shown in admin when widget is incompatible or has issues |
| `free` | `false` | Set to `true` if your widget is free. Free third-party widgets activate without a license check. |
| `bundled` | `false` | Reserved for Pubvana-authored addons that ship with the CMS. Third-party widgets must not set this to `true`. |
| `min_pubvana_version` | `""` | Minimum Pubvana version required |
| `max_pubvana_version` | `""` | Maximum Pubvana version this widget is compatible with |
| `update_url` | `""` | API endpoint for update checks. Widgets without this cannot be updated through admin. |

#### Third-Party Store & License Fields

These fields are only relevant if you sell your widget and run your own store/license API. See [ThirdPartyAddons.md](ThirdPartyAddons.md) for the full API protocol specification.

| Field | Type | Description |
|-------|------|-------------|
| `license_validate_url` | string | Endpoint the CMS POSTs to when a site admin enters a license key for your widget. |
| `license_check_url` | string | Endpoint the CMS POSTs to for periodic license revalidation (90-day cycle). |
| `item_url` | string | Endpoint the CMS GETs to resolve your widget's numeric product ID (`{item_url}/{folder}`). |
| `store_url` | string | URL to your storefront page for this widget (linked in admin for license renewal). |
| `items_url` | string | Catalog listing endpoint (used by marketplace integrations). |
| `categories_url` | string | Category listing endpoint. |
| `categories_all_url` | string | Full category listing with products. |
| `category_url` | string | Single category endpoint. |
| `featured_url` | string | Featured products endpoint. |
| `update_check_url` | string | Alternative update check endpoint (if different from `update_url`). |
| `download_url` | string | Direct download endpoint for updates. |

> **For most third-party widgets:** You only need `free`, `update_url`, `license_validate_url`, `license_check_url`, `item_url`, and `store_url`. The remaining URL fields are for full marketplace integrations. If your widget is free, only `free: true` and optionally `update_url` are needed.

**Notes:**
- `min_pubvana_version` / `max_pubvana_version` — Used by the update system to prevent incompatible core updates and to find the right release version.
- `update_url` — The CMS POSTs here when checking for updates. Pubvana-built addons use `https://pubvana.net/api/dstore/v1/update/check`. Third-party developers provide their own endpoint.
- `support_url` — Displayed in the admin UI when the widget is incompatible ("Contact the developer").

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
- **Context parameters:** Values prefixed with `@` are resolved from the current request, not from saved options. Use these when a widget needs runtime information like which post is being viewed. Currently supported: `@current_post_id` (the ID of the blog post being viewed, or `null` on non-post pages). Example:
  ```json
  "params": { "postId": "@current_post_id" }
  ```
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
| `AuthorProfileModel.getForPost` | Author profile for the current post. Params: `postId` (context param — see below) |
| `LanguageModel.getSwitcherData` | Available languages for the locale switcher |

---

## 3. Render Flow

There are no PHP widget classes. `WidgetService` handles the entire lifecycle:

1. `WidgetService::renderArea($slug)` finds widget instances assigned to the area
2. For each instance, reads `widget_info.json` from the widget folder
3. Reads defaults from `admin.options`, loads saved values from `widget_instances.options_json` in the DB, merges saved over defaults
4. Reads `output.providers`, passes to `WidgetDataService::resolve()` with merged options
5. `WidgetDataService` validates each provider against its whitelist, calls `Model.method` with resolved params, returns data keyed by template variable name
6. Merges theme CSS classes (`cls_` variables from `theme_info.json`), theme icon classes (`icon_*` variables resolved from the active theme's `icon_pack` — see Section 5b), saved admin options, and provider results into one template data array
7. Engine renders `output.template` with that data
8. HTML returned and concatenated into the page

**Theme portability:** Widget instances are assigned to areas by **slug**, not by theme-specific IDs. If two themes define the same area slug (e.g. `sidebar`), widget instances carry over automatically when the user switches themes.

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

**Truthiness:** `null`, `false`, `''`, `0`, `'0'`, `[]` are falsy. Everything else is truthy. (Note: unchecked checkboxes save as `"0"`, which is correctly treated as falsy in `{% if %}` conditions.)

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
{% site_url 'path' %}
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

Themes declare their class overrides in `theme_info.json` under `css_class_mapping`. These are injected into every widget automatically at render time.

### Standard `cls_` Variables

These variables are available in every widget and plugin template. Themes override them via `css_class_mapping` in `theme_info.json`. Use `{{ cls_name | default('semantic-default') }}` in your templates.

#### Widget Shell
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_widget` | `widget widget-{type}` | Outermost wrapper |
| `cls_title` | `widget-title` | Widget heading |
| `cls_content` | `widget-content` | Free-form content area |
| `cls_empty` | `widget-empty` | Empty state message |
| `cls_meta` | `widget-meta` | Metadata text |
| `cls_link` | `widget-list-link` | Standard link |

#### Layout
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_container` | `container` | Main content wrapper |
| `cls_row` | `row` | Grid row |
| `cls_col` | `col` | Generic column |
| `cls_col_half` | `col-half` | 50% column |
| `cls_col_third` | `col-third` | 33% column |
| `cls_col_quarter` | `col-quarter` | 25% column |

#### Typography
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_h1` | `heading-1` | Heading 1 |
| `cls_h2` | `heading-2` | Heading 2 |
| `cls_h3` | `heading-3` | Heading 3 |
| `cls_h4` | `heading-4` | Heading 4 |
| `cls_h5` | `heading-5` | Heading 5 |
| `cls_h6` | `heading-6` | Heading 6 |
| `cls_lead` | `text-lead` | Intro/lead text |
| `cls_blockquote` | `blockquote` | Block quote |

#### Text Color (Semantic)
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_text_muted` | `text-muted` | De-emphasized text |
| `cls_text_primary` | `text-primary` | Brand/primary color |
| `cls_text_secondary` | `text-secondary` | Secondary color |
| `cls_text_success` | `text-success` | Success/positive |
| `cls_text_danger` | `text-danger` | Error/destructive |
| `cls_text_warning` | `text-warning` | Caution |
| `cls_text_info` | `text-info` | Informational |
| `cls_text_light` | `text-light` | Light on dark bg |

#### Text Alignment
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_text_left` | `text-left` | Left-aligned |
| `cls_text_center` | `text-center` | Centered |
| `cls_text_right` | `text-right` | Right-aligned |

#### Background (Semantic)
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_bg_primary` | `bg-primary` | Primary background |
| `cls_bg_secondary` | `bg-secondary` | Secondary background |
| `cls_bg_success` | `bg-success` | Success background |
| `cls_bg_danger` | `bg-danger` | Danger background |
| `cls_bg_warning` | `bg-warning` | Warning background |
| `cls_bg_light` | `bg-light` | Light surface |
| `cls_bg_dark` | `bg-dark` | Dark surface |

#### Cards
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_card` | `widget-card` | Card wrapper |
| `cls_card_header` | `widget-card-header` | Card header |
| `cls_card_body` | `widget-card-body` | Card body |
| `cls_card_footer` | `widget-card-footer` | Card footer |
| `cls_card_image` | `widget-card-image` | Card image |
| `cls_card_title` | `widget-card-title` | Card title |
| `cls_card_text` | `widget-card-text` | Card body text |

#### Lists
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_list` | `widget-list` | Styled list (ul) |
| `cls_list_item` | `widget-list-item` | List item (li) |
| `cls_list_plain` | `list-plain` | Unstyled list (no bullets) |
| `cls_list_inline` | `list-inline` | Horizontal list |

#### Tables
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_table` | `table` | Base table |
| `cls_table_striped` | `table-striped` | Alternating rows |
| `cls_table_hover` | `table-hover` | Hover highlight |
| `cls_table_head` | `table-head` | Table header row |
| `cls_table_cell` | _(none)_ | `<td>` styling |
| `cls_table_header_cell` | _(none)_ | `<th>` styling |

#### Forms
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_form` | `widget-form` | Form wrapper |
| `cls_form_group` | `form-group` | Field + label wrapper |
| `cls_label` | `form-label` | Form label |
| `cls_input` | `widget-form-input` | Text input |
| `cls_select` | `form-select` | Select dropdown |
| `cls_textarea` | `form-textarea` | Textarea |
| `cls_checkbox` | `form-checkbox` | Checkbox input |
| `cls_form_help` | `form-help` | Help/hint text |

#### Buttons
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_button` | `widget-form-button` | Generic button (widget context) |
| `cls_btn_primary` | `btn-primary` | Primary action |
| `cls_btn_secondary` | `btn-secondary` | Secondary action |
| `cls_btn_success` | `btn-success` | Positive/confirm |
| `cls_btn_danger` | `btn-danger` | Destructive/delete |
| `cls_btn_warning` | `btn-warning` | Caution action |
| `cls_btn_outline` | `btn-outline` | Outlined/ghost variant |
| `cls_btn_sm` | `btn-sm` | Small button |
| `cls_btn_lg` | `btn-lg` | Large button |
| `cls_btn_block` | `btn-block` | Full-width button |

#### Alerts
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_alert` | `alert` | Base alert wrapper |
| `cls_alert_success` | `alert-success` | Success notice |
| `cls_alert_danger` | `alert-danger` | Error notice |
| `cls_alert_warning` | `alert-warning` | Warning notice |
| `cls_alert_info` | `alert-info` | Info notice |

#### Navigation
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_nav` | `nav` | Nav wrapper |
| `cls_nav_item` | `nav-item` | Nav entry |
| `cls_nav_link` | `nav-link` | Nav anchor |
| `cls_nav_active` | `nav-active` | Active nav item |
| `cls_dropdown` | `dropdown-menu` | Dropdown menu |
| `cls_dropdown_item` | `dropdown-item` | Dropdown entry |

#### Breadcrumbs
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_breadcrumb` | `breadcrumb` | Breadcrumb wrapper |
| `cls_breadcrumb_item` | `breadcrumb-item` | Breadcrumb entry |
| `cls_breadcrumb_active` | `breadcrumb-active` | Current page |

#### Badges & Tags
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_badge` | `widget-badge` | Count/label badge |
| `cls_tags` | `widget-tags` | Tag cloud container |
| `cls_tag` | `widget-tag` | Individual tag link |

#### Images
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_thumbnail` | `widget-thumbnail` | Thumbnail image |
| `cls_img_fluid` | `img-fluid` | Responsive image |
| `cls_img_rounded` | `img-rounded` | Rounded corners |
| `cls_img_circle` | `img-circle` | Circular crop |

#### Social
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_social_links` | `widget-social-links` | Social links container |
| `cls_social_link` | `widget-social-link` | Individual social link |

#### Table of Contents
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_toc_nav` | `widget-toc-nav` | TOC nav wrapper |
| `cls_toc_list` | `widget-toc-list` | TOC list |
| `cls_toc_item` | `widget-toc-item` | TOC list item |
| `cls_toc_link` | `widget-toc-link` | TOC link |

#### Paywall
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_fade` | `widget-paywall-fade` | Fade overlay |
| `cls_cta` | `widget-paywall-cta` | CTA container |
| `cls_icon` | `widget-paywall-icon` | Lock icon |
| `cls_paywall_title` | `widget-paywall-title` | Paywall heading |
| `cls_message` | `widget-paywall-message` | Paywall message |

#### Pagination
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_pager_list` | `pv-pagination` | Pagination `<ul>` wrapper |
| `cls_pager_item` | `pv-page-item` | Each `<li>` page entry |
| `cls_pager_link` | `pv-page-link` | `<a>` or `<span>` inside each item |
| `cls_pager_active` | `pv-page-active` | Added to active `<li>` |
| `cls_pager_disabled` | `pv-page-disabled` | Added to disabled `<li>` |

#### Display & Flex
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_hidden` | `hidden` | Hide element |
| `cls_flex` | `flex` | Flex container |
| `cls_flex_center` | `flex-center` | Centered both axes |
| `cls_flex_between` | `flex-between` | Space-between |
| `cls_flex_col` | `flex-col` | Vertical flex |
| `cls_flex_wrap` | `flex-wrap` | Wrapping flex |

#### Borders & Shape
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_rounded` | `rounded` | Standard rounding |
| `cls_rounded_lg` | `rounded-lg` | Large rounding |
| `cls_rounded_circle` | `rounded-circle` | Circle/pill |
| `cls_shadow` | `shadow` | Standard shadow |
| `cls_shadow_sm` | `shadow-sm` | Subtle shadow |

#### Float & Alignment
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_left` | `float-left` | Float/align left |
| `cls_right` | `float-right` | Float/align right |

#### Miscellaneous
| Variable | Semantic Default | Used For |
|----------|-----------------|----------|
| `cls_divider` | `divider` | Horizontal separator |
| `cls_sr_only` | `sr-only` | Screen reader only |

#### Spacing — Margin

Scale 0–5. Names use `left`/`right`, not `start`/`end`.

| Pattern | Count | Used For |
|---------|-------|----------|
| `cls_m_{0-5}` | 6 | All-side margin |
| `cls_mt_{0-5}` | 6 | Margin top |
| `cls_mb_{0-5}` | 6 | Margin bottom |
| `cls_ml_{0-5}` | 6 | Margin left |
| `cls_mr_{0-5}` | 6 | Margin right |
| `cls_mx_{0-5}` | 6 | Margin horizontal |
| `cls_mx_auto` | 1 | Center horizontally |
| `cls_my_{0-5}` | 6 | Margin vertical |

#### Spacing — Padding

| Pattern | Count | Used For |
|---------|-------|----------|
| `cls_p_{0-5}` | 6 | All-side padding |
| `cls_pt_{0-5}` | 6 | Padding top |
| `cls_pb_{0-5}` | 6 | Padding bottom |
| `cls_pl_{0-5}` | 6 | Padding left |
| `cls_pr_{0-5}` | 6 | Padding right |
| `cls_px_{0-5}` | 6 | Padding horizontal |
| `cls_py_{0-5}` | 6 | Padding vertical |

Note: Variable names use `left`/`right` (not `start`/`end`). Themes map the values to their framework's conventions (e.g. BS5 maps `cls_ml_3` → `ms-3`, `cls_text_left` → `text-start`).

---

### Naming Convention

- All CSS class variables start with `cls_`
- Use `cls_` + descriptive noun: `cls_list`, `cls_badge`, `cls_card_body`
- Keep defaults semantic, not framework-specific

---

## 5b. Icon Variables — The `icon_*` Pattern

Alongside `cls_*` for structural styling, widgets have access to `icon_*` variables for icon library classes. These are automatically resolved from the active theme's `icon_pack` and `icon_pack_ver` declared in `theme_info.json` and injected into every widget at render time — no configuration or provider needed.

### How It Works

The CMS reads the active theme's icon pack declaration, looks up every supported platform key in the `IconService` registry, and injects variables prefixed with `icon_` into the widget template data. For example, if the active theme declares `"icon_pack": "BootstrapIcons"` with `"icon_pack_ver": "1"`, then `icon_facebook` resolves to `bi bi-facebook`, `icon_github` resolves to `bi bi-github`, and so on.

### Available Icon Variables

**Brand icons** (29 platforms):

| Variable | Description | FA6 Example | BootstrapIcons Example |
|----------|-------------|-------------|----------------------|
| `icon_facebook` | Facebook | `fa-brands fa-facebook` | `bi bi-facebook` |
| `icon_messenger` | Facebook Messenger | `fa-brands fa-facebook-messenger` | `bi bi-messenger` |
| `icon_x` | X (Twitter) | `fa-brands fa-x-twitter` | `bi bi-twitter-x` |
| `icon_instagram` | Instagram | `fa-brands fa-instagram` | `bi bi-instagram` |
| `icon_youtube` | YouTube | `fa-brands fa-youtube` | `bi bi-youtube` |
| `icon_linkedin` | LinkedIn | `fa-brands fa-linkedin` | `bi bi-linkedin` |
| `icon_pinterest` | Pinterest | `fa-brands fa-pinterest` | `bi bi-pinterest` |
| `icon_tiktok` | TikTok | `fa-brands fa-tiktok` | `bi bi-tiktok` |
| `icon_snapchat` | Snapchat | `fa-brands fa-snapchat` | `bi bi-snapchat` |
| `icon_reddit` | Reddit | `fa-brands fa-reddit` | `bi bi-reddit` |
| `icon_discord` | Discord | `fa-brands fa-discord` | `bi bi-discord` |
| `icon_twitch` | Twitch | `fa-brands fa-twitch` | `bi bi-twitch` |
| `icon_github` | GitHub | `fa-brands fa-github` | `bi bi-github` |
| `icon_whatsapp` | WhatsApp | `fa-brands fa-whatsapp` | `bi bi-whatsapp` |
| `icon_telegram` | Telegram | `fa-brands fa-telegram` | `bi bi-telegram` |
| `icon_mastodon` | Mastodon | `fa-brands fa-mastodon` | `bi bi-mastodon` |
| `icon_tumblr` | Tumblr | `fa-brands fa-tumblr` | `bi bi-globe` |
| `icon_vimeo` | Vimeo | `fa-brands fa-vimeo-v` | `bi bi-vimeo` |
| `icon_flickr` | Flickr | `fa-brands fa-flickr` | `bi bi-globe` |
| `icon_dribbble` | Dribbble | `fa-brands fa-dribbble` | `bi bi-dribbble` |
| `icon_behance` | Behance | `fa-brands fa-behance` | `bi bi-behance` |
| `icon_medium` | Medium | `fa-brands fa-medium` | `bi bi-medium` |
| `icon_spotify` | Spotify | `fa-brands fa-spotify` | `bi bi-spotify` |
| `icon_soundcloud` | SoundCloud | `fa-brands fa-soundcloud` | `bi bi-globe` |
| `icon_slack` | Slack | `fa-brands fa-slack` | `bi bi-slack` |
| `icon_skype` | Skype | `fa-brands fa-skype` | `bi bi-skype` |
| `icon_steam` | Steam | `fa-brands fa-steam` | `bi bi-steam` |
| `icon_patreon` | Patreon | `fa-brands fa-patreon` | `bi bi-globe` |
| `icon_paypal` | PayPal | `fa-brands fa-paypal` | `bi bi-paypal` |

**Generic icons** (non-brand):

| Variable | Description | FA6 Example | BootstrapIcons Example |
|----------|-------------|-------------|----------------------|
| `icon_website` | Globe / generic website | `fa-solid fa-globe` | `bi bi-globe` |

### Usage in Templates

Always use `icon_*` variables with a `default()` fallback. The fallback ensures the widget renders correctly even if no theme is active or the theme doesn't declare an icon pack. Use Font Awesome 5/6 classes as defaults since they are the most widely used:

```
{# Correct — uses theme icon with FA fallback #}
<i class="{{ icon_facebook | default('fab fa-facebook') }}"></i>
<i class="{{ icon_website | default('fas fa-globe') }}"></i>
<i class="{{ icon_x | default('fab fa-twitter') }}"></i>

{# Wrong — hardcoded icon class, breaks on non-FA themes #}
<i class="fab fa-facebook"></i>
```

### Complete Example: Social Links in a Widget

This pattern from the **AuthorBio** widget shows `cls_*` and `icon_*` working together — structural styling from the theme's CSS framework, icon classes from the theme's icon pack:

```
<div class="{{ cls_social_links | default('widget-social-links') }}">
    {% if profile.website %}
        <a href="{{ profile.website }}"
           class="{{ cls_social_link | default('widget-social-link') }}"
           target="_blank" rel="noopener" title="Website">
            <i class="{{ icon_website | default('fas fa-globe') }}"></i>
        </a>
    {% endif %}
    {% if profile.twitter %}
        <a href="https://twitter.com/{{ profile.twitter }}"
           class="{{ cls_social_link | default('widget-social-link') }}"
           target="_blank" rel="noopener" title="Twitter">
            <i class="{{ icon_x | default('fab fa-twitter') }}"></i>
        </a>
    {% endif %}
    {% if profile.facebook %}
        <a href="https://facebook.com/{{ profile.facebook }}"
           class="{{ cls_social_link | default('widget-social-link') }}"
           target="_blank" rel="noopener" title="Facebook">
            <i class="{{ icon_facebook | default('fab fa-facebook') }}"></i>
        </a>
    {% endif %}
</div>
```

With a Bootstrap Icons theme, `icon_facebook` becomes `bi bi-facebook`. With a Tabler Icons theme, it becomes `ti ti-brand-facebook`. The widget template never changes.

### Supported Icon Packs

The CMS supports 7 icon packs. See **ThemeBuilder.md Section 11** for the full list with version numbers and example classes.

---

## 6. Available Tag Functions

| Function | Signature | Description |
|----------|-----------|-------------|
| `lang` | `{% lang 'Blog.key' %}` or `{% lang 'Blog.key' arg1 %}` | Localized string. Supports `{0}`, `{1}` placeholders. |
| `base_url` | `{% base_url 'path' %}` | Site base URL + path |
| `site_url` | `{% site_url 'path' %}` | Locale-aware site URL + path |
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

**Paywall:** `paywallTitle` ("Premium Content"), `paywallMessage` ("This content is available to premium subscribers.")

**Author:** `authorCardLabel`, `unknownAuthor`

**Category/Tag/Archive:** `categoryHeading`, `tagHeading`, `archiveHeading`, `noPostsInCategory`, `noPostsWithTag`, `noPostsInPeriod`

**Search:** `searchResultsHeading`, `searchShowingFor`, `searchNoResults`

**Comments:** `commentsHeading`, `commentFormTitle`, `commentLabel`, `commentPostBtn`, `commentModerated`, `commentLoginRequired`, `commentLoginLink`, `commentsClosed`

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
    "author": "Your Name",
    "free": true,
    "bundled": false,
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

---

## 11. Vetting

Widgets are checked against the Pubvana vetting service during discovery. This is a non-blocking background check -- widgets that have not been vetted can still be assigned and rendered, but the admin sees a status badge next to each widget in the management UI.

### Four States

| Status | Badge | Meaning |
|--------|-------|---------|
| `unknown` | Gray "Unknown" | Widget has not been checked yet (new install or network error) |
| `safe` | Green "Safe" | Widget is in the Pubvana registry and has been reviewed |
| `known` | Green "Safe" + yellow warning | Widget is in the registry but has a noted limitation or caution |
| `malicious` | Red "Not Safe" | Widget has been flagged as harmful -- a warning is displayed prominently |

### Author Normalization

The `author` field in `widget_info.json` is normalized before it is sent to the vetting service: converted to lowercase and spaces replaced with underscores. For example, `"Pubvana Team"` becomes `pubvana_team`. Use a consistent author name across all your widgets so the vetting registry can group them correctly.

### Submitting for Vetting

To have your widget reviewed and listed in the Pubvana registry, submit it at **pubvana.net/vetted/submit**. You will need to provide a ZIP containing a valid `widget_info.json`. The Pubvana team will review the code and notify you by email when a decision is made.
