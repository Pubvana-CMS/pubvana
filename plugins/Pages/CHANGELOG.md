# Changelog

All notable changes to the Pages plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.6] - 2026-10-06

### Fixed
- Re-creating a page after deleting one failed on the unique slug index.
- A page status other than `draft` or `published` was stored as given.
- A partial update switched comments off when the request omitted `allow_comments`.
- Restore and delete reported success when the page or revision was missing.
- A search term containing `%` or `_` matched every page.
- The page list order could change between pages when two pages shared a timestamp.

## [0.3.5] - 2026-09-30

### Fixed
- HTML sanitizer no longer strips safe tags.

## [0.3.4] - 2026-09-27

### Changed
- `$app->get()` reads `.env` values and `settings()->get()` reads database values.

## [0.3.3] - 2026-09-26

### Changed
- Documentation cleanup.

### Fixed
- Tests for code that had moved.

## [0.3.2] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.3.1] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.3.0] - 2026-09-20

### Added
- Pages registers its menus and homepage options with adext.
- Any plugin can offer itself as the homepage.
- Migration `2026-09-20-133834_MigrateHomepageTypeToken.php` changes an existing homepage setting from `pages` to `page`.

### Changed
- Pages follows MVC.

### Fixed
- Page URLs and spelling.

## [0.2.2] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.2.1] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

### Fixed
- Pre-restore snapshot taken before the restore runs.

## [0.2.0] - 2026-09-14

### Added
- Author Card block.
- `normalizeExternalUrl()` helper: accepts a full URL or a bare value combined with a base.

### Changed
- Admin blocks can be enabled or disabled individually.

## [0.1.6] - 2026-09-13

### Fixed
- `update()` validates the title the same way `store()` does.

## [0.1.5] - 2026-09-12

### Changed
- Public page template restructured.

## [0.1.4] - 2026-09-07

### Added
- Default page seed data.

### Fixed
- Theme override path.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.2] - 2026-09-03

### Fixed
- Homepage docblock and closure.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Pages plugin: pages and page revisions.
- README and AGENTS.md.
