# AGENTS.md — SiteHealth plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

SiteHealth runs a battery of read-only diagnostics over the environment, security posture, configuration, and plugin state, and surfaces the results on an admin page (Tools → Site Health) plus a dashboard card that appears only when issues exist. It warns early about misconfigurations so admins can fix them before they become incidents.

- **Package:** `pubvana/sitehealth` (`pubvana.json:2`), display name Site Health, semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`readonly` properties at `Services/CheckResult.php:19-25`, plus `str_starts_with`/`str_contains` in several checks)
- **Namespace:** `Pubvana\Plugins\SiteHealth` (`Plugin.php:5`), with `Controllers`, `Services`, `Interfaces`, and `Views` sub-trees
- **Runtime dependencies (declared at the app level, not in the plugin):** `enlivenapp/flight-shield` (ShieldCheck presence), `enlivenapp/migrations` (MigrationsCheck via `MigrationSetup`); core `$app->settings()`, `adext()`, `db()`, `session()`, plus Flight helpers. Check classes take everything they need as constructor injection, so they stay decoupled from the engine where possible
- **Config:** `Config/Config.php`: `routePrepend` `site-health`, `cache_ttl` 3600 (cache lifetime for `writable/cache/sitehealth.json`)
- **Docs:** `README.md`

## Project guidelines

1. **Checks are read-only by design.** A check reads PHP ini values, `.env`, file permissions, disk space, DB connectivity, migration status, or `installed.json` and never modifies them (`Services/DatabaseCheck.php:21-33`, `Services/EnvironmentFilePermissionsCheck.php:28-37`). Reason: diagnostics must never change the thing they diagnose. The only writer in the plugin is `HealthService` itself, which persists results to the cache file.
2. **Never print credentials or config values in messages.** Messages name the failing item, never its value: `.env` checks only report that a key is empty or a placeholder (`Services/ConfigDefaultsCheck.php:41-64`); DB failures report the exception message, not the DSN or password. Reason: results render in the admin for anyone with dashboard access, and secret-leak risk is not worth a readable message.
3. **Use the `CheckResult` constants, never string literals, for `status` and `category`.** `PASS`/`WARNING`/`CRITICAL` and `CAT_*` (`Services/CheckResult.php:9-16`). Reason: the view's badge/icon/severity mapping keys off these exact values (unknown categories never render, summary counts only known statuses).
4. **Keep the severity bar honest.** Critical is reserved for "broken or dangerously misconfigured": PHP below the floor, a missing required extension, < 100 MB free, non-HTTP(S) HTTPS off in production, `display_errors` on in production, `.env` missing or world-writable, no Shield, non-writable runtime dirs, pending migrations, unmet package dependencies. Everything softer is a warning. Do not upgrade warnings to critical.
5. **A check must always return one `CheckResult`, even on unexpected failure.** I/O that can throw is caught inside the check and degrades to a WARNING with remediation (`Services/DatabaseCheck.php:21-33`, `Services/PluginMigrationsCheck.php:27-39`). Reason: one throwing check must never kill the whole run or the dashboard.
6. **Do not break the cache contract.** `runAll()` returns cached results for `cache_ttl` seconds, refreshes when the cache expires or `$force` is true, and `POST /admin/site-health/rerun` clears then forces. Results must stay JSON-serializable (only the `toArray()` shape) so the cache round-trips.
7. **Plugins that ship elsewhere extend SiteHealth through `addCheck()` or the adext `health` / `checks` point, not by editing this plugin.** External contributions may return a `CheckResult` or an array that includes `id`; anything else is ignored (`Services/HealthService.php:54-64`). Only in-tree diagnostics belong in `getChecks()`.
8. **Stick to the four existing categories for new built-in checks.** Environment, Security, Configuration, Plugins. The view renders categories from a fixed map in the controller (`Controllers/HealthAdminController.php:23-28`); a fifth category added to code but not to that map would be collected yet invisible.
9. **Keep the dashboard card conditional.** `dashboardCards()` returns an empty array (no card) when nothing is wrong, and at most one card (dangers out-rank warnings) otherwise (`Services/HealthService.php:80-116`). "No issues, no card" is an advertised behavior.
10. **Build messages from check results, not from user/anonymous input.** Every dynamic fragment is `htmlspecialchars`-escaped in the view and derived from environment/status values. No message content is ever echoed raw.

## Repository layout

```
plugins/SiteHealth/
├── Config/Config.php                     routePrepend 'site-health', cache_ttl 3600
├── Controllers/HealthAdminController.php Admin page + rerun; category label/icon map
├── Interfaces/CheckInterface.php         run(): CheckResult
├── Services/
│   ├── CheckResult.php                   Immutable result record (readonly) + status/category consts
│   ├── HealthService.php                 $app->health(): run/group/cache/addCheck, summary, dashboard card
│   ├── PhpVersionCheck.php               Floor 8.2.0, recommended 8.3.0
│   ├── PhpExtensionsCheck.php            8 required / 5 recommended extensions
│   ├── DatabaseCheck.php                 Connectivity + min versions (mysql 5.7, mariadb 10.3, sqlite 3.24)
│   ├── DiskSpaceCheck.php                Uploads partition: <100 MB critical, <500 MB warning
│   ├── HttpsCheck.php                    HTTPS + force_https, dev-env tolerance
│   ├── DebugModeCheck.php                environment + display_errors
│   ├── EnvironmentFilePermissionsCheck.php  .env world-writable (critical) / world-readable (warning)
│   ├── ShieldCheck.php                   Flight Shield installed + configured
│   ├── SessionConfigCheck.php            httponly, secure, samesite, gc_maxlifetime
│   ├── RequiredSettingsCheck.php         CMS.siteUrl / CMS.siteName placeholders
│   ├── WritableDirectoriesCheck.php      public/uploads, writable/cache, writable/logs
│   ├── ConfigDefaultsCheck.php           .env placeholders (SITE_URL, DB_PASS, keys, SITE_NAME, ADMIN_EMAIL)
│   ├── PluginMigrationsCheck.php         Pending migrations via MigrationSetup
│   └── PluginDependenciesCheck.php       flightphp-* package require graph from installed.json
├── Plugin.php                            Entry; maps health facade; admin routes; dashboard card
├── pubvana.json                          Manifest; admin.menu (Site Health under tools)
├── Views/admin/index.php                 Category-grouped results, summary cards, rerun form
└── README.md
```

## Core architecture

**Service.** `$app->health()` returns a static-cached `HealthService` bound to the engine, the `PDO` connection, and the plugin config (`Plugin.php:25-31`). The service orchestrates four categories of checks, builds a summary, caches results, and supplies dashboard card data.

**Check pipeline.** `getChecks()` constructs 14 built-in checks (`Services/HealthService.php:151-173`), appends programmatic `additionalChecks`, and `runAll()` folds in external checks contributed through adext (`health` / `checks`, filtered to `CheckResult` or `id`-carrying arrays). Each result is flattened via `CheckResult::toArray()` and summarized as pass/warning/critical counts with an overall state (critical outranks warning, warning outranks good).

**Caching.** Results are JSON-serialized to `{projectRoot}/writable/cache/sitehealth.json` (note the `writable/cache` path: creating it only happens on demand). `loadCache()` validates the file is intact and fresh within `cache_ttl`; expired or malformed caches are recomputed on the same call. `clearCache()` unlinks the file and the rerun route clears then forces a fresh pass.

**Admin.** `GET /site-health` renders the page (grouped by category, status badges/icons, an overall headline, cached-at timestamp, and the rerun form); `POST /site-health/rerun` clears the cache, re-runs with `$force`, flashes success, and redirects back (`Controllers/HealthAdminController.php:32-39`).

**Dashboard.** The `admin.dashboard` `cards` registration only returns a card when critical or warning counts are non-zero, with the danger tone preferred over the warning tone (`Plugin.php:46-52`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3; the ignored-error baseline covers the MigrationSetup/ActiveRecord internals)
  - `find plugins/SiteHealth -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] The admin page lists 14 checks across the 4 categories with correct statuses, messages, and remediation (rendered only for non-pass)
  - [ ] `cached_at` shows the run time; visiting again within `cache_ttl` shows the same timestamp; after expiry it refreshes automatically
  - [ ] Re-run clears the cache, forces a fresh pass, flashes success, and returns to the page
  - [ ] Simulate a warning (set `environment=development`) and a critical (e.g. read-protect uploads or set a placeholder `.env`): dashboard shows the respective card; with all clear, no card renders
  - [ ] A third-party adext `health`/`checks` contribution returning a `CheckResult` and one returning a bare array both appear; one returning garbage is skipped
  - [ ] Break DB connectivity: `database` is critical with a message that exposes no credentials
  - [ ] SessionConfigCheck stays warning (not critical) under 3 issues and critical at 3+
  - [ ] View output is escaped (htmlspecialchars on every message/name/url); nothing rendered raw

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **One check per file, implementing `CheckInterface`, constructed with constructor-promoted private/readonly properties** (`Services/DatabaseCheck.php:17`, `Services/CheckResult.php:18-25`).
3. **Keep the `id` a stable literal repeated identically across every status branch of a check** (e.g. `id: 'php-version'` in all three `PhpVersionCheck` returns). The view anchors rows to it; plugins link to check ids.
4. **Construct results with named arguments** (`new CheckResult(id: ..., name: ...)`), used consistently across all checks.
5. **Checks accept their inputs via constructor injection** (paths, `\PDO`, `Engine`) and read runtime values only inside `run()`. This keeps each check self-contained and testable.
6. **Escape all dynamic output in the view** (`htmlspecialchars` on names, messages, ids) and keep badge/icon HTML driven by the fixed status match map.
7. **Settings/config reads prefer `app->settings()` and fall back to `app->get()`** (see `settingsValue()` in `RequiredSettingsCheck` and `resolveSiteUrl()` in `HttpsCheck`); cast booleans explicitly.
8. **Never fetch remote data or run network calls inside a check.** Diagnostics stay local and fast, and they must not depend on external reachability.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing behavior, check list, extensibility contract, routes |
| `Services/HealthService.php:151-173` | The authoritative list of built-in checks per category |
| `Services/CheckResult.php` | The status/category vocabulary every check must use |
| `Controllers/HealthAdminController.php:23-28` | The category → label/icon display map |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a built-in check | New check class + register it in the matching `getChecks()` category block |
| Add a third-party check | `addCheck()` or adext `health` / `checks` callable (never edit this plugin) |
| Tune a threshold | `PhpVersionCheck` min/rec, `DiskSpaceCheck` MB floors, `SessionConfigCheck` lifetime cap, `minimumVersions` in `DatabaseCheck` |
| Change severity logic | The status constants inside the relevant check (keep the guideline 4 bar) |
| Change cache behavior | `cache_ttl` in `Config/Config.php`, or `loadCache()`/`saveCache()` guards |
| Add a category | `CheckResult::CAT_*` const + controller `$categories` label/icon map |
| Change dashboard wording | `dashboardCards()` labels/descriptions (`Services/HealthService.php:91-113`) |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in actual code; no guesses about behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`); PHPStan level 3 clean on the app; only `CheckResult` consts used for status/category
- [ ] New checks stay read-only, self-contained, exception-safe, and below the critical-or-warning severity bar
- [ ] Messages leak no credentials or config values; view output fully escaped
- [ ] Cache contract intact (serializable results, TTL respected, rerun clears + forces)
- [ ] Dashboard card still appears only when issues exist
- [ ] README updated only if user-facing behavior changed; keep the check count and category list in sync

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Purely advisory: results and remediation snippets are guidance; nothing is auto-fixed or self-healing. `POST /site-health/rerun` only recomputes diagnostics.
- No public endpoints; everything lives under the admin group and the tools-scoped dashboard card.
- No scheduled runs, email alerts, or history/trend tracking; the page and card reflect the current cached pass only.
- The plugin cannot certify security; it only reports signals like `display_errors`, `.env` permissions, and HTTPS posture.
- Known cosmetic inconsistencies to reconcile, not fixed here:
  - The view alert says results "are updated only when you click Re-run Checks", but `runAll()` already refreshes automatically once `cache_ttl` elapses (`Views/admin/index.php:72`, `Services/HealthService.php:41-46`). `<!-- TODO: reconcile cache staleness copy in the admin view with auto-refresh-on-expiry behavior -->`