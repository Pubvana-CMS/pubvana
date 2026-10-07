# AGENTS.md: Redirects plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Redirects manages 301/302 URL redirects and aggregates incoming 404 traffic. It intercepts requests before normal routing (`before('start')`), issues matches as configured, and logs unresolved not-found requests into a redirect-link table that admins triage into redirects.

- **Package:** `pubvana/redirects` (`pubvana.json:2`), semver `0.3.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (typed `mixed` parameters, `str_starts_with`/`str_contains`, typed arrow functions)
- **Namespace:** `Pubvana\Plugins\Redirects` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base); Pubvana core classes `AdminController`, `PluginInterface`; core services and host plugins `$app->db()`, `adext()`, `pages()`, `blog()`, `pluginLoader()->routePrefix()`, `request()`, `session()`, `redirect()`; server globals `$_SERVER['QUERY_STRING']`, `HTTP_REFERER`, `HTTP_USER_AGENT`; the Pages plugin's seeded `not-wordpress` page is the target of the seed redirects
- **Config:** `Config/Config.php`: `routePrepend` (`redirects`), `skip_prefixes` (`['/admin', '/api']`), and `incoming_404s.skip_prefixes` (same defaults)
- **Docs:** `README.md`

## Project guidelines

1. **Only match enabled redirects, and only for `GET`/`HEAD` outside the CLI.** Matching runs through `Redirect::findActiveBySourcePath()` (both the exact and the wildcard finder filter `enabled = 1`), gated by method and `php_sapi_name()` in `RedirectsService::handleCurrentRequest()`. Reason: POST redirects break forms, and CLI routes have no request path.
2. **Restrict status codes to 301 and 302.** `RedirectsService::preparePayload()` coerces anything else back to 301. Reason: any other code would mislead clients and search engines.
3. **Never drop the query string when redirecting.** `RedirectsService::buildRedirectLocation()` forwards the original query, joining with `&` when the target already has one. Reason: pages reached via redirects routinely rely on query parameters.
4. **Keep the self-redirect guard in place.** A redirect whose target path matches the current path (same host) is never issued (`RedirectsService::isSelfRedirect()`, called from `handleCurrentRequest()`). Reason: otherwise an accidental duplicate-path rule loops forever.
5. **Normalize both sides of every comparison.** Admin-entered source paths and incoming request paths must pass through the same normalization (leading slash, duplicated-slash collapse, trailing-slash strip except root). The helpers are `RedirectsService::normalizeSourcePath()`, `normalizeTargetUrl()`, and `normalizeIncomingPath()`. Reason: `source_path` is a unique column, so un-normalized variants would either collide or silently fail to match.
6. **Never bypass the schema of the two services.** `RedirectsService::preparePayload()` is the only whitelist for redirect fields, and every write runs its source path past `assertSourcePathFree()` first, so a duplicate never reaches the admin as a 500. Reason: the models are lean, and the payload mapping keeps `enabled`/`status_code`/`notes` coercions in one place.
7. **Keep the 404 log keyed by `source_path` and reset resolution on each hit.** `RedirectLinksService::logCurrentRequest()` updates an existing entry or inserts a new one, and clears `resolved_redirect_id`/`resolved_at`. Reason: a path that 404s again after being resolved must surface in the Active list once more.
8. **Preserve the create-from-404 association, both ways.** `RedirectsAdminController::store()` marks the entry resolved and links the new redirect id, and `RedirectsService::delete()` reopens the entries a deleted redirect resolved. Reason: the 404 manager's workflow depends on that link being accurate in both directions.
9. **Keep the skipped prefixes honored on both paths.** `shouldSkipPath()` in each service rejects admin and API traffic from matching and from logging. Reason: hijacked admin URLs and noisy internal API 404s must stay out of the tables.
10. **Target suggestions are a convenience and must tolerate missing plugins.** The Pages and Blog lookups are each wrapped in `try/catch (\Throwable)` and skipped on failure, and each builds its URLs from the host plugin's own route prefix. Reason: the quick-target picker must not break when a host plugin is disabled or renamed.
11. **Do not assume the seeded `not-wordpress` target exists.** The seed rows are 301s to `/page/not-wordpress` (`Database/Seeds/Seed.php`), a page the Pages plugin seeds. Keep any new seed redirect on that same pattern. Reason: the two seeds install together, but a redirect's own logic must never depend on the page.
12. **Wildcards: one trailing `*`, and the most specific pattern wins.** A source path may hold a single `*`, and it must be the last character. `$1` in the target is optional: without it the capture is dropped and every match lands on the same URL. Among the patterns that match one path, the longest literal prefix ahead of the `*` wins, so `/blog/post/*` beats `/blog/*`. Reason: the admin can tell which rule applies without knowing the table order.
13. **An entry sits in exactly one status tab.** `ignored` and `resolved` are mutually exclusive: `RedirectLinksService::setIgnored()` clears any resolution, and `markResolved()` clears `ignored`. Reason: the Active, Ignored, and Resolved counts must add up.
14. **Page both admin lists through `PaginationService`.** Each controller clamps the page with `max(1, ...)` and hands the built array to `app/Views/admin/_pagination.php`. Reason: `?page=0` reaches MySQL as a negative OFFSET and a hand-rolled loop emits one link per page.

## Repository layout

```
plugins/Redirects/
├── Config/Config.php                     routePrepend, skip_prefixes, incoming_404s.skip_prefixes
├── Controllers/
│   ├── RedirectsAdminController.php      Redirect CRUD and quick-target picker
│   └── RedirectLinksAdminController.php  404 manager: list statuses, ignore/unignore, delete
├── Database/
│   ├── Migrations/
│   │   ├── 2026-09-17-105235_CreateRedirectsTable.php       redirects (source_path unique, enabled indexed)
│   │   └── 2026-09-17-105236_CreateRedirectLinksTable.php   redirects_links (source_path unique; ignored, resolved_redirect_id indexed)
│   └── Seeds/Seed.php                    Seed: 116 WordPress attack-vector redirects to /page/not-wordpress
├── Models/
│   ├── Redirect.php                      redirects table; ordered list, by-id, active-by-source-path
│   └── RedirectLink.php                  redirects_links table; by status, by-id, by-source-path
├── Services/
│   ├── RedirectsService.php              $app->redirects(): CRUD, target suggestions, live matching
│   └── RedirectLinksService.php          $app->redirectLinks(): 404 log CRUD and request logging
├── Plugin.php                            Entry point; routes, dashboard items, request interception
├── pubvana.json                          Manifest; admin.menu (URL Manager submenu), admin.dashboard
├── Views/admin/
│   ├── index.php                         Redirect list
│   ├── create.php                        New redirect (prefill from 404 manager)
│   ├── edit.php                          Edit redirect
│   └── incoming-404s.php                 404 manager with status tabs
└── README.md
```

## Core architecture

**Services.** Two singletons are mapped in `Plugin::register()`: `redirects` and `redirectLinks`, both static-cached with the engine and plugin config.

**Request interception (the plugin's center of gravity).**
- `before('start')` → `$app->redirects()->handleCurrentRequest()`: skips CLI, non-GET/HEAD, and skipped prefixes, matches the normalized path against enabled redirects, guards self-redirects, bumps `hit_count`/`last_hit_at`, then redirects.
- `before('notFound')` → `$app->redirectLinks()->logCurrentRequest()`.
- `before('halt')` → logs the current request again when the halt status is 404.

**Admin screens.** Redirects CRUD under the URL Manager submenu adds, edits, deletes, and (via `getTargetSuggestions()`) suggests Pages/blog targets. The 404 manager lists entries by status (`active` = not ignored and unresolved, `ignored`, `resolved`, `all`), ignores/unignores, deletes, and links a new redirect to an entry through `incoming_404_id`. Both lists page through `PaginationService` and include `app/Views/admin/_pagination.php`.

**Data flow (log).** A 404 request normalizes its path, skips prefixed paths, then finds-or-creates a `redirects_links` row. New rows start active with a hit count of zero; every hit increments `hit_count`, refreshes `last_seen_at` plus the last query/referrer/user-agent, and re-opens the entry by clearing resolution.

**Extension points (adext).** `admin.dashboard` cards (active 404s with danger tone, enabled redirects) and a recent-redirect-links section. No public routes: everything is a plugin, nothing renders a page.

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Redirects/` (six test classes, `RedirectsPluginTest`, `RedirectsAdminControllersTest`, `RedirectsServiceTest`, `RedirectLinksServiceTest`, `RedirectsTargetUrlSafetyTest`, `RedirectsMigrationsSeedTest`, plus the `RedirectsSchema` table helper). It is also exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Redirects -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Redirects`
- Manual verification checklist:
  - [ ] Create a 301 for `/old/path/`; verify `/old/path`, `/old/path/`, and `//old//path` all redirect because normalization is identical on both sides
  - [ ] Disable the redirect; verify the path stops redirecting
  - [ ] Target `https://example.com?x=1` with incoming query `?y=2`; verify the redirect forwards `?x=1&y=2`
  - [ ] Point a redirect at itself (same source and target path); verify no redirect fires
  - [ ] Hit `/admin/anything` and a stray `/api/x`; verify neither redirects and neither is logged
  - [ ] Hit an unknown path twice; verify one row with `hit_count` 2 and `last_seen_at` refreshed
  - [ ] Create a redirect from the 404 manager; verify the entry shows Resolved and links the redirect id
  - [ ] Ignore/unignore an entry; verify the status tabs filter correctly (`active`/`ignored`/`resolved`/`all`)
  - [ ] Break a previously resolved path again; verify it returns to the active list (resolution reset)
  - [ ] Run a 404 through the CLI; verify nothing is logged (CLI guard)
  - [ ] Confirm the anti-scan seed rows land as 301s to `/page/not-wordpress`
  - [ ] Load either admin list with `?page=0` and `?page=abc`; verify page 1 and no error
  - [ ] Save a redirect on a path that already has one; verify an error message, not a 500
  - [ ] Define `/a/*` and `/a/b/*`, then hit `/a/b/c`; verify the longer prefix wins
  - [ ] Give a wildcard a target with no `$1`; verify the match still redirects to that target
  - [ ] Delete a redirect that resolved a 404; verify the entry returns to Active
  - [ ] Ignore a resolved entry; verify it leaves the Resolved list

Coverage: the suite covers both services, the admin controllers, target URL safety, migrations/seeds, and the plugin registration.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file.** No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Redirect::__construct()`, `RedirectLink::__construct()`).
3. **Keep the `@property` column docblocks in sync with the migrations** (the headers of `Redirect` and `RedirectLink`).
4. **Pull fresh model instances through a private `model()` helper** (`RedirectsService::model()`, `RedirectLinksService::model()`). Reason: a shared instance would hold query state across calls.
5. **Use `DateTimeImmutable` for every timestamp write** (`RedirectsService::now()`, `RedirectLinksService::now()`).
6. **Controllers strip `_csrf_token` before forwarding POST data** (in `RedirectsAdminController::store()` and `update()`).
7. **Whitelist the status code wherever one is read.** Redirects are 301/302 only; never pass a raw status through.
8. **Do not add a third service facade.** `redirects` and `redirectLinks` are the two fixed entry points; route all new queries through them.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| The `before()` interception handlers in `Plugin::register()` | The interception behavior (start, notFound, halt) |
| `RedirectsService::normalizeSourcePath()` and `normalizeTargetUrl()` | Canonical path and target normalization |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a redirect field | Migration `2026-09-17-105235` + `preparePayload()` + views |
| Change skip prefixes | `Config/Config.php` (both `skip_prefixes` sets) |
| Add a target-suggestion group | `RedirectsService::getTargetSuggestions()` |
| Change 404 status filtering | `RedirectLink::applyStatus()` and the `?status=` switch in `RedirectLinksAdminController::index()` |
| Add a 404 manager action | New controller method + route (the admin `addRoutes()` block in `Plugin::register()`) + view button |
| Change matching behavior (e.g. regex) | `findActiveBySourcePath()` and `handleCurrentRequest()` |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean on the app (`composer phpstan`)
- [ ] Matching still gated to enabled redirects, `GET`/`HEAD`, non-CLI, and non-skipped prefixes; self-redirect guard intact
- [ ] Wildcards still accept an optional `$1`, and the most specific pattern still wins
- [ ] Both admin lists still page through `PaginationService` with a page floor of 1
- [ ] Query-string forwarding preserved; status codes still coerced to 301/302; 404 entries still reset on log
- [ ] Seed rows stay on the anti-scan 301 pattern; the create-from-404 association still links entries
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Exact-path matching plus one trailing-`*` wildcard, where the longest literal prefix wins. No regex, no mid-path wildcards, no case-insensitive rules.
- 404 logging keeps the most recent query/referrer/user-agent per path, not a history of hits.
- No automatic resolution; 404s are triaged by an admin.
- No translations; labels are hardcoded in views.
- Auth middleware is disabled for development, the admin routes pass an empty middleware list. Enforcement is future work against seeded permissions.
