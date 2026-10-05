# Changelog

All notable changes to the Search plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.2.2] - 2026-09-26

### Fixed
- A future `published_at` no longer yields a negative age.

### Changed
- Documentation cleanup.

## [0.2.1] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.2.0] - 2026-09-20

### Added
- Search ranking is in `SearchService`, with the score and the best possible score shown.

### Changed
- Store integration updated.

## [0.1.4] - 2026-09-19

### Changed
- Block registration and template resolution follow the shared block loading rules.

## [0.1.3] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.2] - 2026-09-12

### Changed
- Public page template restructured.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Search plugin: search across all content types.
- README and AGENTS.md.
