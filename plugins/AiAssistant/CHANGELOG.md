# Changelog

All notable changes to the AI Assistant plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.15] - 2026-10-07

### Added
- 404 management over the API: list the tracked 404s, ignore or unignore one, delete one, and send a 404 path to a new URL.
- Grants `404s.read`, `404s.ignore`, `404s.delete`, and `404s.resolve`. Resolving a 404 also needs `redirects.create`.

## [0.3.14] - 2026-10-06

### Changed
- The page update endpoint no longer sends `allow_comments` when the request omits it.

## [0.3.13] - 2026-10-05

### Fixed
- The help guide and the fact-check error messages showed incorrect addresses.
- A page update switched comments off when the request omitted `allow_comments`.
- A new redirect was created disabled when the request did not set `enabled`.
- A tag or category failure was reported as a failed post write.

### Changed
- The key list loads every key's grants in one query.
- A service failure is logged against the calling key.

## [0.3.12] - 2026-09-30

### Fixed
- HTML sanitizer no longer strips safe tags.

## [0.3.11] - 2026-09-28

### Fixed
- The AI API rejects a past `publish_on` for a scheduled post.
- The AI Assistant cannot change a scheduled post time with only the schedule permission.

## [0.3.10] - 2026-09-27

### Changed
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.

## [0.3.9] - 2026-09-26

### Fixed
- An admin can clear a blocked key manually.
- `AiService` uses the tolerant service wrapper for navigation.
- `AiKeyGrant::replaceFor()` runs its DELETE and INSERT in a transaction.

### Changed
- Documentation cleanup.

## [0.3.8] - 2026-09-25

### Fixed
- Key generation no longer exposes the key in sessions through a flash message.
- `AiService::authenticate()` checks `isEnabled()` before `isBlocked()`.

## [0.3.7] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.3.6] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.3.5] - 2026-09-20

### Changed
- Store integration updated.

## [0.3.4] - 2026-09-19

### Changed
- Block registration and template resolution follow the shared block loading rules.

## [0.3.3] - 2026-09-17

### Changed
- Admin controller split out of the monolithic controller.
- Migrations consolidated to one file per table for the initial beta release.

## [0.3.2] - 2026-09-16

### Changed
- README and AGENTS.md standardized.

## [0.3.1] - 2026-09-13

### Fixed
- An update-only key could unpublish content.
- Response-size caps on AI fetches.

## [0.3.0] - 2026-09-10

### Added
- Stand-alone API controller; the AI Assistant and Marketplace both use it.

## [0.2.0] - 2026-09-06

### Added
- Fact Checking: external AI assistants verify claims in posts and pages under a versioned integrity prompt and file structured reports, with a report history, editor panels, and a placeable public block.
- `csrf.exempt` registration for the API routes.

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- AI Assistant plugin: API-key ingestion endpoints and per-key grants.
- README and AGENTS.md.
