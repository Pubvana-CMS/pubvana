# Changelog

All notable changes to the Media plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.2.1] - 2026-10-06

### Fixed
- Flip Horizontal and Flip Vertical were swapped.
- The resize operation was listed as available but failed.
- Replacing a video poster removed the old poster before the new one was written.
- Deleting an item could remove a file outside the uploads directory.
- An edit operation with a non-string value raised a server error.
- A failed image derivative left the uploaded files on disk.
- An upload failure returned a server error instead of a JSON error.

## [0.2.0] - 2026-09-30

### Added
- Avatar uploads for admin and public profile editors.

## [0.1.11] - 2026-09-28

### Changed
- Documentation refers to blocks, not widgets.

## [0.1.10] - 2026-09-26

### Fixed
- The stored `mime_type` comes from the detected file type, not the browser-supplied value.
- Media URLs are full URLs.

### Changed
- Documentation cleanup.

## [0.1.9] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

## [0.1.8] - 2026-09-21

### Fixed
- Models return fresh instances from their finders.

## [0.1.7] - 2026-09-20

### Changed
- Store integration updated.

## [0.1.6] - 2026-09-17

### Changed
- Migrations consolidated to one file per table for the initial beta release.

## [0.1.5] - 2026-09-16

### Changed
- README and AGENTS.md standardized.
- Admin routes rely on the middleware the core already applies.
- EXIF data is stripped on upload.

## [0.1.4] - 2026-09-13

### Fixed
- `MediaService` handles `move_uploaded_file()` and `copy()` results.

## [0.1.3] - 2026-09-12

### Changed
- Public page template restructured.

### Fixed
- Media detail, picker, and editor views escape alt text, titles, and filenames instead of injecting them into `innerHTML`.

## [0.1.2] - 2026-09-06

### Changed
- Route prefix comes from the plugin registration instead of a hardcoded path.
- Database access moved to SimplePDO.

## [0.1.1] - 2026-09-02

### Fixed
- PHPStan Level 8 findings across the plugin.

## [0.1.0] - 2026-08-30

### Added
- Media plugin: media library with image editing.
- README and AGENTS.md.
