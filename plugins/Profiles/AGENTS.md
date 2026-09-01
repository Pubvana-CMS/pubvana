# AGENTS.md — Profiles plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Profiles gives each user a browsable public profile and a self-service edit page: display name, bio, avatar, website, and social links, plus a job title and employer. Profiles are created lazily per user and are owned by the `users` table through a cascade foreign key.

- **Package:** `pubvana/profiles` (`pubvana.json:2`), semver `0.1.0`, category `admin`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (e.g. nullable return type `?User` at `Controllers/ProfilesPublicController.php:87`)
- **Namespace:** `Pubvana\Plugins\Profiles` (`Plugin.php:5`), with `Controllers`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (the `User` model used for lookups at `Controllers/ProfilesPublicController.php:8, 89`); Pubvana core classes `AdminController`, `PublicController`, `PluginInterface`; core services `$app->db()`, `adext()`, `auth()`, `session()`, `media()` (the avatar picker only), `pluginLoader()->routePrefix()`, and the `render`/`redirect`/`halt` helpers
- **Config:** `Config/Config.php`: `routePrepend` (`profile`)
- **Docs:** none existed; `README.md` added alongside this file

## Project guidelines

1. **Treat `$app->profiles()` as the model facade.** The `profiles` map returns a `Profile` ActiveRecord instance directly, not a service (`Plugin.php:17-23`). Reason: consumers call model methods (`findOrCreate`, `updateProfile`), so wrapping the result in another layer would break every call site.
2. **Always go through `findOrCreate()` before reading or writing a profile.** It is the only path that guarantees a row exists (`Models/Profile.php:21-36`), and `updateProfile()` depends on it. Reason: the `user_id` column is unique, so a bare select-then-insert is racy.
3. **Keep `updateFromArray()` whitelisted.** Only `display_name`, `bio`, `avatar`, `website`, `twitter`, `facebook`, `linkedin`, `job_title`, `works_for` are writable, and each is trimmed to `null` when empty (`Models/Profile.php:45-55`). Never widen the whitelist without a migration for the new column. Reason: raw request data must never reach the model.
4. **Public edit is owner-only.** Both `edit()` and `update()` compare the authenticated user id against the target user id and refuse otherwise (`Controllers/ProfilesPublicController.php:51-55, 73-77`). Reason: only the account holder edits their own public profile.
5. **Admin other-user editing keys on `profile.edit.any`.** The owner can always edit themselves; editing someone else requires the permission (`Controllers/ProfilesAdminController.php:29-33, 57-61`). The `$authMiddleware = null` at `Plugin.php:26` is a development placeholder, so keep the in-controller checks as the enforcement layer until middleware is wired.
6. **Never accept an untrusted posted redirect without validation.** `update()` redirects to whatever `return_url` the form posted (`Controllers/ProfilesAdminController.php:63-71`). Keep that behavior in mind when adding new posts; it is not currently validated against open-redirect vectors.
7. **Build the avatar URL with the existing normalization.** A stored avatar may or may not start with a slash; the public renderer prepends `/` and strips any leading slash (`Controllers/ProfilesPublicController.php:30-33`). Keep that exact shape.
8. **Keep the public asset URL and disk path in sync.** The `public.css` adext registration points at `/assets/plugin/Profiles/css/profiles.css` (`Plugin.php:43-47`), which must stay in step with `assets/css/profiles.css`. Reason: a mismatch is a silently 404'd stylesheet.
9. **Do not touch the `.pv-profile-*` class names without updating the theme.** The public views are theme templates (`profile`, `profile_edit` at `Controllers/ProfilesPublicController.php:35, 59`) that consume the classes defined in `assets/css/profiles.css`. The plugin does not own those templates.
10. **Compose public routes from `routePrefix()`, never hardcode `/profile`.** The prefix resolves through `pluginLoader()->routePrefix('pubvana/profiles')` (`Plugin.php:36-41`). Reason: the prefix is configurable via `routePrepend`, and a hardcoded path breaks under custom prefixes.

## Repository layout

```
plugins/Profiles/
├── Config/Config.php                  routePrepend (profile)
├── Controllers/
│   ├── ProfilesAdminController.php    Own profile page, admin edit of any user (profile.edit.any), update
│   └── ProfilesPublicController.php   Public show/edit/update (owner-only edit), user lookup via FlightShield
├── Database/
│   ├── Migrations/2026-08-26-100001_CreateProfilesTable.php
│   │                                  profiles (user_id unique, FK to users with CASCADE delete)
│   └── Seeds/Seed.php                 Seed: profile.edit, profile.edit.any
├── Models/Profile.php                 profiles table; findOrCreate, whitelisted updateFromArray
├── Plugin.php                         Entry point; profiles facade, routes, public CSS
├── pubvana.json                       Manifest; declares admin.dashboard (see gap below)
├── Views/admin/profile/index.php      Shared profile edit form (own or other user)
├── assets/css/profiles.css            Public .pv-profile-* styles for the theme templates
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:15-48`). Maps the `profiles` facade (`Plugin.php:17-23`), registers three admin routes (`Plugin.php:29-33`) and three public routes under the resolved route prefix (`Plugin.php:37-41`), and registers the public stylesheet (`Plugin.php:43-47`).

**Data flow.** Any profile page first calls `findOrCreate($userId)`, which lazily inserts a bare row (timestamps only) when none exists (`Models/Profile.php:21-36`). Saves flow through `updateProfile()` → `findOrCreate()` → `updateFromArray()` with the whitelist (`Models/Profile.php:38-55`).

**Public tenant**. `show()` resolves the user via FlightShield's `findByCredentials(['username' => ...])`, 404s on miss, renders the theme `profile` template with `isOwner` and a normalized `avatar_url` (`Controllers/ProfilesPublicController.php:17-42`). `edit()` and `update()` render/redirect to the theme `profile_edit`.

**Admin tenant.** `index()` renders the shared `pubvana/profiles/admin/profile/index` view for the current user with the Media avatar picker; `show(@userId)` renders the same view for another user when permitted, seeding the `returnUrl` differently (`Controllers/ProfilesAdminController.php`).

**Gap to resolve.** `pubvana.json` declares `provides.admin.dashboard` cards and sections (`pubvana.json:7-12`), but `Plugin.php` registers no `admin.dashboard` adext items, so no card or section renders. `<!-- TODO: reconcile the pubvana.json dashboard declaration with the missing adext registration in Plugin.php -->`

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3, sees `app/` plus `scanDirectories: vendor/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Profiles -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] First visit to any own-profile path creates exactly one row; a second visit reuses it
  - [ ] Save all nine fields; confirm empty inputs store `null` and the whitelist rejects unknown keys
  - [ ] Avatar picker stores a path; the public page renders the avatar with exactly one leading slash
  - [ ] A logged-in non-owner hitting `/profile/{other}/edit` is redirected, not served
  - [ ] Editing another user from `/admin/profile/{id}` is blocked without `profile.edit.any` and allowed with it
  - [ ] Unknown usernames return a 404 (`halt`), not an empty page
  - [ ] `GET /assets/plugin/Profiles/css/profiles.css` returns the stylesheet
  - [ ] No dashboard card or section appears for Profiles (matches current code, despite the manifest declaration)
  - [ ] Deleting a user cascades the profile row (foreign key `CASCADE`)

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `\flight\ActiveRecord` and declare their table string in the constructor** (`Models/Profile.php:7-12`).
3. **Use `DateTimeImmutable` for all model timestamps** (`Models/Profile.php:28, 53`).
4. **Controllers strip `_csrf_token` (and `return_url`, where consumed) before calling the facade** (`Controllers/ProfilesAdminController.php:63-65`, `Controllers/ProfilesPublicController.php:79-80`).
5. **Views render the CSRF field with `csrf_field()` and escape every echoed value with `htmlspecialchars`** (`Views/admin/profile/index.php:17-18, 22-23`).
6. **User lookups go through the FlightShield `User` model, not raw queries** (`Controllers/ProfilesPublicController.php:87-91`).
7. **Prefer `findByUserId()`/`updateProfile()` over direct property assignment outside the model.** All row access flows through the model methods.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing docs: features, installation, usage, contributing |
| `Controllers/ProfilesPublicController.php:21-33, 44-56` | Owner-only edit rules and 404 handling |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a profile field | Migration (`2026-08-26-100001_CreateProfilesTable.php`) + `updateFromArray()` whitelist + admin view + theme template |
| Change the public route prefix | `Config/Config.php` (`routePrepend`) |
| Gate other-user editing differently | `Controllers/ProfilesAdminController.php:28-61` (`profile.edit.any` checks) |
| Change public profile markup | Active theme `profile`/`profile_edit` templates and `assets/css/profiles.css` |
| Wire real auth middleware | Replace `$authMiddleware = null` at `Plugin.php:26` keyed on `profile.edit`/`profile.edit.any` |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Whitelist, migration, and view stay in lockstep; no raw request data reaches the model
- [ ] `findOrCreate` still the only row-creation path; owner and `profile.edit.any` guards unchanged
- [ ] Public CSS path on disk matches the registered URL; `.pv-profile-*` classes untouched without a theme change
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Public display templates live in the active theme, not in this plugin; there is no `Views/public/` here.
- No hard profile delete; profiles disappear only through the `users` cascade delete.
- No external avatar sources (Gravatar, Uploadcare, etc.); the avatar is a stored image path picked via the Media plugin.
- No pagination, search, or discovery of profiles; a profile is reached by a known username.
- Known gaps to reconcile, not implemented here: the unvalidated `return_url` redirect at `Controllers/ProfilesAdminController.php:63-71` and the unimplemented dashboard declaration at `pubvana.json:7-12`.