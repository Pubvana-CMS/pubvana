# Changelog

All notable changes to the Forms plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.2.11] - 2026-10-06

### Fixed
- Creating a form failed when a deleted form still held the same slug.

## [0.2.10] - 2026-10-06

### Fixed
- Typing into a form field lost focus after the first character.
- The Required switch could not be turned off.
- A form name produced a slug with the spaces removed and the letter s replaced by a hyphen.
- The options box joined options with a literal `\n` instead of a new line.
- A hidden field showed its label.
- A required hidden field blocked the form.
- A required checkbox group marked every box required.
- The field Width setting had no effect.
- Two submissions sent at the same time could both be stored.
- A field with a blank name was dropped without an error.
- Two fields could share the same name.

### Changed
- The builder's shortcode examples are titled Shortcodes.
- README and AGENTS.md cover the hidden field and the width setting.

## [0.2.9] - 2026-09-26

### Fixed
- The honeypot field is no longer named "website", so a legitimate form field works.
- Form status is validated against draft and published on create and update.
- A form field named after the captcha post field no longer collides with it.

### Changed
- Documentation cleanup.

## [0.2.8] - 2026-09-24

### Changed
- Theme and update version requirements read from the store.

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
- Spam gates apply to every form.

## [0.2.1] - 2026-09-12

### Changed
- Documentation updates and housekeeping.

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
- Forms plugin: form builder with submissions.
- README and AGENTS.md.
