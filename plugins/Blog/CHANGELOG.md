# Changelog

All notable changes to the Blog plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.8] - 2026-10-06

### Fixed
- Creating a post failed when a deleted post still held the same slug.

## [0.3.7] - 2026-10-05

### Fixed
- The category list showed no post counts.
- A category field posted as a single value instead of a list failed the save.
- Post content was stored unpurified when HTMLPurifier was unavailable.
- Category and tag saves cleared the existing links outside a transaction.
- A post update, delete, or revision restore reported success when nothing changed.
- Deleting a category left its children pointing at it.
- A preview link kept working after the post was published.
- A single post page loaded every category and tag.
- The comments host, navigation, and broken-links lists loaded every post column.
- `createRecord()` accepted arbitrary keys.
- The post list printed one link for every page.

## [0.3.6] - 2026-09-30

### Fixed
- HTML sanitizer no longer strips safe tags.

## [0.3.5] - 2026-09-28

### Added
- Cron runner for scheduled posts.

### Fixed
- Raw SQL moved out of the blog and comments services.
- Author lookups use the Profiles plugin instead of instantiating its model directly.
- `BlogPublicController::getAuthor()` no longer builds a raw FlightShield User.
- Blog registers Profiles concerns through the plugin registration.
- Feed generation loads categories, tags, and authors in one pass.
- Related posts load tag and category names in one pass.
- Scheduled publish dates are validated on store and update.

## [0.3.4] - 2026-09-27

### Changed
- `$app->get()` reads `.env` values and `settings()->get()` reads database values.
- `SITE_URL` is read from `.env` as `$app->get('siteUrl')`.

## [0.3.3] - 2026-09-26

### Fixed
- Author URLs no longer hardcode `/profile/`.
- The route prefix is a required constructor argument, so a missing value is a type error at construction.

### Changed
- Documentation cleanup.

## [0.3.2] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.3.1] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.3.0] - 2026-09-20

### Added
- Blog and Pages register their menus and homepage options with adext.
- Any plugin can offer itself as the homepage.
- Migration `2026-09-20-133834_MigrateHomepageTypeToken.php` changes an existing homepage setting from `pages` to `page`.

### Changed
- Blog follows MVC.
- Search ranking is in `SearchService`, with the score and the best possible score shown.

### Fixed
- Blog search returned nothing for any term: `LIKE` used `ESCAPE '\'`, which MySQL rejects. The escape character is `!`, and `escapeLikePattern()` handles `!`, `%`, and `_`.

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
- Taxonomy pagination queries per page instead of filtering the global post list in memory.
- LIKE wildcard injection in the blog service.
- The admin accepts only the defined post statuses.

## [0.1.5] - 2026-09-12

### Changed
- Public page template restructured.

## [0.1.4] - 2026-09-07

### Added
- Default page and blog post seed data.

### Fixed
- Theme override path.

## [0.1.3] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

### Fixed
- PHPStan Level 8 findings from the performance work.

## [0.1.2] - 2026-09-03

### Fixed
- Homepage docblock and closure.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Blog plugin: posts, categories, tags, and revisions.
- README and AGENTS.md.
