# Changelog

All notable changes to the Analytics plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

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
