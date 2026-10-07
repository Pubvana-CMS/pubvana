# AGENTS.md: Profiles plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Profiles gives each user a browsable public profile and a self-service edit page: display name, bio, avatar, website, and social links, plus a job title and employer. Profiles are created lazily per user and belong to the `users` table through a cascade foreign key.

- **Package:** `pubvana/profiles` (`pubvana.json:2`), semver `0.3.0`, category `admin`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
   - **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (e.g. nullable return type `?User` at `Controllers/ProfilesPublicController.php:220`)
- **Namespace:** `Pubvana\Plugins\Profiles` (`Plugin.php:5`), with `Controllers`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (the `User` model used for lookups at `Controllers/ProfilesPublicController.php:8, 226`); Pubvana core classes `AdminController`, `PublicController`, `PluginInterface`; core services `$app->db()`, `adext()`, `auth()`, `session()`, `media()` (the avatar picker and avatar file cleanup), `pluginLoader()->routePrefix()`, and the `render`/`redirect`/`halt` helpers
- **Config:** `Config/Config.php`: `routePrepend` (`profile`)
- **Docs:** `README.md`

## Project guidelines

1. **Treat `$app->profiles()` as the model facade.** The `profiles` map returns a `Profile` ActiveRecord instance directly, not a service (`Plugin.php:17-23`). Reason: consumers call model methods (`findOrCreate`, `updateProfile`), so wrapping the result in another layer would break every call site.
2. **Always go through `findOrCreate()` before reading or writing a profile.** It is the only path that guarantees a row exists (`Models/Profile.php:55-70`), and `updateProfile()` depends on it. Reason: the `user_id` column is unique, so a bare select-then-insert is racy.
3. **Keep `updateFromArray()` whitelisted.** Only `display_name`, `bio`, `avatar`, `website`, `twitter`, `facebook`, `linkedin`, `job_title`, `works_for` are writable, each trimmed to `null` when empty. `website`, `twitter`, `facebook` and `linkedin` must each be a full `http://` or `https://` URL; anything else (bare domains, handles, `javascript:`, `data:`, scheme-relative) fails `UrlService::isSafeExternalUrl()` and the write is rejected with nothing saved (`Models/Profile.php`, `updateProfile()` then returns null for the controller to flash an error). There are no handle fields and no prefixing on read: the stored value is the emitted value. Never widen the whitelist without a migration for the new column. Reason: raw request data must never reach the model, and all four fields are rendered as navigable hrefs.
   - **Never render a profile URL raw in an href.** `ProfilesPublicController::show()` and `ProfileBlockService::provide()` pass `safe_website` plus `twitter_url`/`facebook_url`/`linkedin_url`, each the value only when it passes `isSafeExternalUrl()`, and the theme template uses them (`themes/default/Views/pubvana/profiles/profile.tpl`). The render guard covers rows stored before the write-side rule existed.
4. **Public edit is owner-only.** Both `edit()` and `update()` compare the authenticated user id against the target user id and refuse otherwise (`Controllers/ProfilesPublicController.php:73-77, 181-186`). Reason: only the account holder edits their own public profile.
5. **Admin other-user editing keys on `profile.edit.any`.** The owner can always edit themselves; editing someone else requires the permission (the `show()`, `update()` and `avatar()` checks in `Controllers/ProfilesAdminController.php`). Admin routes are gated on `admin.access` automatically by the core; the in-controller `profile.edit.any` checks are the per-action enforcement layer.
6. **Every posted redirect goes through `UrlService::sameSite()`.** `update()` passes the posted `return_url` through `sameSite()` before redirecting (both redirects in `ProfilesAdminController::update()`), so a hostile value can never reach a `Location` header. Keep that choke point when adding new posts.
7. **Build the avatar URL with the existing normalization.** A stored avatar may or may not start with a slash; the public renderer prepends `/` and strips any leading slash (`Controllers/ProfilesPublicController.php:26-29`). Keep that exact shape.
8. **Keep the public asset URL and disk path in sync.** The `public.css` adext registration points at `/assets/plugin/Profiles/css/profiles.css` (`Plugin.php:68-71`), which must stay in step with `assets/css/profiles.css`. Reason: a mismatch is a silently 404'd stylesheet.
9. **Do not touch the `.pv-profile-*` class names without updating the theme.** The public views are theme templates (`profile` at `Controllers/ProfilesPublicController.php:43`, `profile_edit` at `:93`) that consume the classes defined in `assets/css/profiles.css`. The plugin does not own those templates.
10. **Compose public routes from `routePrefix()`, never hardcode `/profile`.** The prefix resolves through `pluginLoader()->routePrefix('pubvana/profiles')` (`Plugin.php:18, 48-53`). Reason: the prefix is configurable via `routePrepend`, and a hardcoded path breaks under custom prefixes.
11. **A public profile URL carries the user id, never the username.** The route param is `@id` (`Plugin.php:48-53`), the controller resolves it with `findUser()` (`Controllers/ProfilesPublicController.php:220`), and every link builder produces `{prefix}/{id}` (`ProfileBlockService::provide()` and `nameAndUrlFor()`, `Seo/Services/SeoService`). Reason: a username in a URL publishes account names for free. The handle is never rendered either, and the author block payload does not carry it. Editors pass `profileBase` to the profile templates rather than writing the path out.
12. **A soft-deleted account still resolves on its public page.** `findUser()` reads the row by `id` with no `deleted_at` filter, because the profile row survives the soft delete and posts the account wrote keep their byline link. Attribution outlives the account; do not reintroduce `findById()`, which filters deleted rows. Only a user with no row at all is a 404.
13. **An avatar file is deleted when the row stops pointing at it, never before.** `storeAvatar()` names each file `{user id}-{token}.webp` so a replacement gets a fresh URL instead of a cached one, and removes nothing. `update()` runs the cleanup after a successful save (`removeUnusedAvatar()` in both controllers): `MediaService::sweepAvatars()` drops the user's other avatar files, keeping the one the row now points at, and a previous path outside the avatar directory goes through `deleteLegacyAvatar()`. Reason: the row is the record of the live file, so an abandoned form cannot leave it pointing at a deleted file.

## Repository layout

```
plugins/Profiles/
├── Config/Config.php                  routePrepend (profile)
├── Controllers/
│   ├── ProfilesAdminController.php    Own profile page, admin edit of any user (profile.edit.any), update
│   └── ProfilesPublicController.php   Public show/edit/update (owner-only edit), user lookup via FlightShield
├── Database/
│   ├── Migrations/2026-09-17-105234_CreateProfilesTable.php
│   │                                  profiles (user_id unique, FK to users with CASCADE delete)
│   └── Seeds/Seed.php                 Seed: profile.edit, profile.edit.any
├── Models/Profile.php                 profiles table; findOrCreate, whitelisted updateFromArray
├── Plugin.php                         Entry point; profiles facade, routes, public CSS
├── pubvana.json                       Manifest; name, semver and plugin metadata
├── Views/admin/profile/index.php      Shared profile edit form (own or other user)
├── Views/public/blocks/author-card.tpl  Author Card block template (plugin default layout)
├── assets/css/profiles.css            Public .pv-profile-* styles for the theme templates
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:16-83`). Maps the `profiles` facade (`Plugin.php:21-27`) and the `profileBlock` provider (`Plugin.php:29-35`), registers four admin routes (`Plugin.php:40-45`) and four public routes under the resolved route prefix (`Plugin.php:48-53`), exempts the public avatar upload from the CSRF gate (`Plugin.php:62-65`), and registers the public stylesheet (`Plugin.php:68-71`).

**Data flow.** Any profile page first calls `findOrCreate($userId)`, which lazily inserts a bare row (timestamps only) when none exists (`Models/Profile.php:55-70`). Saves flow through `updateProfile()` → `findOrCreate()` → `updateFromArray()` with the whitelist (`Models/Profile.php:79-86, 99-122`).

**Public tenant.** `show()` takes the user id from the URL and resolves it with `findUser()` (`Controllers/ProfilesPublicController.php:18, 220`), which reads the row by `id` including soft-deleted accounts; the profile row survives a soft delete and posts keep their byline link, so the page stays answerable. It renders the theme `profile` template with `isOwner`, `profileBase` and a normalized `avatar_url` (`Controllers/ProfilesPublicController.php:18-52`). `edit()` and `update()` render/redirect to the theme `profile_edit`. The account username is never rendered on the public pages and never appears in a URL.

**Admin tenant.** `index()` renders the shared `pubvana/profiles/admin/profile/index` view for the current user with the Media avatar picker; `show(@userId)` renders the same view for another user when permitted, seeding the `returnUrl` differently (`Controllers/ProfilesAdminController.php`).

**No dashboard surface.** The manifest declares no `provides` entries and `Plugin.php` registers no `admin.dashboard` items, so Profiles adds no dashboard card or section. The admin profile page is reached through the core admin layout's Profile menu entry (`/admin/profile`).

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Profiles/` (12 test files: `ProfilesPluginTest`, `ProfilesBlockRegistrationTest`, `ProfileWebsiteValidationTest`, `ProfilesAdminReturnUrlTest`, `ProfilesPublicControllerTest`, `ProfilesPublicWebsiteGuardTest`, `ProfilesAdminControllerUrlTest`, `ProfilesAdminControllerTest`, `ProfilesAvatarEndpointTest`, `ProfileModelTest`, `ProfileBlockServiceTest`, `ProfilesMigrationsSeedTest`). It is also exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Profiles -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Profiles`
- Manual verification checklist:
  - [ ] First visit to any own-profile path creates exactly one row; a second visit reuses it
  - [ ] Save all nine fields; confirm empty inputs store `null` and the whitelist rejects unknown keys
  - [ ] Avatar picker stores a path; the public page renders the avatar with exactly one leading slash
  - [ ] Clearing the avatar and saving deletes the stored file
  - [ ] A logged-in non-owner hitting `/profile/{other}/edit` is redirected, not served
  - [ ] Editing another user from `/admin/profile/{id}` is blocked without `profile.edit.any` and allowed with it
  - [ ] Unknown ids (no `users` row) return a 404 (`halt`), not an empty page, while a soft-deleted account's profile still serves
  - [ ] `GET /assets/plugin/Profiles/css/profiles.css` returns the stylesheet
  - [ ] No dashboard card or section appears for Profiles (matches current code, despite the manifest declaration)
  - [ ] Deleting a user cascades the profile row (foreign key `CASCADE`)

Coverage: the suite covers the model, both controllers (URLs, website validation, return-url, guards), the block service, migrations/seeds, and the plugin registration.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Profile.php:42-45`).
3. **Use `DateTimeImmutable` for all model timestamps** (`Models/Profile.php:62, 118`).
4. **Controllers strip `_csrf_token` (and `return_url`, where consumed) before calling the facade** (`Controllers/ProfilesAdminController.php:63-65`, `Controllers/ProfilesPublicController.php:79-80`).
5. **Views render the CSRF field with `csrf_field()` and escape every echoed value with `htmlspecialchars`** (`Views/admin/profile/index.php:17-18, 22-23`).
6. **User lookups go through the FlightShield `User` model, not raw queries** (`Controllers/ProfilesPublicController.php:87-91`).
7. **Prefer `findByUserId()`/`updateProfile()` over direct property assignment outside the model.** All row access flows through the model methods.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `Controllers/ProfilesPublicController.php:21-33, 44-56` | Owner-only edit rules and 404 handling |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a profile field | Migration (`2026-09-17-105234_CreateProfilesTable.php`) + `updateFromArray()` whitelist + admin view + theme template |
| Change the public route prefix | `Config/Config.php` (`routePrepend`) |
| Gate other-user editing differently | The `profile.edit.any` checks in `ProfilesAdminController` (`show()`, `update()`, `avatar()`) |
| Change public profile markup | Active theme `profile`/`profile_edit` templates and `assets/css/profiles.css` |
| Gate admin routes per-action | Add a `PermissionMiddleware` keyed on `profile.edit`/`profile.edit.any` to the admin route middleware slots |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 8 is clean on the app (`composer phpstan`)
- [ ] Whitelist, migration, and view stay in lockstep; no raw request data reaches the model
- [ ] `findOrCreate` still the only row-creation path; owner and `profile.edit.any` guards unchanged
- [ ] Public CSS path on disk matches the registered URL; `.pv-profile-*` classes untouched without a theme change
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Public display templates (profile pages) are in the active theme, not in this plugin. The only `Views/public/` asset here is the Author Card block template, which follows the plugin default block layout.
- No hard profile delete; profiles disappear only through the `users` cascade delete.
- No external avatar sources (Gravatar, Uploadcare, etc.); the avatar is a stored image path picked via the Media plugin.
- No pagination, search, or discovery of profiles; a profile is reached by a known user id.
