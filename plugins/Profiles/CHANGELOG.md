# Changelog

All notable changes to the Profiles plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.1] - 2026-10-07

### Added
- Job title and employer fields on the admin profile form.

### Fixed
- The plugin did not load because `pubvana.json` had a trailing comma.
- The Author Card carried the account username.
- Removing an avatar left its file on disk.
- Uploading an avatar deleted the previous file before the form was saved.
- A rejected save named the website field for any of the four links.
- A profile field holding "0" was stored as empty.
- The profile pages raised an error when the Media plugin was disabled.

## [0.3.0] - 2026-09-30

### Added
- Avatar uploads for admin and public profile editors.

### Fixed
- `csrf.exempt` accepts a pattern.

## [0.2.8] - 2026-09-28

### Fixed
- Profiles provides author info to other plugins instead of leaking the username.
- Blog registers Profiles concerns through the plugin registration.

## [0.2.7] - 2026-09-26

### Fixed
- The Author Card no longer hardcodes `/profile/`.
- Profile URLs are full URLs.
- The admin show screen has a not-found guard.

### Changed
- Documentation cleanup.

## [0.2.6] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.2.5] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.2.4] - 2026-09-20

### Changed
- Store integration updated.

## [0.2.3] - 2026-09-19

### Changed
- Block registration and template resolution follow the shared block loading rules.

## [0.2.2] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.2.1] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.
- The plugin no longer adds a dashboard card.

## [0.2.0] - 2026-09-14

### Added
- Author Card block.
- `normalizeExternalUrl()` helper: accepts a full URL or a bare value combined with a base.

### Changed
- Admin blocks can be enabled or disabled individually.

## [0.1.4] - 2026-09-13

### Fixed
- Open redirects: the posted return URL is host-validated, with the admin base as the fallback.
- A `javascript:` URL can no longer be stored.

## [0.1.3] - 2026-09-12

### Changed
- Public page template restructured.

## [0.1.2] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

### Fixed
- PHPStan Level 8 findings from the performance work.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Profiles plugin: public user profiles.
- README and AGENTS.md.
