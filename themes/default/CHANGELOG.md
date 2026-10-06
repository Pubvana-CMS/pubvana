# Changelog

All notable changes to the Default theme will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.4.28] - 2026-10-06

### Fixed
- The comment thread template used arithmetic in a class name, which the template engine rejects.
- Comment threads did not render.

## [1.4.27] - 2026-10-05

### Fixed
- The page list had no marker where page numbers were skipped.

## [1.4.26] - 2026-10-04

### Added
- Theme overrides for every plugin block: Blog Archive and Related Posts, Comments Recent Comments, Forms, Profiles Author Card, Search, Social Links, and AI Assistant Fact Check Summary.
- Theme override for the Comments public thread at `pubvana/comments/comments.tpl`.

### Removed
- `partials/comments.tpl`, which nothing included. The Comments plugin resolves its thread through `pubvana/comments/comments.tpl`.

## [1.4.25] - 2026-09-29

### Changed
- `after-content` moved out of the page templates and into the layout.
- Comments partial restored under `partials/`.

## [1.4.24] - 2026-09-28

### Fixed
- Author lookups use the Profiles plugin instead of instantiating its model directly.
- Profile pages no longer leaks the username.

## [1.4.23] - 2026-09-27

### Added
- Force password reset is enforced sitewide, and the reset page offers "Sign out instead".

## [1.4.22] - 2026-09-26

### Changed
- The comments partial moved out of the theme.

## [1.4.21] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [1.4.20] - 2026-09-21

### Fixed
- The sidebar shows only where the Show Sidebar On option says it should.

## [1.4.19] - 2026-09-20

### Added
- Search results show the score and the best possible score.

### Changed
- Store integration updated.

## [1.4.18] - 2026-09-19

### Added
- 404 pages render in the active theme.
- Text block template.

### Changed
- Block templates load from `public/blocks/`, in this order: app override, theme override, plugin default.

## [1.4.17] - 2026-09-14

### Added
- Author Card block template.

## [1.4.16] - 2026-09-13

### Fixed
- Theme override path.
- A `javascript:` URL can no longer be stored through the profile editor.

### Changed
- Block templates moved under their plugin's `public/blocks/` path.

## [1.4.15] - 2026-09-12

### Changed
- Public page templates restructured under `pubvana/`, one folder per plugin.

## [1.4.14] - 2026-09-09

### Added
- Trust client integrated into themes.

## [1.4.13] - 2026-09-07

### Added
- Flash messages on the public side, with an alerts partial.
- Shield login and theme override views.
- Default page and blog post seed data.

### Fixed
- Theme override path.

## [1.4.12] - 2026-09-05

### Added
- Plugin asset CSS resolution with theme override, and `footer_scripts` output.
- Region calls simplified to a single `{% region name %}` call.

### Changed
- The theme serves as the template example for theme builders, with comments throughout.

## [1.4.12] - 2026-08-30

### Added
- Port from Pubvana v2: Bootstrap 5 on the Bootswatch Flatly palette, with a navbar, optional hero, breadcrumbs, a sidebar region, a multi-column footer, and full-page templates for every public content type.


