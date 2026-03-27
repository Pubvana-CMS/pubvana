# Pubvana Theme Builder Reference

## Overview

Themes live at `themes/{folder}/`. Each theme is a self-contained folder containing PHP view files, a `theme_info.php` manifest, and an `assets/` subdirectory. There is no Blade, Twig, or any template engine. Every file is plain PHP.

Themes are **not locked to any CSS framework**. Bootstrap 5 and Font Awesome 6 are loaded from CDN in the default and Ember themes' `layout.php` files, but a theme author controls `layout.php` entirely and can use any framework (Tailwind, Bulma, none, etc.).

---

## Directory Structure

```
themes/
  my_theme/
    theme_info.php            ← manifest (required)
    assets/
      css/
        theme.css             ← theme-specific CSS (required by convention)
      images/                 ← optional
      js/                     ← optional
    views/
      layout.php              ← outer HTML shell (head, nav, footer, JS)
      home.php                ← blog index / front page
      post.php                ← single post
      page.php                ← static page
      category.php            ← category archive
      tag.php                 ← tag archive
      archive.php             ← date archive
      search.php              ← search results
      partials/
        post-card.php         ← post listing card
        sidebar.php           ← sidebar wrapper
        pagination.php        ← pagination wrapper
        author-card.php       ← author bio card on posts
        comment-form.php      ← comment submission form
        comments-list.php     ← comment thread
        _comment.php          ← single comment (recursive for replies)
```

All 8 views and 7 partials should be present in a full theme. A child theme can omit any view that falls back to the parent.

---

## theme_info.php (Manifest)

Returns a PHP array. Required keys: `name`, `version`. All others optional.

```php
return [
    'name'        => 'My Theme',
    'version'     => '1.0.0',
    'author'      => 'Your Name',
    'description' => 'One line description.',
    'screenshot'  => 'screenshot.png',   // relative to theme root, shown in admin
    'premium'     => true,               // optional flag

    // Parent theme for inheritance (optional)
    'parent'      => 'default',          // folder name of parent

    // Widget areas this theme declares (slug => human label)
    'widget_areas' => [
        'sidebar'        => 'Main Sidebar',
        'footer-1'       => 'Footer Column 1',
        'footer-2'       => 'Footer Column 2',
        'footer-3'       => 'Footer Column 3',
        'before-content' => 'Before Content',
    ],

    // Theme options editable in Admin > Themes > Options
    'options' => [
        'show_sidebar' => [
            'type'    => 'checkbox',   // text, checkbox, textarea, number
            'label'   => 'Show Sidebar',
            'default' => '1',
        ],
        'footer_copyright' => [
            'type'    => 'text',
            'label'   => 'Footer Copyright Text',
            'default' => '',
        ],
    ],
];
```

Widget area slugs can be anything. The system creates `widget_areas` DB rows on theme activation matching whatever slugs the theme declares.

---

## Two-Phase Render Pattern

Every content view (`home.php`, `post.php`, etc.) follows this pattern:

```php
ob_start();
// ... build inner HTML ...
$main_content = ob_get_clean();

echo theme_view(theme_layout(), [
    'seo'          => $seo ?? [],
    'primary_nav'  => $primary_nav ?? [],
    'social_links' => $social_links ?? [],
    'main_content' => $main_content,
    // + any extras like json_ld, preview_mode
]);
```

`theme_layout()` returns the absolute filesystem path to the active theme's `layout.php`, falling back to `themes/default/views/layout.php`.

`layout.php` receives `$main_content` as a string and echoes it with `<?= $main_content ?? '' ?>`.

---

## Variables Available in All Theme Views

These come from `BaseController::initController()` merged into every `$this->data` array:

| Variable | Type | Description |
|---|---|---|
| `$theme` | object or null | Active theme DB row |
| `$site_name` | string | From `setting('App.siteName')` |
| `$site_tagline` | string | From `setting('App.siteTagline')` |
| `$settings` | array | Reserved (empty) |
| `$current_locale` | string | Request locale |
| `$langSwitcher` | array | `['buttons' => [...]]` for hreflang |
| `$primary_nav` | array of objects | NavigationModel, `nav_group = 'primary'` |
| `$footer_nav` | array of objects | NavigationModel, `nav_group = 'footer'` |
| `$social_links` | array of objects | SocialModel, `is_active = 1` |
| `$plugin_menu_items` | array | Plugin-contributed nav items |

Navigation item objects have `->url`, `->label`, `->target` properties.

Social link objects have `->url`, `->icon` (Font Awesome class string), `->platform`.

---

## Per-View Variables

### home.php / category.php / tag.php / archive.php / search.php

| Variable | Type | Description |
|---|---|---|
| `$posts` | array of objects | Post objects for listing |
| `$pager` | CI4 Pager or null | Pagination object |
| `$seo` | array | Keys: `title`, `description`, `og_title`, `og_description`, `og_image` |
| `$json_ld` | string | Pre-encoded JSON-LD |

Additional per-view:

- **category.php**: `$category` object with `->name`, `->slug`, `->description`
- **tag.php**: `$tag` object with `->name`, `->slug`
- **archive.php**: `$year`, `$month` (integers); `$archive` object with `->title`
- **search.php**: `$query` (string)

### post.php

| Variable | Type | Description |
|---|---|---|
| `$post` | object | Full post object (see properties below) |
| `$comments` | array | Nested comment tree, each has `->children` |
| `$author_profile` | object or null | Author profile data |
| `$seo` | array | SEO metadata |
| `$json_ld` | string | JSON-LD markup |
| `$paywall` | bool | True if premium post and user not logged in |
| `$preview_mode` | bool | True when accessed via preview token |

**Post object properties**: `->title`, `->slug`, `->content`, `->body` (raw), `->excerpt`, `->featured_image` (relative path), `->published_at`, `->views`, `->author_id`, `->is_premium`, `->content_type`

**Author profile properties**: `->display_name`, `->bio`, `->avatar`, `->website`, `->twitter`, `->facebook`, `->linkedin`, `->email`, `->username`

### page.php

| Variable | Type | Description |
|---|---|---|
| `$page` | object | Page object with `->title`, `->content`, `->content_type` |
| `$seo` | array | SEO metadata |

---

## Helper Functions Available in Theme Views

Defined in `app/Helpers/cms_helper.php`, autoloaded globally.

| Function | Returns | Description |
|---|---|---|
| `active_theme()` | object or null | Active theme DB row |
| `theme_url(string $path)` | string | Full URL to `public/themes/{folder}/{path}` |
| `theme_layout()` | string | Absolute path to active theme's layout.php |
| `theme_view(string $path, array $data)` | string | Extract + include a PHP file |
| `widget_area(string $slug)` | string | Renders all widgets assigned to that area |
| `site_name()` | string | Site name from settings |
| `site_tagline()` | string | Site tagline from settings |
| `post_url(string $slug)` | string | `base_url('blog/{slug}')` |
| `category_url(string $slug)` | string | `base_url('category/{slug}')` |
| `tag_url(string $slug)` | string | `base_url('tag/{slug}')` |
| `render_content(object $entity)` | string | Renders Markdown or raw HTML based on `$entity->content_type` |
| `excerpt(string $text, int $length)` | string | Strip tags + truncate at word boundary |
| `slug_from_title(string $title)` | string | URL-safe slug |

All standard CI4 helpers are also available: `base_url()`, `esc()`, `setting()`, `csrf_field()`, `auth()`, `lang()`, etc.

---

## Theme Options

Options are declared in `theme_info.php` under `'options'`. Supported field types: `text`, `checkbox`, `textarea`, `number`.

Stored in the `theme_options` table: `(id, theme_id, option_key, option_value TEXT)`.

Retrieval via `ThemeService`:
- `getThemeOption(int $themeId, string $key, mixed $default)` — single value
- `saveThemeOption(int $themeId, string $key, mixed $value)` — upsert

Admin UI at `/admin/themes/{id}/options` generates the form from the `options` array in `theme_info.php`.

Currently both built-in themes query options inline from `db_connect()->table('theme_options')` at the top of each view file rather than going through the service.

---

## Theme Inheritance (Child Themes)

`theme_info.php` can declare `'parent' => 'default'` (folder name of parent theme).

`ThemeService::view()` checks for the file in the active theme first. If missing, it looks in the parent theme's `views/` directory. This allows a child theme to override only specific views.

---

## Widget System

Widgets live at `WIDGETS_PATH` (`widgets/`).

### Built-in Widgets

`ad_unit`, `archive_list`, `author_bio`, `categories_list`, `recent_comments`, `recent_posts`, `related_posts`, `search_form`, `social_links`, `table_of_contents`, `tag_cloud`, `text_block`

### Widget Structure

```
widgets/
  my_widget/
    widget_info.php          ← returns info array (name, description, version, options)
    MyWidgetWidget.php       ← class extending BaseWidget
    views/
      widget.php             ← frontend render view
      admin_form.php         ← optional: custom admin form for complex options
```

**Class naming**: folder `recent_posts` → class `RecentPostsWidget` → file `RecentPostsWidget.php`

**BaseWidget** (at `app/Libraries/BaseWidget.php`) requires `getInfo(): array`. Override `buildOutput(array $options): string` for custom data fetching.

### Rendering in Themes

`widget_area('sidebar')` → `WidgetService::renderArea('sidebar')` → queries `widget_instances` joined to `widget_areas` and `widgets` filtered by area slug and `is_active = 1` → instantiates each widget class → calls `render($options)` → concatenates HTML.

Widget options stored in `widget_instances.options_json` (TEXT, JSON object).

### Widget CSS Conventions

Widgets emit a wrapping `<div class="widget ...">` with `<h4 class="widget-title">`. The default theme's CSS targets `.widget` and `.widget-title`. Ember targets `.ember-sidebar .widget` and `.ember-sidebar .widget-title`.

---

## CSS Frameworks and Assets

Both built-in themes load CSS from CDN in `layout.php`:

- **Bootstrap 5.3.2** — `cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css`
- **Font Awesome 6.5.0** — `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css`
- **Bootstrap 5.3.2 JS bundle** — loaded at bottom of `<body>`

A theme author controls `layout.php` entirely and can use any CSS framework or none at all. There is no global CSS injected from outside the theme.

The only locally-served asset per theme is `assets/css/theme.css`, referenced via `theme_url('css/theme.css')`.

Ember additionally loads Google Fonts (Inter + Lora) and injects a `<style>` block for CSS custom properties (`--ember-accent`, `--ember-accent-dark`) derived from the `accent_color` theme option.

---

## Navigation Menus

`$primary_nav` and `$footer_nav` are arrays of objects with `->url`, `->label`, `->target`.

Both built-in themes hard-code Home and Blog links before looping `$primary_nav` in `layout.php`. `$footer_nav` is available but neither built-in theme renders it (they use footer widget areas instead).

---

## Pagination

CI4's pager is used. The pagination partial calls `$pager->links('default', 'default_full')`.

The `default_full` template is CI4's built-in. An additional `bootstrap_full` template exists at `app/Views/bootstrap_full.php` (used by admin panel).

---

## SEO and Analytics

The `$seo` array passed to `layout.php` has keys: `title`, `description`, `og_title`, `og_description`, `og_image`.

`layout.php` handles: `<title>`, `<meta name="description">`, Open Graph `<meta>` tags, hreflang `<link>` tags, Google Analytics gtag snippet, and JSON-LD `<script>` block.

---

## Paywall

The paywall CTA is NOT in the theme. It lives at `app/Views/partials/paywall.php` and is included via CI4's native `view('partials/paywall')`. Both themes' `post.php` check `$paywall` (bool) and either render excerpt + paywall CTA or full `render_content($post)`. The paywall view includes its own inline `<style>` block.

---

## Multi-Language / i18n Support

Pubvana supports 6 locales out of the box: English (en), Spanish (es), French (fr), Indonesian (id), Portuguese (pt), Slovak (sk).

### How It Works

- `lang('Blog.keyName')` resolves strings from `app/Language/{locale}/Blog.php`
- The active locale is set automatically from the URL (`/{locale}/blog/...`) or defaults to `en`
- `$current_locale` is available in all theme views (set by BaseController)
- `$langSwitcher` provides pre-built data for rendering a language switcher UI

### Using `lang()` in Theme Views

Every user-visible string should use `lang()` instead of hardcoded text. The `Blog.*` keys cover all standard theme strings.

```php
// Simple string
<?= lang('Blog.readMore') ?>

// With placeholder substitution ({0}, {1}, etc.)
<?= lang('Blog.views', [number_format($post->views)]) ?>
<?= lang('Blog.commentsHeading', [count($comments)]) ?>
```

Available `Blog.*` keys include: `home`, `blog`, `readMore`, `views`, `commentsHeading`, `commentFormTitle`, `commentLabel`, `commentPostBtn`, `commentModerated`, `commentLoginRequired`, `commentLoginLink`, `searchPlaceholder`, `search`, `searchResultsHeading`, `searchShowingFor`, `searchPostsPlaceholder`, `searchNoResults`, `categoryHeading`, `noPostsInCategory`, `tagHeading`, `noPostsWithTag`, `archiveHeading`, `noPostsInPeriod`, `noPostsYet`, `previewModeBanner`, `allRightsReserved`, `rssFeed`, `sitemap`, `authorCardLabel`, `unknownAuthor`

For the full list, see `app/Language/en/Blog.php`.

### Dynamic `<html lang>` Attribute

```php
<html lang="<?= esc(service('request')->getLocale()) ?>">
```

Do not hardcode `<html lang="en">`.

### hreflang Tags for SEO

Add to the `<head>` section of `layout.php`:

```php
<?php if (!empty($langSwitcher['buttons'])): ?>
<?php foreach ($langSwitcher['buttons'] as $btn): ?>
<link rel="alternate" hreflang="<?= esc($btn['code']) ?>" href="<?= esc(base_url(ltrim($btn['url'], '/'))) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= esc(base_url()) ?>">
<?php endif; ?>
```

### Language Switcher UI

`$langSwitcher` provides three pre-built rendering formats: `buttons`, `dropdown`, `ul`. The array is only non-empty when more than one language is active in the admin.

Each item contains: `code`, `name`, `native_name`, `url`, `direction` (ltr/rtl), `active` (bool), `css` (pre-built class strings).

Example using the `ul` format:

```php
<?php if (!empty($langSwitcher['ul'])): ?>
<nav class="pv-lang-switcher">
    <ul class="<?= $langSwitcher['ul'][0]['css']['list'] ?>">
        <?php foreach ($langSwitcher['ul'] as $item): ?>
        <li class="<?= esc($item['css']['item']) ?>">
            <a href="<?= esc(base_url(ltrim($item['url'], '/'))) ?>"
               class="<?= esc($item['css']['link']) ?>"
               <?= $item['active'] ? 'aria-current="true"' : '' ?>>
                <?= esc($item['native_name']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php endif; ?>
```

There is no shared partial for the switcher. Each theme builds its own UI.

### RTL Support

The `direction` field is available per language in `$langSwitcher` items (sourced from the `languages.direction` DB column). No theme currently handles RTL. To support it, set `<html dir="rtl">` when the current locale's direction is `rtl`.

### Theme-Specific Language Keys

Themes use the app's `Blog.*` keys. There is no automatic mechanism for a theme to ship its own language files from within the theme folder. If a theme needs custom keys, add them to `app/Language/{locale}/Blog.php` or create a new file at `app/Language/{locale}/ThemeName.php` and call via `lang('ThemeName.keyName')`.

### Date Formatting

PHP's `date()` function is not locale-aware. Both built-in themes use `date('F j, Y', ...)` which always outputs English month names regardless of locale. For locale-aware dates, use PHP's `IntlDateFormatter` (requires the `intl` extension, which is already a Pubvana requirement).

---

## Asset Symlink

`ThemeService::symlinkAssets(string $folder)` creates:

```
{FCPATH}themes/{folder}  →  {THEMES_PATH}{folder}/assets
```

This makes `theme_url('css/theme.css')` resolve to a web-accessible URL. The symlink is created automatically on theme activation and when visiting Admin > Themes.

---

## Key Path Constants

```php
THEMES_PATH   = ROOTPATH . 'themes/'       // e.g. /var/www/html2/themes/
WIDGETS_PATH  = ROOTPATH . 'widgets/'      // e.g. /var/www/html2/widgets/
FCPATH        = __DIR__ in public/index.php // e.g. /var/www/html2/public/
ROOTPATH      = derived from Paths.php     // e.g. /var/www/html2/
```

---

## Key Files

| File | Purpose |
|---|---|
| `app/Services/ThemeService.php` | Theme discovery, activation, view rendering, symlinks, options |
| `app/Helpers/cms_helper.php` | `theme_view()`, `theme_url()`, `theme_layout()`, `widget_area()`, URL helpers |
| `app/Libraries/BaseWidget.php` | Base class all widgets extend |
| `app/Services/WidgetService.php` | Widget rendering by area |
| `app/Controllers/Admin/Themes.php` | Admin theme management, options UI |
| `app/Config/Constants.php` | `THEMES_PATH`, `WIDGETS_PATH` definitions |
| `app/Controllers/BaseController.php` | Injects global variables into all views |
| `app/Language/en/Blog.php` | Master English strings for all theme keys |
| `app/Libraries/LanguageSwitcher.php` | Builds language switcher data arrays with URL rewriting |
| `app/Models/LanguageModel.php` | DB access for the `languages` table |
