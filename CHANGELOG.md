# Changelog

All notable changes to Pubvana will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

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
