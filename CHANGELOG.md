# Changelog

All notable changes to Pubvana will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [3.0.0-alpha.3] - 2026-09-03

### Added
- New Updates plugin: checks releases.json for new Pubvana versions, with manual and opt-in automatic updates
- Updates page in the v2 layout: Update Settings card (Manual/Automatic toggle + crontab lines), status banners, preflight table with Required/Optional badges, and a confirmation modal before applying
- Granular update progress (preflight, backup, download, validate, extract, copy, migrate, cleanup) with live polling in the admin
- Mandatory pre-update backup through the Backups plugin; a failed backup aborts the update
- Safe-target capping: never jumps past a version blocked by an installed plugin or theme declaring min/max_pubvana_version
- Per-version skip list, so a troublesome release can be passed over
- CLI commands: `runway updates:check`, `runway updates:apply`, `runway updates:auto-update`, with the auto-update chain registered as a daily task on the core cron system
- Release packaging workflow (`.github/workflows/release.yml`) that builds `release.zip` including vendor/, verifies the tag against pubvana.json, and keeps CHANGELOG.md in sync with releases.json
- New Cron system: `CronService` runs plugin-registered tasks on fixed intervals (every minute / 4 hours / daily) through a root `cron` script, with run locks against overlapping crontab hits, per-task error isolation, and cron.log
- New Broken Links plugin: scans outbound links in published posts and pages, with a Tools > Broken Links admin (run scan, recheck, dismiss); wired as a daily task on the cron system
- New Activity Log plugin: records admin activity
- PHPUnit CI workflow (`.github/workflows/test.yml`)

### Changed
- Database access moved to SimplePDO
- README restructured: badges, project logo, and the v2/v3 separation note

## [3.0.0-alpha.2] - 2026-09-02

### Added
- PHPStan static analysis to Level 8 across app and plugins, enforced via GitHub Actions
- Psalm taint analysis with Flight input stubs and baseline
- Initial PHPUnit test suite and configuration
- New Social Links plugin for user social profiles
- New Backups plugin: backup and restore for Pubvana
- Tabler build system: npm, sass, and build scripts

### Changed
- Reworked PHPStan ignore patterns (PROJECT_ROOT bootstrap, ActiveRecord/Collection and Flight magic exceptions)
- Cleaned up plugin and project README files for easier onboarding

### Fixed
- PHPStan Level 8 errors across app and plugins
- Various PHPStan/Psalm configuration and stub issues

## [3.0.0-alpha.1]

### Added
- FlightPHP-based architecture (replaces CodeIgniter)
- Plugin system with enable/disable from admin panel
- Vision template engine (no PHP execution in public templates)
- Region and block system with drag-and-drop placement
- Tabler admin UI with dark mode
- Jodit WYSIWYG editor for all content types
- Media library with image editing
- Built-in SEO (meta, sitemaps, schema, LLMs.txt)
- Comment system with moderation
- URL redirect manager with 404 tracking
- Form builder with submissions
- Server-side analytics with daily rollups
- Search across all content types
- Role-based access control (Shield)
- SMTP email with encrypted credentials
- Plugin developer docs (architecture, Vision, adext, runway)
