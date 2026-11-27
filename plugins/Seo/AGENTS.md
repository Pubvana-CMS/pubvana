# AGENTS.md: Seo plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Seo manages on-page SEO for Pubvana: head meta tags with per-content overrides, Open Graph and Twitter cards, a connected JSON-LD graph, an XML sitemap, robots.txt with per-bot AI crawler directives, llms.txt for AI/GEO, and an in-editor content analysis panel. It also carries an AI Assistant API (`/api/ai/seo/*`) that lets a granted key read and change the site SEO settings and score a draft.

- **Package:** `pubvana/seo` (`pubvana.json:2`), semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`str_contains` at `Models/SeoMeta.php:94, 102`)
- **Namespace:** `Pubvana\Plugins\Seo` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, `Database\Migrations`, and `Views` sub-trees
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (author lookup at `Services/SeoService.php:544`); plugin hosts Blog, Pages, Profiles, Media (image pickers); core services `$app->settings()`, `adext()`, `request()`, `db()`, `media()`, `pages()`, `blog()`, `profiles()`, `url()` (site origin for emitted URLs), plus `view()->fetch()` and the `halt`/`json`/`header` helpers
- **Config:** `Config/Config.php`: `routePrepend` (empty string, so the public files live at root level) and `aiApiPrefix` (`/api/ai/seo`, the base for the AI Assistant grants and routes)
- **Docs:** `README.md`

## Project guidelines

1. **Never let `renderHead()` emit `<title>`.** It renders description, canonical, robots, hreflang, Open Graph, Twitter, verification, injected, and JSON-LD lines only; the theme's header renders the title from `buildTitle()` (`Services/SeoService.php:392-396`). Reason: a duplicated `<title>` tag breaks the single-title rule the theme relies on.
2. **Escape every value before emitting it.** Head tags use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; the sitemap uses `ENT_XML1` (`Services/SeoService.php:401-461`, `Services/SitemapService.php:151`). The only exception is `addTag()`, which injects raw HTML intentionally. Reason: title/description/canonical content is admin-controlled and must stay inert.
3. **Publish only indexable URLs.** Sitemaps and llms.txt skip any item whose `seo_meta` robots directive is `noindex` (`Services/SitemapService.php:70-95`, `Services/LlmsTxtService.php:101-124`). Reason: telling crawlers to index a noindex URL defeats the directive.
4. **Fetch files are always served; there is no on/off switch.** `/sitemap.xml`, `/robots.txt`, and `/llms.txt` always respond, so the robots.txt sitemap reference can never point at a dead URL (`Controllers/SeoPublicController.php`). New public endpoints follow the same rule, and the sitemap keeps its `X-Robots-Tag: noindex` header. Content inclusion is still tunable (`Seo.sitemap_include_pages`, `Seo.sitemap_include_posts`, `Seo.sitemap_include_archives`, and the matching `Seo.llms_txt_*` keys).
5. **The author node is the human, never an AI/model identity.** `resolveAuthor()` resolves the profile and account username, and `authorContext()` exposes it to the Blog and Pages controllers. The Person node appears for posts and pages that carry an author with a name (`Services/SeoService.php:579-620`). AI provenance is a separate signal: `digitalSourceType` is added only when the context is `ai_generated` and `Seo.ai_disclosure_enabled` is on (`Services/SchemaService.php:213-215, 235-237`). Reason: editorial responsibility stays with a person, and disclosure is a policy toggle, not a side effect of generation.
6. **Read every tunable through the `Seo.` settings namespace with an explicit default.** Examples: `title_separator` `|`, `title_template` `{title} {sep} {site_name}`, `default_language` `en` (`Services/SeoService.php:245-262, 317-319`). Reason: defaults are the fallback everywhere, so a new knob must be plumbed through settings, not hardcoded at one call site.
7. **`saveMeta()` is the single write path for `seo_meta`.** The whitelist (`meta_title`, `meta_description`, `canonical_url`, `robots_directive`, `og_title`, `og_description`, `og_image`, `og_type`, `twitter_card`, `schema_type`, `seo_score`, `hreflang` at `Services/SeoService.php:204-208`) and the focus-keywords handling (array or CSV, capped at 5 via `setFocusKeywordsArray`) stay fixed. Add a field here and add a column to the migration together.
8. **Keep URL patterns consistent across detection and generation.** `detectContent()` hardcodes `blog/{slug}`, `blog/category/{slug}`, and `page/{slug}` (`Services/SeoService.php:55-130`); sitemap and llms.txt hardcode `/blog/` and `/page/` (`Services/SitemapService.php:74, 97`, `Services/LlmsTxtService.php:60, 74`). They currently match the default route prefixes. Do not hardcode yet another variant; updating a prefix means updating all of these.
9. **Keep the JSON-LD graph connected.** All nodes carry stable `@id` references (`#org`, `#website`, `#article`, `#webpage`, `#person`, `#breadcrumb`) so publisher/author/breadcrumb link to one graph (`Services/SchemaService.php:39-99`). New nodes must join existing ids; isolated blocks would duplicate identity for crawlers.
10. **`ContentAnalysisService::analyze()` stays a pure function.** It reads only its `$data` array and returns `{score, checks, ai_score, ai_checks}` with no side effects (`Services/ContentAnalysisService.php`). The SEO checks and AI readiness checks score separately. Persistence happens later when the editor panel saves `seo_score` through `saveMeta()`. Keep analysis side-effect free.
11. **Match each AI crawler against its stance.** Training bots default to `block`; discovery, retrieval, and preview bots default to `allow`. Only `block` emits a `User-agent`/`Disallow` pair, so allowed bots ride the wildcard rather than adding named groups that would replace it (`Services/RobotsTxtService.php`). Adding a bot means one entry in `AI_CRAWLERS` with `stance`, `purpose`, and `description`; the settings key derives from the bot name. Reason: an unknown bot must never silently default to `allow`.
12. **Keep the read-model light and per-request.** `getMetaField()` caches the meta record statically per content context for the life of the request (`Services/SeoService.php:480-503`). Reason: renderHead calls it many times; the cache keeps one query per content item.
13. **Every emitted URL builds on the configured site origin, never the request Host header.** All five services get their base from `UrlService::siteOrigin()` (the `SITE_URL` deployment value; unconfigured falls back to the constant `http://localhost`) and `getCurrentUrl()` contributes the request path only (`Services/SeoService.php`, `Services/SchemaService.php`, `Services/SitemapService.php`, `Services/RobotsTxtService.php`, `Services/LlmsTxtService.php`). Reason: the `Host` header is attacker-controlled and would otherwise poison canonical, `og:url`, JSON-LD `@id`, sitemap, robots.txt, and llms.txt URLs (AUDIT M4, same rule as the password-reset link in H5). Do not re-derive scheme/host from the request.
14. **`SeoSettingsService` is the single read and write path for `Seo.*` settings.** `all()` serves the admin screen and the API. `saveAdminForm()` keeps the settings form's behavior (raw strings, every boolean written, any crawler name posted), and `saveApi()` normalizes and caps input for the AI Assistant. Both use the same field lists, so a new setting goes there first, then the admin view. The API path limits crawler names to `getAiCrawlerList()` and stances to `allow`/`block`, so an API call cannot invent settings keys.
15. **The AI grants and the AI routes share `aiApiPrefix`.** `Plugin::register()` reads the config once and builds both from it, so a grant can never point at a route that does not exist. `SeoApiController` extends `AiApiController` for auth, grants, the audit log, and the response envelope, and returns 503 when the AI Assistant plugin is not loaded.

## Repository layout

```
plugins/Seo/
├── Config/Config.php                     routePrepend (empty -> root-level files), aiApiPrefix
├── Controllers/
│   ├── SeoAdminController.php            Settings page, save meta (AJAX), analyze (AJAX)
│   ├── SeoApiController.php              /api/ai/seo/* for the AI Assistant (help, settings, analyze)
│   └── SeoPublicController.php           /sitemap.xml, /robots.txt, /llms.txt
├── Database/
│   └── Migrations/2026-09-17-105237_CreateSeoMetaTable.php
│                                        seo_meta (unique (content_type, content_id); indexed content_type)
├── Models/SeoMeta.php                    seo_meta table; focus keywords JSON, noindex/nofollow helpers, stats
├── Services/
│   ├── SeoService.php                    $app->seo(): context detect, head assembly, meta CRUD, title/canonical/OG
│   ├── SchemaService.php                 $app->seoSchema(): single connected JSON-LD @graph
│   ├── SitemapService.php                $app->seoSitemap(): homepage, pages, posts, category/tag archives
│   ├── RobotsTxtService.php              $app->seoRobots(): default rules + AI crawler directives
│   ├── LlmsTxtService.php                $app->seoLlmsTxt(): llmstxt.org format for AI discoverability
│   └── ContentAnalysisService.php        $app->seoAnalysis(): 15 SEO checks + 8 AI readiness checks, separate scores
│   └── SeoSettingsService.php            $app->seoSettings(): settings reads, admin form write, normalized API write
├── Plugin.php                            Entry point; seven facades, routes, ai.grant registrations, dashboard, content.edit.panel
├── pubvana.json                          Manifest; admin.menu (SEO under settings), admin.dashboard
├── Views/admin/
│   ├── settings.php                      Global SEO settings incl. AI crawler toggles
│   ├── content-panel.php                 In-editor SEO panel (autosave + analyze)
│   └── create-notice.php                 Shown before a content item exists
└── README.md
```

## Core architecture

**Facades.** Six static-cached services are mapped: `seo` (DB + engine), `seoSchema`, `seoSitemap` (DB + engine), `seoRobots`, `seoLlmsTxt` (DB + engine), `seoAnalysis` (`Plugin.php:29-75`).

**Context detection.** Blog and Pages controllers pass `seo_context` directly: post context carries title, description, body content, image, author, author id, categories, tags, AI flag, timestamps, and OG type; page context carries the body content, author, author id, AI flag, timestamp, and OG type (`plugins/Blog/Controllers/BlogPublicController.php`, `plugins/Pages/Controllers/PagesPublicController.php`). When a controller supplies no context, `detectContent()` inspects the request URL: homepage, `blog/{slug}` post, category/tag/blog archives, and `page/{slug}` (`Services/SeoService.php:43-134`). Core's `PublicController::render()` sets the resulting context and seeds the theme `header` variable; it also adds the auto-built breadcrumbs.

**Head assembly.** `renderHead()` chains `buildDescription()` (160-char truncation, with a page-body fallback when no excerpt or meta description exists), `buildCanonical()`, `buildRobots()`, `buildHreflang()`, `buildOpenGraph()`, `buildTwitterCard()`, verification tags, plugin-injected tags, then the JSON-LD block (`Services/SeoService.php:396-471`). Per-content overrides win over context values; site-level settings backfill the rest.

**Structured data.** `SchemaService::render()` emits one `@graph`: Organization (home/post/page) with `name`, `url`, `logo`, `sameAs`, `description`, and `contactPoint`; WebSite with SearchAction, `inLanguage`, and `publisher` (homepage only); Person (post author and page creator, resolved through `SeoService::authorContext()`); BreadcrumbList (when context supplies `breadcrumbs`); and BlogPosting/WebPage as the main entity with `mainEntityOfPage`, `inLanguage`, `wordCount`, `articleSection`, and `keywords` when the context carries that data (`Services/SchemaService.php:39-99`).

**Fetch files.** `/sitemap.xml` (always served, homepage + published pages/posts + optional category/tag archives, `<loc>`/`<lastmod>` only, `noindex` excluded), `/robots.txt` (custom body or defaults, then per-bot AI `Disallow` lines grouped by discovery, retrieval, training, and preview, then sitemap reference), `/llms.txt` (always served, llmstxt.org format, 50 page/post cap, `noindex` excluded, optional `## Optional` links), and `/llms-full.txt` (always served, full text of published pages and posts capped at 500 items and 2 MB).

**Editor panel.** The `content.edit.panel` adext callable renders the panel (or a create-notice before the item exists), computing the content URL base from `SITE_URL` and the Blog/Pages route prefixes, and embedding a Media og-image picker (`Plugin.php`). The panel autosaves `seo[...]` fields to `POST /admin/seo/meta` and scores content via `POST /admin/seo/analyze`, showing a separate SEO score and AI readiness score.

**Dashboard.** Two cards: SEO Coverage (percent of published pages+posts with a `meta_title`; tone by missing count) and Avg SEO Score (`averageScore()`) (`Plugin.php:99-105, 151-188`).

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Seo/` (7 files: `SeoHostHeaderTest`, `SeoFetchFileServiceTest`, `ContentAnalysisServiceTest`, `SeoMetaModelTest`, `SeoServiceTest`, `SeoControllerTest`, `SchemaServiceXssTest`). It is also exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; the ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Seo -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Seo`
- Manual verification checklist:
  - [ ] `/sitemap.xml` is valid XML, includes homepage + published content + archives, omits no-indexed items, and always responds
  - [ ] `/robots.txt` shows defaults plus `Disallow: /` only for blocked AI crawlers, the custom body when provided, and a sitemap reference using `CMS.siteUrl`
  - [ ] `/llms.txt` follows llmstxt.org (H1, blockquote, `## Pages`/`## Blog`/`## Topics`/`## Optional`), caps at 50 each, omits no-indexed items, and always responds
  - [ ] `/llms-full.txt` carries the body text of published pages/posts, omits no-indexed items, and always responds
  - [ ] The public head shows exactly one `<title>` (from the theme), description truncated to 160, canonical from context, absolute OG image, and no `robots` directive when unset
  - [ ] `addMeta()`/`addTag()` output appears in the head; the injected raw tag is emitted verbatim
  - [ ] A post renders `og:type article`, an article JSON-LD node, and a Person author node with `sameAs`/`jobTitle`/`worksFor` from the profile; breadcrumbs render from a `breadcrumbs` context
  - [ ] An `ai_generated` post shows `digitalSourceType` only when `Seo.ai_disclosure_enabled` is on
  - [ ] `analyze` returns an SEO score and an AI readiness score with both checklists; saving the panel persists `seo_score`; a second save updates the same `(content_type, content_id)` row
  - [ ] Dashboard Coverage/Avg Score math matches published content with/without meta
  - [ ] Disabling Blog or Pages empties their sitemap/llms sections instead of failing
  - [ ] `GET /api/ai/seo/help` answers with any valid key, granted or not, and lists both grants and every field
  - [ ] `GET /api/ai/seo/settings` needs `seo.read`; `POST` needs `seo.manage`; a missing token gets 401 and a missing grant 403
  - [ ] `POST /api/ai/seo/settings` writes only the keys sent, ignores unknown names, and refuses an empty body with 422
  - [ ] `POST /api/ai/seo/analyze` returns a score and checks, and refuses a body with no title and no content with 422
  - [ ] With the AI Assistant plugin off, the SEO API answers 503 instead of crashing

Coverage: the suite covers the service, the meta model, the content analysis service, the fetch-file services, the controller, host-header safety, and schema XSS escaping.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel`, declare their table string in the constructor, and keep the `@property` docblock matched to the migration** (`Models/SeoMeta.php:9-36`).
3. **Keep focus keywords as a JSON column handled only through `getFocusKeywordsArray()`/`setFocusKeywordsArray()` (max 5)** (`Models/SeoMeta.php:67-86`).
4. **Emitted markup is always escaped** (`htmlspecialchars`, `ENT_QUOTES`); XML uses `ENT_XML1`. No raw interpolation in tag builders.
5. **Settings are read with explicit defaults every time**; booleans written as `true`/`false` via `settings->set()` (`Controllers/SeoAdminController.php:76-84`).
6. **Keep the six facade boundaries.** `seo`, `seoSchema`, `seoSitemap`, `seoRobots`, `seoLlmsTxt`, `seoAnalysis` are the fixed entry points; do not absorb one into another.
7. **AJAX endpoints return JSON envelopes with proper status codes** (`saveMeta` returns `{success,id,score}`, `analyze` returns `{score,checks}`, bad calls use 400).

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `Services/SeoService.php:396-471` | The exact head block that core injects via the theme `header` variable |
| `Services/SchemaService.php:39-99` | The connected-graph JSON-LD shape |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a per-content meta field | `saveMeta()` whitelist + `2026-09-17-105237` migration + `content-panel.php` + `build*()` consumer |
| Change world defaults | Settings view + the matching `build*()` default in the service |
| Add or change a setting | `SeoSettingsService` field list + `all()`/`describe()` + `SeoAdminController` key lists + settings view + the `build*()` consumer |
| Add an AI Assistant endpoint | `SeoApiController` action + route in `Plugin.php` + `ai.grant` entry + `SeoPluginTest` |
| Adjust AI crawler set or stances | `AI_CRAWLERS` const + `getAiCrawlerList()` descriptions + settings view |
| Add a public fetch file | Public controller route (`Plugin.php`) that always responds; inclusion toggles are the only knob |
| Exclude content from crawl surfaces | Set its `robots_directive` to a `noindex` value; sitemap/llms honor it |
| Change score weighting | `ContentAnalysisService::analyze()` check thresholds (keep score `passed/total * 100`) |
| Add a schema node | `SchemaService` builder method + link into the existing `@id` graph |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean on the app (`composer phpstan`)
- [ ] Single `<title>` rule intact; all emitted values escaped; `addTag()` still the only raw path
- [ ] `noindex` filtering, `*_enabled` gating, and the connected `@id` graph preserved
- [ ] `saveMeta()` whitelist and migration stay in lockstep; author stays human; AI disclosure stays setting-gated
- [ ] `analyze()` remains side-effect free; dashboard math resilient to missing hosts
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No localization; labels are hardcoded in views.
- A single flat sitemap; no sitemap indexes, paging, or image/video sitemaps.
- robots.txt directives are advisory text, not access enforcement; blocking a crawler there still needs server-level rules.
- No content rewrite or keyword suggestions beyond the analysis warnings.
- Known gaps to reconcile, not fixed here:
  - `analyze()` emits 15 checks (`Services/ContentAnalysisService.php:39-63`); keep the count in sync when adding or removing checks. `<!-- TODO: reconcile the check count with ContentAnalysisService::analyze -->`
  - Sitemap/llms/detectContent URL patterns hardcode the default Blog/Pages route prefixes (guideline 8). `<!-- TODO: derive prefixes through pluginLoader()->routePrefix when prefixes become configurable -->`
  - Sitemap and llms.txt are capped (`SitemapService::MAX_ITEMS`, `LlmsTxtService::MAX_SECTION_ITEMS`) rather than paged; a sitemap index is still out of scope. `<!-- TODO: sitemap index when a site outgrows the flat cap -->`
  - The dashboard coverage scan samples up to `Plugin::DASHBOARD_SCAN_LIMIT` items per type. `<!-- TODO: replace the sample with real counts when the host services expose them -->`
