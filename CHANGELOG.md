# Changelog

All notable changes to Pubvana CMS, starting from the v2.0.0.

---

## v2.3.0 - 2026-04-07

### Breaking Changes

**Migrations schema consolidated.** The `slug` column has been removed from `plugins`, `themes`, and `widgets` tables. The addon's folder name is now the canonical identifier. The `slug` field in `*_info.json` files is no longer read or required.

**If upgrading from 2.2.x**, run these SQL statements manually:

```sql
-- Drop slug column from all addon tables
ALTER TABLE plugins DROP COLUMN slug;
ALTER TABLE themes DROP COLUMN slug;
ALTER TABLE widgets DROP COLUMN slug;

-- Add description column to themes (if not present)
ALTER TABLE themes ADD COLUMN description VARCHAR(255) NULL AFTER name;

-- Change widget description from TEXT to VARCHAR(255)
ALTER TABLE widgets MODIFY COLUMN description VARCHAR(255) NULL;

-- Add disabled columns to all addon tables (if not present)
ALTER TABLE plugins ADD COLUMN disabled TINYINT(1) NULL DEFAULT NULL AFTER is_active,
                    ADD COLUMN disabled_reason VARCHAR(255) NULL DEFAULT NULL AFTER disabled;
ALTER TABLE themes ADD COLUMN disabled TINYINT(1) NULL DEFAULT NULL AFTER is_active,
                   ADD COLUMN disabled_reason VARCHAR(255) NULL DEFAULT NULL AFTER disabled;
ALTER TABLE widgets ADD COLUMN disabled TINYINT(1) NULL DEFAULT NULL AFTER is_active,
                    ADD COLUMN disabled_reason VARCHAR(255) NULL DEFAULT NULL AFTER disabled;

-- Rename pv_approved to pv_safe (if not already renamed)
ALTER TABLE plugins CHANGE COLUMN pv_approved pv_safe TINYINT(1) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE themes CHANGE COLUMN pv_approved pv_safe TINYINT(1) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE widgets CHANGE COLUMN pv_approved pv_safe TINYINT(1) UNSIGNED NULL DEFAULT NULL;

-- Add marketplace_licenses.author (if not present)
ALTER TABLE marketplace_licenses ADD COLUMN author VARCHAR(100) NULL DEFAULT NULL AFTER product_name;
```


### Fixed
- Active plugin migrations now run on every admin page load, catching any automated updates pending migrations from plugin updates
- ExtensionUpdateService reads `update_url` from database instead of hardcoded constant
- ICU quoting fix for `widgetValidationFailed` and `widgetValidationFailedLink` language keys (single quotes were escaping placeholders)
- Scheduled post date now persists correctly when editing (was silently reverting to original date)
- Published posts always set `published_at` to current timestamp (prevents future-dated published posts)
- Scheduled posts require a future date (validation added)
- Redirects form now posts to correct route (`/admin/redirects/store`)
- Duplicate flash messages removed from 6 admin views (layout already handles them)
- Edit forms (posts, pages, categories, affiliates, users) now redirect back to the edit form instead of the list

### Added
- **MVC cleanup**: all `db_connect()` calls eliminated from Controllers, Services, Filters, Views, and Commands — replaced with proper Model methods
- `AddonModelTrait` shared by ThemeModel, WidgetModel, PluginModel for common addon operations
- `UserAdminModel` for TOTP and ownership operations on the users table
- `BackupModel` for database introspection operations
- `BrokenLinkService` extracted from command — powers new "Run Scan" button in admin
- `RedirectFilter` — 301/302 redirects now execute via a before-filter (previously stored but never processed)
- Ban/unban feature using Shield's Bannable trait — admin UI with optional reason, status badges, filter by banned
- Per-post `allow_comments` field — checkbox on post create/edit, merged with global setting at controller level
- Paywall enforced at controller level — `post.content` stripped before reaching theme when user lacks `posts.read.premium` permission
- "Comments are closed" message in all 9 themes when comments disabled (global or per-post)
- Paywall message in all 9 themes using `Blog.paywallTitle` / `Blog.paywallMessage`
- Plugin activation now auto-runs `Database/Seeds/` after migrations and before Installer
- ~167 hardcoded English strings in admin views replaced with lang() keys across all 6 locales
- Third-party addon licensing: license validation, product ID resolution, and 90-day revalidation route through third-party APIs based on addon author
- `free` field added to all `*_info.json` files — free third-party addons activate without a license check
- Standardized URL fields across all `*_info.json` files (`license_validate_url`, `license_check_url`, `store_url`, `items_url`, `item_url`, `download_url`, etc.)
- Per-addon license key entry in admin Themes, Plugins, and Widgets pages with inline validation
- Admin notifications for license and activation issues
- Plugins language file added to all 6 locales (en, es, fr, id, pt, sk)
- Activation chain enforces correct field combinations for bundled, paid, and free addons
- Register-and-disable pattern: addons with invalid or incomplete `*_info.json` files are now registered in the DB as disabled (`disabled=1`, `disabled_reason` set) instead of being silently skipped
- `disabled` and `disabled_reason` columns on plugins, themes, and widgets tables
- `description` column on themes table (was missing, now matches plugins/widgets)
- Disabled badge and reason shown in admin Plugins, Themes, and Widgets pages
- Disabled addons blocked from activation, boot, rendering, and migration execution
- Orphan cleanup added to theme and widget discovery (matching plugins)
- Required-field validation for themes and widgets aligned with plugins (`name`, `version`, `description`, `author`)
- Name/description change tracking added to theme and widget sync (matching plugins)
- Language keys for disabled addon reasons in all 6 locales

### Changed
- **Static pages route**: `/:slug` → `/pages/:slug` to eliminate catch-all route conflicts
- **Paywall**: checks `posts.read.premium` permission instead of just `loggedIn()`; content stripped at controller, not theme
- **Paywall lang keys**: `paywallTitle` now "Premium Content", `paywallMessage` updated; `paywallSignIn` and `paywallCreateAccount` removed
- **ExtensionUpdateService** renamed to **AddonUpdateService**
- **Folder is the identifier**: the `slug` field in `*_info.json` is no longer used. The addon's folder name serves as the unique identifier for vetting, marketplace product resolution, update checks, and renew links
- Widget `description` column changed from TEXT to VARCHAR(255) to match plugins
- Widget discovery now resolves product IDs inline and fires admin notifications on failure
- All addon models updated with new meta and URL `$allowedFields`
- DStore admin label "Slug" changed to "Folder (case-sensitive)" in all 6 locales
- 8 incremental addon-table migrations consolidated into the 3 original create-table migrations
- 4 post-column migrations (`share_on_publish`, `preview_token`, `is_premium`, `media_id`) consolidated into CreatePostsTable
- Plugin Installer.php role narrowed to filesystem-only; data seeding moves to `Database/Seeds/`
- DigitalStore and PvDocs plugins refactored: data seeding moved from Installer to Seeds

### Removed
- `slug` column from plugins, themes, and widgets tables
- `slug` field from all `*_info.json` files
- Slug change detection code and notifications
- `ExtensionUpdateService.php` (replaced by `AddonUpdateService.php`)
- `Blog.paywallSignIn` and `Blog.paywallCreateAccount` lang keys

---

## v2.2.9 - 2026-04-05

### Changed
- Store APIs switched from slug to numeric `store_product_id` for update, license, and download endpoints
- Terminology normalized from "extension" to "addon" across language files
- Plugin migrations now run automatically during addon updates via `downloadAndInstall()`
- `*Builder.md` docs excluded from release ZIP

### Added
- `store_product_id` column on themes, widgets, plugins, and marketplace_licenses tables
- `is_listed` column on `ds_products` (separates storefront visibility from product validity)
- "Core Bundled" badge in admin UI; update actions hidden for bundled addons
- Version bumps for all bundled widgets, themes, and plugins


---

## v2.2.8 - 2026-04-05

### Fixed
- Update system: removed broken background queue/exec paths, now synchronous-only
- Auto-update chain: addons update before core so `max_pubvana_version` is current before compatibility check

### Added
- Pre-flight update check before applying updates
- `bundled` flag on addons — bundled addons skip the compatibility gate
- Sync of widgets, themes, and plugins on updates page load and post-update
- Idle status timeout in update polling JS
- Version bumps: all widgets to 2.0.2, `max_pubvana_version` to 2.2.15 across all addons
- README install steps updated for `shield:user create` flow

---

## v2.2.7 - 2026-04-04

### Fixed
- Admin media page: detail panel fields hidden until an image is selected
- Admin media page: upload and detail cards now styled consistently
- Admin posts edit/create: heredoc replaced with ob_start — fixes `<?= ?>` tags not processing in inline scripts
- Media picker modal: infinite click loop on file browse fixed
- Featured image Browse/Remove buttons stay on one line with `d-flex`
- Restored missing `Config/Social.php` — fixes post save crash when social sharing checks settings

### Added
- Public user profile page at `/accounts/profile` — all logged-in users can edit username, email, password
- Author profile fields (bio, avatar, social links) for author+ roles on profile page
- Avatar upload for authors at `/accounts/avatar` with image validation and resize
- Security: username/email/password changes trigger email notification and force re-login
- Security: email changes deactivate account and require verification via Shield's EmailActivator
- `premiumsubscriber` auth group — `posts.read.premium` moved off base subscriber
- Auth icons in all theme navbars: profile (fa-user-pen), admin (fa-user-gear), login (fa-lock-open)
- `can_access_admin` variable available in all theme views
- `profile.tpl` view added to all 9 themes
- `media_id` column migration for posts table (featured image media library link)
- Profile lang keys added to all 6 languages (en, es, fr, id, pt, sk)

---

## v2.2.6 - 2026-04-04

### Fixed
- Theme and admin asset URLs now resolve correctly for subdirectory installs via new `theme_url()` and `admin_theme_url()` helpers
- `.htaccess` static file routing fixed for subdirectory installs using `%{CONTEXT_PREFIX}`
- `theme_url()` fallback no longer generates incorrect `assets/` path segment

### Added
- Default theme public assets gitkeep'd so CSS/JS available immediately after clone
- Welcome post and page seeder for fresh installs
- `admin_theme_url()` helper for admin asset paths
- `public/.htaccess` with CI4 routing fallback

### Removed
- `UserSeeder` removed — installer handles initial user creation

---

## v2.2.5 - 2026-04-04

### Fixed
- Social login and sharing credentials now saved to database via Settings library — previously wrote directly to `.env` file which fails on production servers
- Email settings form field names mismatched controller (`email_from_name` vs `from_name`) — full SMTP config now saves correctly
- `CreateSettingsTable` migration removed duplicate `context` column that conflicted with vendor Settings package on fresh installs
- `spark` file removed from `.gitignore` — was missing from all releases
- `testbad` test theme untracked from git — was shipping in releases
- Root `.htaccess` now routes static files from `public/` and handles root request for non-DocumentRoot deployments

### Changed
- All social/OAuth credentials stored in database settings, not `.env` — `writeEnvKey()` method removed entirely
- `Config\Social` reads from `setting()` instead of `env()`
- `SocialSharingService` reads Facebook page credentials from `Config\Social` instead of `env()`
- ThemeSeeder no longer seeds Flatly/Cyborg (premium marketplace themes)

### Security
- Removed `.env` file writing from admin panel — production `.env` files should never be writable by the application

---

## v2.2.4 - 2026-04-03

### Fixed
- Stale `Autoload.php` reference to non-existent `TemplateEngine/Nodes.php`
- Removed dropped columns (`license_key`, `license_last_checked`, `license_valid`) from `MarketplaceItemModel` allowedFields
- Added missing update-tracking fields to `ThemeModel`, `WidgetModel`, `PluginModel` allowedFields
- Removed unused `categories/create` GET route and controller method
- Deleted orphaned `admin/partials/update_banner.php` view

### Added
- `MarketplaceLicenseModel` and `PostRevisionModel` — replaced all raw `$db->table()` calls with proper model usage
- `zipball_url` now populated in `UpdateService::checkForUpdate()` result — CMS updater can now download releases

### Changed
- Removed `public/` from `ApplyService` copy directories — `index.php` changes documented as breaking changes instead

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
