# AGENTS.md — Blog plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Blog is the content module of Pubvana. It provides posts, categories, tags, revision history, preview links for drafts, RSS and Atom feeds, dashboard cards, five public blocks, a search provider, and a comments host.

- **Package:** `pubvana/blog` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`match` at `Services/BlogService.php:561`, `str_starts_with` at `Controllers/BlogPublicController.php:377`, union type `int|bool` at `Controllers/BlogPublicController.php:387`)
- **Namespace:** `Pubvana\Plugins\Blog` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (`\Enlivenapp\FlightShield\Models\User` at `Controllers/BlogPublicController.php:258`), `ezyang/htmlpurifier` (optional, guarded by `class_exists` at `Services/BlogService.php:591`); core engine services used as `$app->blog()`, `db()`, `adext()`, `pluginLoader()`, `settings()`, `media()`, `profiles()`, `auth()`, `session()`, `slugify`
- **Peer plugin requirements:** Media (admin editor init `$app->media()->joditInit()` at `Controllers/BlogAdminController.php:44`, `/storage/` asset URLs at `Controllers/BlogPublicController.php:381`) and Profiles (author lookup `$app->profiles()->findByUserId()` at `Controllers/BlogPublicController.php:266`)
- **Docs:** `README.md`

## Project guidelines

1. **Route all writes through `BlogService`.** Controllers must call service methods (`createPost`, `updatePost`, `syncPostCategories`, `syncPostTags`) and never touch models directly. Reason: the service owns revision snapshots, taxonomy sync, `ai_generated` handling, and content purification (`Controllers/BlogAdminController.php:74-89`).
2. **Never change a slug after creation.** `Post::updateRecord()` only writes whitelisted fields and `slug` is excluded (`Models/Post.php:119-122`). Reason: the immutable slug keeps post URLs, feeds, previews, and nav links stable.
3. **Never change `ai_generated` after creation.** It is set once at create time (`Services/BlogService.php:85`) and influences the public AI disclosure (`Controllers/BlogPublicController.php:387-394`). Reason: the flag records provenance, not current state.
4. **Bump post views with `incrementViewsDirect()`, not `incrementViews()`.**
   The raw `UPDATE ... SET views = views + 1 ... WHERE deleted_at IS NULL` (`Models/Post.php:155-163`) is atomic and avoids the N+1 hydration that the old two-round-trip save caused. Reason: concurrent-safe counting with minimal query cost.
5. **Snapshot before every state change.** `PostRevision::createFromPost()` records title, content, excerpt, and status before a write (`Models/PostRevision.php:39-52`), and a restore first snapshots the current state (`Services/BlogService.php:153-154`). Reason: every restore is itself reversible.
6. **Prune revisions after each snapshot.** History is capped at `max_revisions` (default `15`, `Config/Config.php:5`) via `PostRevision::pruneForPost()` (`Models/PostRevision.php:54-69`). Reason: unbounded history would bloat the `post_revisions` table.
7. **Keep content purification enabled.** `purify_content` defaults to true (`Services/BlogService.php:77, 98`) and runs stored HTML through htmlpurifier when available (`Services/BlogService.php:589-596`). Reason: admin-authored HTML is trusted input, but purging keeps output consistent and defendable.
8. **Filter every public/read query to `status = 'published' AND deleted_at IS NULL`.**
   Posts are soft-deleted (`Models/Post.php:134-138`) and carry statuses `draft`, `published`, `scheduled`; public listing, search, feed, nav, and comment-host queries must exclude drafts and tombstones (e.g. `Plugin.php:186`, `Models/Post.php:41-49`). Reason: only published, live posts reach the public surface.
9. **Treat `preview_token` as a capability, not a secret you add to URLs.** It is a unique column (migration `000001`) and the `/preview/@token` route renders unpublished posts without auth (`Plugin.php:74`, `Controllers/BlogPublicController.php:201-226`). Reason: the token is the sole gate for draft review before publish.
10. **Taxonomy sync is delete-then-insert.** `syncForPost` drops existing join rows, then inserts the incoming set (`Models/PostCategory.php:27-39`, `Models/PostTag.php:27-39`). Reason: keeps join tables consistent on every save at small-cardinality cost, so only pass validated ID sets.
11. **Build all slugs with `Flight::slugify()` / `$app->slugify()`.** Used for posts, categories (with server-side duplicates handling) and inline tags (`Controllers/BlogAdminController.php:58, 219, 261`; `Services/BlogService.php:271`). Reason: consistent, URL-safe slugs across all admin forms and the tag auto-create path.
12. **Feed routes are root paths, not prefixed.** `/feed` and `/rss` serve RSS 2.0 and `/atom.xml` serves Atom, all registered at site root (`Plugin.php:79-83`). Reason: canonical feed URLs follow the existing conventions the Analytics plugin skips.

## Repository layout

```
plugins/Blog/
├── assets/css/blog.css              Admin stylesheet, registered on admin.css (Plugin.php:201-204)
├── Config/Config.php                routePrepend: 'blog'; max_revisions: 15
├── Controllers/
│   ├── BlogAdminController.php      Admin CRUD for posts, categories, tags (extends core AdminController)
│   └── BlogPublicController.php     Public listing, post, category, tag, preview, RSS/Atom (extends core PublicController)
├── Database/
│   ├── Migrations/                  Six migrations dated 2026-08-26
│   │   ...000001_CreatePostsTable.php            posts (slug, preview_token unique; soft-delete cols)
│   │   ...000002_CreateCategoriesTable.php       categories (slug unique; parent_id)
│   │   ...000003_CreateTagsTable.php             tags (slug unique)
│   │   ...000004_CreatePostsToCategoriesTable.php  posts_to_categories (post_id, category_id unique)
│   │   ...000005_CreateTagsToPostsTable.php        tags_to_posts (tag_id, post_id unique)
│   │   ...000006_CreatePostRevisionsTable.php      post_revisions (per-post history)
│   └── Seeds/Seed.php               Install seed: 6 auth_permissions aliases (posts.*, categories.*, tags.*)
├── Models/
│   ├── Post.php                     posts; find/paginate/count, whitelisted updateRecord, softDelete, view counters
│   ├── Category.php                 categories; slug checks, whitelisted updateRecord
│   ├── Tag.php                      tags; findOrCreate, batched findByIds (kills an N+1)
│   ├── PostCategory.php             posts_to_categories; ids lookup, sync, deleteForCategory
│   ├── PostTag.php                  tags_to_posts; ids lookup, sync, deleteForTag
│   └── PostRevision.php             post_revisions; snapshot, per-post history, capped prune
├── Services/BlogService.php         Singleton mapped as $app->blog() (Plugin.php:22-28); the service layer
├── Plugin.php                       Entry point; register() wires everything into the engine
├── pubvana.json                     Manifest; provides admin.menu (Posts/Categories/Tags) and admin.dashboard
├── Views/
│   ├── admin/                       index, create, edit, revisions, categories, category-form, tags
│   └── public/blocks/               recent-posts, categories, tags, archive, related-posts (.tpl)
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register(Engine $app, Router $router, array $config = [])` (`Plugin.php:20`). It maps the `blog` singleton (`Services\BlogService`, built from `$app->db()` and the plugin config, `Plugin.php:22-28`), then increments nothing else via adext.

**Extension points (adext registrations).**
- Admin routes grouped under `pubvana.blog` (`Plugin.php:36-61`): post CRUD + revisions/restore, category CRUD, tag delete.
- Public routes under the `pubvana/blog` route prefix (`Plugin.php:65-76`): listing, paginated listing, category and tag indexes/detail, preview, single post.
- Feed routes at the site root (`Plugin.php:79-83`).
- `public.head` feed auto-discovery link tags, priority 10 (`Plugin.php:87-91`).
- `admin.dashboard` cards and sections (`Plugin.php:95-105`), backed by `dashboardCards()` / `dashboardSections()`.
- `block.available`: recent-posts, categories, tags, archive, related-posts (`Plugin.php:109-164`), each with options schema and a block template under `Views/public/blocks/`.
- `search.provider` for posts (`Plugin.php:168-171`) with weighted title/excerpt/body relevance scoring (`Services/BlogService.php:407-477`).
- `comments.host` content items `['type' => 'blog', 'id', ...]` (`Plugin.php:175-178`, `Services/BlogService.php:486-506`).
- `nav.linkable` default: published posts as navigation targets (`Plugin.php:182-197`).
- `admin.css` stylesheet (`Plugin.php:201-204`).

**Write path.** `BlogAdminController` reads form data (`->_csrf_token` stripped, `Controllers/BlogAdminController.php:56, 123`), builds the slug, then `BlogService::createPost()` / `updatePost()` snapshots a revision, prunes, and writes via the model. Taxonomy is re-synced afterwards (`Controllers/BlogAdminController.php:88-89, 151-152`).

**Read path.** Public controllers resolve `$app->blog()` and model queries, then render Vision templates (`post`, `archive`, `categories`, `tags`, `home`; e.g. `Controllers/BlogPublicController.php:121`). Post view counting is atomic (`Services/BlogService.php:159-166`, `Models/Post.php:155-163`).

### Revisions

Every create, update, and restore snapshots the pre-write state into `post_revisions` (title, content, excerpt, status only). Restoring writes those fields back and creates a new snapshot, so restores are themselves reversible. The retained history is capped at `max_revisions` (default `15`).

### Taxonomy

Categories and tags are many-to-many through `posts_to_categories` and `tags_to_posts`. Tags are derived from a comma-separated string at save time: names are trimmed, slugged via `Flight::slugify()`, and either resolved or created through `Tag::findOrCreate()` (`Services/BlogService.php:265-281`). Category association takes explicit IDs.

### Feeds

`BlogPublicController::rss()` and `atom()` render RSS 2.0 and Atom XML strings directly (20 most recent published posts), drawing `CMS.siteName`, `CMS.siteUrl`, and `CMS.siteByline` from app settings with fallbacks (`Controllers/BlogPublicController.php:399-526`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; note the app is scanned, this plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3, sees `app/` plus `scanDirectories: vendor/`; the `enlivenapp` migration/activerecord internals are covered by the ignored-error baseline in `phpstan.neon`)
  - `php -l plugins/Blog/{**/*.php,*.php}` for syntax: `find plugins/Blog -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Create, edit, delete a post through `/admin/blog`; slug auto-suffixed on collision; slug never editable afterwards
  - [ ] Publish a draft, then confirm the public `/{prefix}/{slug}` page renders and increments the view counter once per hit
  - [ ] Create with `ai_generated` toggled off then on; confirm the flag never changes on later edits
  - [ ] Create, restore, and prune revisions; stop at `max_revisions` entries per post
  - [ ] Load `/feed`, `/rss`, `/atom.xml` and validate XML; feeds omit drafts and tombstones
  - [ ] Preview a draft via `/admin/blog/{id}/edit` preview link and the `/preview/@token` route
  - [ ] Confirm the five blocks render and that related-posts scores shared tags/categories highest
  - [ ] Search a post by title word, excerpt word, and body word; check the weighted ordering

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Post.php:27-32`). Do not hardcode table names in business logic.
3. **Prefer the ActiveRecord fluent query (eq, in, notEq, like, isNull, order, limit, offset) over raw SQL.** Raw PDO prepared statements are allowed only for cross-row mutations that the fluent API cannot express, and must carry a comment explaining why (see the `incrementViewsDirect()` rationale, `Models/Post.php:146-163`).
4. **`updateRecord()` must stay whitelisted.** Only fields listed in the `$allowed` array may be written (`Models/Post.php:119-122`, `Models/Category.php:75`). Never pass raw request arrays into model writes; controllers unset `_csrf_token` first (`Controllers/BlogAdminController.php:56`).
5. **Views render through Vision paths.** Admin views use `pubvana/blog/admin/{view}` and block templates use `pubvana/blog/public/blocks/{name}`; public pages render core templates (`post`, `archive`, `categories`, `tags`, `home`). Block templates are `.tpl` files.
6. **Keep block providers returning plain template-ready arrays** (`title` + items) so block templates stay dumb; compute URLs using the passed `$prefix`, never hardcoded `/blog`.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing module docs (structure, routes, revisions, previews, `ai_generated`). Note: line 78 claims `commentable_type` is `post`, but the code issues `type => 'blog'` (`Services/BlogService.php:498`, `Controllers/BlogPublicController.php:117, 223`). `<!-- TODO: reconcile README commentable_type claim with code -->` |
| `Plugin.php:13-17` | Plugin purpose and `@package` attribution |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add or change a route (admin, public, feed) | `Plugin.php:36-83` |
| Change the revision cap | `Config/Config.php:5` (`max_revisions`) |
| Add a new block | Register in `Plugin.php:109-164`, provider method in `Services/BlogService.php`, template in `Views/public/blocks/` |
| Change post listing/pagination per page | `Services/BlogService.php:40-53` (admin) and `Controllers/BlogPublicController.php:35` (public, 10/page) |
| Add a permission alias | `Database/Seeds/Seed.php` install rows (`auth_permissions`) |
| Change feed item limit or XML shape | `Controllers/BlogPublicController.php:399-526` |
| Add a column to `posts` | Migration `Database/Migrations/2026-08-26-000001_CreatePostsTable.php`, `Models/Post.php` property docblock, and the `updateRecord()` whitelist |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present, no em dashes in new prose, one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Writes go through `BlogService`; slugs immutable; `ai_generated` untouched on edit
- [ ] Revision snapshot + prune added to any new state-changing write path
- [ ] Public queries filter `status='published'` and `deleted_at IS NULL`; feeds exclude drafts and tombstones
- [ ] New URL-producing code honors the `pubvana/blog` route prefix (feeds stay at root)
- [ ] Taxonomy sync accepts validated ID sets only; character input stays on the tag name path
- [ ] README updated only if user-facing behavior changed; doc/README claims cross-checked against code

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No locale/i18n support; labels and media strings are hardcoded English.
- No raw SQL beyond the documented `incrementViewsDirect()`; search relevance stays in-process (no full-text engine).
- No front-end asset pipeline; the single admin stylesheet is loaded via `admin.css` registration.
