# AGENTS.md — Redirects plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Redirects manages 301/302 URL redirects and aggregates incoming 404 traffic. It intercepts requests before normal routing (`before('start')`), issues matches as configured, and logs unresolved not-found requests into a redirect-link table that admins triage into redirects.

- **Package:** `pubvana/redirects` (`pubvana.json:2`), semver `0.2.1`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`mixed` at `Services/RedirectsService.php:326`, `str_starts_with`/`str_contains` at `Services/RedirectsService.php:281, 295`, typed arrow function at `Services/RedirectsService.php:62`)
- **Namespace:** `Pubvana\Plugins\Redirects` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base); Pubvana core classes `AdminController`, `PluginInterface`; core services and host plugins `$app->db()`, `adext()`, `pages()`, `blog()`, `pluginLoader()->routePrefix()`, `request()`, `session()`, `redirect()`; server globals `$_SERVER['QUERY_STRING']`, `HTTP_REFERER`, `HTTP_USER_AGENT`; the Pages plugin's seeded `not-wordpress` page is the target of the seed redirects
- **Config:** `Config/Config.php`: `routePrepend` (`redirects`), `skip_prefixes` (`['/admin', '/api']`), and `incoming_404s.skip_prefixes` (same defaults)
- **Docs:** `README.md`

## Project guidelines

1. **Only match enabled redirects, and only for `GET`/`HEAD` outside the CLI.** Matching runs through `findActiveBySourcePath()` (`enabled = 1`, `Models/Redirect.php:55-63`), gated by method and `php_sapi_name()` (`Services/RedirectsService.php:181-193`). Reason: POST redirects break forms, and CLI routes have no request path.
2. **Restrict status codes to 301 and 302.** `preparePayload()` coerces anything else back to 301 (`Services/RedirectsService.php:217-220`). Reason: any other code would mislead clients and search engines.
3. **Never drop the query string when redirecting.** `buildRedirectLocation()` forwards the original query, joining with `&` when the target already has one (`Services/RedirectsService.php:288-297`). Reason: pages reached via redirects routinely rely on query parameters.
4. **Keep the self-redirect guard in place.** A redirect whose target path matches the current path (same host) is never issued (`Services/RedirectsService.php:203-205, 311-324`). Reason: otherwise an accidental duplicate-path rule loops forever.
5. **Normalize both sides of every comparison.** Admin-entered source paths and incoming request paths must pass through the same normalization (leading slash, duplicated-slash collapse, trailing-slash strip except root). The helpers live in `Services/RedirectsService.php:231-286`. Reason: `source_path` is a unique column, so un-normalized variants would either collide or silently fail to match.
6. **Never bypass the schema of the two services.** `preparePayload()` is the only whitelist for redirect fields (`Services/RedirectsService.php:215-229`). Reason: the models are lean and the payload mapping keeps `enabled`/`status_code`/`notes` coercions in one place.
7. **Keep the 404 log keyed by `source_path` and reset resolution on each hit.** `logCurrentRequest()` updates an existing entry or inserts a new one, and clears `resolved_redirect_id`/`resolved_at` (`Services/RedirectsLinksService.php:161-183`). Reason: a path that 404s again after being resolved must surface in the Active list once more.
8. **Preserve the create-from-404 association.** When a redirect is stored with an `incoming_404_id`, the controller marks that entry resolved and links the new redirect id (`Controllers/RedirectsAdminController.php:48-51`). Reason: the 404 manager's workflow depends on that link surviving.
9. **Keep the skipped prefixes honored on both paths.** `shouldSkipPath()` in each service rejects admin and API traffic from matching and from logging (`Services/RedirectsService.php:299-309`, `Services/RedirectsLinksService.php:212-223`). Reason: hijacked admin URLs and noisy internal API 404s must stay out of the tables.
10. **Target suggestions are a convenience and must tolerate missing plugins.** The Pages and Blog lookups are each wrapped in `try/catch (\Throwable)` and skipped on failure (`Services/RedirectsService.php:143-173`). Reason: the quick-target picker must not break when a host plugin is disabled.
11. **Do not assume the seeded `not-wordpress` target exists.** The seed rows are 301s to `/page/not-wordpress` (`Database/Seeds/Seed.php`), a page the Pages plugin seeds. Keep any new seed redirect on that same pattern. Reason: the two seeds install together, but a redirect's own logic must never depend on the page.

## Repository layout

```
plugins/Redirects/
├── Config/Config.php                     routePrepend, skip_prefixes, incoming_404s.skip_prefixes
├── Controllers/
│   ├── RedirectsAdminController.php      Redirect CRUD and quick-target picker
│   └── RedirectLinksAdminController.php  404 manager: list statuses, ignore/unignore, delete
├── Database/
│   ├── Migrations/
│   │   ├── 2026-08-28-100001_CreateRedirectsTable.php       redirects (source_path unique, enabled indexed)
│   │   └── 2026-08-28-100002_CreateRedirectLinksTable.php   redirects_links (source_path unique; ignored, resolved_redirect_id indexed)
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

**Services.** Two singletons are mapped: `redirects` (`Plugin.php:32-38`) and `redirectLinks` (`Plugin.php:40-46`), both static-cached with the engine and plugin config.

**Request interception (the plugin's center of gravity).**
- `before('start')` → `$app->redirects()->handleCurrentRequest()`: skips CLI, non-GET/HEAD, and skipped prefixes, matches the normalized path against enabled redirects, guards self-redirects, bumps `hit_count`/`last_hit_at`, then redirects (`Plugin.php:132-134`).
- `before('notFound')` → `$app->redirectLinks()->logCurrentRequest()` (`Plugin.php:136-138`).
- `before('halt')` → logs the current request again when the halt status is 404 (`Plugin.php:140-145`).

**Admin screens.** Redirects CRUD under the URL Manager submenu adds, edits, deletes, and (via `getTargetSuggestions()`) suggests Pages/blog targets. The 404 manager lists entries by status (`active` = not ignored and unresolved, `ignored`, `resolved`, `all`), ignores/unignores, deletes, and links a new redirect to an entry through `incoming_404_id`.

**Data flow (log).** A 404 request normalizes its path, skips prefixed paths, then finds-or-creates a `redirects_links` row. New rows start active with a hit count of zero; every hit increments `hit_count`, refreshes `last_seen_at` plus the last query/referrer/user-agent, and re-opens the entry by clearing resolution.

**Extension points (adext).** `admin.dashboard` cards (active 404s with danger tone, enabled redirects) and a recent-redirect-links section (`Plugin.php:68-128`). No public routes: everything ships as a plugin, nothing renders a page.

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3, sees `app/` plus `scanDirectories: vendor/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Redirects -name '*.php' -exec php -l {} \;`
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

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `\flight\ActiveRecord` and declare their table string in the constructor** (`Models/Redirect.php:21-24`, `Models/RedirectLink.php:22-25`).
3. **Keep the `@property` column docblocks in sync with the migrations** (`Models/Redirect.php:7-18`, `Models/RedirectLink.php:7-19`).
4. **Pull fresh model instances through a private `model()` helper** (`Services/RedirectsService.php:337-340`, `Services/RedirectLinksService.php:236-239`). Reason: a shared instance would hold query state across calls.
5. **Use `DateTimeImmutable` for every timestamp write** (`Services/RedirectsService.php:332-335`, `Services/RedirectLinksService.php:231-234`).
6. **Controllers strip `_csrf_token` before forwarding POST data** (`Controllers/RedirectsAdminController.php:45-46, 81-82`).
7. **Whitelist the status code wherever one is read.** Redirects are 301/302 only; never pass a raw status through.
8. **Do not add a third service facade.** `redirects` and `redirectLinks` are the two fixed entry points; route all new queries through them.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | Table semantics, matching rules, 404 manager workflow, service reference, normalization notes |
| `Plugin.php:130-146` | The interception contract (start/notFound/halt) |
| `Services/RedirectsService.php:231-286` | Canonical path and target normalization |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a redirect field | Migration `2026-08-28-100001` + `preparePayload()` + views |
| Change skip prefixes | `Config/Config.php` (both `skip_prefixes` sets) |
| Add a target-suggestion group | `getTargetSuggestions()` (`Services/RedirectsService.php:139-174`) |
| Change 404 status filtering | `RedirectLink::allByStatus()` (`Models/RedirectLink.php:30-45`) and the `?status=` switch in `RedirectLinksAdminController::index()` |
| Add a 404 manager action | New controller method + route (`Plugin.php:53-64`) + view button |
| Change matching behavior (e.g. regex) | `findActiveBySourcePath()` and `handleCurrentRequest()`; update the README "Exact-path matching only" claim |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Matching still gated to enabled redirects, `GET`/`HEAD`, non-CLI, and non-skipped prefixes; self-redirect guard intact
- [ ] Query-string forwarding preserved; status codes still coerced to 301/302; 404 entries still reset on log
- [ ] Seed rows stay on the anti-scan 301 pattern; the create-from-404 association still links entries
- [ ] README updated only if user-facing behavior changed; keep the "Exact-path matching only" and normalization claims truthful

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Exact-path matching only; no regex, wildcard, or case-insensitive rules.
- 404 logging keeps the most recent query/referrer/user-agent per path, not a history of hits.
- No automatic resolution; 404s are triaged by an admin.
- No translations; labels are hardcoded in views (noted in `README.md`).
- Auth middleware is disabled for development (`Plugin.php:49`); enforcement is future work against seeded permissions.