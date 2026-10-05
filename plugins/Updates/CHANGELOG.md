# Changelog

All notable changes to the Updates plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.1] - 2026-10-04

### Changed
- The addon check no longer asks the Marketplace to refresh its catalog.

## [0.3.0] - 2026-09-29

### Changed
- Core updates are no longer reported through the trust service.

## [0.2.12] - 2026-09-28

### Fixed
- Error logging goes to the PHP error log.
- Runway commands that threw exceptions or errored.

## [0.2.11] - 2026-09-27

### Fixed
- `Support/helpers.php` moved to its proper place.

## [0.2.10] - 2026-09-26

### Fixed
- Dangling `docs/Cron.md` references.

### Changed
- Documentation cleanup.

## [0.2.9] - 2026-09-25

### Fixed
- The protected-path check covers nested protected files.
- Two updates cannot start at the same time.
- A crashed update no longer blocks the next update for 30 minutes.
- `trustClient()->coreItem()` passes `null` to `trustGate()`.

## [0.2.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.
- The cached check is dropped after an apply, and an addon's floor accepts a beta.
- The Updates page reports a release that an installed addon holds back.
- The crontab lines use `/usr/local/bin/php`.

## [0.2.7] - 2026-09-20

### Changed
- Store integration updated.

## [0.2.6] - 2026-09-18

### Fixed
- Trust and update checks call the live site instead of a localhost endpoint.

## [0.2.5] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.2.4] - 2026-09-16

### Changed
- README and AGENTS.md standardized.

## [0.2.3] - 2026-09-13

### Fixed
- Null middleware and controller.
- The manual breaking-change confirmation is enforced by the server, not only the view.

## [0.2.2] - 2026-09-12

### Changed
- Documentation updates and housekeeping.
- Marketplace and Updates integration with each other and the store.
- Public page template restructured.

### Fixed
- Validators check names instead of symlink entries, and `rmdir`/`is_dir` recursion no longer follows links.

## [0.2.1] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools.

## [0.2.0] - 2026-09-09

### Added
- Trust client integrated into Updates.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.2] - 2026-09-05

### Changed
- Documentation updates.

## [0.1.1] - 2026-09-03

### Changed
- Wording on the Updates screen.

## [0.1.0] - 2026-09-03

### Added
- Updates plugin: checks `releases.json` for new Pubvana versions, with manual and opt-in automatic updates.
- Updates page in the v2 layout: Update Settings card (Manual/Automatic toggle and crontab lines), status banners, a preflight table with Required/Optional badges, and a confirmation modal before applying.
- Granular update progress (preflight, backup, download, validate, extract, copy, migrate, cleanup) with live polling in the admin.
- Mandatory pre-update backup through the Backups plugin; a failed backup aborts the update.
- Safe-target capping: never jumps past a version blocked by an installed plugin or theme declaring min/max_pubvana_version.
- Per-version skip list.
- CLI commands: `runway updates:check`, `runway updates:apply`, `runway updates:auto-update`, with the auto-update chain registered as a daily task on the core cron system.
