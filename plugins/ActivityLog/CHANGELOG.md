# Changelog

All notable changes to the Activity Log plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.8] - 2026-09-26

### Fixed
- Read a config key that was never registered.
- Raw PDO queries moved out of the service and into the model.

### Changed
- Documentation cleanup.

## [0.1.7] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.6] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.5] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.4] - 2026-09-16

### Changed
- README and AGENTS.md standardized.

## [0.1.3] - 2026-09-13

### Fixed
- Activity Log no longer trusts `X-Forwarded-For` or `X-Real-IP` for the client address.

## [0.1.2] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools > Reports.

## [0.1.1] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.0] - 2026-09-03

### Added
- Activity Log plugin: records admin activity, with a Tools > Reports > Activity Log screen.
- `pubvana.json` with the plugin name, description, and admin menu entry.

### Fixed
- Description in `pubvana.json`.
