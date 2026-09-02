# AGENTS.md — Search plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Search aggregates content matches from content plugins (Blog, Pages, and future sources) into a single ranked, paginated results page. Content plugins register themselves as adext `search` providers; admins toggle whole sources on and off and edit two scalar settings. The plugin also ships a theme-region search form block.

- **Package:** `pubvana/search` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`mixed` parameter at `Services/SearchService.php:201`; `str_contains`/`str_starts_with` at `Services/SearchService.php:241, 259, 261`)
- **Namespace:** `Pubvana\Plugins\Search` (`Plugin.php:5`), with `Controllers` and `Services` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** no third-party packages and no database; only core services `$app->adext()`, `settings()`, `request()`, `session()`, `render`/`redirect`, plus the `mb_*` string functions
- **Config:** `Config/Config.php`: `routePrepend` (empty string, so the public route lives at root-level `/search`)
- **Docs:** `README.md`

## Project guidelines

1. **Own the ranking here, nowhere else.** `scoreItem()` applies uniform weights (title first, then excerpt, then full body, plus a small recency boost) to every source (`Services/SearchService.php:232-285`). Provider plugins must find content only; their AGENTS.md files say the same. Reason: consistent, explainable results across sources.
2. **Enforce the provider result contract at the boundary.** Items without a non-empty `title` or `url` are dropped, and a throwing provider is caught and skipped (`Services/SearchService.php:82-95`). Reason: one bad provider must never blank the whole search page.
3. **Strip HTML before anything is scored or highlighted.** Scoring lowercases a `strip_tags` copy of `content` (`Services/SearchService.php:236`); highlighting runs `htmlspecialchars` first and only then injects `<mark>` (`Services/SearchService.php:307-327`). Never highlight unescaped text. Reason: `<mark>` injection over provider-unsanitized HTML is an XSS vector.
4. **Keep highlight work on the visible slice only.** Highlighting runs after pagination, on the `array_slice` result (`Services/SearchService.php:112-117`). Reason: highlighting the full result set on a large index would waste the request.
5. **Non-legacy sources are enabled by default.** `enabledSources()` treats a source as on unless its key sits in the `Search.disabledSources` JSON list (`Services/SearchService.php:146-162, 191-196`). New providers therefore appear automatically; never require an admin to flip them on.
6. **Persist admin controls through the `Search.` settings namespace.** Toggles live in `Search.disabledSources` (JSON array), scalars in `Search.resultsPerPage` and `Search.minQueryLength` (`Services/SearchService.php:183, 201-204`; `Controllers/SearchAdminController.php:46-47`). Reason: the plugin has no database, and the settings service is the source of truth.
7. **Keep tokenization shared.** `tokenize()` is the single lowercase/tokenizer with quoted-phrase support (`Services/SearchService.php:211-223`). Reason: phrasing support only works if scoring and highlighting both consume the same token set.
8. **Do not drag a database into this plugin.** Aggregation is a live scan over adext providers and the settings store. Reason: adding storage changes the plugin's shape and conflicts with the "no DB" architecture the code is built around.
9. **Admin toggles key off the registry key.** The checkbox name is `source_{key}` and `setSourceEnabled` compares raw keys (`Controllers/SearchAdminController.php:49-52`). Keep any new control keyed the same way. Reason: keys are the stable identity; labels are display-only.
10. **Empty and short queries render cleanly.** A blank `q` renders the empty state without running a search; a query under `minQueryLength` returns an error message and zero results (`Controllers/SearchPublicController.php:36-44`, `Services/SearchService.php:47-57`). Preserve both branches when changing the endpoint.

## Repository layout

```
plugins/Search/
├── Config/Config.php                     routePrepend (empty -> root-level /search)
├── Controllers/
│   ├── SearchPublicController.php         GET /search?q=... results page, pagination data
│   └── SearchAdminController.php          GET/POST /search source toggles and settings
├── Services/SearchService.php            $app->search(): aggregation, scoring, highlight, pagination
├── Plugin.php                            Entry point; facade, routes, search form block
├── pubvana.json                          Manifest; admin.menu (Search, ti-search)
├── Views/admin/index.php                 Source manager and settings form
├── Views/public/blocks/search.tpl         Block form (action, label, placeholder, button_text)
└── README.md                             Provider contract, result shape, source management
```

## Core architecture

**Facade.** `$app->search()` is a static-cached `SearchService` built on the engine alone (`Plugin.php:32-38`).

**Provider discovery.** `sources()` reads adext type `search`, slot `provider` (`Services/SearchService.php:135-138`). `enabledSources(false)` filters that list through the disabled-keys JSON and returns the live set used in aggregation.

**Scoring and merge.** Each provider's callable receives the raw trimmed query and returns normalized content matches. Items gain `_source` and `_score`, are sorted by score with `published_at` as the tiebreaker, paginated, and highlighted before returning the `{items, total, page, per_page, query, error, from}` envelope (`Services/SearchService.php:73-128`). `from` is the comma-joined contributing source keys (`Services/SearchService.php:332-341`).

**Admin flow.** `index()` renders the settings plus every registered source with its enabled state; `save()` coerces `results_per_page`/`min_query_length` to at least 1, persists both, then flips every source based on checkbox presence (`Controllers/SearchAdminController.php`).

**Public flow.** `GET /search?q=term` renders the theme `search` template. Pagination is built Blog-style (`current`, `total`, `prev`/`next`), preserving the URL-encoded query (`Controllers/SearchPublicController.php:52-71`).

**Block registration.** The `pubvana.search.form` block is registered in PHP under adext `block`/`available` (`Plugin.php:55-66`) with four input options and a Vision template that GET-posts `q` to the configurable `action` URL (`Views/public/blocks/search.tpl`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3)
  - `find plugins/Search -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Query shorter than `minQueryLength` returns the error state with zero results; empty `q` renders the empty page
  - [ ] Toggle a source off in `/admin/search`; its items vanish from results and from the `from` line; the toggle persists across a reload
  - [ ] Register a scratch provider; it appears in the admin list enabled by default
  - [ ] Phrase query `"two words"` scores a title phrase above isolated word hits
  - [ ] A term in a title scores higher than the same term only in content, with a recency boost visible between an old and a new item
  - [ ] A title or excerpt containing `&`/`<` renders the match highlighted and the characters escaped (no raw HTML)
  - [ ] Beyond `resultsPerPage` matches, pagination shows `prev`/`next` links that preserve `q`
  - [ ] All sources disabled shows the "No search sources are enabled" error
  - [ ] The search form block, placed in a region, GET-submits `q` to its configured `action`
  - [ ] A provider that throws is silently skipped (nothing fatal)

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **`SearchService` holds only the engine; string work uses `mb_*` functions** (`Services/SearchService.php:29-32, 216, 234-236`).
3. **Read settings only through `$this->setting()` with defaults equal to the documented ones** (`resultsPerPage` 10, `minQueryLength` 3).
4. **Validate decoded JSON defensively.** `disabledSourceKeys()` filters to string values and re-indexes (`Services/SearchService.php:191-196`); keep it strict about types.
5. **Keep `scoreItem()` weights centralized.** Add new scoring dimensions there, not scattered in callbacks.
6. **Views escape everything echoed; the block template stays output-only** (`Views/admin/index.php` uses `htmlspecialchars` throughout).
7. **Do not hardcode paths in the public controller.** Pagination builds from `/search` because the route is fixed at root level; if `routePrepend` semantics change, revisit that.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | Provider registration, result shape, ranking model, source management, block options |
| `Services/SearchService.php:41-128` | The envelope the public and admin controllers consume |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Alter ranking behavior | `scoreItem()` and `ageDays()` (`Services/SearchService.php:232-300`) |
| Change tokenization or phrase support | `tokenize()` (`Services/SearchService.php:211-223`) |
| Add a scalar setting | `Controllers/SearchAdminController.php:43-47` + `Services/SearchService.php:201-204` + admin view |
| Adjust block options | `Plugin.php:55-66` and `Views/public/blocks/search.tpl` |
| Change pagination links | `buildPagination()` (`Controllers/SearchPublicController.php:52-71`) |
| Document a new provider | `README.md` "Registering a search source" against the actual contract |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Scoring still centralized in `SearchService`; providers untouched for ranking
- [ ] Escape-then-highlight ordering intact; highlight still scoped to the paginated slice
- [ ] Source toggles still keyed by registry key, stored in `Search.disabledSources`, defaults enabled
- [ ] Short/empty query branches preserved; block template stays escaped and GET-based
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No search index, cache, or scheduled crawl; every request scans providers live.
- No fuzzy matching, typo tolerance, or stemmed scoring; deterministic token scoring only.
- No per-item admin controls; admins toggle whole sources, not individual results.
- No RSS, API, or per-source results pages; a single `/search` page.
- No database tables, migrations, or seeds of its own.
