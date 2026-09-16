# AGENTS.md — Marketplace plugin

Guidance for AI agents contributing to this plugin, the buy-side companion for the Pubvana Digital Store.

## Overview

**pubvana/marketplace** is the companion app for the store at pubvanacms.com. It never processes payments and never merges with the Digital Store plugin. The Marketplace browses the store catalog, pushes items to an account-bound cart, opens the store checkout in a new tab, then verifies and installs purchases on this site.

- **Package:** `pubvana/marketplace` (`pubvana.json:2`), semver `0.1.0`, category `commerce`
- **License:** MIT, matching the main project
- **PHP floor:** the main project requires PHP `^8.2`; code stays within it
- **Namespace:** `Pubvana\Plugins\Marketplace` with `Controllers`, `Services`, `Models`, and `Database\Seeds` sub-namespaces
- **Runtime dependencies:** Pubvana core (Engine, AdminController, PluginInterface, adext, settings, shield, sessions, CSRF), `enlivenapp/migrations`, curl with `file_get_contents` fallback
- **One database table:** `marketplace_installs`. It is internal bookkeeping only. No user-facing key list is rendered from it.
- **Config:** `Config/Config.php`: `routePrepend`, `store_url`, `api_timeout`, `catalog_cache_ttl`, `verify_days` (default 14), `revalidate_days`
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Two-plugin boundary is sacred.** Marketplace is a separate plugin from the Digital Store. Do not merge them, do not call into store models directly across the plugin boundary, and do not change store plugin files from here. The Marketplace talks to the store only over its HTTP API.
2. **One identity per addon: the package.** An addon IS its manifest pubvana.json identity (plugins: `name`, themes: `slug`, e.g. `pubvana/blog`, `default`). It is the lookup key between Marketplace and the Updates plugin (`installFromPackage()`, `checkAddonUpdates()`, `trackedPackages()`), keyed in `marketplace_installs.package_id` and stamped from the freshly installed manifest at install/verify time. Never match addons by install folder or `slug` separately across plugins; those are store-payload and destination details, not identity.
3. **The store catalog `slug` is the package.** Store rule: a catalog/purchase item's `slug` is the same string as the installed manifest `name`.
4. **No user-facing key entry.** The Marketplace never prompts the buyer to type a license key. Ownership is verified back at the store with the account token. `license_key` is stored in `marketplace_installs` for diagnostics only.
5. **Phone-home cadence is `verify_days`, not daily.** The 24h cron task calls `verifyIfDue()`, which enforces the cadence itself. Do not hit the store on every request or every cron tick. The connect screen must disclose periodic verification (~2 weeks).
6. **Validate every zip before extraction.** Reject entries with `..`, absolute paths, drive letters, or NUL bytes; only accept http/https download hosts on the store allow-list (`pubvanacms.com` apex or any subdomain, plus `localhost`/`plugindev` in development only). This is the same safety rule as the Updates plugin; do not weaken it.
7. **One install path.** `installFromPackage()` is the only public store-install entry for cross-plugin use (Updates addon-update calls it). No client-supplied download URLs reach the install flow; URLs are always re-derived server-side from the license.
8. **No payment integration here.** Checkout happens on pubvanacms.com. The Marketplace only opens the store checkout URL and verifies afterward.
9. **Single-site domain moves require email confirmation.** The transfer-request endpoint starts the flow; the store emails a confirm link. The Marketplace can prompt and request; it must not rebind on its own.
10. **Controllers strip `_csrf_token` before POST data use; permission gate is flash + redirect, never `halt()`.** Standard v3 patterns.
11. **HTTP always:** curl first, `file_get_contents` fallback, timeouts, non-empty user agent.

## Repository layout

```
plugins/Marketplace/
├── Plugin.php                            Entry point; maps 'marketplace' singleton, admin routes, dashboard card, Site Health check, 24h cron task
├── pubvana.json                          Manifest; admin.menu (Marketplace under tools)
├── README.md                             User-facing docs
├── Config/Config.php                     routePrepend, store_url, api_timeout, catalog_cache_ttl, verify_days, revalidate_days, max_bytes, max_zip_bytes
├── Controllers/
│   └── MarketplaceAdminController.php    Admin: index, connect, disconnect, purchases, verify, addToCart, install, installFree, reinstallAll, cartOpen
├── Services/
│   └── MarketplaceService.php            $app->marketplace(): catalog, cart, purchases, install, verification, domain moves
├── Models/
│   └── MarketplaceInstall.php            marketplace_installs table model
├── Database/
│   ├── Migrations/
│   │   ├── 2026-09-06-000001_CreateMarketplaceInstallsTable.php   marketplace_installs (store_product_id unique)
│   │   └── 2026-09-11-115828_AddPackageIdToMarketplaceInstalls.php  adds package_id
│   └── Seeds/Seed.php                    Seed: marketplace.manage permission
└── Views/admin/
    ├── index.php                         Connect screen, catalog, cart
    └── purchases.php                     Purchases and installs
```

No generated dirs in this plugin.

## Core architecture

### Store API surface (server-to-server, no CORS)

- `GET {store}/api/store/categories` - categories
- `GET {store}/api/store/items?currency=` - marketplace-listed items
- `GET {store}/api/store/free?slug=` - streams a free product's package (is_free or scope none); free items are free to use anywhere and update with no license
- `POST {store}/api/store/cart/add` - push item into account-bound cart
- `GET {store}/api/store/purchases?domain=` - owned products + license state for this domain
- `POST {store}/api/store/license/validate` - returns a download URL for a valid license
- `POST {store}/api/store/license/transfer-request` - begin a domain move
- `POST {store}/api/store/auth/token` - exchange email for account token

Auth is a `Marketplace.account_token` setting sent as an `Authorization: Bearer` header. It is never sent in the query string, and every outbound request and redirect hop is validated against the store host allow-list (http/https only, loopback/dev hosts in development only).

### Install flow

`install($storeProductId, $itemType)` loads the local install record by store product id, validates the license via the API for this domain, downloads the package (host-checked), inspects the zip, extracts under `writable/cache/marketplace`, moves the resolved root (single-wrapper-dir aware) into `plugins/{folder}/` or `themes/{folder}/`, then stamps the identity and version from the freshly installed manifest (`package_id`, `installed_version`).

`installFromPackage($package)` is the cross-plugin entry: finds the record by `package_id` and runs `install()`. A package with no purchase record (or no license key) falls to the free path `installFreePackage()`, which requires the store catalog to list it free (is_free or scope none), downloads through the store free endpoint, then records the install via `trackFreeInstall()` so later checks see it as a store item. The Updates plugin's addon-update action calls `installFromPackage()` and nothing inside Marketplace internals.

`checkAddonUpdates()` reads each installed addon's manifest identity live (manifest `name` + `semver`) and compares against catalog versions; results are keyed by package. `trackedPackages()` returns the package identities holding install records, so other surfaces (Updates) can label an addon Marketplace-sourced versus core-included. `unlicensedPackages()` returns catalog packages with no verified purchase record here, each flagged `free` (scope none: genuinely free items, disclosure-friendly) or not, for the Updates page's "Free" / "Not purchased" rows. Disclosure only, never enforcement. `freePackageVersions()` is the free counterpart to `checkAddonUpdates()`: latest store versions for free packages, so untracked free installs stay updatable for free. `refreshCatalog()` stamps the cached catalog stale so the next `items()` re-fetches; the Updates "Check all" button calls it.

`reinstallAll()` iterates purchases and reinstalls every licensed, non-`file` item already installed locally, reporting ok/skipped/failed counts.

`verifyIfDue()` / `purchases()` reconcile `marketplace_installs` from the store's answer for this domain, updating license validity, scope, expiry/renewal, and registered domain; `package_id` is backfilled from the local manifest when missing. `localInstallRecords()` serializes the table for the Purchases view.

### Connect / account

`connectAccount($email)` exchanges the email for an account token, stores `Marketplace.account_token`, `account_email`, `connected_at`, and `verify_disclosed_at`. `disconnectAccount()` blanks the token and email.

## Development and testing

```bash
php -l <touched files>                 # lint
composer phpstan                       # level 8, via composer
composer psalm                         # taint analysis
vendor/bin/phpunit                     # run the suite
php runway cron 24h                    # exercise the cron task (graceful when not due)
```

## Coding standards

- **PHPStan (level 8):** the `marketplace()` service has an `@phpstan-method` entry and a `MarketplaceService` shell in `phpstan-stubs.php`.
- `declare(strict_types=1);` first line in every class file.
- Docblock header on every file: `@package Pubvana\Plugins\Marketplace`.
- Namespace matches directory exactly.
- All HTTP uses curl first, `file_get_contents` fallback, both timeouts, non-empty user agent.
- Views escape everything with `htmlspecialchars`; admin URLs use the `/admin/marketplace/...` prefix (adext prepends `/admin` automatically for admin routes).
- New settings keys use the `Marketplace.` namespace through the settings store, not the config file.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | User-facing features and usage |
| [Config/Config.php](./Config/Config.php) | store_url, verify_days, revalidate_days, timeouts, size caps |
| [Services/MarketplaceService.php](./Services/MarketplaceService.php) | installFromPackage() at line 609, install() at line 755, checkAddonUpdates() at line 476, verifyIfDue() at line 1135 |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Change the store URL | `store_url` in `Config/Config.php` |
| Change the verification cadence | `verify_days` in `Config/Config.php`; enforced by `verifyIfDue()` at `Services/MarketplaceService.php:1135` |
| Change catalog cache lifetime | `catalog_cache_ttl` in `Config/Config.php` |
| Add an admin route | Admin route block in `Plugin.php` |
| Change the install flow | `install()` at `Services/MarketplaceService.php:755` |
| Change the cross-plugin install entry | `installFromPackage()` at `Services/MarketplaceService.php:609` |
| Change addon update checks | `checkAddonUpdates()` at `Services/MarketplaceService.php:476` |
| Change the dashboard card | `dashboardCards()` in `Plugin.php` |
| Change the Site Health check | `healthCheck()` in `Plugin.php` |

## PR / contribution checklist

- [ ] `php -l` clean on every touched file; `composer phpstan` and `composer psalm` clean
- [ ] `vendor/bin/phpunit` green
- [ ] Zip safety intact (traversal entries still rejected; host allow-list unchanged)
- [ ] Phone-home still cadence-gated (no per-request store hits)
- [ ] README updated if user-facing behavior or config changed

## Out of scope

- Payments, refunds, or gateway config (store's territory).
- The Digital Store plugin itself: don't modify it from here.
- License issuance and key generation (store-side).
- Domain transfer confirmation logic (store-side; Marketplace only requests).