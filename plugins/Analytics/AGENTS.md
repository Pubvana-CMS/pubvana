# AGENTS.md — Analytics plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

**pubvana/analytics** (display name "Analytics") is server-side traffic tracking and reporting. It records a row per public page view, rolls old rows into daily aggregates, and renders reports under Tools.

- **Package:** `pubvana/analytics` (local plugin, no Packagist)
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`mixed` type hints, `str_starts_with`, arrow functions)
- **Namespace:** `Pubvana\Plugins\Analytics` (PSR-4 style, matches the folder path)
- **Runtime dependencies:** none beyond PHP, PDO, and the app core. The report view loads Chart.js from a CDN
- **Config:** `Config/Config.php` plus the `Analytics.tracking_enabled` site setting
- **Manifest:** `pubvana.json` (admin menu under Tools)
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Tracking must never break page delivery.** `logView()` and `maybeRollup()` swallow every failure (`AnalyticsService.php:400`, `AnalyticsService.php:345`). New tracking code must keep this contract; a cache, DB, or lock problem on a public request is a no-op, never an error page.
2. **Track rows only, never a hit per byte.** The raw table holds one row per view; the daily rollup compacts rows older than the hot window so retention is unbounded without unbounded rows (`AnalyticsService.php:266`). Do not add another row-eater or drop the rollup path.
3. **Respect the skip rules.** Non-GET/HEAD verbs, bots (`AnalyticsService.php:712`), static file extensions, admin/api/assets prefixes, and the configured feed paths are never counted (`AnalyticsService.php:685`). Keep new tracking paths inside the same filters.
4. **No IP or location data.** The README promises visitors are not tracked by IP or location (`README.md:7`), and the service records only the path, a derived group, and the referrer host. Do not add IP, user-agent, or geolocation capture.
5. **Keep ranges canonical.** A range is one of `7`, `30`, `90`, `180`, `365`, or `all` (`AnalyticsService.php:77`). The controller sanitizes request input against this before it reaches a query.
6. **Rollup is idempotent and once-a-day.** The flag file in `writable/cache/` plus an exclusive flock gates the daily run (`AnalyticsService.php:314`). Never run rollup from a request without that guard, and keep the `ON DUPLICATE KEY UPDATE` merge so re-runs cannot double count.

## Repository layout

```
Analytics/
  Plugin.php                    # Entry point: maps 'analytics' service, admin routes, page-view event listener, dashboard card
  pubvana.json                  # Plugin manifest and admin menu under Tools
  README.md                     # User-facing tracking, report, retention, and schema docs
  Config/
    Config.php                  # Defaults: tracking skip lists, rollup hot window
  Controllers/
    AnalyticsAdminController.php  # Admin: index (report), data (AJAX range refresh), toggleTracking
  Services/
    AnalyticsService.php        # View tracking, rollup, reporting, all SQL
  Models/
    PageView.php                # analytics_page_views table model
  Database/
    Migrations/                 # Creates analytics_page_views, analytics_views_daily, analytics_referrers_daily
  Views/
    admin/index.php             # Report page: range filter, chart, top content, referrers, tracking toggle
```

## Core architecture

### Plugin registration

`Plugin.php:26` maps `analytics` as a singleton `AnalyticsService` on the app engine, wired to `$app->db()`, the engine, and the plugin config. Three admin routes are registered under `pubvana.analytics` (`Plugin.php:39`): the report page, a JSON data endpoint for the AJAX range refresh, and a tracking toggle POST.

A listener on `flight.route.executed` calls `logView()` then `maybeRollup()` after every successfully dispatched request (`Plugin.php:50`). Because the event fires only when a route actually dispatched, 404s and static files are never counted. A dashboard card is registered through adext showing 7-day views (`Plugin.php:57`).

### Tracking path

`logView()` (`AnalyticsService.php:375`) runs only on web requests (never CLI), only when tracking is enabled (resolved once per request via `isTrackingEnabled()`, `AnalyticsService.php:356`), only for GET/HEAD, and only after the path survives `shouldSkip()` and the user agent is not a bot. The path is normalized (base stripped, slashes collapsed, trailing slash removed), grouped by its first segment (`groupForPath()`, `AnalyticsService.php:645`), and clipped to column lengths before insert. The referrer is reduced to its host (`referrerDomain()`, `AnalyticsService.php:729`).

### Reporting

`dashboard()` (`AnalyticsService.php:63`) returns the full dataset for a range: total views, a zero-filled per-group trend series, top content, and top referrers. Ranges under the hot window (`rollup.hot_days`, default 30) read only `analytics_page_views`; longer ranges and `all` UNION the raw rows with the daily aggregate tables (`usesDaily()`, `AnalyticsService.php:109`). The trend chart is assembled with per-bucket axes, granularity day or month for `all`, and groups ordered by total views.

### Rollup

`rollup()` (`AnalyticsService.php:266`) merges raw rows older than the hot window into `analytics_views_daily` and `analytics_referrers_daily` with `ON DUPLICATE KEY UPDATE`, then deletes the raw rows in batches of 5000. `maybeRollup()` (`AnalyticsService.php:314`) guards it with a dated flag file under `writable/cache/analytics_rollup` and an exclusive flock, so the rollup runs at most once a day and never on CLI.

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo.

```bash
php -l plugins/Analytics/Plugin.php           # lint every touched file
php -l plugins/Analytics/Services/AnalyticsService.php
```

- View the report at `/admin/analytics` and confirm the range buttons (7d/30d/90d/180d/1y/All) redraw the chart and tables through `/admin/analytics/data`.
- Confirm the tracking toggle persists and that a disabled state stops new rows.
- Visit a public page and confirm one row lands in `analytics_page_views`, then confirm an admin path, a bot user agent, and a `.css` request do not.
- Confirm the 404 path is not counted (the listener only fires on dispatched routes).
- Coverage: none configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

Steps that go beyond the repo-wide style, derived from the existing code:

1. `declare(strict_types=1);` first line in every class file.
2. Class name, file name, and namespace must align: `Pubvana\Plugins\Analytics\Services\AnalyticsService` lives in `Services/AnalyticsService.php`.
3. All report SQL lives inside `AnalyticsService`; the controller and view never write SQL.
4. Bound parameters only: every user-facing value (range cutoff is derived; limits are interiors) is bound with `bindValue`. Do not interpolate request input into SQL strings. The `LIMIT` value is already sanitized by `max(1, $limit)` before concatenation (`AnalyticsService.php:155`).
5. Keep the report shape stable: `dashboard()` always returns `range`, `totalViews`, `trends` (`granularity`/`labels`/`series`), `topContent`, `referrers`. The view and the JSON endpoint both consume that exact shape.
6. Sanitize ranges with `normalizeRange()` before they reach any query; never trust a raw `range` param.
7. Chart data passes through the existing `escHtml()`/`escAttr()` helpers in the view. Do not inject path or group strings into the DOM unescaped.
8. Table names stay consistent with the plugin: `analytics_page_views`, `analytics_views_daily`, `analytics_referrers_daily`. Do not introduce a new analytics table without a matching migration.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | Tracking behavior, report features, retention, and the table schemas |
| [Config/Config.php](./Config/Config.php) | Skip lists and rollup hot window defaults |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a path/prefix to skip tracking | `tracking.skip_prefixes` / `tracking.skip_paths` in `Config/Config.php:5` |
| Change the hot window before rollup | `rollup.hot_days` in `Config/Config.php:20` |
| Add a bot keyword | `BOT_KEYWORDS` at `AnalyticsService.php:33` |
| Add a static extension to skip | `STATIC_EXTENSIONS` at `AnalyticsService.php:42` |
| Add a report metric | `dashboard()` at `AnalyticsService.php:63`, then the view's render + JS at `Views/admin/index.php` |
| Change rollup merge/delete behavior | `rollup()` at `AnalyticsService.php:266` |
| Change the once-a-day guard | `maybeRollup()` at `AnalyticsService.php:314` |
| Add an admin route | `Plugin.php:39` |

## PR / contribution checklist

- [ ] Changes fit the project guidelines (tracking never breaks the page, no IP/location data, skip rules intact)
- [ ] `php -l` clean on every touched file
- [ ] New or changed tracking paths verified: GET recorded, admin/API/asset/feed skipped, bot UA skipped, 404 not recorded
- [ ] Rollup verified idempotent and once-per-day under the flock guard
- [ ] SQL uses bound parameters or already-sanitized values
- [ ] Report shape unchanged for the view and `/admin/analytics/data`, or both updated together
- [ ] README.md updated if tracking behavior or schema changed

## Out of scope / non-goals

- Client-side or script-based analytics, session tracking, funnels, or per-visitor identity.
- IP, location, or user-agent storage. The promise in the README is that visitors are not tracked that way.
- Advertising metrics, conversion tracking, or external analytics integrations.
- A real-time UI. The report is a daily-grained aggregate plus the recent raw window.
