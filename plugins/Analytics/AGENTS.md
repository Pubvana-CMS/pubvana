# AGENTS.md: Analytics plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

**pubvana/analytics** (display name "Analytics") is server-side traffic tracking and reporting. It records a row per public page view, rolls old rows into daily aggregates, and renders reports under Tools.

- **Package:** `pubvana/analytics` (local plugin, no Packagist)
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`mixed` type hints, `str_starts_with`, arrow functions)
- **Namespace:** `Pubvana\Plugins\Analytics` (PSR-4 style, matches the folder path)
- **Runtime dependencies:** none beyond PHP, PDO, and the app core. The report view loads Chart.js from a CDN
- **Config:** `Config/Config.php`
- **Manifest:** `pubvana.json` (admin menu under Tools)
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Tracking must never break page delivery.** `logView()` and `maybeRollup()` swallow every failure (`AnalyticsService.php:485`, `AnalyticsService.php:423`). New tracking code must keep this behavior; a cache, DB, or lock problem on a public request is a no-op, never an error page.
2. **Track rows only, never a hit per byte.** The raw table holds one row per view; the daily rollup compacts rows older than the hot window so retention is unbounded without unbounded rows (`AnalyticsService.php:277`). Do not add another row-eater or drop the rollup path.
3. **Respect the skip rules.** Non-GET/HEAD verbs, static file extensions, admin/api/assets prefixes, and the configured feed paths are never counted. Bot hits are counted and stored with `is_bot` set to 1, so visitor totals leave them out and the chart can show them on their own line.
4. **No IP or location data.** The README promises visitors are not tracked by IP or location, and the service records only the path, a derived group, the referrer host, and a bot flag. The user agent is read to set the flag and is never stored. Do not add IP, user-agent, or geolocation capture.
5. **Keep ranges canonical.** A range is one of `7`, `30`, `90`, `180`, `365`, or `all` (`AnalyticsService.php:88`). The controller sanitizes request input against this before it reaches a query.
6. **Rollup is idempotent and once-a-day.** The flag file in `writable/cache/` plus an exclusive flock gates the daily run (`AnalyticsService.php:389`). Never run rollup from a request without that guard, and keep the `ON DUPLICATE KEY UPDATE` merge so re-runs cannot double count.

## Repository layout

```
Analytics/
  Plugin.php                    # Entry point: maps 'analytics' service, admin routes, page-view event listener, dashboard card
  pubvana.json                  # Plugin manifest and admin menu under Tools
  README.md                     # User-facing features and usage
  Config/
    Config.php                  # Defaults: tracking skip lists, rollup hot window
  Controllers/
    AnalyticsAdminController.php  # Admin: index (report), data (AJAX range refresh), content and referrer lists
  Services/
    AnalyticsService.php        # View tracking, rollup guard, report assembly
  Models/
    AnalyticsModel.php          # Shared connection and query helpers
    PageView.php                # analytics_page_views reads, rollup run
    ViewsDaily.php              # analytics_views_daily reads and merge
    ReferrersDaily.php          # analytics_referrers_daily reads and merge
    BotsDaily.php               # analytics_bots_daily reads and merge
  Database/
    Migrations/                 # Creates and alters the four analytics tables
  Views/
    admin/index.php             # Report page: range filter, chart, top content, referrers
```

## Core architecture

### Plugin registration

`Plugin.php:29` maps `analytics` as a singleton `AnalyticsService` on the app engine. The plugin builds the four models from `$app->db()` and passes them, the engine, and the plugin config to the service. Four admin routes are registered under `pubvana.analytics` (`Plugin.php:41`): the report page, a JSON data endpoint for the AJAX range refresh, the full content list, and the full referrer list.

A listener on `flight.route.executed` calls `logView()` then `maybeRollup()` after every successfully dispatched request (`Plugin.php:52`). Because the event fires only when a route actually dispatched, 404s and static files are never counted. A dashboard card is registered through adext showing 7-day views (`Plugin.php:59`).

### Tracking path

`logView()` (`AnalyticsService.php:452`) runs only on web requests (never CLI), only when tracking is enabled, only for GET/HEAD, and only after the path survives `shouldSkip()`. The path is normalized (base stripped, slashes collapsed, trailing slash removed), grouped by its first segment (`groupForPath()`), and clipped to column lengths before insert. The referrer is reduced to its host, and a referrer on this site's own host is dropped (`referrerDomain()`). A hit from a bot is stored with `is_bot` set to 1. Cache hits go through the same checks in `processPageCacheHits()`.

### Reporting

`dashboard()` returns the full dataset for a range: total views, the same total for the equal-length period before it, a zero-filled trend, the first 10 content paths, and the first 10 referrers. Every query filters `is_bot = 0` except the bot line. Ranges under the hot window read only `analytics_page_views`. Longer ranges and `all` add the daily tables and merge the rows in PHP. The trend chart draws a Visitors line, a Robots line, then one line per group ordered by total views.

The full lists are separate pages, not more rows in the report. `contentPage()` and `referrersPage()` return one page of 100 rows plus the total count, and `AnalyticsAdminController::renderList()` builds the shared pagination from them.

### Rollup

`rollup()` merges raw rows older than the hot window into `analytics_views_daily`, `analytics_referrers_daily`, and `analytics_bots_daily` with `ON DUPLICATE KEY UPDATE`, then deletes the raw rows in batches of 5000. `PageView::rollupOlderThan()` runs the three merges and the delete in one transaction. `maybeRollup()` guards it with a dated flag file under `writable/cache/analytics_rollup` and an exclusive flock, so the rollup runs at most once a day and never on CLI.

### Data model

Four tables hold the data, created by the migrations in `Database/Migrations/`. One migration adds `is_bot` to the raw table and creates `analytics_bots_daily`.

`analytics_page_views` holds one row per tracked view, kept only inside the hot window:

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `page_path` | varchar(255) | Normalized request path, indexed |
| `page_group` | varchar(50) | First URL segment, indexed |
| `referrer_domain` | varchar(255) | Host of the incoming referrer, nullable |
| `is_bot` | tinyint | 1 for a bot hit, 0 for a visitor |
| `viewed_at` | datetime | Indexed |

`analytics_views_daily` holds rolled views by day, group, and path:

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `day` | varchar(10) | `YYYY-MM-DD` |
| `page_group` | varchar(50) | |
| `page_path` | varchar(255) | |
| `view_count` | int | |

Unique index on `(day, page_group, page_path)`.

`analytics_referrers_daily` holds rolled views by day and referrer domain:

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `day` | varchar(10) | `YYYY-MM-DD` |
| `referrer_domain` | varchar(255) | |
| `view_count` | int | |

Unique index on `(day, referrer_domain)`. A view with no referrer is stored with an empty domain and shown in the report as Direct.

`analytics_bots_daily` holds rolled bot hits by day:

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `day` | varchar(10) | `YYYY-MM-DD` |
| `view_count` | int | |

Unique index on `day`.

## Development and testing

The unit suite is in `tests/Unit/Plugins/Analytics/` and covers the service, the model and controller helpers, the rollup upsert SQL, and the report view's inline-script escaping.

```bash
php -l plugins/Analytics/Plugin.php           # lint every touched file
php -l plugins/Analytics/Services/AnalyticsService.php
vendor/bin/phpunit tests/Unit/Plugins/Analytics
```

- View the report at `/admin/analytics` and confirm the range buttons (7d/30d/90d/180d/1y/All) redraw the chart and tables through `/admin/analytics/data`.
- Confirm the range buttons (7d/30d/90d/180d/1y/All) redraw the chart and tables through `/admin/analytics/data`, and that View all keeps the chosen range.
- Visit a public page and confirm one row goes into `analytics_page_views`, then confirm an admin path and a `.css` request do not. A bot user agent writes a row with `is_bot` set to 1.
- Confirm the 404 path is not counted (the listener only fires on dispatched routes).
- Coverage: the unit suite covers `AnalyticsService` reporting and helpers, `PageView` and `AnalyticsAdminController` helpers, the rollup upsert SQL (MySQL vs MariaDB forms), and the report view's inline JSON escaping. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

Steps that go beyond the repo-wide style, derived from the existing code:

1. `declare(strict_types=1);` first line in every class file.
2. Class name, file name, and namespace must align: `Pubvana\Plugins\Analytics\Services\AnalyticsService` is in `Services/AnalyticsService.php`.
3. All report SQL lives in the models in `Models/`. The service assembles the report, and the controller and view never write SQL.
4. Bound parameters only: every value passed to a query is bound. Do not interpolate request input into SQL strings. The `LIMIT` value is sanitized by `max(1, $limit)` before concatenation.
5. Keep the report shape stable: `dashboard()` always returns `range`, `totalViews`, `previousTotalViews`, `trends` (`granularity`/`labels`/`series`), `topContent`, `referrers`. The view and the JSON endpoint both consume that exact shape.
6. Sanitize ranges with `normalizeRange()` before they reach any query; never trust a raw `range` param.
7. Chart data passes through the existing `escHtml()`/`escAttr()` helpers in the view. Do not inject path or group strings into the DOM unescaped.
8. Table names stay consistent with the plugin: `analytics_page_views`, `analytics_views_daily`, `analytics_referrers_daily`, `analytics_bots_daily`. Do not introduce a new analytics table without a matching migration.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | User-facing features and usage |
| [Config/Config.php](./Config/Config.php) | Skip lists and rollup hot window defaults |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a path/prefix to skip tracking | `tracking.skip_prefixes` / `tracking.skip_paths` in `Config/Config.php:8` |
| Change the hot window before rollup | `rollup.hot_days` in `Config/Config.php:23` |
| Add a bot keyword | `BOT_KEYWORDS` at `AnalyticsService.php:40` |
| Add a static extension to skip | `STATIC_EXTENSIONS` at `AnalyticsService.php:49` |
| Add a report metric | `dashboard()` at `AnalyticsService.php:74`, then the view's render + JS at `Views/admin/index.php` |
| Change rollup merge/delete behavior | `rollup()` at `AnalyticsService.php:277` |
| Change the once-a-day guard | `maybeRollup()` at `AnalyticsService.php:389` |
| Add an admin route | `Plugin.php:41` |

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
