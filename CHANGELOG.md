# Changelog

Notable changes to Pubvana will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).


## [3.0.0-beta.8] - 2026-10-05

### Added
- A Checkout button on the Marketplace catalog screen, beside Purchases and Disconnect.

### Changed
- Marketplace search and its controls sit above the catalog tabs.
- Softaculous package size and download example updated.

### Fixed
- Marketplace catalog cards printed a block of PHP source as text.
- Marketplace checkout opens the store's `/store/checkout`.

### Removed
- The Marketplace free and paid catalog filter.


## [3.0.0-beta.7] - 2026-10-04

### Added
- Marketplace catalog tabs for plugins, themes, and sale items, with search, a free/paid filter, and paging
- Separate sign-in and create-account forms on the Marketplace connect screen
- Trust standing of the target release on the Updates screen
- A confirmation step before applying a release the trust service has not evaluated

### Changed
- BrokenLinks ignores code samples, trims punctuation off URLs, and links each source to its real editor.
- HTML sanitizer stripping safe tags from anchors, with a shared purifier config used by Themes, Comments, Blog, AI Assistant, and Pages
- Pages sanitizes content on store and update
- Free downloads wouldn't install in Marketplace
- Marketplace reads its catalog from the store on each view
- Marketplace update checks page through the whole catalog
- Marketplace shows the store's reason when it cannot serve the catalog, such as maintenance or throttling
- A malicious release cannot be applied
- An automatic update skips a release the trust service has not evaluated
- A trust service outage does not block an update
- Updated flight-shield to 0.6.0, flight-sessions to 0.1.4, migrations to 0.4.1, and PHPStan to 2.2.16
- all plugins/themes README and CHANGELOG updated.

### Removed
- Marketplace catalog caching
- started pulling many of the "I've noticed..." messages. things have settled down a lot and we're getting pretty close to a decent thing to release


## [3.0.0-beta.6] - 2026-09-30

### Changed
- moved core updates out of Trust reporting
- after-content in all the Default theme files moved to layout where it should be.
- Default theme + to v 1.4.13 
- Paginate Redirects pages
- wildcard `*` support for Redirects
- CSP blocking hCaptcha/rCaptcha
- CSRF didn't accept a pattern
- Avatar uploads for admin and profile editors



## [3.0.0-beta.5] - 2026-09-28

### Added
- Email on|off toggle in Settings > Email. 
- Cron runner for Scheduled posts.

### Fixed|Changed
- docs clean up
- Removed confusion around `CMS.siteUrl`, SITE_URL is from .env only, read as `$app->get('siteUrl')`.
- `settings()->get()` and `$app->get()` conflicted with possibly different values. `$app` is now `.env` values, `settings()` is soley database values.
- SITE_NAME and ADMIN_EMAIL removed from .env, HARDENING.md, and Softaculous.
- Login toggles (registration, magic link, remember me, email 2FA, email activation) default to off.
- Force Password reset enforced sitewide and reset page shows "Sign out instead" when a password reset is required.
- cron broken on lsphp because the shebang wasn't removed. so we did.
- updates trying to be too clever and display which php to use (was wrong), now just a static "try here, this is where it usually is" sort of thing.
- PHPStan 2.2.14 --> 2.2.15 'broken' code in Comments. fix to pass again. 
- Comment submission errors are shown as a flash message.
- removed Comments using a special flash message key
- Protected-path check missed nested protected files.
- Two updates could start at the same time.
- A crashed update blocked the next update for 30 minutes.
- `trustClient()->coreItem()` unwrapped couldn't pass `null` to `trustGate()` failing to start the update.
- BackupsRestoreCommand.php declares Commands, should be commands
- Backups: Stderr-only child never returns
- Backups: a dead background process leaves status "started" forever
- Marketplace: Marketplace gets the site domain from the request.
- Marketplace's `Verify()` reports a store failure instead of a success message.
- Marketplace shows free-tier and no-license items as free, with the download action.
- Marketplace reused the package staging directory without emptying it.
- AI key generation exposed the key in sessions from a flash message
- AiService::authenticate() checks isEnabled() before isBlocked()
- Author Card hardcoded '/profile/'
- Blog author URLs hardcoded '/profile/'
- countWithMetaTitle() ran a query and discarded the result
- AiKeyGrant::replaceFor() does DELETE then INSERT with no transaction
- ActivityLog: Raw PDO queries in service, put in model
- BrokenLinks: Fix recheck and scan
- Everything uses full URLs from users
- Comments: guest_website is stored raw
- Comments: store() gates only on isTypeEnabled()
- Comments: HTMLPurifier absence falls back to returning raw comment HTML
- Comments: parent_id is validated for depth only
- Comments: rate limiter check() and hit() are separate
- Comments: rate limiter hit() fires before the insert
- Comments: createRecord() mass-assigns arbitrary keys
- Comments: CommentService uses static \Flight::db()/get() calls inside an otherwise DI service
- Comments: flattenComments/recentCommentsBlock instantiate the FlightShield User model directly, N+1
- Forms: Honeypot field is named "website", so a legitimate form field fails
- Forms: status is not validated against draft/published on create/update
- Media: Stored mime_type comes from $file['type'] (browser-supplied) and the finfo-detected $actualMime validateUpload() computed is discarded
- Forms: a field named after the captcha post field is still a live collision
- Profiles: Admin show() has no not-found guard
- SocialLinks: sort_order assigned as count(all()) after a deletion can collide with an existing row
- Search: SearchService.php with no lower clamp, so a future published_at yields a negative $ageDays
- Fix/Find/Drop tests for code that has moved but still passed for some reason
- Blog: Make the prefix a required constructor arg, so a missing value is a type error at construction rather than a silently wrong URL
- Deleting the superadmin group has no guard and demotes its members to the 'user' group
- plugins.manage not enforced
- navigation.edit not enforced
- update() discards updateProfile()'s Result and flashes "User updated." unconditionally
- Nested repeatable-group textarea values skip HTMLPurifier
- recheckByFolder() had no ../ filtering
- The FORCE_HTTPS 308 Location is built from raw $_SERVER['HTTP_HOST']
- Fix: Support/helpers.php... moved to it's proper place
- A cleared malicious-list finding is force-re-asked forever
- getBaseUrl() falls back to HTTP_HOST + HTTP_X_FORWARDED_PROTO
- Old identity is deleted before the new email is sent.
- Every forgot-password POST writes the raw submitted email into auth_logins
- auth_logins failure rows count toward Shield's per-IP login lockout
- An oversized submitted email 500s instead of failing validation
- countByStatus() loads every row into PHP via findAll() just to count
- Docblock claims HSTS is a default header
- ETag generated without quotes and compared raw against If-None-Match
- Last-Modified/If-Modified-Since is not honored
- error logging now in php's error log. May the odds be ever in your favor.
- BlogService::authorItemsForIds: The profiles half instantiates the Profiles plugin model directly
- BlogPublicController::getAuthor(): builds a raw FlightShield User via static \Flight::db()
- Profiles leak username
- Profiles didn't provide profile author info
- Blog instantiated Profiles concerns in Plugin.php causing routePrefix fallback issues. 
- Blog feed N+1:** rss()/atom() loop 20 items and per item call getPostCategories + getPostTags + getAuthor
- relatedPostsBlock N+1: for each of up to 20 candidate posts it calls getPostTagNames + getPostCategoryIds individually
- Scheduled publish dates unvalidated: store()/update() pass published_at through untouched for scheduled posts
- some runway commands either threw exceptions or errored. 



## [3.0.0-beta.4] - 2026-09-24

### Changed
- The Updates page, the dashboard card, and the Site Health check report a newest release that an installed addon holds back, and name the addon

### Fixed
- The Updates page served the pre-update version after an update applied, and offered the same update again, until the next release check
- Addon compatibility bounds compare the base version: a `pubver_min` of `3.0.0` accepts a `3.0.0-beta` release

## [3.0.0-beta.3] - 2026-09-24

### Added
- Any plugin can offer itself as the homepage through adext
- New migration `2026-09-20-133834_MigrateHomepageTypeToken.php` changes an existing homepage setting from `pages` to `page` (Don't write migrations like this)
- New `ErrorController` and error views; 404 pages render in the site theme
- Block templates load from `public/blocks/`, in this order: app override, theme override, plugin default
- Plugins and themes state `type`, `pubver_min`, and `pubver_max`
- Default theme shows the search score and the best score possible
- New `text.tpl` block in CoreBlocks

### Changed
- Blog and Pages follow MVC, and register their menus and homepage options with adext
- All 17 plugin models return a new object from their finders
- Search ranking is in `SearchService`: constant weights, `maxScore()` per search
- Blog and Pages send their text with the markup stripped
- `enlivenapp/flight-csrf` to `^1.0`, plus updates to `enlivenapp/flight-shield` and `enlivenapp/vision`
- `ThemeService` reads a theme's required Pubvana versions from its `pubvana.json`
- PHPStan level 8 documented in 11 plugin `AGENTS.md` files
- Rewrote the admin email setting descriptions

### Fixed
- Blog search returned nothing for any term: `LIKE` used `ESCAPE '\'`, which MySQL rejects. Escape character is `!`; `escapeLikePattern()` handles `!`, `%`, and `_`
- Search logs provider failures
- Sidebar showed on the homepage no matter the setting
- The plugin manager enable/disable toggle
- Runway failed on `config.config.php`
- `pages` vs `page` in the Pages seed, plus its URLs and spelling

## [3.0.0-beta.2] - 2026-09-18

### Added
- Release zip now includes `writable/` and `cron`, so a GitHub release installs out of the box
- `.gitkeep` whitelists for `writable/store`, `writable/tmp`, `writable/trust`, and `public/uploads`
- INSTALL.md: File & Folder Permissions section documenting the chgrp/chmod scheme for shared hosts

### Changed
- Environment handling made production-safe:
  - `flight.debug` derives from the environment; `APP_DEBUG` is no longer read
  - `FORCE_HTTPS` is independent of the environment and only applies when set explicitly
  - Boot no longer fatals in `--no-dev` installs: Tracy references are guarded, so a release build without the dev packages starts cleanly
- Trust client always reports to `https://pubvanacms.com`; the development localhost endpoint is removed
- Updates dashboard and Site Health messages point at Tools > Maintenance > Updates
- Vendored libraries upgraded and pinned in the lock file:
  - `enlivenapp/flight-shield` `^0.4` (`0.4.2`), including the auth-groups `updated_at` migration
  - `enlivenapp/vision` `1.0.4`
  - `flightphp/active-record` latest
- The migrations database `versions` marker reads `semver` from `pubvana.json` instead of a hardcoded value
- Tests read the current semver from `pubvana.json`, so version bumps no longer break the suite

## [3.0.0-beta.1] - 2026-09-17

### Added
- PHPUnit tests almost entirely throughout
- Admin block toggles: individual blocks can be enabled or disabled from the admin panel
- Author Card block
- `normalizeExternalUrl()` helper: accepts a full URL or a bare value combined with a base
- Optional title on the HTML block
- OWASP-based hardening guidance for site operators

### Changed
- Migrations renamed to the `YYYY-MM-DD-HHMMSS_` filename convention across core and all plugins
- Migrations library bumped to `enlivenapp/migrations ^0.4`; `MigrationSetup` no longer takes a database handle, callers pass the project root and an explicit `path_mode`
- README and plugin AGENTS.md files standardized; v3 marked "In Beta" and the alpha notice removed
- Website domain updated to pubvanacms.com
- Marketplace migration files combined
- Docker files removed from the repository
- Monolithic admin controller untangled
- INSTALL.md: removed the redundant `shield:user password` step, dropped the stale `runway routes` row, and added a caution about guarding terminal access

### Fixed
- Security hardening batch:
  - Open redirects: `sameSite()` host validation applied to Forms, Comments, and Profiles return-url handling
  - Host-header injection into canonical/OG/JSON-LD URLs
  - LIKE wildcard injection in the blog service
  - Stored XSS: `javascript:` URLs in Profiles, media admin innerHTML injection, and raw `application/ld+json` output
  - Symlink traversal in archive and snapshot validators
  - cURL hardening: redirect hop checking, scheme enforcement, private/loopback/link-local IP filtering, and response size caps
  - `mysqldump`/`mysql` credentials no longer visible in process listings
  - Admin-group users could escalate to superadmin (blocked)
  - Plugin admin routes reachable without authentication
  - AI update-only key could unpublish content
  - ActivityLog no longer trusts `X-Forwarded-For`/`X-Real-IP`
  - CSP no longer relies on `'unsafe-inline'`/`'unsafe-eval'` and unpkg scripts
- Backup restore: pre-restore snapshot taken before the restore runs; SQL dump and restore splitting made splitter-safe
- Blog taxonomy pagination queries per page instead of filtering the global post list in memory
- Generic error message rendered in production instead of leaking internals
- Theme override resolution (again)
- Misc PHPStan/Psalm findings: Pages update validation parity, media upload checks, Analytics deprecations, TrustClient default URL, zip-name collision, advisory-locked backup restore

## [3.0.0-alpha.4] - 2026-09-12

### Added
- AI Assistant: Fact Checking. External AI assistants verify claims in posts and pages under a versioned integrity prompt and file structured reports (per-claim verdicts with cited sources, facts separated from opinion); enabled by an admin under Tools > AI Assistant, with a report history, editor panels, and a placeable public block
- Trust client integrated into core, plugins, themes, and Updates
- Stand-alone API controller; AI Assistant and Marketplace wired to it
- Admin menu system rewritten so plugins can register submenus, not just top-level menus
- (h)reCaptcha extracted from Comments into its own service; Comments, Forms, and Login now use it
- Flash messages on the public side, surfaced in the default theme
- Plugin asset CSS resolution with theme override, plus `footer_scripts` output
- Extended Shield login hardening and theme override views
- Default page and blog post seed data
- Marketplace and Updates integration with each other and the store

### Changed
- Performance pass: homepage load from ~2500ms to ~450ms (DB call stacking and asset-serving fat trimmed)
- Public page template restructure
- Theme service rework: activation/switching fixed, override paths fixed, region tags simplified to a single `{% region name %}` call
- Plugins no longer auto-enable on install; core plugins ship enabled
- Repository trimmed to Pubvana v3 code and releasables only (private plugins moved to their own repo), feature freeze for the initial release
- Composer and documentation updates throughout

### Fixed
- Theme activation/switching (all themes were being activated)
- Login redirect bug, especially with 2FA enabled
- Theme override path bug
- PHPStan Level 8 errors from the performance work
- Marketplace build and `csrf.exempt` handling for Marketplace and AI Assistant

## [3.0.0-alpha.3] - 2026-09-03

### Added
- New Updates plugin: checks releases.json for new Pubvana versions, with manual and opt-in automatic updates
- Updates page in the v2 layout: Update Settings card (Manual/Automatic toggle + crontab lines), status banners, preflight table with Required/Optional badges, and a confirmation modal before applying
- Granular update progress (preflight, backup, download, validate, extract, copy, migrate, cleanup) with live polling in the admin
- Mandatory pre-update backup through the Backups plugin; a failed backup aborts the update
- Safe-target capping: never jumps past a version blocked by an installed plugin or theme declaring min/max_pubvana_version
- Per-version skip list, so a troublesome release can be passed over
- CLI commands: `runway updates:check`, `runway updates:apply`, `runway updates:auto-update`, with the auto-update chain registered as a daily task on the core cron system
- Release packaging workflow (`.github/workflows/release.yml`) that builds `release.zip` including vendor/, verifies the tag against pubvana.json, and keeps CHANGELOG.md in sync with releases.json
- New Cron system: `CronService` runs plugin-registered tasks on fixed intervals (every minute / 4 hours / daily) through a root `cron` script, with run locks against overlapping crontab hits, per-task error isolation, and cron.log
- New Broken Links plugin: scans outbound links in published posts and pages, with a Tools > Broken Links admin (run scan, recheck, dismiss); wired as a daily task on the cron system
- New Activity Log plugin: records admin activity
- PHPUnit CI workflow (`.github/workflows/test.yml`)

### Changed
- Database access moved to SimplePDO
- README restructured: badges, project logo, and the v2/v3 separation note

## [3.0.0-alpha.2] - 2026-09-02

### Added
- PHPStan static analysis to Level 8 across app and plugins, enforced via GitHub Actions
- Psalm taint analysis with Flight input stubs and baseline
- Initial PHPUnit test suite and configuration
- New Social Links plugin for user social profiles
- New Backups plugin: backup and restore for Pubvana
- Tabler build system: npm, sass, and build scripts

### Changed
- Reworked PHPStan ignore patterns (PROJECT_ROOT bootstrap, ActiveRecord/Collection and Flight magic exceptions)
- Cleaned up plugin and project README files for easier onboarding

### Fixed
- PHPStan Level 8 errors across app and plugins
- Various PHPStan/Psalm configuration and stub issues

## [3.0.0-alpha.1]

### Added
- FlightPHP-based architecture (replaces CodeIgniter)
- Plugin system with enable/disable from admin panel
- Vision template engine (no PHP execution in public templates)
- Region and block system with drag-and-drop placement
- Tabler admin UI with dark mode
- Jodit WYSIWYG editor for all content types
- Media library with image editing
- Built-in SEO (meta, sitemaps, schema, LLMs.txt)
- Comment system with moderation
- URL redirect manager with 404 tracking
- Form builder with submissions
- Server-side analytics with daily rollups
- Search across all content types
- Role-based access control (Shield)
- SMTP email with encrypted credentials
- Plugin developer docs (architecture, Vision, adext, runway)
