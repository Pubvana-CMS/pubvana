# AGENTS.md — Seo plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Seo manages on-page SEO for Pubvana: head meta tags with per-content overrides, Open Graph and Twitter cards, a connected JSON-LD graph, an XML sitemap, robots.txt with per-bot AI crawler directives, llms.txt for AI/GEO, and an in-editor content analysis panel.

- **Package:** `pubvana/seo` (`pubvana.json:2`), semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`str_contains` at `Models/SeoMeta.php:94, 102`)
- **Namespace:** `Pubvana\Plugins\Seo` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, `Database\Migrations`, and `Views` sub-trees
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (author lookup at `Services/SeoService.php:544`); plugin hosts Blog, Pages, Profiles, Media (image pickers); core services `$app->settings()`, `adext()`, `request()`, `db()`, `media()`, `pages()`, `blog()`, `profiles()`, plus `view()->fetch()` and the `halt`/`json`/`header` helpers
- **Config:** `Config/Config.php`: `routePrepend` (empty string, so the public files live at root level)
- **Docs:** `README.md`

## Project guidelines

1. **Never let `renderHead()` emit `<title>`.** It renders description, canonical, robots, hreflang, Open Graph, Twitter, verification, injected, and JSON-LD lines only; the theme's header renders the title from `buildTitle()` (`Services/SeoService.php:392-396`, `README.md:19-27`). Reason: a duplicated `<title>` tag breaks the single-title contract the theme relies on.
2. **Escape every value before emitting it.** Head tags use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; the sitemap uses `ENT_XML1` (`Services/SeoService.php:401-461`, `Services/SitemapService.php:151`). The only exception is `addTag()`, which injects raw HTML intentionally. Reason: title/description/canonical content is admin-controlled and must stay inert.
3. **Publish only indexable URLs.** Sitemaps and llms.txt skip any item whose `seo_meta` robots directive is `noindex` (`Services/SitemapService.php:70-95`, `Services/LlmsTxtService.php:101-124`). Reason: telling crawlers to index a noindex URL defeats the directive.
4. **Gate every fetch file in its public controller.** `/sitemap.xml` and `/llms.txt` halt 404 when their `Seo.*_enabled` setting is off (`Controllers/SeoPublicController.php:28-31, 58-61`); robots.txt always responds. New public endpoints must copy that pattern and the `X-Robots-Tag: noindex` header on the sitemap.
5. **The author node is the human, never an AI/model identity.** `resolveAuthor()` resolves the profile and account username; the Person node appears only for posts with a name (`Services/SeoService.php:527-574`). AI provenance is a separate signal: `digitalSourceType` is added only when the context is `ai_generated` and `Seo.ai_disclosure_enabled` is on (`Services/SchemaService.php:213-215, 235-237`). Reason: editorial responsibility stays with a person, and disclosure is a policy toggle, not a side effect of generation.
6. **Read every tunable through the `Seo.` settings namespace with an explicit default.** Examples: `title_separator` `|`, `title_template` `{title} {sep} {site_name}`, `default_language` `en` (`Services/SeoService.php:245-262, 317-319`). Reason: defaults are the fallback everywhere, so a new knob must be plumbed through settings, not hardcoded at one call site.
7. **`saveMeta()` is the single write path for `seo_meta`.** The whitelist (`meta_title`, `meta_description`, `canonical_url`, `robots_directive`, `og_title`, `og_description`, `og_image`, `og_type`, `twitter_card`, `schema_type`, `seo_score`, `hreflang` at `Services/SeoService.php:204-208`) and the focus-keywords handling (array or CSV, capped at 5 via `setFocusKeywordsArray`) stay fixed. Add a field here and add a column to the migration together.
8. **Keep URL patterns consistent across detection and generation.** `detectContent()` hardcodes `blog/{slug}`, `blog/category/{slug}`, and `page/{slug}` (`Services/SeoService.php:55-130`); sitemap and llms.txt hardcode `/blog/` and `/page/` (`Services/SitemapService.php:74, 97`, `Services/LlmsTxtService.php:60, 74`). They currently match the default route prefixes. Do not hardcode yet another variant; updating a prefix means updating all of these.
9. **Keep the JSON-LD graph connected.** All nodes carry stable `@id` references (`#org`, `#website`, `#article`, `#webpage`, `#person`, `#breadcrumb`) so publisher/author/breadcrumb link to one graph (`Services/SchemaService.php:39-99`). New nodes must join existing ids; isolated blocks would duplicate identity for crawlers.
10. **`ContentAnalysisService::analyze()` stays a pure function.** It reads only its `$data` array and returns `{score, checks}` with no side effects (`Services/ContentAnalysisService.php:22-74`). Persistence happens later when the editor panel saves `seo_score` through `saveMeta()`. Keep analysis side-effect free.
11. **Match each AI crawler against its stance.** Training bots default to `block`, retrieval/citation bots to `allow`; only `block` emits a `User-agent`/`Disallow` pair (`Services/RobotsTxtService.php:24-37, 84-101`). Adding a bot means updating the const map, its description, and the settings-key normalization. Reason: an unknown bot must never silently default to `allow`.
12. **Keep the read-model light and per-request.** `getMetaField()` caches the meta record statically per content context for the life of the request (`Services/SeoService.php:480-503`). Reason: renderHead calls it many times; the cache keeps one query per content item.

## Repository layout

```
plugins/Seo/
├── Config/Config.php                     routePrepend (empty -> root-level files)
├── Controllers/
│   ├── SeoAdminController.php            Settings page, save meta (AJAX), analyze (AJAX)
│   └── SeoPublicController.php           /sitemap.xml, /robots.txt, /llms.txt
├── Database/
│   └── Migrations/2026-08-29-100004_CreateSeoMetaTable.php
│                                        seo_meta (unique (content_type, content_id); indexed content_type)
├── Models/SeoMeta.php                    seo_meta table; focus keywords JSON, noindex/nofollow helpers, stats
├── Services/
│   ├── SeoService.php                    $app->seo(): context detect, head assembly, meta CRUD, title/canonical/OG
│   ├── SchemaService.php                 $app->seoSchema(): single connected JSON-LD @graph
│   ├── SitemapService.php                $app->seoSitemap(): homepage, pages, posts, category/tag archives
│   ├── RobotsTxtService.php              $app->seoRobots(): default rules + AI crawler directives
│   ├── LlmsTxtService.php                $app->seoLlmsTxt(): llmstxt.org format for AI discoverability
│   └── ContentAnalysisService.php        $app->seoAnalysis(): 15 pass/fail/warning checks, score 0-100
├── Plugin.php                            Entry point; six facades, routes, dashboard, content.edit.panel
├── pubvana.json                          Manifest; admin.menu (SEO under settings), admin.dashboard
├── Views/admin/
│   ├── settings.php                      Global SEO settings incl. AI crawler toggles
│   ├── content-panel.php                 In-editor SEO panel (autosave + analyze)
│   └── create-notice.php                 Shown before a content item exists
└── README.md
```

## Core architecture

**Facades.** Six static-cached services are mapped: `seo` (DB + engine), `seoSchema`, `seoSitemap` (DB + engine), `seoRobots`, `seoLlmsTxt` (DB + engine), `seoAnalysis` (`Plugin.php:29-75`).

**Context detection.** `detectContent()` inspects the request URL: homepage, `blog/{slug}` post, category/tag/blog archives, and `page/{slug}`, each building a typed context (title, description, url, image, author, AI flag, OG type, timestamps) (`Services/SeoService.php:43-134`). Core's `PublicController::render()` calls this when the plugin is loaded and seeds the theme `header` variable (`README.md:21`).

**Head assembly.** `renderHead()` chains `buildDescription()` (160-char truncation), `buildCanonical()`, `buildRobots()`, `buildHreflang()`, `buildOpenGraph()`, `buildTwitterCard()`, verification tags, plugin-injected tags, then the JSON-LD block (`Services/SeoService.php:396-471`). Per-content overrides win over context values; site-level settings backfill the rest.

**Structured data.** `SchemaService::render()` emits one `@graph`: Organization (home/post/page), WebSite with SearchAction (homepage only), Person (post author), BreadcrumbList (when context supplies `breadcrumbs`), and BlogPosting/WebPage as the main entity (`Services/SchemaService.php:39-99`).

**Fetch files.** `/sitemap.xml` (settings-gated, homepage + published pages/posts + category/tag archives, `<loc>`/`<lastmod>` only, `noindex` excluded), `/robots.txt` (custom body or defaults, then per-bot AI `Disallow` lines, then sitemap reference), `/llms.txt` (settings-gated, llmstxt.org format, 50 page/post cap, `noindex` excluded).

**Editor panel.** The `content.edit.panel` adext callable renders the panel (or a create-notice before the item exists), computing the content URL base from `CMS.siteUrl` and the Blog/Pages route prefixes, and embedding a Media og-image picker (`Plugin.php:109-145`). The panel autosaves `seo[...]` fields to `POST /admin/seo/meta` and scores content via `GET /admin/seo/analyze`.

**Dashboard.** Two cards: SEO Coverage (percent of published pages+posts with a `meta_title`; tone by missing count) and Avg SEO Score (`averageScore()`) (`Plugin.php:99-105, 151-188`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3; the ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Seo -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] `/sitemap.xml` is valid XML, includes homepage + published content + archives, omits no-indexed items, and 404s with `Seo.sitemap_enabled` off
  - [ ] `/robots.txt` shows defaults plus `Disallow: /` only for blocked AI crawlers, the custom body when provided, and a sitemap reference using `CMS.siteUrl`
  - [ ] `/llms.txt` follows llmstxt.org (H1, blockquote, `## Pages`/`## Blog`/`## Topics`), caps at 50 each, omits no-indexed items, and 404s when disabled
  - [ ] The public head shows exactly one `<title>` (from the theme), description truncated to 160, canonical from context, absolute OG image, and no `robots` directive when unset
  - [ ] `addMeta()`/`addTag()` output appears in the head; the injected raw tag is emitted verbatim
  - [ ] A post renders `og:type article`, an article JSON-LD node, and a Person author node with `sameAs`/`jobTitle`/`worksFor` from the profile; breadcrumbs render from a `breadcrumbs` context
  - [ ] An `ai_generated` post shows `digitalSourceType` only when `Seo.ai_disclosure_enabled` is on
  - [ ] `analyze` returns a score and the checklist; saving the panel persists `seo_score` and the meta fields; a second save updates the same `(content_type, content_id)` row
  - [ ] Dashboard Coverage/Avg Score math matches published content with/without meta
  - [ ] Disabling Blog or Pages empties their sitemap/llms sections instead of failing

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

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
| `README.md` | Feature scope, head output contract, panel workflow, service reference, dependencies |
| `Services/SeoService.php:396-471` | The exact head block that core injects via the theme `header` variable |
| `Services/SchemaService.php:39-99` | The connected-graph JSON-LD contract |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a per-content meta field | `saveMeta()` whitelist + `2026-08-29-100004` migration + `content-panel.php` + `build*()` consumer |
| Change world defaults | Settings view + the matching `build*()` default in the service |
| Adjust AI crawler set or stances | `AI_CRAWLERS` const + `getAiCrawlerList()` descriptions + settings view |
| Add a public fetch file | Public controller route (`Plugin.php:91-95`) following the `*_enabled` gating pattern |
| Exclude content from crawl surfaces | Set its `robots_directive` to a `noindex` value; sitemap/llms honor it |
| Change score weighting | `ContentAnalysisService::analyze()` check thresholds (keep score `passed/total * 100`) |
| Add a schema node | `SchemaService` builder method + link into the existing `@id` graph |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Single `<title>` contract intact; all emitted values escaped; `addTag()` still the only raw path
- [ ] `noindex` filtering, `*_enabled` gating, and the connected `@id` graph preserved
- [ ] `saveMeta()` whitelist and migration stay in lockstep; author stays human; AI disclosure stays setting-gated
- [ ] `analyze()` remains side-effect free; dashboard math resilient to missing hosts
- [ ] README updated only if user-facing behavior changed; keep the check-count claim in sync with `analyze()`

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No localization; labels are hardcoded in views (noted in `README.md`).
- A single flat sitemap; no sitemap indexes, paging, or image/video sitemaps.
- robots.txt directives are advisory text, not access enforcement; blocking a crawler there still needs server-level rules.
- No content rewrite or keyword suggestions beyond the analysis warnings.
- Known gaps to reconcile, not fixed here:
  - `README.md:17` claims 14 SEO checks, but `analyze()` emits 15 checks (`Services/ContentAnalysisService.php:39-63`). `<!-- TODO: reconcile README check count with ContentAnalysisService::analyze -->`
  - Sitemap/llms/detectContent URL patterns hardcode the default Blog/Pages route prefixes (guideline 8). `<!-- TODO: derive prefixes through pluginLoader()->routePrefix when prefixes become configurable -->`
  - `getDashboardCards()` assumes `pages()` and `blog()` exist and would throw if either host were disabled (`Plugin.php:155-156`). `<!-- TODO: guard dashboard card math against missing host plugins -->`
