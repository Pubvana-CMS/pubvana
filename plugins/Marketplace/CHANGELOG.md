# Changelog

All notable changes to the Marketplace plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.1] - 2026-10-05

### Added
- A Checkout button beside Purchases and Disconnect.

### Changed
- Search and its controls sit above the catalog tabs.

### Fixed
- Catalog cards printed a block of PHP source as text.
- Checkout opens the store's `/store/checkout`.

### Removed
- The free and paid catalog filter.

## [0.3.0] - 2026-10-04

### Added
- Catalog tabs for plugins, themes, and sale items, with search, a free/paid filter, and paging.
- Separate sign-in and create-account forms on the connect screen.
- A package the trust service marks malicious is refused before it is copied into place.

### Changed
- The catalog is read from the store on each view.
- Update checks page through the whole catalog.
- The store's own reason is shown when it cannot serve the catalog, such as maintenance or throttling.

### Removed
- Catalog caching.

## [0.2.11] - 2026-10-02

### Fixed
- Free downloads install.
- Store failures show the store's message.

## [0.2.10] - 2026-09-27

### Changed
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.

## [0.2.9] - 2026-09-26

### Fixed
- Dangling `docs/Cron.md` references.

### Changed
- Documentation cleanup.

## [0.2.8] - 2026-09-25

### Fixed
- The site domain comes from the request.
- `Verify()` reports a store failure instead of a success message.
- Free-tier and no-license items show as free, with the download action.
- The package staging directory is emptied before reuse.

## [0.2.7] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.2.6] - 2026-09-20

### Changed
- Store integration updated.

## [0.2.5] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.2.4] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Website domain updated to pubvanacms.com.
- Marketplace migration files combined.

## [0.2.3] - 2026-09-13

### Fixed
- Response-size caps on store fetches.
- `MarketplaceInstall::allLicensed()`.

## [0.2.2] - 2026-09-12

### Fixed
- Validators check names instead of symlink entries, and `rmdir`/`is_dir` recursion no longer follows links.
- Redirect hops are re-checked, the scheme is enforced, and the account token is no longer sent as a `?token=` query parameter.

## [0.2.1] - 2026-09-12

### Changed
- Documentation updates and housekeeping.
- Marketplace and Updates integration with each other and the store.

## [0.2.0] - 2026-09-10

### Added
- Stand-alone API controller; the AI Assistant and Marketplace both use it.

## [0.1.1] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.0] - 2026-09-06

### Added
- Marketplace plugin: browse the Pubvana Digital Store, purchase, and install themes and plugins.
- `csrf.exempt` registration for the API routes.

### Fixed
- Theme switching bug.
