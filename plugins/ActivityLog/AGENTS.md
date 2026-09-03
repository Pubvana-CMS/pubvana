# AGENTS.md — Activity Log plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

**pubvana/activity-log** (display name "Activity Log") is an audit trail of admin actions. It provides an explicit logging API and optional auto-tracking of admin mutating routes.

- **Package:** `pubvana/activity-log` (local plugin, no Packagist)
- **License:** MIT, matching the main project
- **PHP:** requires PHP `^8.2` (repo `composer.json`)
- **Namespace:** `Pubvana\Plugins\ActivityLog`
- **Manifest:** `pubvana.json` (admin menu under Tools)
- **Config:** `Config/Config.php`
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Auto-tracking must never break page delivery.** `logFromRoute()` swallows every failure. A cache, DB, or routing problem on a public request is a no-op, never an error page.
2. **Track mutations only.** The `flight.route.executed` listener only fires for POST/PUT/DELETE/PATCH on `/admin/*` routes. GET requests are never logged.
3. **Respect the skip list.** Auth, assets, API, and self-referential routes are excluded from auto-tracking (`Plugin.php:85`). Keep new skip patterns inside the same list.
4. **No sensitive data in details.** The `details` field stores JSON context (route, method, params). Do not add passwords, tokens, or PII.
5. **Keep action/entity_type vocabularies stable.** New actions/entity types should follow existing naming (snake_case, singular). Update `getActions()`/`getEntityTypes()` if needed for filter dropdowns.

## Repository layout

```
ActivityLog/
  Plugin.php                          # Entry point: service, routes, event listener, dashboard card
  pubvana.json                        # Plugin manifest and admin menu under Tools
  README.md                           # User-facing docs
  Config/
    Config.php                        # Defaults: track_admin_actions, retention_days
  Controllers/
    ActivityLogAdminController.php    # Admin: index (filterable list with pagination)
  Services/
    ActivityLogService.php            # log(), logFromRoute(), list(), count(), dashboard data
  Models/
    ActivityLog.php                   # activity_logs table model with filtered queries
  Database/
    Migrations/
      2026-09-02-000001_CreateActivityLogsTable.php
    Seeds/Seed.php                    # Seeds activity_log.view permission
  Views/
    admin/index.php                   # Filterable table with pagination
```

## Core architecture

### Plugin registration

`Plugin.php:31` maps `activityLog` as a singleton `ActivityLogService` on the app engine, wired to `$app->db()` and the plugin config. The service receives the app instance via `setApp()` for accessing auth/request.

Admin route registered under `pubvana.activity-log` (`Plugin.php:45`): `GET /admin/activity-log` → `ActivityLogAdminController::index`, gated by `activity_log.view` permission.

Dashboard card registered via adext (`Plugin.php:52`) showing 24h activity count.

### Auto-tracking

`Plugin.php:72` registers a listener on `flight.route.executed`. After every successfully dispatched route:
- Checks `activity_log.track_admin_actions` config (default `true`)
- Filters to `/admin/*` routes with POST/PUT/DELETE/PATCH
- Skips `/admin/auth/*`, `/admin/assets/*`, `/admin/api/*`, `/admin/activity-log*`
- Calls `$app->activityLog()->logFromRoute($route)`

`ActivityLogService::logFromRoute()` (`Services/ActivityLogService.php:135`) infers action/entity from route pattern using a whitelist map (`inferFromRoute()`), extracts entity ID/name from route params, and calls `log()`.

### Explicit logging

Plugins call `$app->activityLog()->log([...])` with structured data. The service enriches with current user, IP, user agent, and timestamp.

### Reporting

`list()` and `count()` support filtering by user, action, entity_type, entity_name, date range. Pagination at 25/page.

## Development and testing

This plugin has no `composer.json` and no test suite.

```bash
php -l plugins/ActivityLog/Plugin.php
php -l plugins/ActivityLog/Services/ActivityLogService.php
php -l plugins/ActivityLog/Models/ActivityLog.php
php -l plugins/ActivityLog/Controllers/ActivityLogAdminController.php
```

- Enable plugin at `/admin/plugins` (runs migration)
- Visit `/admin/activity-log` — verify filters, pagination, empty state
- Perform admin actions (create/edit/delete blog post, page, redirect, etc.) — verify entries appear
- Disable `track_admin_actions` in Config — verify auto-tracking stops, explicit logging still works
- Check dashboard card at `/admin` shows 24h count
- Verify permission gate: user without `activity_log.view` cannot access

## Coding standards

- **PHPStan (level 8):** model carries `@property`/`@method` annotations; service facade has `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.
- `declare(strict_types=1);` first line in every class file.
- Class name, file name, and namespace must align.
- All SQL uses bound parameters; no interpolation of request input.
- Keep the log shape stable: `log()` always writes the same columns. The view consumes `created_at`, `user_name`, `action`, `entity_type`, `entity_id`, `entity_name`, `ip`.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | User-facing features, configuration, API |
| [Config/Config.php](./Config/Config.php) | Skip lists and retention defaults |
| [Services/ActivityLogService.php:135](./Services/ActivityLogService.php) | `inferFromRoute()` — route-to-action mapping |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a route to auto-tracking | `inferFromRoute()` at `Services/ActivityLogService.php:175` |
| Change skip patterns | `shouldSkipRoute()` at `Services/ActivityLogService.php:158` and `Plugin.php:85` |
| Add a filter field | `filtered()`/`countFiltered()` in `Models/ActivityLog.php:75`, view form in `Views/admin/index.php` |
| Change dashboard card | `Plugin.php:52` |
| Change retention/cleanup | `Config/Config.php:5` (future CLI command) |
| Add action/entity_type to dropdowns | `getActions()`/`getEntityTypes()` in `Services/ActivityLogService.php` |

## PR / contribution checklist

- [ ] `php -l` clean on every touched file
- [ ] Auto-tracking verified: admin POST logged, GET skipped, auth/assets/api skipped, config toggle works
- [ ] Explicit `log()` API works and enriches user/IP/UA
- [ ] Filters and pagination work; empty state renders
- [ ] Dashboard card shows correct 24h count
- [ ] Permission gate works
- [ ] SQL uses bound parameters
- [ ] README.md updated if user-facing behavior changed

## Out of scope / non-goals

- Login/logout tracking (not implemented)
- Real-time UI (polling/WebSocket)
- Per-visitor or frontend tracking
- IP/location/geolocation storage beyond request IP
- Automatic log pruning (retention_days is config only; future CLI command)