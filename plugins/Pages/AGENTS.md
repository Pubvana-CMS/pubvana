# AGENTS.md — Pages plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Pages is the static pages module of Pubvana: About, Contact, Terms, and similar content containers with SEO-friendly rendering, draft/published status, soft delete, and revision history. Pages are commentable and searchable through adext hosts.

- **Package:** `pubvana/pages` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (union type `int|bool` at `Controllers/PagesPublicController.php:67`, typed property declarations with defaults at `Models/Page.php:50-60`)
- **Namespace:** `Pubvana\Plugins\Pages` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (paginated/fluent queries), `enlivenapp/migrations` (migration base); Pubvana core classes `AdminController`, `PublicController`, `PluginInterface`; core services `$app->db()`, `adext()`, `auth()`, `session()`, `settings()`, `media()` (only for the Jodit editor init), and the `render`/`redirect`/`stop` helpers
- **Config:** `Config/Config.php`: `routePrepend` (`page`) and `max_revisions` (`15`)
- **Docs:** `README.md`

## Project guidelines

1. **Keep the slug immutable after creation.** `updatePage()` must never write `slug`; slugs come only from `generateSlug()` at insert (`Models/Page.php:343-361`, `updatePage` at `Models/Page.php:302-322`). Reason: nav targets, search results, and comment URLs all depend on stable permalinks.
2. **Snapshot before every state change.** `updatePage()` in the service snapshots the pre-update page first (`Services/PagesService.php:99-109`). Reason: every edit must leave the previous state recoverable. Do not add new mutating paths that skip `createFromPage()`.
3. **Cap the revision table on every write.** Call `pruneRevisions()` after creating a revision; it prunes oldest-first to `max_revisions` (`Services/PagesService.php:156-160`). Reason: the table is unbounded otherwise, and restores never bump the counter.
4. **Public routes only ever serve published, non-deleted pages.** `findBySlug()` requires `status = published` and `deleted_at IS NULL` (`Models/Page.php:87-96`). Reason: a leaked draft or archived page breaks the admin's draft workflow.
5. **Delete is always a soft delete.** `deletePage()` only stamps `deleted_at` (`Services/PagesService.php:113-121`). Reason: early boot is not destructive; the schema plus indexes on `status` and `deleted_at` support later cleanup.
6. **Never branch on a concrete migration style.** The plugin includes both a `change()` migration and an `up()`/`down()` migration (`Database/Migrations/2026-09-17-104827_CreatePagesTable.php:18`, `Database/Migrations/2026-09-17-104828_CreatePagesRevisionsTable.php:16`). Keep the style each file already uses.
7. **Own the excerpt logic only as finding content.** `searchContent()` supplies normalized matches, including the stripped body as `content` so the Search plugin can score a body hit; ranking, scoring and the term-centered excerpt's weight belong to the Search plugin (`Models/Page.php:225`). Use `strip_tags` + `html_entity_decode` and `mb_*` string functions so excerpts never leak markup and never split multi-byte characters.
8. **Keep the comments host key `page`.** `commentHostItems()` emits `type => 'page'` (`Services/PagesService.php:203-219`), matching the `commentable` payload the public view renders (`Controllers/PagesPublicController.php:58`). Reason: the Comments plugin keys threads by this type.
9. **Keep the Jodit dependency optional.** Create/edit views guard on `!empty($joditHtml)` because `joditInit()` comes from the Media plugin (`Views/admin/edit.php:89-90`). Reason: Pages must stay usable if Media is disabled.
10. **Do not expose the AI flag to the public except through the disclosure.** The `ai_generated` origin flag is set once at creation and later edits never change it (`Controllers/PagesAdminController.php:56-57`); the public view only emits an AI disclosure when the SEO `ai_disclosure_enabled` setting is on (`Controllers/PagesPublicController.php:67-74`). Reason: the flag is editorial metadata, the disclosure is a policy decision.

## Repository layout

```
plugins/Pages/
├── Config/Config.php                  routePrepend (page), max_revisions (15)
├── Controllers/
│   ├── PagesAdminController.php       CRUD, revisions, restore (/admin/pages/*)
│   └── PagesPublicController.php      Public render (/page, /page/@slug) with AI disclosure
├── Database/
│   ├── Migrations/
│   │   ├── 2026-09-17-104827_CreatePagesTable.php       pages (slug unique; indexed status, deleted_at)
│   │   └── 2026-09-17-104828_CreatePagesRevisionsTable.php  pages_revisions (indexed page_id)
│   └── Seeds/Seed.php                 Seed: pages.manage permission + default "Not WordPress" page
├── Models/
│   ├── Page.php                       pages table; finders, pagination, slug gen, soft delete, search
│   └── PageRevision.php               pages_revisions table; snapshot, per-page prune
├── Services/PagesService.php          Service facade mapped as $app->pages() (Plugin.php:34-40)
├── Plugin.php                         Entry point; routes and adext registers
├── pubvana.json                       Manifest; provides admin.menu (Pages, ti-file), admin.dashboard
├── Views/admin/
│   ├── index.php                      List with pagination and delete
│   ├── create.php                     New page form
│   ├── edit.php                       Edit form with revisions link
│   └── revisions.php                  Revision history and restore
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:22-95`). Maps the `pages` singleton to `PagesService` with the engine DB and plugin config (`Plugin.php:34-40`). Auth middleware is disabled for development (`Plugin.php:43`; permission `pages.manage` is seeded for later enforcement).

**Routes.** Eight admin routes under `pubvana.pages` (`Plugin.php:46-55`) and two public routes (`Plugin.php:58-61`). `/page` redirects to the homepage; `/page/@slug` renders a published page. The public renderer resolves a theme-level `page` template with title, content, commentable info, and an AI disclosure flag (`Controllers/PagesPublicController.php:44-62`).

**Extension points (adext registrations).**
- `admin.dashboard` card (total pages) and section (recent five) (`Plugin.php:65-75`).
- `nav.linkable`: published pages as label/url targets for the navigation manager (`Plugin.php:78-81`).
- `search` provider: `content_type 'Page'` (case-sensitive), results from `Page::searchContent()` (`Plugin.php:84-88`).
- `comments.host`: published pages as commentable content with per-page `allow_comments` (`Plugin.php:91-94`).
- `homepage.provider`: Pages is a front page candidate under token `page` (from `routePrepend`), priority 30, and it declares `CMS.homepagePageId` as a field of its own, so core carries no Pages setting. The callable renders the chosen published page through `PagesPublicController::view($slug, true)`, which is what sets the `is_homepage` flag the SEO plugin reads. It returns `false`, declining `/`, when no page is chosen or the chosen page is missing or unpublished.

**Revision pipeline.** Every create, update, and restore calls `createFromPage()`, which copies title/content/status/allow_comments (never slug) into a new row before pruning (`Models/PageRevision.php:71-84`). `max_revisions` defaults to 15 and is only overridable through config.

**Data flow (search).** `Page::searchContent()` OR-matches title/slug/content on published, non-deleted pages, then builds a term-centered excerpt from de-tagged, entity-decoded content, returning `id/title/url/excerpt/content/content_type/published_at` for the Search plugin to rank.

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Pages/` (9 files: `PagesUpdateTitleGuardTest`, `PagesServiceUrlTest`, `PagesAdminControllerTest`, `PagesPublicControllerTest`, `PagesAdminControllerUrlTest`, `PagesMigrationsSeedTest`, `PagesPluginTest`, `PagesServiceTest`, `PagesModelsTest`). It is also exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Pages -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Pages`
- Manual verification checklist:
  - [ ] Create a page; confirm a revision snapshot exists and the revision list shows it
  - [ ] Create two pages with the same title; confirm the second slug gets a `-N` suffix
  - [ ] Try to change the slug through an update; confirm it stays untouched on the public URL
  - [ ] Make five edits then many more; confirm `pages_revisions` stays capped at `max_revisions`
  - [ ] Restore an older revision; confirm content reverts and the restore itself creates a revision
  - [ ] Set a page to draft; confirm `GET /page/@slug` still 404s on a prior published URL
  - [ ] Delete a page; confirm it disappears from public, dashboard, nav, search, and comments host, but a prior URL still 404s (soft delete)
  - [ ] Confirm search returns published pages with a term-centered excerpt and no residual HTML
  - [ ] Confirm the comments host lists `type 'page'` and flags `allow_comments`
  - [ ] Disable Media; confirm create/edit still render, just without Jodit
  - [ ] With `ai_generated` set and SEO disclosure enabled, confirm the public disclosure shows only then
  - [ ] Set Settings > Site > Homepage to Static Page with a published page chosen; confirm `/` renders that page at 200 (no redirect) and its canonical tag points at the site root
  - [ ] Unpublish that page; confirm `/` falls through to the blog index instead of 404ing, and the log records the decline

Coverage: the suite covers the service (URLs, updates, title guard), both controllers, the models, migrations/seeds, and the plugin registration.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Page.php:45-48`, `Models/PageRevision.php:37-40`).
3. **De-notated, typed column properties on models** (e.g. `public string $status = 'draft';` at `Models/Page.php:50`). Keep them in sync with the migration schema and the `@property` docblock (`Models/PageRevision.php:14-22`).
4. **`DateTimeImmutable` for every timestamp write** (`Models/Page.php:281, 320, 329`).
5. **Controllers strip `_csrf_token` from posts before passing to the service** (`Controllers/PagesAdminController.php:48, 84`). Never forward raw request data wholesale.
6. **Views render the CSRF field with `csrf_token()`** and template keys are always `pubvana/pages/admin/{name}`.
7. **Public views never use raw `echo` of page content; content flows through the theme's `page` template** (`Controllers/PagesPublicController.php:53-61`).

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `Services/PagesService.php:99-110` | The snapshot-before-update rule |
| `Controllers/PagesPublicController.php:44-74` | Public rendering behavior and disclosure logic |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Change revision cap | `Config/Config.php` (`max_revisions`) |
| Adjust slug generation | `Page::generateSlug()` (`Models/Page.php:343-361`) |
| Add a searchable field | `Page::searchContent()` (`Models/Page.php:225`) |
| Add a public route | `Plugin.php` public `addRoutes` block and `PagesPublicController` |
| Enforce permissions | Add a `PermissionMiddleware` keyed on `pages.manage` to the admin route middleware slots |
| Add an admin field | Migration + `Page`/`PageRevision` typed props + `updatePage` + create/edit views |
| Change the homepage token | `Plugin.php` homepage registration (`token`, derived from `routePrepend`) |
| Change the homepage page picker | `Plugin.php` homepage `fields` (`CMS.homepagePageId`, `options_callable`) |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] No slug writes outside `generateSlug()`; no hard deletes; no skipped snapshots or prunes
- [ ] Public queries still filter `status = published` and `deleted_at IS NULL`
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean on the app (`composer phpstan`)
- [ ] Both migration styles kept as-is; revision cap still applied after any new write path
- [ ] Media (Jodit) stays optional in views; README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No versioned publishing, staging, or scheduled publish; a page is either `draft` or `published`.
- No slug editing, and no multi-language page variants.
- Hard deletion is out of scope; soft delete is the only delete path.
- This file documents code as written. Known gap: `restoreRevision()` snapshots after `updatePage()` overwrites the page (`Services/PagesService.php:143-150`), so the pre-restore state is not preserved. `<!-- TODO: reconcile PagesService::restoreRevision ordering so the pre-restore state is snapshotted -->`
