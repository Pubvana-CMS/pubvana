# Changelog

All notable changes to Pubvana will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [3.0.0-beta.2] - 2026-09-18

### Added
- Release zip now ships `writable/` and `cron`, so a GitHub release installs out of the box
- `.gitkeep` whitelists for `writable/store`, `writable/tmp`, `writable/trust`, and `public/uploads`
- INSTALL.md: File & Folder Permissions section documenting the chgrp/chmod scheme for shared hosts

### Changed
- Environment handling made production-safe:
  - `flight.debug` derives from the environment; `APP_DEBUG` is no longer read
  - `FORCE_HTTPS` is independent of the environment and only applies when set explicitly
  - Boot no longer fatals in `--no-dev` installs: Tracy references are guarded, so a release build without the dev packages starts cleanly
- Trust client always reports to `https://pubvanacms.com`; the development localhost endpoint is removed
- Updates dashboard and Site Health messages point at Tools > Maintenance > Updates

## [3.0.0-beta.1] - 2026-09-17

### Added
- PHPUnit tests almost entirely throughout
- Admin block toggles: individual blocks can be enabled or disabled from the admin panel
- Author Card block
- `normalizeExternalUrl()` helper: accepts a full URL or a bare value combined with a base
- Optional title on the HTML block
- OWASP-based hardening guidance for site operators

### Changed
- Migrations renamed to the `YYYY-MM-DD-HHMMSS_` filename convention across core and all plugins
- Migrations library bumped to `enlivenapp/migrations ^0.4`; `MigrationSetup` no longer takes a database handle, callers pass the project root and an explicit `path_mode`
- README and plugin AGENTS.md files standardized; v3 marked "In Beta" and the alpha notice removed
- Website domain updated to pubvanacms.com
- Marketplace migration files combined
- Docker files removed from the repository
- Monolithic admin controller untangled
- INSTALL.md: removed the redundant `shield:user password` step, dropped the stale `runway routes` row, and added a caution about guarding terminal access

### Fixed
- Security hardening batch:
  - Open redirects: `sameSite()` host validation applied to Forms, Comments, and Profiles return-url handling
  - Host-header injection into canonical/OG/JSON-LD URLs
  - LIKE wildcard injection in the blog service
  - Stored XSS: `javascript:` URLs in Profiles, media admin innerHTML injection, and raw `application/ld+json` output
  - Symlink traversal in archive and snapshot validators
  - cURL hardening: redirect hop checking, scheme enforcement, private/loopback/link-local IP filtering, and response size caps
  - `mysqldump`/`mysql` credentials no longer visible in process listings
  - Admin-group users could escalate to superadmin (blocked)
  - Plugin admin routes reachable without authentication
  - AI update-only key could unpublish content
  - ActivityLog no longer trusts `X-Forwarded-For`/`X-Real-IP`
  - CSP no longer relies on `'unsafe-inline'`/`'unsafe-eval'` and unpkg scripts
- Backup restore: pre-restore snapshot taken before the restore runs; SQL dump and restore splitting made splitter-safe
- Blog taxonomy pagination queries per page instead of filtering the global post list in memory
- Generic error message rendered in production instead of leaking internals
- Theme override resolution (again)
- Misc PHPStan/Psalm findings: Pages update validation parity, media upload checks, Analytics deprecations, TrustClient default URL, zip-name collision, advisory-locked backup restore

## [3.0.0-alpha.4] - 2026-09-12

### Added
- AI Assistant: Fact Checking. External AI assistants verify claims in posts and pages under a versioned integrity prompt and file structured reports (per-claim verdicts with cited sources, facts separated from opinion); enabled by an admin under Tools > AI Assistant, with a report history, editor panels, and a placeable public block
- Trust client integrated into core, plugins, themes, and Updates
- Stand-alone API controller; AI Assistant and Marketplace wired to it
- Admin menu system rewritten so plugins can register submenus, not just top-level menus
- (h)reCaptcha extracted from Comments into its own service; Comments, Forms, and Login now use it
- Flash messages on the public side, surfaced in the default theme
- Plugin asset CSS resolution with theme override, plus `footer_scripts` output
- Extended Shield login hardening and theme override views
- Default page and blog post seed data
- Marketplace and Updates integration with each other and the store

### Changed
- Performance pass: homepage load from ~2500ms to ~450ms (DB call stacking and asset-serving fat trimmed)
- Public page template restructure
- Theme service rework: activation/switching fixed, override paths fixed, region tags simplified to a single `{% region name %}` call
- Plugins no longer auto-enable on install; core plugins ship enabled
- Repository trimmed to Pubvana v3 code and releasables only (private plugins moved to their own repo), feature freeze for the initial release
- Composer and documentation updates throughout

### Fixed
- Theme activation/switching (all themes were being activated)
- Login redirect bug, especially with 2FA enabled
- Theme override path bug
- PHPStan Level 8 errors from the performance work
- Marketplace build and `csrf.exempt` handling for Marketplace and AI Assistant

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
