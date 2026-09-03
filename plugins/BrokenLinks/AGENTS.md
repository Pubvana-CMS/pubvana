# AGENTS.md — Broken Links plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Broken Links scans outbound links in published posts and pages, checks each via HTTP, and stores results in the `broken_links` table. Admins review, recheck, or permanently dismiss entries under **Tools > Broken Links**. A CLI command supports automated or manual scanning.

- **Package:** `pubvana/brokenlinks` (`pubvana.json:2`), semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`)
- **Namespace:** `Pubvana\Plugins\BrokenLinks` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, `Database\Migrations`, and `commands` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (auth), `enlivenapp/flight-sessions` (flash messages); core engine services `$app->db()`, `adext()`, `settings()`, `session()`, `redirect()`; curl extension for HTTP checks
- **Config:** `Config/Config.php`: `routePrepend` (`broken-links`), `timeout` (`10`), `max_redirects` (`5`), `user_agent` (`Pubvana-LinkChecker/1.0`)
- **Docs:** `README.md`

## Project guidelines

1. **Collect sources via adext, not hardcoded queries.** `BrokenLinksService::collectSources()` reads `$app->adext()->get('brokenlinks', 'source')` and calls each registered callable. Plugins register their own content sources; BrokenLinks never reaches into other plugins' tables directly. Reason: clean separation of concerns, extensibility without coupling.
2. **Dismissal is permanent.** Once `dismissed = 1`, the row is never updated, re-enabled, or touched on re-scan (`Services/BrokenLinksService.php:168-170`). The upsert skips dismissed rows entirely. Reason: permanent dismissal prevents scan noise from dismissed entries re-appearing.
3. **Sequential URL checking.** All HTTP checks run one at a time (`Services/BrokenLinksService.php:scan()`). Reason: shared-host friendly, avoids hammering external servers.
4. **Never modify dismissed rows during upsert.** The upsert method returns early if the existing entry is dismissed (`Services/BrokenLinksService.php:170-172`). Reason: a re-scan must not override a permanent dismiss decision.
5. **Upsert keys on (source_type, source_id, url_hash).** The unique index prevents duplicate rows for the same URL in the same content item (`Database/Migrations/2026-09-03-100001`). The SHA1 hash enables indexing without a full-text key. Reason: deterministic deduplication across scans.
6. **Delete OK rows after scanning each source.** After all URLs for a source are checked, rows with 2xx status are deleted (`Services/BrokenLinksService.php:248-255`). Reason: links that were broken but are now fixed should not linger in the results.
7. **Use DOMDocument for HTML link extraction.** Parse `<a href>` tags with `LIBXML_NOERROR | LIBXML_NOWARNING` to suppress warnings on fragment HTML (`Services/BrokenLinksService.php:291-298`). Reason: handles real-world HTML from Jodit editors better than regex alone.
8. **Filter to external URLs only.** Same-host, mailto, tel, javascript, data, and fragment-only links are excluded (`Services/BrokenLinksService.php:310-318`). Reason: only outbound links need HTTP checking.
9. **HTTP checks use curl directly, not a framework HTTP client.** HEAD first, GET fallback on 405, configurable timeout and redirect limit (`Services/BrokenLinksService.php:262-287`). Reason: avoids adding a framework dependency; curl is universally available on shared hosts.
10. **Fresh model instances via private `model()` helper.** Every query that needs a new model instance calls `$this->model()` (`Services/BrokenLinksService.php:360-363`). Reason: shared instances hold query state across calls.
11. **DateTimeImmutable for every timestamp write.** All `now()` calls use `new \DateTimeImmutable()` (`Services/BrokenLinksService.php:355-358`). Reason: immutable timestamps prevent accidental mutation.
12. **Controllers strip `_csrf_token` before forwarding POST data.** Standard v3 pattern. Reason: prevents CSRF token from being stored or processed as data.

## Repository layout

```
plugins/BrokenLinks/
├── Config/Config.php                                      routePrepend, timeout, max_redirects, user_agent
├── Controllers/
│   └── BrokenLinksAdminController.php                     Admin UI: list, scan, recheck, dismiss
├── Database/
│   └── Migrations/
│       └── 2026-09-03-100001_CreateBrokenLinksTable.php   broken_links table
├── Models/
│   └── BrokenLink.php                                     broken_links table model
├── Services/
│   └── BrokenLinksService.php                             Core logic: scan, extract, check, CRUD
├── commands/
│   ├── BrokenLinksCheckCommand.php                        CLI: broken-links:check
│   └── BrokenLinksCronCommand.php                         Cron stub for future scheduling
├── Views/
│   └── admin/
│       └── index.php                                      Admin view: grouped results
├── Plugin.php                                             Entry point; routes, dashboard, service facade
├── pubvana.json                                           Manifest; admin.menu (Tools > Broken Links)
├── AGENTS.md                                              This file
└── README.md                                              User documentation
```

## Core architecture

**Service.** One singleton is mapped: `brokenLinks` (`Plugin.php:32-38`), built from the engine PDO, app instance, and plugin config.

**Content sources.** Plugins register as content sources via adext type `brokenlinks` slot `source`. Each registration provides a `label` and a `callable` that returns `array<int, array{type: string, id: int, title: string, content: string}>`. The Blog and Pages plugins each register their own source; future plugins follow the same pattern. `collectSources()` iterates all registrations.

**Scanning flow.** `scan()` collects sources, extracts external links from each via DOMDocument + regex, checks each URL via curl HEAD (GET fallback on 405), upserts results, then deletes rows that are now OK. Results are keyed on `(source_type, source_id, url_hash)`.

**Admin screen.** A single page under **Tools > Broken Links** shows results grouped by source (post/page badge + title as edit link). Actions: Run Scan (full), Recheck (single URL), Dismiss (permanent). A toggle shows/hides dismissed entries.

**CLI.** `php runway broken-links:check` runs the same scan logic. Returns exit code 1 if any broken links found, 0 otherwise. Auto-discovered by Runway from `plugins/BrokenLinks/commands/`.

**Cron stub.** `BrokenLinksCronCommand` is a skeleton ready for the cron infrastructure. It delegates to the same scan method. When the cron system is built, this wires up in one line.

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root):
  - `composer phpstan` (level 8)
  - `find plugins/BrokenLinks -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Run a scan from the admin UI; confirm it checks outbound links in published posts and pages
  - [ ] Confirm links to the same site are excluded from scanning
  - [ ] Confirm mailto, tel, javascript, and data URLs are excluded
  - [ ] Recheck a single broken link that returns a non-2xx status
  - [ ] Recheck a broken link that is now reachable; confirm it is removed
  - [ ] Dismiss a broken link; confirm it no longer appears in the default view
  - [ ] Show dismissed entries; confirm dismissed links appear with muted styling
  - [ ] Run `php runway broken-links:check` and confirm CLI output
  - [ ] Run a scan twice; confirm no duplicate rows are created
  - [ ] Confirm a dismissed row is not updated when the same URL is found broken again

No coverage is configured for this plugin.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/BrokenLink.php:38-41`).
3. **Keep the `@property` column docblocks in sync with the migrations** (`Models/BrokenLink.php:7-18`).
4. **Pull fresh model instances through a private `model()` helper** (`Services/BrokenLinksService.php:360-363`). Reason: a shared instance would hold query state across calls.
5. **Use `DateTimeImmutable` for every timestamp write** (`Services/BrokenLinksService.php:355-358`).
6. **Controllers strip `_csrf_token` before forwarding POST data.**
7. **Dismissed rows are never modified by the scan service.** The upsert returns early on dismissed entries.
8. **Content sources are registered via adext, not hardcoded.** The service iterates `$app->adext()->get('brokenlinks', 'source')`.

## PR / contribution checklist

- [ ] `declare(strict_types=1)` present; no em dashes in new prose
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean
- [ ] Dismissal is permanent; upsert skips dismissed rows
- [ ] Content sources registered via adext, not hardcoded queries
- [ ] Sequential URL checking preserved
- [ ] Fresh model instances via `model()` helper
- [ ] DateTimeImmutable for all timestamps
- [ ] README updated only if user-facing behavior changed
