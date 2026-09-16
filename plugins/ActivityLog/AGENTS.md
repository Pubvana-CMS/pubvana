# Activity Log

## Overview

`pubvana/activity-log` records admin changes. It logs mutating admin routes automatically and lets other plugins write entries through `$app->activityLog()->log()`.

- Package: `pubvana/activity-log` (local plugin, `pubvana.json:2`)
- PHP: `^8.2` (repo `composer.json`)
- Namespace: `Pubvana\Plugins\ActivityLog`
- Enabled by default. No install step.
- License: MIT
- Docs: [README.md](./README.md)

## Project guidelines

1. Do not let logging break the request. `log()` and `logFromRoute()` catch all failures (`Services/ActivityLogService.php:59`, `Services/ActivityLogService.php:90`). Reason: a logging error must never become an error page.
2. Track mutations only. The listener checks POST, PUT, DELETE, PATCH on `/admin/*` (`Plugin.php:72`). Reason: reads would flood the table.
3. Keep skip patterns together. Auth, assets, API, and self routes are skipped (`Plugin.php:92`, `Services/ActivityLogService.php:216`). Reason: avoids loops and noise.
4. Do not store secrets in `details`. Only route, method, and params are stored (`Services/ActivityLogService.php:90`). Reason: logs are readable by staff and must not leak credentials.
5. Keep action and entity_type names stable, snake_case, singular (`Services/ActivityLogService.php:239`). Reason: filter dropdowns and history depend on consistent values.
6. Use bound parameters for all queries (`Models/ActivityLog.php:112`). Reason: request input reaches filters directly.

## Repository layout

```
ActivityLog/
  Plugin.php                          # Entry point, routes, listener, dashboard card
  pubvana.json                        # Manifest, Tools menu entry (pubvana.json:8)
  README.md                           # User docs
  Config/
    Config.php                        # routePrepend, track_admin_actions, retention_days (Config/Config.php:6)
  Controllers/
    ActivityLogAdminController.php    # index list with filters (Controllers/ActivityLogAdminController.php:15)
  Services/
    ActivityLogService.php            # log, logFromRoute, list, count (Services/ActivityLogService.php:59)
  Models/
    ActivityLog.php                   # activity_logs queries (Models/ActivityLog.php:56)
  Database/
    Migrations/
      2026-09-02-000001_CreateActivityLogsTable.php  # activity_logs table (Database/Migrations/2026-09-02-000001_CreateActivityLogsTable.php:13)
    Seeds/Seed.php                    # activity_log.view permission (Database/Seeds/Seed.php:9)
  Views/
    admin/index.php                   # Filter form and table (Views/admin/index.php:1)
```

No generated dirs in this plugin.

## Core architecture

Entry point is `Plugin.php:24`. It maps `activityLog` to `ActivityLogService` (`Plugin.php:30`), adds `GET /admin/activity-log` with `activity_log.view` check (`Plugin.php:43`), adds a dashboard card (`Plugin.php:48`), and listens for `flight.route.executed` (`Plugin.php:72`).

Data flow for auto tracking: listener checks config (`Plugin.php:74`), keeps only POST, PUT, DELETE, PATCH on `/admin/*`, skips auth, assets, API, and self routes (`Plugin.php:92`), then calls `logFromRoute()` (`Services/ActivityLogService.php:90`). That method checks config, skips non admin routes (`Services/ActivityLogService.php:216`), maps route to action and entity (`Services/ActivityLogService.php:239`), then calls `log()` (`Services/ActivityLogService.php:59`).

Data flow for manual logging: any plugin calls `$app->activityLog()->log()` with action, entity_type, entity_id, entity_name, and details. The service fills user, IP, user agent, and timestamp, then saves through `Models/ActivityLog.php:56`.

Data flow for reads: controller `index()` (`Controllers/ActivityLogAdminController.php:15`) reads query filters, calls `list()` (`Services/ActivityLogService.php:142`) and `count()` (`Services/ActivityLogService.php:153`), which use `filtered()` (`Models/ActivityLog.php:78`) and `countFiltered()` (`Models/ActivityLog.php:94`). Page size is 25.

### Route to action map

`inferFromRoute()` (`Services/ActivityLogService.php:239`) strips `/admin` and matches by prefix, with longer paths first (for example `/redirects/404-manager` before `/redirects`). `matchAction()` (`Services/ActivityLogService.php:325`) picks the action by HTTP method and path. Entity ID comes from route params (`Services/ActivityLogService.php:350`). Entity name comes from route params or falls back to entity type (`Services/ActivityLogService.php:365`).

### IP handling

Only `$_SERVER['REMOTE_ADDR']` is used (`Services/ActivityLogService.php:385`). Reason: proxy headers can be forged. Do not add `X-Forwarded-For` handling here.

## Development and testing

No `composer.json` in this plugin. Use repo root commands.

```bash
composer lint
composer phpstan
composer psalm
composer test
```

Tests live in `tests/Unit/Plugins/ActivityLog/`:

```
tests/Unit/Plugins/ActivityLog/ActivityLogAdminControllerTest.php
tests/Unit/Plugins/ActivityLog/ActivityLogMigrationsSeedTest.php
tests/Unit/Plugins/ActivityLog/ActivityLogPluginTest.php
tests/Unit/Plugins/ActivityLog/ActivityLogServiceTest.php
tests/Unit/Plugins/ActivityLog/ClientIpSourceTest.php
```

Run the scoped suite with:

```bash
vendor/bin/phpunit tests/Unit/Plugins/ActivityLog
```

<!-- TODO: add coverage target -->

Manual check: visit `/admin/activity-log`, confirm filters, pages, and empty state. Do an admin create, edit, and delete, confirm rows appear. Turn off `track_admin_actions` in `Config/Config.php:7`, confirm auto rows stop but manual `log()` still writes. Check `/admin` card count. Confirm a user without `activity_log.view` cannot open the page.

## Coding standards

1. Keep `declare(strict_types=1);` as the first line in every class file (`Plugin.php:1`, `Services/ActivityLogService.php:1`, `Models/ActivityLog.php:1`, `Controllers/ActivityLogAdminController.php:1`). Reason: repo rule, PHP 8.2 only.
2. Keep class name, file name, and namespace aligned (`Plugin.php:9`, `Services/ActivityLogService.php:5`, `Models/ActivityLog.php:5`). Reason: PSR-4 autoloading breaks otherwise.
3. Keep model annotations current (`Models/ActivityLog.php:32`). Reason: PHPStan level 8 needs the property and method shapes.
4. Use bound parameter helpers (`eq`, `like`, `ge`, `le`) in `Models/ActivityLog.php:112`. Do not use raw `where()` with input. Reason: filters come from the query string.
5. Keep the `log()` column shape stable (`Services/ActivityLogService.php:59`). Reason: the view (`Views/admin/index.php:1`) reads those exact fields.
6. Follow strict MVC(S): controller reads input and calls the service (`Controllers/ActivityLogAdminController.php:15`), service holds logic (`Services/ActivityLogService.php:59`), model touches the DB (`Models/ActivityLog.php:56`). Reason: project rule, only models may query.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | User docs, what it does and where to click |
| [Config/Config.php](./Config/Config.php) | Defaults for route prefix, tracking flag, retention |
| [Services/ActivityLogService.php](./Services/ActivityLogService.php) | `log()` at line 59, `logFromRoute()` at line 90, `inferFromRoute()` at line 239 |
| [Plugin.php](./Plugin.php) | Service setup at line 30, admin route at line 43, dashboard card at line 48, listener at line 72 |
| [Models/ActivityLog.php](./Models/ActivityLog.php) | Filtered reads at line 78, counts at line 94, filter rules at line 112 |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a route to auto tracking | `inferFromRoute()` in `Services/ActivityLogService.php:239` |
| Change skip patterns | `Plugin.php:92` and `shouldSkipRoute()` in `Services/ActivityLogService.php:216` |
| Add a filter field | `applyFilters()` in `Models/ActivityLog.php:112`, `filtered()` in `Models/ActivityLog.php:78`, form in `Views/admin/index.php` |
| Change dashboard card | `Plugin.php:48` |
| Change retention default | `Config/Config.php:8` |
| Change permission | Seed in `Database/Seeds/Seed.php:9`, check in `Plugin.php:40` |
| Change table shape | Migration in `Database/Migrations/2026-09-02-000001_CreateActivityLogsTable.php:13` |

## PR / contribution checklist

- [ ] `composer lint` clean on touched files
- [ ] `composer phpstan` level 8 clean
- [ ] `composer psalm` clean
- [ ] `composer test` green, or scoped `vendor/bin/phpunit tests/Unit/Plugins/ActivityLog` green
- [ ] Auto tracking checked: admin POST logged, GET skipped, auth, assets, API, and self routes skipped, config flag works
- [ ] Manual `log()` fills user, IP, and user agent
- [ ] Filters and pages work, empty state renders
- [ ] Dashboard card shows correct 24h count
- [ ] User without `activity_log.view` cannot open the page
- [ ] README.md updated if user visible behavior changed

## Out of scope / non-goals

- Login and logout tracking (not implemented)
- Live updates in the list UI
- Visitor or front end tracking
- Location lookup beyond request IP
- Auto prune (retention_days is a setting only, no cleanup job yet)