# Changelog

All notable changes to the Redirects plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.0] - 2026-10-07

### Changed
- Wildcard rules are matched most specific first, so the longest literal prefix before the `*` wins.
- `$1` in a wildcard target is optional, and the captured text is dropped when it is absent.
- Both admin lists page through the shared pagination service.
- Target suggestions build page URLs from the Pages route prefix.

### Fixed
- A second redirect on an existing source path raised a server error instead of showing a form message.
- Deleting a redirect left the 404 entries it resolved in the Resolved list.
- Ignoring an entry left it in the Resolved list as well.
- A source path holding `*` anywhere but the end matched nothing.
- A target placeholder other than `$1` was stored and sent to visitors.

## [0.2.0] - 2026-09-30

### Added
- Redirects pages are paginated.
- Wildcard `*` support for redirect targets.

### Fixed
- A target placeholder was accepted with no wildcard source path.

## [0.1.9] - 2026-09-26

### Changed
- Documentation cleanup.

## [0.1.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.7] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.1.6] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.5] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.4] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.3] - 2026-09-13

### Fixed
- `target_url` accepts only the allowed schemes.

## [0.1.2] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Redirects plugin: URL redirect manager with 404 tracking.
- README and AGENTS.md.
