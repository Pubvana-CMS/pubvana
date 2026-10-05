# Changelog

All notable changes to the Comments plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.2.11] - 2026-09-30

### Fixed
- HTML sanitizer no longer strips safe tags.

## [0.2.10] - 2026-09-28

### Fixed
- Raw SQL moved out of the comments service.

## [0.2.9] - 2026-09-26

### Fixed
- `guest_website` is sanitized before storage.
- `store()` gates on every enabled comment type.
- A missing HTMLPurifier no longer falls back to raw comment HTML.
- `parent_id` is validated against the comment it replies to.
- The rate limiter checks and records in one step, before the insert.
- `createRecord()` accepts only the defined keys.
- `CommentService` uses the injected database and app instead of static calls.
- Comment flattening and the recent comments block load users in one pass.

### Changed
- Comment URLs are full URLs.
- Documentation cleanup.

## [0.2.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.
- Comment submission errors are shown as a flash message.

## [0.2.7] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.2.6] - 2026-09-20

### Changed
- Store integration updated.

## [0.2.5] - 2026-09-19

### Changed
- Block registration and template resolution follow the shared block loading rules.

## [0.2.4] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.2.3] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.

## [0.2.2] - 2026-09-13

### Fixed
- Open redirects: the return URL is host-validated, so a scheme-relative `//evil.com` is refused.
- The referrer is normalized once and feeds all four redirect exits.

## [0.2.1] - 2026-09-12

### Changed
- Documentation updates and housekeeping.
- Public page template restructured.

## [0.2.0] - 2026-09-07

### Added
- (h)reCaptcha extracted from Comments into its own service; Comments, Forms, and Login use it.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.2] - 2026-09-05

### Added
- Plugin asset CSS resolution with theme override, and `footer_scripts` output.

### Changed
- Region calls simplified to a single `{% region name %}` call.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Comments plugin: threaded comments with moderation.
- README and AGENTS.md.
