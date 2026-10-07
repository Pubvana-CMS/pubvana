# Changelog

All notable changes to the Site Health plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.12] - 2026-10-07

### Changed
- The health service reads `routePrepend` from `$app` instead of the plugin config.

### Fixed
- `required-settings` read `CMS.siteName` from the app store, where it never lives.
- `required-settings` compared the site name against `Pubvana`, not the shipped default `Pubvana v3`.
- The extensions check reported OPcache missing on a server that has it.
- The extensions check ignored the PDO driver for the configured database.
- A contributed check returning only an `id` warned in the view.
- A check in a category outside the four counted in the summary but did not render.

## [0.1.11] - 2026-09-27

### Changed
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.
- `SITE_NAME` and `ADMIN_EMAIL` removed from `.env`, HARDENING.md, and Softaculous.

## [0.1.10] - 2026-09-26

### Changed
- Documentation cleanup.

## [0.1.9] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

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
- Check descriptions state what each check does.

## [0.1.5] - 2026-09-13

### Fixed
- `runAll()` isolates each check, so one failure does not stop the rest.
- The database check no longer leaks the raw PDO message.

## [0.1.4] - 2026-09-10

### Changed
- Admin menu registration rewritten so the plugin registers its submenu under Tools.

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
- Site Health plugin: site checks and reporting.
- README and AGENTS.md.
