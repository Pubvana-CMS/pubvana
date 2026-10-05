# Changelog

All notable changes to the SEO plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.11] - 2026-09-28

### Fixed
- Author lookups use the Profiles plugin instead of instantiating its model directly.

## [0.1.10] - 2026-09-27

### Changed
- `$app->get()` reads `.env` values and `settings()->get()` reads database values.
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.

## [0.1.9] - 2026-09-26

### Fixed
- `countWithMetaTitle()` uses the count it queries.
- Stray docblock inside a method body.
- SEO URLs are full URLs.

### Changed
- Documentation cleanup.

## [0.1.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.7] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.6] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.5] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.
- The plugin no longer adds a dashboard card.

## [0.1.4] - 2026-09-13

### Fixed
- Canonical, Open Graph, and JSON-LD URLs are built from the configured site URL, not the request host.

## [0.1.3] - 2026-09-12

### Fixed
- JSON-LD output escapes `<`, `>`, `&`, `"`, and `'`, so a title cannot break out of the script block.

## [0.1.2] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- SEO plugin: meta tags, sitemaps, schema, and LLMs.txt.
- README and AGENTS.md.
