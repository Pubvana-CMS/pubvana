# Changelog

All notable changes to the Social Links plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.10] - 2026-10-07

### Added
- Edit and update for a social link, with an Edit action on each row.

### Changed
- Admin routes require the `social.manage` permission.

### Fixed
- The Signal link rendered a blank mark.
- Moving the first or last link reported it as not found.
- `move()` treated an unknown direction as down.
- A custom icon class the stylesheets do not define was stored and rendered blank.

## [0.1.9] - 2026-09-26

### Fixed
- `sort_order` no longer collides with an existing row after a deletion.
- Social link URLs are full URLs.

### Changed
- Documentation cleanup.

## [0.1.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.7] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.1.6] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.5] - 2026-09-19

### Changed
- Block registration and template resolution follow the shared block loading rules.

## [0.1.4] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.3] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.2] - 2026-09-12

### Changed
- Documentation updates and housekeeping.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-09-01

### Added
- Social Links plugin: social profiles for the site.
- README and AGENTS.md.
