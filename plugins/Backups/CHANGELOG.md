# Changelog

All notable changes to the Backups plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.13] - 2026-10-05

### Changed
- A full backup now includes the `plugins/` directory.
- A restore removes files the snapshot does not contain, and keeps the protected configs.

### Fixed
- A restore showed the previous backup's completed progress instead of its own.
- A backup or restore that died in the background left the progress screen on its last step.
- A failed file copy during a restore was logged and skipped.
- A restore that could not write the site files failed partway through the copy.
- A restore that was killed left its extraction directory behind.
- A backup whose zip could not be finalized was reported as created.
- Retention could delete the snapshot a restore was reading.
- A restore of an archive with no `database.sql` restored files only and reported success.
- A mysql client failure mid-restore ran the whole dump again through PDO.
- Releasing the operation lock unlinked the lock file, which could let two operations run at once.
- The restore and delete buttons stayed active while an operation was running.
- The pure-PHP dump included views in the table list.
- The pure-PHP dump wrote one INSERT per table, which could fail on a large table.
- The backup download built a Content-Length header from a failed `filesize()` call.

## [0.1.12] - 2026-09-28

### Fixed
- Pubvana commands that threw exceptions or errored.

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
