# Changelog

All notable changes to the Backups plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.12] - 2026-09-28

### Fixed
- Runway commands that threw exceptions or errored.

## [0.1.11] - 2026-09-26

### Changed
- Documentation cleanup.

## [0.1.10] - 2026-09-25

### Fixed
- `BackupsRestoreCommand.php` declares `commands`, not `Commands`.
- A stderr-only child process returns, and a dead background process no longer leaves its status at "started".

## [0.1.9] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.8] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.7] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.6] - 2026-09-13

### Fixed
- Restore splits SQL on statement boundaries, and the dump is written splitter-safe.
- The progress lock is advisory.
- `RestoreService` no longer follows symlinks.
- `validateZipContents()` requires ZipArchive.
- Zip-name collision.

## [0.1.5] - 2026-09-12

### Fixed
- `mysqldump` and `mysql` credentials no longer appear in the process list.

## [0.1.4] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.2] - 2026-09-04

### Fixed
- Composer autoload error.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-09-01

### Added
- Backups plugin: backup and restore for Pubvana.
- README and AGENTS.md.
