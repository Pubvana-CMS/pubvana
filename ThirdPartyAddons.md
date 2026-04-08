# Third-Party Addon Developer Guide

This guide covers everything you need to distribute themes, widgets, and plugins for Pubvana CMS. It covers the `*_info.json` files, the API protocols your store needs to implement, and how the built-in DigitalStore (DStore) plugin handles all of this out of the box.

For building the addon itself, see:
- [PluginBuilder.md](PluginBuilder.md) - Plugin development
- [ThemeBuilder.md](ThemeBuilder.md) - Theme development
- [WidgetBuilder.md](WidgetBuilder.md) - Widget development

---

## 1. The `*_info.json` Files

Every addon has an info file in its root directory:

| Addon Type | File | Location |
|------------|------|----------|
| Plugin | `plugin_info.json` | `plugins/{PluginName}/` |
| Theme | `theme_info.json` | `themes/{theme_name}/` |
| Widget | `widget_info.json` | `widgets/{WidgetName}/` |

All three types share a common set of fields. Themes and widgets add type-specific fields on top (see their respective Builder docs).

---

## 2. Common Fields (All Addon Types)

### Required

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Human-readable addon name. |
| `version` | string | Semver format (e.g. `1.0.0`). Version changes reset vetting status. |
| `description` | string | One-line summary, max 255 characters. |
| `author` | string | Author name. Normalized to lowercase with underscores for vetting lookups (e.g. "My Company" becomes `my_company`). |
| `author_url` | string | `""` | Your website URL (linked in admin). |
| `support_url` | string | `""` | Support/contact URL (shown in admin when addon has issues). |
| `free` | boolean | `false` | `true` = free addon, activates without a license check. `false` = paid, requires a valid license. |
| `bundled` | boolean | `false` | **Reserved for Pubvana-authored addons.** Do not set to `true`. |
| `min_pubvana_version` | string | `""` | Minimum compatible Pubvana version (e.g. `"2.2.3"`). |
| `max_pubvana_version` | string | `""` | Maximum compatible Pubvana version (e.g. `"2.2.15"`). |

### Store & License Fields

These fields tell Pubvana CMS how to interact with your store for updates, licensing, and product resolution. If your addon is free and you don't provide updates, you can leave all of these empty.

| Field | Method | When Needed | Description |
|-------|--------|-------------|-------------|
| `update_url` | POST | If you push updates | Update check endpoint. Without this, your addon cannot be updated through Pubvana CMS. |
| `license_validate_url` | POST | If paid | Called when a site admin enters a license key. |
| `license_check_url` | POST | If paid | Periodic license revalidation (90-day cycle). |
| `item_url` | GET | If paid | Product ID resolution (`{item_url}/{folder}`). |
| `store_url` | -- | If paid | Storefront page for this addon (linked in admin for license renewal). |
| `items_url` | GET | Optional | Catalog listing endpoint. |
| `categories_url` | GET | Optional | Category listing endpoint. |
| `categories_all_url` | GET | Optional | Full category listing with products. |
| `category_url` | GET | Optional | Single category endpoint. |
| `featured_url` | GET | Optional | Featured products endpoint. |
| `update_check_url` | POST | Optional | Alternative update check endpoint if different from `update_url`. |
| `download_url` | POST | Optional | Direct download endpoint. |

---

## 3. What You Need Based on Your Addon Type

### Free addon (no license)

Set `"free": true` in your `*_info.json`. Optionally provide `update_url` if you want site admins to receive updates through the admin UI.

```json
{
    "name": "My Widget",
    "version": "1.0.0",
    "description": "A useful free widget.",
    "author": "Your Name",
    "free": true,
    "update_url": "https://yoursite.com/api/v1/update/check"
}
```

### Paid addon (with licensing)

Set `"free": false`. Provide the license and update endpoints so Pubvana CMS can validate purchases and deliver updates.

```json
{
    "name": "My Premium Plugin",
    "version": "1.0.0",
    "description": "A premium plugin with license validation.",
    "author": "Your Name",
    "free": false,
    "update_url": "https://yoursite.com/api/v1/update/check",
    "license_validate_url": "https://yoursite.com/api/v1/license/validate",
    "license_check_url": "https://yoursite.com/api/v1/license/check",
    "item_url": "https://yoursite.com/api/v1/item",
    "store_url": "https://yoursite.com/products/my-premium-plugin",
    "support_url": "https://yoursite.com/support",
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15"
}
```

---

## 4. API Protocol Specification

Pubvana CMS communicates with your store through a set of HTTP endpoints. All request and response bodies are JSON. Your endpoints must return proper HTTP status codes (200 for success).

### 4.1 Update Check

**When:** Pubvana CMS checks for updates on a schedule and when the admin visits the Updates page.

**Method:** `POST` to your `update_url`

**Request headers:**
- `Content-Type: application/json`
- `User-Agent: Pubvana-CMS/{version}`

**Request body:**

```json
{
    "pubvana_version": "2.2.10",
    "extensions": [
        {
            "product_id": 42,
            "type": "plugin",
            "version": "1.0.0",
            "license_key": "abc123-def456"
        }
    ]
}
```

The `extensions` array may contain multiple addons if you host more than one -- Pubvana CMS batches all addons sharing the same `update_url` into a single request. `license_key` is `null` for free addons.

**Expected response (200 OK):**

```json
{
    "updates": [
        {
            "product_id": 42,
            "type": "plugin",
            "latest_version": "1.2.0",
            "download_url": "https://yoursite.com/api/v1/download/42",
            "changelog": "Bug fixes and performance improvements."
        }
    ],
    "no_update": [43, 44],
    "incompatible": [
        {
            "product_id": 45,
            "type": "widget"
        }
    ],
    "errors": {
        "46": "License expired"
    }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `updates` | array | Addons with available updates. Each must have `product_id`, `type`, `latest_version`, and `download_url`. `changelog` is optional. |
| `no_update` | array | Flat array of `product_id` integers that are already up to date. |
| `incompatible` | array | Addons incompatible with this Pubvana version. Objects with `product_id` and `type`. |
| `errors` | object | Product IDs (as string keys) mapped to error messages. |

All four keys are optional -- include only what applies.

### 4.2 Download

**When:** After an update check returns a `download_url`, Pubvana CMS attempts to downloads the update.

**Method:** `POST` to the `download_url` from the update check response

**Request body:**

```json
{
    "license_key": "abc123-def456",
    "pubvana_version": "2.2.10"
}
```

**Expected response:** HTTP 200 with `Content-Type: application/zip` -- the response body is the ZIP file. Any non-ZIP content type or non-200 status is treated as a failure.

**ZIP structure:** The ZIP must contain a single top-level folder matching the addon's directory name, with the full addon contents inside. Path traversal entries (e.g. `../`) are rejected.

### 4.3 License Validation

**When:** A site admin enters a license key for your addon in the admin panel.

**Method:** `POST` to your `license_validate_url`

**Request body:**

```json
{
    "license_key": "abc123-def456",
    "domain": "https://customer-site.com/",
    "product_id": 42
}
```

**Expected response (200 OK):**

```json
{
    "valid": true
}
```

On failure:

```json
{
    "valid": false,
    "error": "License key not found or inactive."
}
```

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `valid` | boolean | yes | Whether the license key is valid for this product and domain. |
| `error` | string | if invalid | Human-readable error message shown to the site admin. |

If `valid` is `false` or missing, Pubvana CMS rejects the license and displays the `error` message.

### 4.4 License Check (Revalidation)

**When:** Pubvana CMS periodically revalidates licenses (every 90 days by default). This is a read-only check -- it should not bind domains or have side effects.

**Method:** `POST` to your `license_check_url`

**Request body:**

```json
{
    "key": "abc123-def456",
    "product_id": 42,
    "domain": "https://customer-site.com/"
}
```

The `domain` is always sent. If your product requires domain locking, verify it matches the registered domain. If not, you can ignore it.

**Expected response (200 OK):**

```json
{
    "valid": true,
    "expires_at": "2027-01-01 00:00:00",
    "is_subscription": true,
    "subscription_renews_at": "2026-07-01 00:00:00",
    "registered_domain": "https://customer-site.com/"
}
```

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `valid` | boolean | yes | Whether the license is still valid. |
| `expires_at` | string | no | License expiration date (`Y-m-d H:i:s`). Stored for display. |
| `is_subscription` | boolean | no | Whether this is a recurring subscription license. |
| `subscription_renews_at` | string | no | Next renewal date for subscriptions. |
| `registered_domain` | string | no | The domain this key is registered to. |

If `valid` is `false`, Pubvana CMS deactivates the addon (themes fall back to the default theme). If your endpoint is unreachable or returns a non-200 status, Pubvana CMS marks it as unreachable but does **not** deactivate -- network errors are not treated as license failures.

### 4.5 Product ID Resolution

**When:** Pubvana CMS resolves numeric product IDs for addons that don't have one stored yet.

**Method:** `GET` to `{item_url}/{folder}`

Example: `GET https://yoursite.com/api/v1/item/my_premium_plugin`

**Expected response (200 OK):**

```json
{
    "id": 42
}
```

Pubvana CMS stores this `id` as the `store_product_id` and uses it in all subsequent API calls.

---

## 5. The DStore Plugin -- Reference Implementation

Pubvana's own **Digital Store** (DStore) plugin is a full e-commerce system that implements every API endpoint described above. It powers the Pubvana Marketplace at `pubvana.net` and handles:

- Product catalog with categories
- License key generation, validation, and domain binding
- Key generation/revocation system for product releases
- Update checks and ZIP downloads
- Subscription and one-time purchase support
- Multiple payment gateways (Stripe, PayPal, Square, Mollie)

### Using DStore for your own store

If you want to sell Pubvana addons, you can run your own Pubvana site with the DStore plugin. It provides all the API endpoints out of the box:

| Endpoint | DStore Route |
|----------|-------------|
| Update check | `POST /api/dstore/v1/update/check` |
| Download | `POST /api/dstore/v1/download/{productId}` |
| License validate | `POST /api/dstore/v1/license/validate` |
| License check | `POST /api/dstore/v1/license/check` |
| Product lookup | `GET /api/dstore/v1/item/{id_or_folder}` |
| Catalog | `GET /api/dstore/v1/items` |
| Categories | `GET /api/dstore/v1/categories` |
| All categories | `GET /api/dstore/v1/categories/all` |
| Single category | `GET /api/dstore/v1/categories/{slug}` |
| Featured | `GET /api/dstore/v1/featured` |
| Cart summary | `GET /api/dstore/v1/cart` |

Point your addon's `*_info.json` URL fields to your DStore instance and everything works automatically. For example, if your store is at `https://mystore.com`:

```json
{
    "update_url": "https://mystore.com/api/dstore/v1/update/check",
    "license_validate_url": "https://mystore.com/api/dstore/v1/license/validate",
    "license_check_url": "https://mystore.com/api/dstore/v1/license/check",
    "item_url": "https://mystore.com/api/dstore/v1/item",
    "store_url": "https://mystore.com/dstore/product/my-addon"
}
```

### Building your own compatible API

If you prefer to use your own store platform, implement the endpoints described in Section 4. Pubvana CMS doesn't care what technology serves the responses -- it only cares about the JSON shapes and HTTP status codes.

---

## 6. Vetting

Pubvana maintains a vetting registry that allows site admins to see whether an addon has been reviewed.

### Statuses

| Status | Badge | Meaning |
|--------|-------|---------|
| `unknown` | Gray | Not yet submitted or pending review. |
| `safe` | Green | Reviewed and marked safe by Pubvana. |
| `known` | Green + yellow warning | Recognized but with caveats (a yellow warning note is shown alongside the green badge). |
| `malicious` | Red | Flagged as harmful. |

### How it works

When Pubvana CMS discovers a new addon (or a version change), it sends the addon's folder name, `version`, and `author` to the Pubvana vetting service. The response sets the addon's status.

- **Version changes reset vetting to `unknown`.** A v1.0.0 marked safe does not carry over to v1.1.0.
- Non-vetted **plugins** require the admin to confirm activation via a security warning modal. Themes and widgets do not have this gate.
- The `author` field is normalized (lowercased, spaces to underscores) for lookup. Use a consistent author name across all your addons.

### Submitting for vetting

Submit your addon (free or paid) at **pubvana.net/vetted/submit** with a ZIP containing a valid `*_info.json`. The Pubvana team reviews the code and notifies you by email. Each new version must be resubmitted. Your addon is deleted after evaluation.

---

## 7. Distribution

### Free addons

Set `"free": true` in your `*_info.json`. Distribute the ZIP to your users. They install by dropping the folder into the appropriate directory (`plugins/`, `themes/`, or `widgets/`). Submit for vetting to get the "Safe" badge.

### Paid addons

Sell independently through your own store (or a DStore instance) and provide the ZIP to your customers. Site admins install by dropping the folder into the appropriate directory and letting Pubvana CMS discover it.

After installation, the admin enters the license key in the addon's admin page. Pubvana CMS validates it against your `license_validate_url`.

---

## 8. Version Compatibility

Always set `min_pubvana_version` and `max_pubvana_version` in your `*_info.json`. Pubvana CMS uses these to:

- Prevent core Pubvana automated updates that would break installed addons
- Show compatibility warnings in the admin UI
- Find the correct addon release version during update checks

When releasing a new version of your addon, bump `max_pubvana_version` to the latest Pubvana version you've tested against.

---

## 9. CLI Commands & Cron (Plugins Only)

Plugins can ship spark CLI commands by including a `Commands/` directory with classes extending `CodeIgniter\CLI\BaseCommand`. These are auto-discovered when the plugin is active -- no configuration needed. Prefix command names with your plugin slug to avoid collisions (e.g. `dstore:cleanup`, `myplugin:sync`).

To run commands on a schedule, add a `cron` key to `plugin_info.json`:

```json
{
    "cron": {
        "minute": ["dstore:expire-carts"],
        "quarterday": ["dstore:sync-inventory"],
        "daily": ["dstore:cleanup"]
    }
}
```

| Frequency | Schedule |
|-----------|----------|
| `minute` | Every minute |
| `quarterday` | Every 6 hours |
| `daily` | Once per day |

Only include the frequencies you need. The `cron` key is optional. See [PluginBuilder.md](PluginBuilder.md) Section 7.1 for full details. This feature is only available to plugins -- themes and widgets cannot ship commands.

---

## 10. Quick Reference -- Minimum `*_info.json` Examples

### Free plugin (no updates)

```json
{
    "name": "My Plugin",
    "version": "1.0.0",
    "description": "Does something useful.",
    "author": "Your Name",
    "author_url": "https://yoursite.com",
    "support_url": "https://yoursite.com/support",
    "free": true
}
```

### Free plugin (with updates)

```json
{
    "name": "My Plugin",
    "version": "1.0.0",
    "description": "Does something useful.",
    "author": "Your Name",
    "author_url": "https://yoursite.com",
    "support_url": "https://yoursite.com/support",
    "free": true,
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
    "update_url": "https://yoursite.com/api/v1/update/check"
}
```

### Paid plugin (full licensing)

```json
{
    "name": "My Premium Plugin",
    "version": "1.0.0",
    "description": "A premium plugin.",
    "author": "Your Name",
    "free": false,
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
    "update_url": "https://yoursite.com/api/v1/update/check",
    "license_validate_url": "https://yoursite.com/api/v1/license/validate",
    "license_check_url": "https://yoursite.com/api/v1/license/check",
    "item_url": "https://yoursite.com/api/v1/item",
    "store_url": "https://yoursite.com/products/my-premium-plugin",
    "support_url": "https://yoursite.com/support",
    "author_url": "https://yoursite.com"
}
```

The same field patterns apply to themes and widgets -- only the type-specific fields differ (see their Builder docs).
