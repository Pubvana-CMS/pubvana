# AGENTS.md — Search plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Search aggregates content matches from content plugins (Blog, Pages, and future sources) into a single ranked, paginated results page. Content plugins register themselves as adext `search` providers; admins toggle whole sources on and off and edit two scalar settings. The plugin also provides a theme-region search form block.

- **Package:** `pubvana/search` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`mixed` parameter at `Services/SearchService.php:249`; `str_contains`/`str_starts_with` throughout `scoreItem()`, `Services/SearchService.php:280-364`)
- **Namespace:** `Pubvana\Plugins\Search` (`Plugin.php:5`), with `Controllers` and `Services` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** no third-party packages and no database; only core services `$app->adext()`, `settings()`, `request()`, `session()`, `render`/`redirect`, plus the `mb_*` string functions
- **Config:** `Config/Config.php`: `routePrepend` (empty string, so the public route lives at root-level `/search`)
- **Docs:** `README.md`

## Project guidelines

1. **Own the ranking here, nowhere else.** `scoreItem()` applies fixed weights to every source (`Services/SearchService.php:280-364`). Per token: a quoted phrase pays 20/12/8 for title/excerpt/content, a single word pays 12/10/8 in the title for a prefix, whole-word, or inner-substring hit, then 5 for the excerpt and 3 for the content, plus a recency boost of up to +4. The weights are class consts (`Services/SearchService.php:51-59`), so `scoreItem()`, `maxScore()` and any future consumer read the same numbers. Provider plugins must find content only and must not return a score field; their AGENTS.md files say the same. Keep the title tiers in that order: a word-boundary match always implies a substring match, so testing the substring first leaves the 10-point tier dead. Reason: consistent, explainable results across sources.
2. **Enforce the provider result shape at the boundary.** Items without a non-empty `title` or `url` are dropped, and a provider that throws, lacks a callable, or returns a non-array is logged and skipped (`Services/SearchService.php:113-137`). The logging is deliberate: a skipped provider contributes nothing, so without a log line a broken source is indistinguishable from a term that matched nothing. Reason: one bad provider must never blank the whole search page, and a broken one has to be findable afterwards.
3. **Strip HTML before anything is scored or highlighted.** Scoring lowercases a `strip_tags` copy of `content` (`Services/SearchService.php:284`); highlighting runs `htmlspecialchars` first and only then injects `<mark>` (`Services/SearchService.php:388-414`). Never highlight unescaped text. Reason: `<mark>` injection over provider-unsanitized HTML is an XSS vector.
4. **Keep highlight work on the visible slice only.** Highlighting runs after pagination, on the `array_slice` result (`Services/SearchService.php:157-160`). Reason: highlighting the full result set on a large index would waste the request.
5. **Non-legacy sources are enabled by default.** `enabledSources()` treats a source as on unless its key sits in the `Search.disabledSources` JSON list (`Services/SearchService.php:194`, `Services/SearchService.php:239`). New providers therefore appear automatically; never require an admin to flip them on.
6. **Persist admin controls through the `Search.` settings namespace.** Toggles live in `Search.disabledSources` (JSON array), scalars in `Search.resultsPerPage` and `Search.minQueryLength` (`Services/SearchService.php:215, 249`; `Controllers/SearchAdminController.php:46-47`). Reason: the plugin has no database, and the settings service is the source of truth.
7. **Keep tokenization shared.** `tokenize()` is the single lowercase/tokenizer with quoted-phrase support (`Services/SearchService.php:259`). Reason: phrasing support only works if scoring and highlighting both consume the same token set.
8. **Do not drag a database into this plugin.** Aggregation is a live scan over adext providers and the settings store. Reason: adding storage changes the plugin's shape and conflicts with the "no DB" architecture the code is built around.
9. **Admin toggles key off the registry key.** The checkbox name is `source_{key}` and `setSourceEnabled` compares raw keys (`Controllers/SearchAdminController.php:49-52`). Keep any new control keyed the same way. Reason: keys are the stable identity; labels are display-only.
10. **Empty and short queries render cleanly.** A blank `q` renders the empty state without running a search; a query under `minQueryLength` returns an error message and zero results (`Controllers/SearchPublicController.php:40-51`, `Services/SearchService.php:82-91`). Preserve both branches when changing the endpoint.
11. **The score readout is unconditional.** The theme renders `score {{ result._score }} / {{ max_score }}` for every result, with no environment or session gate (`Controllers/SearchPublicController.php`; `themes/default/Views/pubvana/search/search.tpl`). `_score` and `max_score` are public surface: treat anything put in them as visible to a visitor. Reason: the readout is wanted on the live site, so the gate that once hid it outside development was removed.
12. **Derive the ceiling, never hand-sum it.** `maxScore()` adds up the same consts `scoreItem()` pays, so the envelope's `max_score` tracks the query: 24 for one word, 44 for a phrase or two words, 64 for three tokens (`Services/SearchService.php:350`). The title tiers are mutually exclusive, so only `TITLE_PREFIX` counts toward the ceiling, and recency counts at full value even though an older item cannot collect it. Reason: a hand-summed ceiling drifts from the real weights and silently turns the displayed ratio into a lie.

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
└── README.md                             Provider registration, result shape, source management
```

## Core architecture

**Facade.** `$app->search()` is a static-cached `SearchService` built on the engine alone (`Plugin.php:32-38`).

**Provider discovery.** `sources()` reads adext type `search`, slot `provider` (`Services/SearchService.php:183`). `enabledSources(false)` filters that list through the disabled-keys JSON and returns the live set used in aggregation.

**Scoring and merge.** Each provider's callable receives the raw trimmed query and returns normalized content matches. Items gain `_source` and `_score`, are sorted by score with `published_at` as the tiebreaker, paginated, and highlighted before returning the `{items, total, page, per_page, query, error, from, max_score}` envelope (`Services/SearchService.php:113-172`). `from` is the comma-joined contributing source keys (`Services/SearchService.php:416`); `max_score` is the ceiling this query's weights allow (`Services/SearchService.php:350`), which is 0.0 on the two error paths where nothing was scored. The envelope items also carry the internal `_score` and `_source`, which only the development score readout consumes.

**Admin flow.** `index()` renders the settings plus every registered source with its enabled state; `save()` coerces `results_per_page`/`min_query_length` to at least 1, persists both, then flips every source based on checkbox presence (`Controllers/SearchAdminController.php`).

**Public flow.** `GET /search?q=term` renders the theme `search` template. Pagination is built Blog-style (`current`, `total`, `prev`/`next`), preserving the URL-encoded query (`Controllers/SearchPublicController.php:22-51`).

**Block registration.** The `pubvana.search.form` block is registered in PHP under adext `block`/`available` (`Plugin.php:55-66`) with four input options and a Vision template that GET-posts `q` to the configurable `action` URL (`Views/public/blocks/search.tpl`).

## Provider registration

Content plugins register as search sources through adext type `search`, slot `provider`:

```php
$adext->register('search', 'provider', 'pubvana.my-plugin', [
    'label'        => 'My Items',
    'content_type' => 'Item',
    'description'  => 'Short description shown in the admin source list.',
    'callable'     => fn(string $term) => $app->myPlugin()->searchProvider($term, $prefix),
]);
```

`label` and `callable` are required; `description` and `content_type` are optional. The callable receives the raw query string and returns content matches; it finds content only, it does not rank it.

**Result shape.** Return a list of normalized matches:

```php
[
    'id'           => 12,
    'title'        => 'My Item',
    'url'          => $prefix . '/item-slug',
    'excerpt'      => 'Plain-text snippet, may be pre-truncated around the term',
    'content_type' => 'Item',
    'published_at' => '2026-05-06 12:00:00',
]
```

Providers should return only published or otherwise visible content, give `excerpt` as plain text with HTML stripped, give `content` as the stripped body so the service can score a body hit (both in-tree providers do; omit it and the item forfeits the content tier), and set `published_at` for the recency boost (use a creation date when there is no publish date). Never return a score field: the service ignores anything it did not compute itself.

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Search/` (3 files: `SearchServiceTest`, `SearchPluginTest`, `SearchControllersTest`). It is also exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`)
  - `find plugins/Search -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Search`
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
  - [ ] A provider that throws is logged and skipped, and the page still renders results from the other sources
  - [ ] Every result shows a `score N / M` line, in development and in production alike
  - [ ] `max_score` moves with the query: 24 for a single word, 44 for a quoted phrase, and no result ever exceeds it

Coverage: the suite covers the service (scoring, aggregation, highlighting, the derived ceiling), the controllers, and the plugin registration.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **`SearchService` holds only the engine; string work uses `mb_*` functions** (`Services/SearchService.php:64, 259, 282-284`).
3. **Read settings only through `$this->setting()` with defaults equal to the documented ones** (`resultsPerPage` 10, `minQueryLength` 3).
4. **Validate decoded JSON defensively.** `disabledSourceKeys()` filters to string values and re-indexes (`Services/SearchService.php:239`); keep it strict about types.
5. **Keep `scoreItem()` weights centralized.** Add new scoring dimensions there, not scattered in callbacks.
6. **Views escape everything echoed; the block template stays output-only** (`Views/admin/index.php` uses `htmlspecialchars` throughout).
7. **Do not hardcode paths in the public controller.** Pagination builds from `/search` because the route is fixed at root level; if `routePrepend` semantics change, revisit that.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `AGENTS.md` "Provider registration" | adext search registration and result shape |
| `Services/SearchService.php:76-172` | The envelope the public and admin controllers consume |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Alter ranking behavior | The weight consts (`Services/SearchService.php:51-59`), then `scoreItem()` and `ageDays()` (`Services/SearchService.php:280, 366`) |
| Change the reported maximum | `maxScore()` (`Services/SearchService.php:350`); it reads the weight consts, so it follows them automatically |
| Change what the score line shows | `themes/default/Views/pubvana/search/search.tpl`; the readout is unconditional |
| Change tokenization or phrase support | `tokenize()` (`Services/SearchService.php:259`) |
| Add a scalar setting | `Controllers/SearchAdminController.php:43-47` + `Services/SearchService.php:249` + admin view |
| Adjust block options | `Plugin.php:55-66` and `Views/public/blocks/search.tpl` |
| Change pagination links | `buildPagination()` (`Controllers/SearchPublicController.php:59`) |
| Document a new provider | `AGENTS.md` "Provider registration" |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean on the app (`composer phpstan`)
- [ ] Scoring still centralized in `SearchService`; providers untouched for ranking and carrying no score field
- [ ] Title tiers kept in prefix/whole-word/substring order, so the 10-point tier stays reachable
- [ ] Any weight change went into the consts, so `maxScore()` moved with it
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
