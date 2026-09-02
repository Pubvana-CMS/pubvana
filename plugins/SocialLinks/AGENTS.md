# AGENTS.md — Social Links plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Social Links is the port of the v2 `widgets/SocialLinks` feature. It stores site-wide social profile links in one table, manages them from an admin screen under Settings, and renders them anywhere via a public block with self-hosted Font Awesome 7 Free icons.

- **Package:** `pubvana/social-links` (`pubvana.json:2`), semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`readonly`-free, nullable return `?SocialLink` at `Services/SocialLinksService.php:204`, arrow functions at `Plugin.php:45`)
- **Namespace:** `Pubvana\Plugins\SocialLinks` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base); Pubvana core classes `Pubvana\Controllers\Admin\AdminController`, `Pubvana\Services\PluginInterface`; core services `$app->db()`, `adext()`, `session()`, `request()`, `redirect()`
- **Config:** `Config/Config.php`: `default_target` (`_blank`), `link_rel` (`noopener noreferrer`), `fallback_label` (`Website`), `fallback_icon` (`fa-solid fa-link`), `block_title` (`Follow Us`)
- **Docs:** `README.md`

## Project guidelines

1. **Route all reads and writes through the `$app->socialLinks()` service facade** (`Plugin.php:32-39`). Controllers must not touch models directly. Reason: the service owns the platform catalog, URL validation, icon normalization, and sequential ordering.
2. **Known platforms derive their label and icon from the catalog; "custom" takes posted values.** `platformLabel()` and `platformIcon()` fall back to config defaults (`Services/SocialLinksService.php:151-165`). Reason: one canonical map keeps the admin dropdown, stored rows, and rendered icons in lockstep.
3. **Never widen the platform catalog without a published Font Awesome class.** Every icon in `PLATFORMS` is verified against the staged `assets/css/brands.min.css` (`Services/SocialLinksService.php:33-70`). Reason: FA7 splits brand marks into brands.min.css, so an unverified class renders a broken box. Known missing brands in FA7 Free (do not rely on them): `stackoverflow`, `nextdoor`, `buffers`.
4. **Validate and normalize URLs on write, never on render.** Bare domains get `https://` prepended, non-http(s) or hostless values are rejected (`Services/SocialLinksService.php:289-301`). Reason: the public template renders stored URLs unmodified and the link must be safe to emit.
5. **Keep the icon regex strict.** Custom icons must match `^fa-[a-z0-9]+( [a-z0-9-]+)*$` or be replaced with the fallback (`Services/SocialLinksService.php:315-320`). Reason: the class is echoed unescaped-safe but still must not allow arbitrary markup.
6. **Treat `sort_order` as authoritative and sequential.** New links get `count(all())`, and `move()` swaps then re-normalizes 0..n via `persistOrder()` (`Services/SocialLinksService.php:238-251, 274-282`). Reason: re-normalizing absorbs any column drift so display order matches admin order.
7. **Seed only the permission alias, do not gate in-controller.** `auth_permissions` seeds `social.manage` (`Database/Seeds/Seed.php:6-9`); `$authMiddleware = null` at `Plugin.php:40` is the development placeholder and no `can()` check is added. Reason: this matches the Redirects plugin and the core's disabled-auth state; gating is future middleware work.
8. **Keep the three public.css FA loads in priority order.** `fontawesome` (base), `brands`, `solid`, then the plugin sheet (`Plugin.php:66-89`). Reason: brands and solid depend on the base font classes; inversing the order breaks rendering of `.fa-brands`.
9. **Self-hosting means the staged files are part of the plugin.** Never swap to a CDN without updating both the CSP (`app/Middleware/SecurityHeadersMiddleware.php`) and this plugin's README. The current CSP (`style-src 'self'`, `font-src 'self'`) already permits `/assets/plugin/SocialLinks/...`.
10. **The block is icons-only.** The v2 "icons vs icons+text" style option is dropped because the region manager renders block options only as `repeater`, `textarea`, or text input (no select), so the block exposes a single `title` option (`Plugin.php:51-55`).
11. **No runtime auto-sharing.** v2's `SocialSharingService` (auto-post to X/Facebook on publish) is not part of this port; this plugin only displays links. OAuth (v2 `SocialAuth`) is likewise out of scope.

## Repository layout

```
plugins/SocialLinks/
├── Config/Config.php                     default_target, link_rel, fallback_label, fallback_icon, block_title
├── Controllers/SocialLinksAdminController.php  List, store, toggle, delete, reorder
├── Database/
│   ├── Migrations/2026-09-01-100001_CreateSocialLinksTable.php  social_links (is_active indexed)
│   └── Seeds/Seed.php                    Seed: social.manage permission
├── Models/SocialLink.php                 social_links table; allOrdered, activeOrdered, findById
├── Services/SocialLinksService.php       $app->socialLinks(): platform catalog, CRUD, ordering, block provider
├── Plugin.php                            Entry point; facade, admin routes, block, FA7 css registration
├── pubvana.json                          Manifest; admin.menu under settings, empty admin.dashboard
├── Views/
│   ├── admin/index.php                   Add form + list with reorder/toggle/delete actions
│   └── public/blocks/social-links.tpl    Public block template (Vision)
├── assets/
│   ├── css/                              fontawesome.min.css, brands.min.css, solid.min.css, social-links.css
│   ├── fontawesome/LICENSE.txt           FA7 Free license (fonts SIL OFL, icons CC BY 4.0, code MIT)
│   └── webfonts/                         fa-brands-400.woff2, fa-solid-900.woff2
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:23-99`). Maps the `socialLinks` service singleton (`Plugin.php:32-39`), registers five admin routes under the settings menu (`Plugin.php:42-50`) via `adext()->addRoutes('admin', ...)`, registers the `pubvana.social-links` block (`Plugin.php:53-59`), and registers FA7 base/brands/solid plus `social-links.css` on both `public.css` and `admin.css` (`Plugin.php:62-97`).

**Write path.** The admin controller strips `_csrf_token` and forwards POST data to the service (`Controllers/SocialLinksAdminController.php:28-47`). The service normalizes the platform key, validates the URL, picks label/icon (catalog or custom), assigns the next `sort_order`, and writes timestamps as `DateTimeImmutable` strings (`Services/SocialLinksService.php:177-216`).

**Read path.** `all()` feeds the admin list; `activeLinks()` feeds the block provider (`Services/SocialLinksService.php:174-193`). The block provider returns a plain template-ready array (`title`, `links`, `target`, `rel`), and the Vision template at `Views/public/blocks/social-links.tpl` renders anchors with escaped `aria-label` and framebusting `rel` attributes.

**Ordering.** `move($id, 'up'|'down')` swaps the link with its neighbor and calls `persistOrder()` to rewrite sequential `sort_order` values 0..n for rows that differ (`Services/SocialLinksService.php:232-282`).

**Extension points (adext).** One `block.available` (`pubvana.social-links`), a `public.css` group, and an `admin.css` group (`Plugin.php:53-97`). No public routes; the menu entry comes from `pubvana.json` (`provides.admin.menu.settings`).

## Development and testing

This plugin has no `composer.json` and no test suite, like other in-tree Pubvana plugins. It is exercised through the full app.

- Static analysis (the app standard): code is written to PHPStan **level 8**; the committed `phpstan.neon` gates pushes at **level 3**. Neither config analyses `plugins/`, so run the plugin against a throwaway level-8 config:
  - `vendor/bin/phpstan analyse --no-progress -c /tmp/opencode/phpstan-l8.neon` (level 8; mirror `phpstan.neon` paths/ignores but point `paths` at `plugins/SocialLinks` and `app/`)
  - `vendor/bin/phpstan analyse` (the repo's level-3 gate, from the root)
  - `find plugins/SocialLinks -name '*.php' -exec php -l {} \;``
- Manual verification checklist:
  - [ ] Add every catalog platform; each row stores the matching label and `.fa-brands` class
  - [ ] Add a custom link; label and icon class are honored; empty fields fall back to `Website` / `fa-solid fa-link`
  - [ ] Submit a bare domain (`x.com/user`); it stores as `https://x.com/user`; submit `javascript:...` or garbage; it is rejected with the flash error
  - [ ] Toggle a link off; it disappears from the block but remains in the admin list
  - [ ] Reorder up/down; first/last controls are disabled at the ends, and reordering survives a page reload
  - [ ] Place the Social Links block in a region; the rendered anchor carries `target="_blank" rel="noopener noreferrer"` and the FA icon renders
  - [ ] Confirm the title option defaults to `Follow Us` and honors a saved custom title
  - [ ] `GET /assets/plugin/SocialLinks/css/brands.min.css` and the two webfonts return 200
  - [ ] The admin list icons render (admin.css FA registration works)

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/SocialLink.php:15-20`).
3. **Keep the `@property` column docblocks in sync with the migrations** (`Models/SocialLink.php:7-17`).
4. **Pull fresh model instances through a private `model()` helper** (`Services/SocialLinksService.php:269-272`). Reason: a shared instance would hold query state across calls.
5. **Use `DateTimeImmutable` for every timestamp write** (`Services/SocialLinksService.php:190, 223, 277`).
6. **Controllers strip `_csrf_token` before forwarding POST data** (`Controllers/SocialLinksAdminController.php:30-31`).
7. **Views render the CSRF field with `csrf_field()` / `csrf_token()` and escape echoed values with `htmlspecialchars`** (`Views/admin/index.php`).
8. **Keep block providers returning plain template-ready arrays so templates stay dumb** (title + items + target/rel). The template never computes logic.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing docs: install, admin usage, block placement, catalog, config, license |
| `Services/SocialLinksService.php:33-70` | The authoritative platform/icon catalog |
| `Config/Config.php` | Defaults for labels, icons, target, rel, title |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add or change an admin route | `Plugin.php:42-50` |
| Add a platform | `PLATFORMS` map in `Services/SocialLinksService.php` (verify the icon in `brands.min.css` first) |
| Change link rendering defaults | `Config/Config.php` (`default_target`, `link_rel`, `fallback_*`, `block_title`) |
| Change blocks' visual style | `assets/css/social-links.css` and `Views/public/blocks/social-links.tpl` |
| Add a permission alias | `Database/Seeds/Seed.php` install rows (`auth_permissions`) |
| Change the block title default | `options.title.default` at `Plugin.php:54` and `block_title` config |
| Upgrade Font Awesome | Re-stage the Free web kit into `assets/` (`css/*`, `webfonts/*`, `LICENSE.txt`) and re-verify the catalog |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`); the plugin is level-8 clean via the throwaway config (the core interface's untyped `Engine` parameter in `register()` is the one accepted exemption, shared by every plugin)
- [ ] URL normalization on write only; catalog and stored rows in lockstep; new brands verified against `brands.min.css`
- [ ] `sort_order` stays sequential after any reorder; `persistOrder()` still normalizes
- [ ] `social.manage` seeded once; no premature `can()` gate added while middleware is disabled
- [ ] FA css load order preserved (base, brands, solid, plugin sheet) on both public and admin
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- Not social auth (OAuth flows) and not auto-sharing on publish; this plugin only stores and displays links.
- No per-user social links; profiles already handle user-scoped handles via Profiles.
- No icon upload or custom SVG sources; icons are Font Awesome 7 Free class names.
- No translations; labels are hardcoded English.
- Auth middleware is disabled for development (`Plugin.php:40`); enforcement against `social.manage` is future work.
