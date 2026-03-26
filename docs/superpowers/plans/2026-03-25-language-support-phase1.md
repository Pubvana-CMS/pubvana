# v1 Migration Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore features that were present in Pubvana v1 (CI3) but dropped or broken during the CI4 rewrite: multi-language support with lang() calls, honeypot spam protection, and Atom feeds.

**Architecture (language support):** `languages` table stores available locales → admin enables/disables/sets default → `Config\App::$supportedLocales` synced before routing via `Config\Events` pre_system or `Config\Registrar` → CI4 `{locale}` route prefix handles switching → `LanguageSwitcher` library builds three data array formats (buttons, dropdown, ul) passed to every public view → all hardcoded English strings replaced with `lang()` calls → CI4's built-in English fallback handles missing translations.

**Locale sequencing note:** `$supportedLocales` must be populated **before** route matching occurs, not in `BaseController::initController()` (which runs after routing). Use a `pre_system` event in `app/Config/Events.php` or a `Registrar` class to query active languages and set `$supportedLocales` early enough for the `{locale}` route segment validation to work.

**Tech Stack:** CI4 Language class, `{locale}` route segment, Shield's existing auth translations (22 languages).

**Scope note:** This is UI string translation only — same as v1. Blog post/page content translation is a separate future feature. Ember theme is out of scope for this phase — only the default theme gets `lang()` wiring.

**Config note:** `Config\App::$negotiateLocale` stays `false`. We use URL-based locale detection via `{locale}` route segments, not Accept-Language header negotiation.

---

## String Inventory

### v1 Language Keys (to port)

| File | Key Count | Notes |
|------|-----------|-------|
| `admin_lang.php` | ~400 | Full admin panel — settings, posts, pages, categories, links, nav, comments, themes, widgets, social, languages, dashboard, updates |
| `blog_lang.php` | ~100 | Public blog — headers, buttons, comments, search, contact, email notifications, errors, pagination |
| `pages_lang.php` | 1 | "Edit Page" |
| **Total v1 keys** | **~500** | |

### v2 New Strings (not in v1)

The CI4 rewrite added features v1 didn't have. These need new `lang()` keys:

| Area | Est. New Keys | Features |
|------|--------------|----------|
| Admin views | ~200 | Media library, marketplace, analytics, affiliates, broken links, activity log, backup, import, 2FA, revisions, schedule, store, premium settings, SEO settings |
| Public theme views | ~30 | Reading time, preview mode, premium paywall, schema markup |
| Controller flash messages | ~170 | All `->with('message', '...')` and `setFlashdata()` calls |
| **Total new keys** | **~400** | |

### Grand Total: ~900 keys across 3 language files

Split into CI4 format:
- `app/Language/{locale}/Admin.php` — ~600 keys (v1 admin + new admin features + flash messages)
- `app/Language/{locale}/Blog.php` — ~130 keys (v1 blog + new public features)
- v1's `Pages.php` had 1 key — merge into `Blog.php`, no separate Pages file

---

## Translation Matrix

| Locale | Code | v1 Status | Plan |
|--------|------|-----------|------|
| English | `en` | Source | Port all v1 keys + add ~400 new keys |
| French | `fr` | ~95% admin, ~70% blog | Port + complete missing keys |
| Indonesian | `id` | Blog ~70% | Port + complete all missing (admin + blog) |
| Portuguese | `pt` | Blog ~70% | Port + complete all missing (admin + blog) |
| Slovak | `sk` | Blog ~15% (headers only) | Port + complete all missing (admin + blog) |
| Spanish (Latin Am.) | `es` | New | Full translation |
| All other 16 | various | English placeholders in v1 | No files — CI4 falls back to English automatically |

---

## File Structure

| Action | File | Responsibility |
|--------|------|---------------|
| Create | `app/Database/Migrations/2026-03-25-120000_CreateLanguagesTable.php` | `languages` table |
| Create | `app/Database/Seeds/LanguageSeeder.php` | 22 pre-seeded language rows |
| Create | `app/Models/LanguageModel.php` | getActive, getDefault, toggleActive, makeDefault, getSupportedLocales |
| Create | `app/Controllers/Admin/Languages.php` | index, enable, disable, makeDefault |
| Create | `app/Views/admin/languages/index.php` | Admin language management table |
| Create | `app/Libraries/LanguageSwitcher.php` | Builds buttons/dropdown/ul arrays from active languages + current URL |
| Modify | `app/Controllers/BaseController.php` | Sync supportedLocales on boot, set locale, build `$lang` arrays, pass to views |
| Modify | `app/Config/Routes.php` | Add `{locale}` group wrapping all public routes |
| Create | `app/Language/en/Admin.php` | English admin keys (~600) |
| Create | `app/Language/en/Blog.php` | English blog keys (~130) |
| Create | `app/Language/fr/Admin.php` | French admin |
| Create | `app/Language/fr/Blog.php` | French blog |
| Create | `app/Language/id/Admin.php` | Indonesian admin |
| Create | `app/Language/id/Blog.php` | Indonesian blog |
| Create | `app/Language/pt/Admin.php` | Portuguese admin |
| Create | `app/Language/pt/Blog.php` | Portuguese blog |
| Create | `app/Language/sk/Admin.php` | Slovak admin |
| Create | `app/Language/sk/Blog.php` | Slovak blog |
| Create | `app/Language/es/Admin.php` | Spanish admin |
| Create | `app/Language/es/Blog.php` | Spanish blog |
| Modify | All `app/Views/admin/*.php` (39 content views; `layouts/main.php` listed separately above) | Replace hardcoded strings with `lang('Admin.key')` |
| Modify | All `themes/default/views/*.php` (15 files) | Replace hardcoded strings with `lang('Blog.key')` |
| Modify | All `app/Controllers/Admin/*.php` | Replace hardcoded flash messages with `lang('Admin.key')` |
| Modify | `app/Controllers/Blog.php`, `Contact.php`, `Search.php` | Replace hardcoded strings with `lang('Blog.key')` |
| Modify | `themes/default/views/layout.php` | Dynamic `<html lang="">`, hreflang `<link>` tags |
| Modify | `app/Views/admin/layouts/main.php` | Add Languages nav link to sidebar (sidebar is inline in this file, not a separate partial) |

---

## Language Switcher — Data Arrays

`LanguageSwitcher::build()` returns an associative array with three keys. Each contains an array of language items. The theme author picks whichever format they want and builds their own markup.

### `$langSwitcher['buttons']`

```php
[
    [
        'code'        => 'en',
        'name'        => 'English',
        'native_name' => 'English',
        'url'         => '/en/blog/my-post',
        'direction'   => 'ltr',
        'active'      => true,
        'css'         => [
            'btn' => 'pv-lang-btn pv-lang-btn--en pv-lang-btn--ltr pv-lang-btn--active',
        ],
    ],
    [
        'code'        => 'ar',
        'name'        => 'Arabic',
        'native_name' => 'العربية',
        'url'         => '/ar/blog/my-post',
        'direction'   => 'rtl',
        'active'      => false,
        'css'         => [
            'btn' => 'pv-lang-btn pv-lang-btn--ar pv-lang-btn--rtl',
        ],
    ],
]
```

### `$langSwitcher['dropdown']`

Same item structure, different css keys:
```php
'css' => [
    'select' => 'pv-lang-select',
    'option' => 'pv-lang-option pv-lang-option--en pv-lang-option--ltr pv-lang-option--active',
]
```

### `$langSwitcher['ul']`

Same item structure, different css keys:
```php
'css' => [
    'list' => 'pv-lang-list',
    'item' => 'pv-lang-item pv-lang-item--en pv-lang-item--ltr pv-lang-item--active',
    'link' => 'pv-lang-link pv-lang-link--en',
]
```

### CSS Class Pattern

All classes prefixed `pv-lang-` to avoid collision with theme CSS.

| Class | Purpose |
|-------|---------|
| `pv-lang-btn` / `pv-lang-option` / `pv-lang-item` / `pv-lang-link` | Base element class |
| `pv-lang-{element}--{code}` | Target specific language (e.g. `pv-lang-btn--fr`) |
| `pv-lang-{element}--ltr` / `--rtl` | Target by text direction |
| `pv-lang-{element}--active` | Current active language |
| `pv-lang-select` / `pv-lang-list` | Container classes for dropdown/ul |

Theme author receives raw arrays, builds whatever markup they want. No HTML generated by the core.

---

## Languages Table

```sql
CREATE TABLE `languages` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`        VARCHAR(10)  NOT NULL UNIQUE,
    `name`        VARCHAR(100) NOT NULL,
    `native_name` VARCHAR(100) NOT NULL,
    `direction`   ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 0,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NULL,
    `updated_at`  DATETIME     NULL
);
```

### Seed Data (22 languages)

| code | name | native_name | direction | is_default | is_active |
|------|------|-------------|-----------|------------|-----------|
| en | English | English | ltr | 1 | 1 |
| fr | French | Français | ltr | 0 | 0 |
| es | Spanish | Español | ltr | 0 | 0 |
| pt | Portuguese | Português | ltr | 0 | 0 |
| id | Indonesian | Bahasa Indonesia | ltr | 0 | 0 |
| sk | Slovak | Slovenčina | ltr | 0 | 0 |
| de | German | Deutsch | ltr | 0 | 0 |
| it | Italian | Italiano | ltr | 0 | 0 |
| ja | Japanese | 日本語 | ltr | 0 | 0 |
| ru | Russian | Русский | ltr | 0 | 0 |
| ar | Arabic | العربية | rtl | 0 | 0 |
| bg | Bulgarian | Български | ltr | 0 | 0 |
| cs | Czech | Čeština | ltr | 0 | 0 |
| hu | Hungarian | Magyar | ltr | 0 | 0 |
| lv | Latvian | Latviešu | ltr | 0 | 0 |
| no | Norwegian | Norsk | ltr | 0 | 0 |
| pl | Polish | Polski | ltr | 0 | 0 |
| sl | Slovenian | Slovenščina | ltr | 0 | 0 |
| tr | Turkish | Türkçe | ltr | 0 | 0 |
| uk | Ukrainian | Українська | ltr | 0 | 0 |
| zh-Hans | Chinese (Simplified) | 简体中文 | ltr | 0 | 0 |
| zh-Hant | Chinese (Traditional) | 繁體中文 | ltr | 0 | 0 |

Only English active+default by default. Admin enables others.

---

## Admin Languages Page

Same as v1 — no add/edit/delete. Languages are pre-seeded. Admin can only:
- **Enable** — makes language available to visitors
- **Disable** — removes from visitor picker (cannot disable default)
- **Make Default** — sets as default locale, auto-enables it

Table columns: Language, Native Name, Code, Direction, Is Default, Enabled, Actions.

---

## Route Structure

```php
// Localized public routes — CI4 validates {locale} against $supportedLocales
$routes->group('{locale}', ['namespace' => 'App\Controllers'], function ($routes) {
    // All existing public routes duplicated here
    $routes->get('/',                    'Blog::index');
    $routes->get('blog',                'Blog::index');
    $routes->get('blog/(:segment)',     'Blog::post/$1');
    $routes->get('category/(:segment)', 'Blog::category/$1');
    $routes->get('tag/(:segment)',      'Blog::tag/$1');
    $routes->get('archive/(:num)/(:num)', 'Blog::archive/$1/$2');
    $routes->get('search',              'Search::index');
    $routes->get('contact',             'Contact::index');
    $routes->post('contact',            'Contact::send');
    $routes->get('feed',                'Feed::index');
    $routes->get('atom',                'AtomFeed::index');
    $routes->get('preview/(:segment)',  'Blog::preview/$1');
    $routes->get('go/(:segment)',       'Affiliates::redirect/$1');
    $routes->get('sitemap.xml',         'Sitemap::index');
    $routes->get('news-sitemap.xml',    'NewsSitemap::index');
    $routes->get('(:segment)',          'Pages::show/$1');  // catch-all for pages — MUST be last in group
});

// Non-prefixed routes remain as default locale fallback
$routes->get('blog/(:segment)', 'Blog::post/$1');
// ... all non-prefixed public routes duplicated here as default locale fallback
```

Admin routes stay unprefixed — admin panel uses whatever language the admin has set as default, not per-URL locale switching.

---

## BaseController Changes

**Pre-routing** (in `Config\Events` `pre_system` or `Config\Registrar`):

1. Query `LanguageModel::getActive()` — cache result (key: `active_languages`, TTL: 1 hour)
2. Build `$supportedLocales` array from active language codes, push to `Config\App::$supportedLocales`
3. This must happen before routing so `{locale}` segment validation works

**In `initController()`:**

4. Get current locale from `$this->request->getLocale()` (CI4 sets this from `{locale}` route segment)
5. If no locale in URL, use default from `languages` table
6. Call `Services::language()->setLocale($locale)`
7. Initialize `$this->data['langSwitcher']` to empty array as default (prevents undefined variable in views during incremental implementation)
8. For public controllers only: instantiate `LanguageSwitcher` with active languages + current URI + current locale, set `$this->data['langSwitcher']` to the result
9. Pass `$langSwitcher` to all public views via `$this->data` array (theme views use `ThemeService::view()` which calls `extract($data)`, NOT CI4's `View::setVar`)
10. Admin controllers skip the switcher build entirely — admin panel does not need it

**Note:** The variable is named `$langSwitcher` (not `$lang`) to avoid confusion with v1's `$lang` array and CI4's `lang()` helper function.

---

## SEO

In theme `layout.php` `<head>`:

- `<html lang="<?= service('request')->getLocale() ?>">`
- For each active language, output: `<link rel="alternate" hreflang="{code}" href="{absolute_localized_url}" />`
- Include `<link rel="alternate" hreflang="x-default" href="{absolute_default_locale_url}" />`
- **hreflang URLs must be absolute** (include scheme + host via `base_url()`). Google requires this.

---

## Key Mapping: v1 → CI4

v1 used flat `$lang['key']` format. CI4 uses nested `return ['key' => 'value']` called via `lang('File.key')`.

### Naming Convention

v1 keys map directly but grouped by file:
- `$lang['settings_help_txt']` → `lang('Admin.settingsHelpTxt')` (camelCase keys in CI4)
- `$lang['btn_read_more']` → `lang('Blog.btnReadMore')`

New v2 keys follow the same camelCase pattern.

### v1 Keys That Don't Apply to v2

These v1 keys are dropped (feature removed or replaced):

| v1 Key Pattern | Reason |
|---------------|--------|
| `link_*` (blogroll links) | Replaced by Affiliate Links (separate feature) |
| `*recaptcha*` | Replaced by hCaptcha |
| `*atom*` | Keep — Atom feed restored in Task 11 |
| `*honeypot*` | Re-enabled in Task 10 — port these keys to v2 |
| `mail_protocol_*`, `smtp_*`, `sendmail_*` | Keep — v2 has full email/SMTP settings in admin UI (Settings library → DB) |
| `*ion_auth*` | Replaced by Shield |
| `installer_dir_warning*` | No web installer in v2 |
| `comment_system_*` (Facebook comments) | v2 uses native comments only |
| `*notices*` (email subscriptions) | Not yet implemented in v2 (on roadmap) |

---

## Tasks

### Task 1: Migration + Seeder + Model

**Files:**
- Create: `app/Database/Migrations/2026-03-25-120000_CreateLanguagesTable.php`
- Create: `app/Database/Seeds/LanguageSeeder.php`
- Create: `app/Models/LanguageModel.php`

- [ ] Write migration for `languages` table per schema above
- [ ] Write seeder with 22 language rows
- [ ] Write `LanguageModel` with: `getActive()`, `getDefault()`, `toggleActive($id)`, `makeDefault($id)`, `getSupportedLocales()`
- [ ] `makeDefault()` sets all `is_default=0` then target `is_default=1, is_active=1`
- [ ] `toggleActive()` prevents disabling the default language
- [ ] Run migration + seeder, verify data
- [ ] Commit

### Task 2: Admin Controller + View + Routes

**Files:**
- Create: `app/Controllers/Admin/Languages.php`
- Create: `app/Views/admin/languages/index.php`
- Modify: `app/Config/Routes.php`
- Modify: `app/Views/admin/layouts/main.php` (sidebar is inline here, not a separate partial)

- [ ] Create `Admin/Languages` controller with `index()`, `enable($id)`, `disable($id)`, `makeDefault($id)`
- [ ] Permission check in constructor (reuse existing admin auth pattern)
- [ ] Flash messages for success/failure on each action
- [ ] `enable()`, `disable()`, `makeDefault()` must bust the language cache (see Task 4 caching)
- [ ] Create admin view — SB Admin 2 table with columns: Language, Native Name, Code, Direction, Is Default, Enabled, Actions
- [ ] Action buttons: Enable/Disable (toggle), Make Default (only if not already default)
- [ ] Add routes: `admin/languages`, `admin/languages/enable/(:num)`, `admin/languages/disable/(:num)`, `admin/languages/make-default/(:num)`
- [ ] Add "Languages" link to admin sidebar in `layouts/main.php` under the "Users & Site" collapse group (update `$siteOpen` `in_array` check to include `'languages'`)
- [ ] Verify in browser
- [ ] Commit

### Task 3: LanguageSwitcher Library

**Files:**
- Create: `app/Libraries/LanguageSwitcher.php`

- [ ] Constructor accepts: array of active language rows, current URI string, current locale code
- [ ] `build()` method returns `['buttons' => [...], 'dropdown' => [...], 'ul' => [...]]`
- [ ] Each item contains: code, name, native_name, url, direction, active (bool), css (array with appropriate keys per format)
- [ ] URL builder: takes current URI, strips existing locale prefix if present, prepends new locale code
- [ ] CSS classes follow `pv-lang-{element}--{modifier}` pattern documented above
- [ ] Active language gets `--active` modifier
- [ ] RTL languages get `--rtl`, others get `--ltr`
- [ ] Commit

### Task 4: Locale Routing + BaseController Integration

**Files:**
- Modify: `app/Config/Routes.php`
- Modify: `app/Config/Events.php` (or create `app/Config/Registrar.php`)
- Modify: `app/Controllers/BaseController.php`

- [ ] Add `pre_system` event (or Registrar) to query active languages, cache result (key: `active_languages`, TTL: 1 hour), and sync to `Config\App::$supportedLocales` — this must run before routing
- [ ] Wrap all public routes in `$routes->group('{locale}', ...)` block, including `(:segment)` catch-all for Pages as last route in the group
- [ ] Keep existing non-prefixed routes as default locale fallback
- [ ] In BaseController `initController()`: detect locale from route or fall back to default, set locale via `Services::language()->setLocale()`, initialize `$this->data['langSwitcher']` to empty array
- [ ] For public controllers only: build `$langSwitcher` arrays via `LanguageSwitcher::build()`, merge into `$this->data` for theme views
- [ ] Admin controllers skip the switcher build
- [ ] Admin controller actions from Task 2 bust the `active_languages` cache on enable/disable/makeDefault
- [ ] **Edge case:** verify that the catch-all `(:segment)` route for Pages does not intercept locale prefixes (e.g. `/fr` must not try to load a page with slug "fr"). The `{locale}` group must be defined before the catch-all.
- [ ] Verify: `/fr/blog` sets French locale, `/blog` uses default, `/fr` hits Blog::index not Pages::show, `/fr/about` loads a localized page
- [ ] Commit

### Task 5: Port v1 English Language Keys

**Files:**
- Create: `app/Language/en/Admin.php`
- Create: `app/Language/en/Blog.php`

- [ ] Port all applicable v1 `admin_lang.php` keys to CI4 `return [...]` format with camelCase keys
- [ ] Drop keys for removed features (see "Keys That Don't Apply" section)
- [ ] Add new keys for v2 features: media library, marketplace, analytics, affiliates, broken links, activity log, backup, import, 2FA, revisions, schedule, store, premium settings, SEO settings
- [ ] Add all controller flash message strings as keys
- [ ] Port all applicable v1 `blog_lang.php` keys
- [ ] Add new keys for v2 public features: reading time, preview mode, premium paywall
- [ ] Commit

### Task 6: Port/Complete Translated Language Files

**Files:**
- Create: `app/Language/fr/Admin.php`, `app/Language/fr/Blog.php`
- Create: `app/Language/id/Admin.php`, `app/Language/id/Blog.php`
- Create: `app/Language/pt/Admin.php`, `app/Language/pt/Blog.php`
- Create: `app/Language/sk/Admin.php`, `app/Language/sk/Blog.php`
- Create: `app/Language/es/Admin.php`, `app/Language/es/Blog.php`

- [ ] French: port v1 translations (~95% admin, ~70% blog), complete all missing keys including v2 additions
- [ ] Indonesian: port v1 blog translations (~70%), translate all admin keys + missing blog keys
- [ ] Portuguese: port v1 blog translations (~70%), translate all admin keys + missing blog keys
- [ ] Slovak: port v1 blog translations (~15% headers), translate everything else
- [ ] Spanish (Latin American): full translation of all keys from scratch
- [ ] Commit per language (5 commits) — each commit message must include: `AI Translated: verification needed from native speaker`

### Task 7: Wire `lang()` Into Admin Views

**Files:**
- Modify: `app/Views/admin/layouts/main.php` (sidebar labels, topbar text, section headers like "Content", "Appearance", "Users & Site", "Tools", "Marketplace", "Dashboard")
- Modify: All 39 content view files in `app/Views/admin/` subdirectories

- [ ] Start with `layouts/main.php` — all sidebar section headers and navigation labels
- [ ] Replace hardcoded strings in each admin view with `lang('Admin.key')` calls
- [ ] Work through by section: dashboard, posts, pages, categories, tags, comments, media, themes, widgets, navigation, users, settings, social, redirects, marketplace, store, analytics, affiliates, broken links, activity log, backup, import, 2FA, schedule, updates
- [ ] Commit after each major section (dashboard+posts, pages+categories+tags, etc.)

### Task 8: Wire `lang()` Into Controllers

**Files:**
- Modify: All `app/Controllers/Admin/*.php`
- Modify: `app/Controllers/Blog.php`, `Contact.php`, `Search.php`

- [ ] Replace all hardcoded flash message strings with `lang('Admin.key')` or `lang('Blog.key')`
- [ ] Replace any hardcoded strings in public controllers
- [ ] Commit

### Task 9: Wire `lang()` Into Theme Views + SEO

**Files:**
- Modify: All 15 files in `themes/default/views/`
- Modify: `themes/default/views/layout.php`

- [ ] Replace hardcoded strings in theme views with `lang('Blog.key')` calls
- [ ] Add dynamic `<html lang="">` attribute to layout
- [ ] Add `<link rel="alternate" hreflang="...">` tags in `<head>` for each active language — URLs must be absolute via `base_url()`
- [ ] Add `<link rel="alternate" hreflang="x-default">` for default language — also absolute URL
- [ ] Verify language switching works end-to-end in browser
- [ ] Commit

### Task 10: Re-enable Honeypot Spam Protection

v1 had honeypot enabled. It was dropped in the CI4 rewrite. CI4 has a built-in `Honeypot` filter (`CodeIgniter\Filters\Honeypot`) already registered as an alias in `app/Config/Filters.php` but not applied to any routes. `Config\Honeypot` exists with default config. This needs research before implementation.

**Files:**
- Modify: `app/Config/Filters.php` (apply honeypot filter to public POST routes)
- Modify: `app/Config/Honeypot.php` (review/adjust field name, template, container settings)
- Verify: `themes/default/views/partials/comment-form.php` and `app/Views/contact_form.php` (confirm auto-injected field appears)

**Note:** CI4's Honeypot filter `after()` method auto-injects the hidden field via body string replacement (`str_ireplace('</form>', ...)`) — manual field additions to form views should NOT be needed. Verify this during research; do not manually add fields that would duplicate auto-injection.

- [ ] Research CI4 4.7's Honeypot filter: confirm `after()` auto-injects hidden fields, how `before()` intercepts filled honeypot POST data, what response it gives when triggered
- [ ] Apply honeypot filter to public POST routes: `blog/*` (comment submission), `contact` — use `$filters` array in `Filters.php` (not inline route filters) so it covers both locale-prefixed and non-prefixed routes
- [ ] Review `Config\Honeypot` defaults — field name should not be obvious (change from default `honeypot` to something less detectable)
- [ ] Verify: auto-injected hidden field appears in comment form and contact form HTML output (no manual edits needed)
- [ ] Verify: normal form submission works, bot-filled honeypot field is rejected
- [ ] Commit

### Task 11: Add Atom Feed

v1 had Atom feeds alongside RSS. Dropped in the rewrite with no justification. Atom is the newer spec with better XML structure, content typing, and namespace support. v2 already has an RSS feed controller (`app/Controllers/Feed.php`) — Atom should exist alongside it.

**Files:**
- Create: `app/Controllers/AtomFeed.php`
- Modify: `app/Config/Routes.php` (add `/atom` route)
- Modify: `themes/default/views/layout.php` (add `<link rel="alternate" type="application/atom+xml">` autodiscovery tag)

- [ ] Research Atom 1.0 spec requirements (RFC 4287) and review v1's Atom implementation if available
- [ ] Create `AtomFeed` controller modeled on existing `Feed.php` — same post query, Atom XML output
- [ ] Add route: `$routes->get('atom', 'AtomFeed::index')`
- [ ] Add Atom autodiscovery `<link>` in theme layout `<head>` alongside existing RSS link
- [ ] Add `lang()` keys for any Atom-specific strings
- [ ] Verify valid Atom XML output
- [ ] Commit

### Task 12: Update README

- [ ] Add checked items to README.md Platform Features roadmap: Multi-language support, Honeypot spam protection, Atom feed
- [ ] Update "Translators & Translations" section: add Spanish (Latin American), note AI translations need native speaker verification
- [ ] Commit
