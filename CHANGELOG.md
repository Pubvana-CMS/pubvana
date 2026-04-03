# Changelog

All notable changes to Pubvana CMS, starting from the v2.0.0 rewrite.

---

## v2.2.3 - 2026-04-02

### Added
- **Marketplace & Licenses Redesign**: Store merged into Marketplace; new Licenses page; DigitalStore API integration; marketplace_licenses table; license enforcement (themes/plugins/widgets); non-dismissable warning banners; API-driven category tabs
- **Premium Core removed** — scheduling, analytics, affiliates, broken links, and activity log now free for all users
- AuthorBio and SocialLinks widgets included in core distribution
- Constants: `PUBVANA_STORE_URL`, `PUBVANA_API_BASE`, `PUBVANA_DSTORE_API`
- Web-based auto-updater with one-click update from admin panel
- Full backup system: site files + gzipped DB dump + metadata, 15-backup retention
- Rollback from any backup (backup current → restore selected → backup restored state)
- Pre-flight checks before update (PHP/CI/Shield versions, disk space, writable dirs)
- CHANGES.json for versioned update notices and breaking change warnings
- Progress reporting with real-time polling UI for backup, update, and rollback operations
- Queue integration via (codeigniter4/queue)[https://github.com/codeigniter4/queue] - backup, update, and rollback jobs
- Spark commands: `pubvana:backup`, `pubvana:rollback` (existing `pubvana:update` rewritten)
- GitHub Actions workflow for automated release builds with vendor/ included
- Update availability badge on all admin pages

### Changed
- BackupService rewritten: writable/backups/ storage, zips app/public/vendor/themes/widgets/plugins + DB
- PubvanaUpdate command rewritten to use modular services (DownloadService, ExtractService, ApplyService)
- Update check moved from Dashboard to BaseAdminController for site-wide visibility
- Updates controller expanded with apply, stream, and status endpoints
- Backup/Update/Rollback dispatch chain: queue → exec → synchronous fallback

### Fixed
- DB dump escaping: replaced `escapeLikeString()` with `escape()` for proper SQL value escaping
- PSR4 compliance: split monolithic TemplateEngine/Nodes.php into individual class files
- Missing language tag references in Admin translations
- CI4 CLI option parsing: use space-separated `--option value` instead of `--option=value`

### Security
- Admin notification inserted when new version detected
- Protected config files (.env, App.php, Database.php) preserved during update and rollback
- Zip extraction validates all entries for path traversal before extracting
- Backup filenames validated with strict regex to prevent path traversal

---

## v2.2.2 - 2026-03-28

### Added
- Theme-aware pagination via `cls_pager_*` keys in theme_info.json css_class_mapping - themes declare CSS classes in JSON, injected safely into templates with framework-agnostic `pv-*` defaults
- Icon pack abstraction: themes declare `icon_pack` and `icon_pack_ver` in theme_info.json; IconService auto-converts icon classes on theme switch
- ThemeService registered as shared service via `Config\Services::theme()`

### Fixed
- MarketplaceService folder regex and info file format
- TagModel limit parameter
- CSRF fields exposed to theme templates via ThemeService data bag; comment form uses `{! csrf_field !}` tag

### Security
- Theme and widget sandboxing: `.tpl` template engine replaces raw PHP execution in themes/widgets
- No PHP files permitted in theme or widget directories

---

## v2.2.1 - 2026-03-22

### Added
- Locale-aware routing with `site_url()` override for locale-prefixed URLs
- `site_url` template tag for themes
- `page_title` top-level variable for browser tab titles
- `author_url` and `support_url` fields in theme manifests

### Changed
- All 8 themes updated: `base_url` → `site_url` for route paths
- Theme versions bumped to 2.0.0
- SeoService: separate page title from meta title

### Fixed
- Locale detection sync in pre_system event
- Post editor: sync Summernote/SimpleMDE content on form submit
- Editor toggle: BS4-compatible buttons replacing BS5 btn-check
- Default to markdown editor on create page

---

## v2.2.0 - 2026-03-16

### Added
- Custom `.tpl` template engine (Lexer, Parser, Interpreter) with:
  - Whitelisted filters only (`upper`, `lower`, `date`, `default`, etc.) - no arbitrary PHP function calls
  - Whitelisted tag functions (`csrf_field`, `lang`, `site_url`, etc.) - controlled vocabulary via TagRegistry
  - Layout inheritance (`{% extends %}`, `{% block %}`)
  - Includes, for loops, if/else, raw output (`{! !}`)
- Asset pipeline: `publishAssets()` copies theme assets to public/ - replaces symlinks
- Widget system redesign:
  - JSON manifests (`widget_info.json`) define options, data providers, and output format
  - `.tpl` templates for all widget output - no PHP execution
  - WidgetDataService with whitelisted Model.method provider registry - widgets can only access data through approved providers
  - Auto-generated admin forms from JSON option definitions
  - `cls_*` pattern: themes declare CSS classes in `css_class_mapping` JSON, injected into widget templates
  - BaseWidget PHP class deleted - widgets are pure data + templates
- All 12 built-in widgets converted to PascalCase folders + JSON + `.tpl` format
- All themes converted to `.tpl` views with layout inheritance
- `validateTheme()` scans theme directories for PHP tags, shows warnings in admin
- Admin notifications system with AJAX dismiss
- Font Awesome 6.7.2 upgrade (from 5.15.3)
- Atom 1.0 feed at `/atom`
- Multi-language support: 6 languages (en, fr, es, id, pt, sk), admin language management
- LanguageSwitcher library with locale-prefixed URL routing
- Honeypot spam protection on comment and contact form routes
- `lang()` wired into all admin and theme views
- hreflang SEO tags
- PluginBuilder.md documentation

### Changed
- ThemeService rewrite: owns data bag, page caching, theme validation
- WidgetService rewrite: JSON-driven render flow, auto-generated admin forms
- Controllers thinned - pass only page-specific data, ThemeService owns shared context
- Admin social links page redesigned
- Admin views refactored to use utility classes instead of inline styles

### Fixed
- Widget and theme rendering issues found during testing
- Theme card button layout
- Dashboard notification loading with try/catch fallback
- Languages table: seed only languages with translation files

### Security
- Honeypot spam protection re-enabled; field name changed to `website_url`

---

## v2.1.2 - 2026-03-08

### Changed
- Composer update: CI4 v4.7.0 → v4.7.1, Shield v1.2.0 → v1.3.0

---

## v2.1.1 - 2026-03-06

### Added
- User documentation site at pubvana.net/docs
- CLI Commands section in README

---

## v2.1.0 - 2026-03-04

### Added
- **Premium Core** (9 features behind PremiumService licence gate):
  - Activity / Audit Log
  - Backup & Export (DB + uploads ZIP)
  - Affiliate Link Manager (`/go/` short-links + click tracking)
  - Broken Link Checker (`php spark links:check` + admin UI)
  - Scheduled Post Queue (FullCalendar view + `php spark posts:publish`)
  - Advanced SEO (news sitemap, OG image generation)
  - Content Analytics (page views, top posts, referrers, Chart.js)
  - Two-Factor Authentication (TOTP via spomky-labs/otphp)
  - Membership / Paywalled Posts (is_premium flag + login-wall)
- PremiumService: licence validation with 90-day periodic re-validation
- TotpFilter: 2FA challenge before admin access
- Premium licence tab in Settings
- Periodic licence re-validation for premium marketplace items

---

## v2.0.6 - 2026-02-28

### Added
- Core update notification system (GitHub API check + admin badge)
- WebP auto-convert on image upload via GD
- Schema.org markup: Article, BreadcrumbList, Author JSON-LD
- Content preview links with shareable preview tokens
- Bulk post actions: publish / unpublish / delete from posts index
- Plugin system: PluginInterface, PluginManager, plugins/ directory

---

## v2.0.5 - 2026-02-24

### Added
- Maintenance Mode toggle in General Settings

### Fixed
- Widget areas, social links, author card, uploads symlink issues

---

## v2.0.4 - 2026-02-22

### Added
- hCaptcha spam protection on comment and contact forms
- PHPUnit test suite with GitHub Actions CI

### Changed
- Login required to comment; rate limited to 5 per user per 10 minutes

### Fixed
- User management: fix update(), add create user functionality

### Security
- Permissions overhaul: explicit `can()` checks on 14 admin controllers
- Revisions: authors restricted to own post revisions only
- Delete remember tokens on user ban to prevent cookie re-auth
- Site owner protected from modification/deletion by non-owners

---

## v2.0.3 - 2026-02-20

### Security
- 11 vulnerabilities fixed (see Security Fixes Log in README for details)

---

## v2.0.2 - 2026-02-18

### Added
- Table of Contents widget (JS-driven, configurable depth)
- Related Posts widget (scored by shared categories and tags)
- Post Revision History (auto-save on update/publish, pruned to 20, restore UI)

---

## v2.0.1 - 2026-02-16

### Added
- Author profiles with admin UI and author-card partial
- Social OAuth login (Google, Facebook)
- Social auto-share on publish (Twitter, Facebook)
- Marketplace API with live HTTP fetch, cache, and mock fallback
- WordPress importer (admin UI + `php spark wp:import` CLI)

---

## v2.0.0 - 2026-02-14

Full rewrite of Pubvana v1.x on CodeIgniter 4 with Shield authentication.
