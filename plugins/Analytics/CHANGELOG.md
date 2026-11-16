# Changelog

All notable changes to the Analytics plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.15] - 2026-11-14

### Added
- All Content and All Referrers pages, opened by View all on the report cards,
  100 rows per page.
- Robot hits are stored with `is_bot` set, charted as a Robots line and left out
  of every visitor total. The migration adds the column and the
  `analytics_bots_daily` table.
- The Total Views card shows the change against the period of equal length
  before the selected range.
- A view with no referrer shows as Direct in the referrer list.

### Changed
- Database work moved from `AnalyticsService` into `Models/`: `PageView`,
  `ViewsDaily`, `ReferrersDaily`, and `BotsDaily`.

### Removed
- The page tracking switch, the `Analytics.tracking_enabled` setting, and the
  `POST /admin/analytics/tracking` route. Tracking runs while the plugin is
  enabled.

### Fixed
- Top referrers no longer lists the site itself. A click on a link inside the
  site sends the site as the referrer, and that is now recorded with no
  referrer.
- A failed rollup no longer counts the same views twice on the next run. The
  merges and the deletes commit together.

## [0.1.14] - 2026-11-08

### Added
- The 1m cron task now counts cache hits. The read path writes a line per hit
  to a log file, and the cron task applies the same bot and path checks as a
  normal request.

## [0.1.13] - 2026-11-07

### Fixed
- Admin routes now require `analytics.view`.
- Viewers without `analytics.view` no longer see the Analytics menu item.

## [0.1.12] - 2026-10-05

### Fixed
- The daily rollup failed with a SQL syntax error on MySQL 8.0.19 and later.
- A half-finished rollup could count the same views twice.
- A failed rollup left no record.
- A settings or request failure during page tracking could break the page.

## [0.1.11] - 2026-09-26

### Changed
- Documentation cleanup.

## [0.1.10] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.9] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.1.8] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.7] - 2026-09-17

### Changed
- Admin controller split out of the monolithic controller.
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.6] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.5] - 2026-09-13

### Fixed
- Deprecation warnings in the plugin.

## [0.1.4] - 2026-09-12

### Fixed
- JSON-LD output escapes `<`, `>`, `&`, `"`, and `'`, so a title cannot break out of the script block.

## [0.1.3] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools > Reports.

## [0.1.2] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Analytics plugin: page views, referrers, and per-section trends.
- README and AGENTS.md.
