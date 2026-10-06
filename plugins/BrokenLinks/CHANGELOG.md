# Changelog

All notable changes to the Broken Links plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.15] - 2026-10-06

### Fixed
- `broken-links:check` threw an error on every run.
- A broken link deleted from a post or page stayed in the report after a rescan.
- A renamed post or page kept its old title in the report.
- A link whose server refused the HEAD request was reported broken even when the link worked.
- Checks without a status code showed "Timeout" whatever the cause.
- The connect-time check that blocks private addresses did not run.

### Changed
- A scan from the admin screen stops at the PHP time limit and reports a partial run.

### Removed
- `broken-links:cron` CLI command.

## [0.1.14] - 2026-09-30

### Fixed
- Code samples are ignored, punctuation is trimmed off URLs, and each source links to its real editor.

## [0.1.13] - 2026-09-28

### Fixed
- Error logging goes to the PHP error log.
- Pubvana commands that threw exceptions or errored.

## [0.1.12] - 2026-09-27

### Changed
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.

## [0.1.11] - 2026-09-26

### Fixed
- `recheck()` no longer deletes a dismissed finding.
- `scan()` wraps throwing adext source callables in a try/catch.
- Dangling `docs/Cron.md` references.

### Changed
- Documentation cleanup.

## [0.1.10] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.9] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.1.8] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.7] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.6] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.1.5] - 2026-09-12

### Fixed
- Redirect hops are checked, private, loopback, and link-local addresses are refused, responses are size-capped, and a HEAD request falls back to GET.

## [0.1.4] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.2] - 2026-09-05

### Changed
- Documentation updates.

## [0.1.1] - 2026-09-03

### Added
- Daily scan task on the cron system.

## [0.1.0] - 2026-09-03

### Added
- Broken Links plugin: scans outbound links in published posts and pages, with a Tools > Broken Links admin to run a scan, recheck, and dismiss.
- Homepage docblock and closure.
