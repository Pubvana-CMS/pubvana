# PluginBuilder - Pubvana Plugin Developer Guide

This document covers everything needed to build a Pubvana plugin. It is self-contained - no other documents are required.

---

## 1. Plugin Directory Structure

Each plugin lives in its own folder under `plugins/`. Plugins are full PHP packages - controllers, models, migrations, views, and assets are all allowed.

```
plugins/DigitalStore/
    Plugin.php                      # Entry point - implements PluginInterface (REQUIRED)
    plugin_info.json                # Required — metadata, licensing, update URLs
    Installer.php                   # Filesystem setup - up() and down() (optional)
    Config/
        Routes.php                  # Route definitions (auto-loaded when plugin is active)
    Controllers/
        Store.php                   # Public-facing controllers
        Admin/
            Dashboard.php           # Admin controllers
            Products.php
    Models/
        ProductModel.php
    Services/
        CartService.php
    Database/
        Migrations/
            2026-01-01-000001_CreateProductsTable.php
        Seeds/
            DefaultSettings.php     # Auto-run on activation (after migrations)
    Views/
        store/
            index.tpl               # Public views rendered inside active theme
            product.tpl
        admin/
            dashboard.php           # Admin views use SB Admin 2 layout (plain PHP)
            products/
                index.php
                create.php
    Language/
        en/
            DigitalStore.php        # Language strings
        es/
            DigitalStore.php
    Api/
        Store.php                   # API controllers
    Commands/
        CleanupExpired.php          # Spark CLI commands (optional, auto-discovered)
    assets/
        css/
        js/
```

**Naming conventions:**
- Plugin folder: PascalCase (e.g. `DigitalStore`, `KnowledgeBase`)
- The folder name IS the namespace suffix: `plugins/DigitalStore/` → `Plugins\DigitalStore\`

---

## 2. plugin_info.json

Every plugin must have a `plugin_info.json` in its root. The first five fields are **required**. Plugins with missing required fields are rejected during discovery - the admin sees an error listing which fields are missing.

```json
{
    "name":        "Digital Store",
    "version":     "1.0.0",
    "description": "A full digital storefront for selling themes, plugins, and digital products.",
    "author":      "Pubvana Team",
    "author_url":  "https://pubvana.net",
    "support_url": "https://pubvana.net/contact",
    "free":        false,
    "bundled":     false,
    "min_pubvana_version": "2.2.3",
    "max_pubvana_version": "2.2.15",
    "update_url":  "https://pubvana.net/api/dstore/v1/update/check"
}
```

### Required Fields

| Field | Type | Rules |
|-------|------|-------|
| `name` | string | Human-readable plugin name. Shown in Admin → Plugins. |
| `version` | string | Semver format (e.g. `1.0.0`, `2.1.3`). When this changes, the plugin's Pubvana approval status is reset and must be re-verified. |
| `description` | string | One-line summary. Shown in the admin plugin list under the name. Max 255 characters. |
| `author` | string | Plugin author name. |

### Optional Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `author_url` | string | `""` | Author's website URL (linked in admin). |
| `support_url` | string | `""` | Support/contact URL shown in admin when the plugin is incompatible or has issues. |
| `free` | boolean | `false` | Set to `true` if your plugin is free. Free third-party plugins activate without a license check. |
| `bundled` | boolean | `false` | Reserved for Pubvana-authored addons that ship with the CMS. Third-party plugins must not set this to `true`. |
| `min_pubvana_version` | string | `""` | Minimum Pubvana version required. |
| `max_pubvana_version` | string | `""` | Maximum Pubvana version this plugin is compatible with. Used by the update system to prevent incompatible core updates. |
| `update_url` | string | `""` | API endpoint the CMS POSTs to when checking for updates. Plugins without this field cannot be updated through the admin UI. |

### Third-Party Store & License Fields

These fields are only relevant if you sell your plugin and run your own store/license API. See [ThirdPartyAddons.md](ThirdPartyAddons.md) for the full API protocol specification.

| Field | Type | Description |
|-------|------|-------------|
| `license_validate_url` | string | Endpoint the CMS POSTs to when a site admin enters a license key for your plugin. |
| `license_check_url` | string | Endpoint the CMS POSTs to for periodic license revalidation (90-day cycle). |
| `item_url` | string | Endpoint the CMS GETs to resolve your plugin's numeric product ID (`{item_url}/{folder}`). |
| `store_url` | string | URL to your storefront page for this plugin (linked in admin for license renewal). |
| `items_url` | string | Catalog listing endpoint (used by marketplace integrations). |
| `categories_url` | string | Category listing endpoint. |
| `categories_all_url` | string | Full category listing with products. |
| `category_url` | string | Single category endpoint. |
| `featured_url` | string | Featured products endpoint. |
| `update_check_url` | string | Alternative update check endpoint (if different from `update_url`). |
| `download_url` | string | Direct download endpoint for updates. |

### Capabilities

Declare platform capabilities your plugin provides. The system reads this at discovery time and stores it in the database.

```json
"capabilities": {
    "email": ["self"]
}
```

| Value | Meaning |
|-------|---------|
| `"self"` | Plugin sends its own emails (order confirmations, notifications, etc.) |
| `"core"` | Plugin can also handle core system emails (contact forms, password resets, etc.) |

A plugin that only sends its own emails uses `["self"]`. A plugin that can also take over all outbound email (e.g. a Mailchimp or SendGrid integration) uses `["core", "self"]`. See **Section 7.2** for the full Email Provider implementation guide.

Omit `capabilities` entirely if your plugin has no special platform integrations.

### CLI / Cron Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `cron` | object | `{}` | Maps frequencies (`minute`, `quarterday`, `daily`) to arrays of spark command names to run on that schedule. See Section 7.1. |

> **For most third-party plugins:** You only need `free`, `update_url`, `license_validate_url`, `license_check_url`, `item_url`, and `store_url`. The remaining URL fields are for full marketplace integrations. If your plugin is free, only `free: true` and optionally `update_url` are needed.

---

## 3. Plugin.php - The Entry Point

Your `Plugin.php` must implement `App\Interfaces\PluginInterface`. This is the only file the `PluginManager` directly loads - everything else is discovered through it.

```php
<?php

namespace Plugins\DigitalStore;

use App\Interfaces\PluginInterface;

class Plugin implements PluginInterface
{
    public function getName(): string
    {
        return 'Digital Store';
    }

    public function getSlug(): string
    {
        return 'digitalstore';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getMenuItems(): array
    {
        return [
            'label'    => 'Digital Store',
            'icon'     => 'fas fa-store',
            'children' => [
                ['label' => 'Dashboard',  'url' => '/dstore/admin',            'nav_key' => 'dstore_dashboard'],
                ['label' => 'Products',   'url' => '/dstore/admin/products',   'nav_key' => 'dstore_products'],
                ['label' => 'Categories', 'url' => '/dstore/admin/categories', 'nav_key' => 'dstore_categories'],
                ['label' => 'Orders',     'url' => '/dstore/admin/orders',     'nav_key' => 'dstore_orders'],
                ['label' => 'Licenses',   'url' => '/dstore/admin/licenses',   'nav_key' => 'dstore_licenses'],
                ['label' => 'Settings',   'url' => '/dstore/admin/settings',   'nav_key' => 'dstore_settings'],
            ],
        ];
    }

    public function getPublicRoutes(): array
    {
        return [
            ['label' => lang('DigitalStore.store'),      'url' => '/dstore'],
            ['label' => lang('DigitalStore.categories'), 'url' => '/dstore/categories'],
            ['label' => lang('DigitalStore.cart'),        'url' => '/dstore/cart'],
        ];
    }

    public function getCsrfExemptions(): array
    {
        return [
            'dstore/webhooks/*',
        ];
    }

    public function register(): void
    {
        // Runs once per request when the plugin is active.
        // Use this to register event listeners, helpers, or services.
        helper('digitalstore');
    }
}
```

---

## 4. PluginInterface - Method Reference

### `getName(): string`

Returns the human-readable plugin name. Shown in the admin sidebar and the Plugins management page.

### `getSlug(): string`

Returns the URL-safe plugin identifier. Must match the `slug` in `plugin_info.json`. Used as the database key and for API calls.

### `getVersion(): string`

Returns the current version string. Must match the `version` in `plugin_info.json`.

### `getMenuItems(): array`

Returns the admin sidebar section for this plugin. Each plugin gets its own collapsible section in the sidebar, rendered below the core admin sections (Content, Appearance, Site, etc.).

Return an **empty array** if the plugin has no admin pages.

**Structure:**

```php
return [
    'label'    => 'Digital Store',       // Sidebar section heading
    'icon'     => 'fas fa-store',        // Font Awesome 5 icon class (include full prefix)
    'children' => [                      // Links shown in the collapsible popout
        [
            'label'   => 'Dashboard',
            'url'     => '/dstore/admin',
            'nav_key' => 'dstore_dashboard',
        ],
        [
            'label'   => 'Products',
            'url'     => '/dstore/admin/products',
            'nav_key' => 'dstore_products',
        ],
    ],
];
```

**How it renders:**

The admin sidebar uses SB Admin 2's accordion pattern. Your plugin gets a top-level `<li>` with the icon and label. Clicking it toggles a collapsible panel listing the children as links. This is the same pattern used by the core sections (Content, Appearance, Site, Tools).

**`nav_key` - active state highlighting:**

Each child must have a unique `nav_key` string. When your admin controller renders a page, pass this key as `active_nav` in the view data:

```php
return view('Plugins\DigitalStore\Views\admin\products\index', array_merge(
    $this->baseData('Products - Digital Store', 'dstore_products'),
    ['products' => $products]
));
```

The sidebar checks the current `active_nav` value against each child's `nav_key`. The matching child link gets the `active` class, and the parent section stays open. If none match, the section is collapsed.

**`nav_key` naming convention:** Prefix with your plugin slug to avoid collisions with core nav keys and other plugins. E.g. `dstore_dashboard`, `dstore_products`, not just `dashboard`, `products`.

### `getPublicRoutes(): array`

Returns public-facing routes for the Navigation admin "Quick Add" dropdown. Each entry has a `label` and `url`. Labels should use `lang()` for i18n support. Return an empty array if the plugin has no public pages.

```php
public function getPublicRoutes(): array
{
    return [
        ['label' => lang('DigitalStore.store'), 'url' => '/dstore'],
        ['label' => lang('DigitalStore.cart'),  'url' => '/dstore/cart'],
    ];
}
```

For dynamic routes (e.g., store categories), query the database and build entries on the fly. Wrap in try/catch in case the table doesn't exist yet during activation.

### `getCsrfExemptions(): array`

Returns an array of URI patterns that should be exempt from CSRF protection.

**When to use this:** Any endpoint in your plugin that receives POST requests from external services - payment gateway webhooks (Stripe, PayPal), third-party callbacks, or machine-to-machine API calls where the caller cannot include a CSRF token.

**When NOT to use this:** Do not exempt your admin form endpoints. Admin pages should use CSRF tokens via `<?= csrf_field() ?>` in forms. Do not exempt your public storefront forms. Only exempt endpoints where the POST originates from outside the browser session.

```php
public function getCsrfExemptions(): array
{
    return [
        'dstore/webhooks/*',           // All webhook routes
        'api/dstore/v1/some/endpoint', // A specific API endpoint
    ];
}
```

Patterns use CI4's URI matching - `*` matches any segment(s). The `PluginManager` collects exemptions from all active plugins at boot time and injects them into the CSRF filter config automatically. You do not edit `Filters.php`.

Return an **empty array** if your plugin has no external-facing POST endpoints.

Note: Routes under `api/*` are already globally CSRF-exempt. You only need to list non-API routes here (e.g. webhook URLs that don't live under `/api/`).

### `register(): void`

Called once per request when the plugin is active. This runs at the `pre_system` event - very early in the CI4 lifecycle, before routing.

**Use this for:**
- Loading custom helpers: `helper('digitalstore');`
- Registering event listeners: `Events::on('post_published', [MyListener::class, 'handle']);`
- Registering services or shared instances
- Registering as an email provider (if your plugin declares `"core"` capability — see Section 7.2)

**Do NOT use this for:**
- Database queries (the request hasn't been routed yet)
- Anything that depends on the current request/route
- Heavy initialization - this runs on every request while the plugin is active

---

## 5. Namespace & Autoloading

`PluginManager` registers a PSR-4 namespace for each active plugin at the `pre_system` event (web requests) and via `PluginManager::cliBoot()` for CLI/spark commands:

```
Plugins\DigitalStore\  →  plugins/DigitalStore/
```

This means all classes inside your plugin folder are autoloaded by CI4's autoloader. You do **not** need to modify `composer.json`, `app/Config/Autoload.php`, or any other config file.

**Class resolution examples:**

| File path | Fully qualified class name |
|-----------|---------------------------|
| `plugins/DigitalStore/Plugin.php` | `Plugins\DigitalStore\Plugin` |
| `plugins/DigitalStore/Controllers/Store.php` | `Plugins\DigitalStore\Controllers\Store` |
| `plugins/DigitalStore/Controllers/Admin/Products.php` | `Plugins\DigitalStore\Controllers\Admin\Products` |
| `plugins/DigitalStore/Models/ProductModel.php` | `Plugins\DigitalStore\Models\ProductModel` |
| `plugins/DigitalStore/Services/CartService.php` | `Plugins\DigitalStore\Services\CartService` |
| `plugins/DigitalStore/Api/Store.php` | `Plugins\DigitalStore\Api\Store` |
| `plugins/DigitalStore/Commands/CleanupExpired.php` | `Plugins\DigitalStore\Commands\CleanupExpired` |

Your plugin has full access to all CI4 services, helpers, and libraries: `service()`, `model()`, `helper()`, `db_connect()`, `config()`, `lang()`, `cache()`, `session()`, etc.

---

## 6. Routes

Define routes in `Config/Routes.php` at your plugin root. This file is auto-loaded when the plugin is active. It receives the standard CI4 `$routes` instance - the same one used by the core `app/Config/Routes.php`.

### API Routes

API endpoints must follow the pattern `/api/{plugin_slug}/v1/`:

```php
<?php

// Public API - read-only, no auth required
// All routes under api/* are globally CSRF-exempt
$routes->group('api/dstore/v1', ['namespace' => 'Plugins\DigitalStore\Api'], static function ($routes) {
    $routes->get('items',              'Store::items');
    $routes->get('items/(:segment)',   'Store::item/$1');
    $routes->get('categories',         'Store::categories');
    $routes->get('featured',           'Store::featured');
    $routes->post('license/validate',  'License::validateKey');
});
```

This pattern ensures:
- All API traffic is under `/api/` which is globally CSRF-exempt
- Each plugin has its own versioned namespace - no collisions
- Version bumps (`v2/`) can coexist with `v1/`

### Admin Routes

Admin routes must use the `admin_auth` and `totp` filters:

```php
// Admin pages - behind admin auth + 2FA
$routes->group('dstore/admin', [
    'namespace' => 'Plugins\DigitalStore\Controllers\Admin',
    'filter'    => ['admin_auth', 'totp'],
], static function ($routes) {
    $routes->get('/',              'Dashboard::index');
    $routes->get('products',       'Products::index');
    $routes->get('products/new',   'Products::create');
    $routes->post('products',      'Products::store');
    $routes->get('products/(:num)/edit', 'Products::edit/$1');
    $routes->post('products/(:num)',     'Products::update/$1');
    $routes->get('orders',         'Orders::index');
    $routes->get('orders/(:num)',  'Orders::show/$1');
    $routes->get('licenses',       'Licenses::index');
    $routes->get('settings',       'Settings::index');
    $routes->post('settings',      'Settings::save');
});
```

### Public Routes

Public pages that render inside the active theme:

```php
// Public storefront - no auth required
$routes->group('dstore', ['namespace' => 'Plugins\DigitalStore\Controllers'], static function ($routes) {
    $routes->get('/',                       'Store::index');
    $routes->get('category/(:segment)',     'Store::category/$1');
    $routes->get('product/(:segment)',      'Store::product/$1');
    $routes->get('cart',                    'Cart::index');
    $routes->post('cart/add',               'Cart::add');
    $routes->post('cart/remove',            'Cart::remove');
    $routes->post('checkout',               'Checkout::begin');
    $routes->get('checkout/success',        'Checkout::success');
    $routes->get('checkout/cancel',         'Checkout::cancel');
});
```

### Webhook Routes

Webhook endpoints receive POSTs from external services (Stripe, PayPal, etc.). These must be listed in `getCsrfExemptions()` since they don't live under `api/*`:

```php
// Webhooks - CSRF-exempt (declared in getCsrfExemptions), no auth
$routes->post('dstore/webhooks/(:segment)', 'Plugins\DigitalStore\Controllers\Webhook::handle/$1');
```

---

## 7. Controllers

### Admin Controllers

Admin controllers extend `App\Controllers\Admin\BaseAdminController`. They use `baseData()` to set the page title and active nav key, and CI4's native `view()` with the plugin's registered namespace to resolve view paths.

```php
<?php

namespace Plugins\DigitalStore\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;

class Products extends BaseAdminController
{
    public function index()
    {
        $products = model(\Plugins\DigitalStore\Models\ProductModel::class)->findAll();

        return view('Plugins\DigitalStore\Views\admin\products\index', array_merge(
            $this->baseData('Products - Digital Store', 'dstore_products'),
            ['products' => $products]
        ));
    }
}
```

CI4's `FileLocator` uses the PSR-4 namespace that `PluginManager` already registers, so `view('Plugins\DigitalStore\Views\admin\products\index')` resolves to `plugins/DigitalStore/Views/admin/products/index.php` automatically. Do not use `adminView()` — that only resolves paths under `app/Views/admin/`.

The second argument to `baseData()` is the `active_nav` value — it must match one of the `nav_key` values from your `getMenuItems()` children. This is how the sidebar knows which link to highlight and which section to keep open.

### Admin View Pattern

Admin views are plain `.php` files. They wrap content in `ob_start()` / `ob_get_clean()` and pass it to the admin layout. This is the pattern used by every admin view in Pubvana - do **not** use CI4's `$this->extend()` / `$this->section()` layout system.

```php
<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800"><?= lang('DigitalStore.productsTitle') ?></h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= lang('DigitalStore.allProducts') ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th><?= lang('DigitalStore.colName') ?></th>
                        <th><?= lang('DigitalStore.colPrice') ?></th>
                        <th><?= lang('DigitalStore.colStatus') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= esc($p->name) ?></td>
                        <td>$<?= esc(number_format($p->price, 2)) ?></td>
                        <td>
                            <span class="badge badge-<?= $p->is_active ? 'success' : 'secondary' ?>">
                                <?= $p->is_active ? lang('DigitalStore.active') : lang('DigitalStore.inactive') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
```

**Important - Bootstrap 4, NOT Bootstrap 5:** The admin uses SB Admin 2 which is Bootstrap 4. Use:
- `badge-success` not `bg-success`
- `close` not `btn-close`
- `data-toggle` not `data-bs-toggle`
- `data-target` not `data-bs-target`
- `data-dismiss` not `data-bs-dismiss`

### Public Controllers

Public-facing controllers extend `App\Controllers\BaseController`. Plugin `.tpl` views are rendered through `ThemeService::view()` using the plugin's namespace — same as theme views, just with a namespaced path. ThemeService handles everything: common data, template engine, layout wrapping, caching.

```php
<?php

namespace Plugins\DigitalStore\Controllers;

use App\Controllers\BaseController;

class Store extends BaseController
{
    public function index()
    {
        $products = model(\Plugins\DigitalStore\Models\ProductModel::class)
            ->where('is_active', 1)
            ->findAll();

        return $this->themeService->view('Plugins\DigitalStore\Views\store\index', [
            'products' => $products,
        ]);
    }
}
```

`ThemeService::view()` detects the namespaced path, resolves it via CI4's FileLocator, and renders it through the template engine inside the active theme's layout. The plugin's `.tpl` uses `{% extends 'layout' %}` to inherit the theme's layout and has access to all theme blocks (`{% block content %}`, `{% block scripts %}`, `{% block head_extra %}`, etc.).

---

## 7.1. Spark Commands (CLI)

Plugins can ship their own spark commands by placing them in a `Commands/` directory. These are auto-discovered when the plugin is active - no registration or configuration needed.

```php
<?php

namespace Plugins\DigitalStore\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanupExpired extends BaseCommand
{
    protected $group       = 'DigitalStore';
    protected $name        = 'dstore:cleanup';
    protected $description = 'Remove expired download tokens and temporary files.';
    protected $usage       = 'dstore:cleanup [--days=30]';
    protected $options     = [
        '--days' => 'Number of days to keep (default: 30)',
    ];

    public function run(array $params): void
    {
        $days = (int) (CLI::getOption('days') ?? 30);
        // ... cleanup logic
        CLI::write("Cleaned up tokens older than {$days} days.", 'green');
    }
}
```

**How it works:** `PluginManager::cliBoot()` runs before CI4's command discovery. It queries active plugins from the database and registers their namespaces if they have a `Commands/` directory. CI4's `discoverCommands()` then finds the command classes automatically.

**Naming convention:** Prefix command names with your plugin slug to avoid collisions (e.g. `dstore:cleanup`, `dstore:report`).

**Standalone and cron usage:** Plugin commands can be run directly (`php spark dstore:cleanup`) or registered to run automatically via the `cron` key in `plugin_info.json`.

### Registering Commands with the Cron System

To have your commands run automatically on a schedule, add a `cron` key to `plugin_info.json`:

```json
{
    "name": "Digital Store",
    "version": "1.0.0",
    ...
    "cron": {
        "minute": ["dstore:expire-carts"],
        "quarterday": ["dstore:sync-inventory"],
        "daily": ["dstore:cleanup", "dstore:report"]
    }
}
```

Three frequencies are available:

| Frequency | Schedule | Use for |
|-----------|----------|---------|
| `minute` | Every minute | Time-sensitive tasks (expiring carts, queue processing) |
| `quarterday` | Every 6 hours | Periodic syncs, cache refreshes |
| `daily` | Once per day | Cleanup, reports, maintenance |

Only include the frequencies your plugin needs. The `cron` key is optional -- omit it entirely if your plugin has no scheduled tasks. Commands listed here must exist in your plugin's `Commands/` directory.

---

## 7.2. Email Provider

Plugins can take over outbound email delivery for the entire site — routing all emails through an external service (Mailchimp Transactional, SendGrid, Postmark, etc.) instead of the core PHP mail / SMTP handler.

### Declaring the Capability

In `plugin_info.json`, declare that your plugin can handle core emails:

```json
"capabilities": {
    "email": ["core", "self"]
}
```

Use `["core", "self"]` if your plugin also sends its own emails (e.g. order confirmations). Use `["core"]` if it only acts as a transport layer.

### Implementing EmailProviderInterface

Your plugin class (or a dedicated service class) must implement `App\Interfaces\EmailProviderInterface`:

```php
<?php

namespace Plugins\MailchimpMailer;

use App\Interfaces\EmailProviderInterface;

class MailchimpProvider implements EmailProviderInterface
{
    public function handleEmail(array $data): bool
    {
        // $data keys:
        //   to          string[]      Recipient email addresses
        //   from        string        Sender email address
        //   fromName    string        Sender display name
        //   subject     string        Email subject (raw, unencoded)
        //   body        string        HTML or plain text body
        //   altMessage  string        Plain text alt body (for HTML emails)
        //   cc          string[]      CC addresses
        //   bcc         string[]      BCC addresses
        //   replyTo     string|null   Reply-to address
        //   replyToName string|null   Reply-to display name
        //   attachments array         CI4 native attachment structure

        $apiKey = setting('MailchimpMailer.apiKey');
        if (empty($apiKey)) {
            // No API key configured — fall through to core
            return false;
        }

        try {
            // Call your provider's API...
            $this->sendViaMandrill($apiKey, $data);
            return true;  // Handled — core email is skipped
        } catch (\Throwable $e) {
            log_message('error', 'MailchimpMailer: send failed — ' . $e->getMessage());
            return false; // Fall through to core as a safety net
        }
    }
}
```

**Return values:**
- `true` — your plugin handled delivery. Core email (`parent::send()`) is skipped.
- `false` — your plugin did not handle it (e.g. not configured, or an error). Core email runs as normal.

### Registering in register()

Call `service('emailProvider')->register()` from your plugin's `register()` method, passing your slug and a handler instance:

```php
public function register(): void
{
    service('emailProvider')->register($this->getSlug(), new MailchimpProvider());
}
```

The `EmailProviderService` is a shared CI4 service — `register()` is called once per request at boot time.

### Admin → Settings → Email

When your plugin is active and registered, it appears in the **Email Provider** dropdown at Admin → Settings → Email. The admin can switch between `Core (default)` and your plugin at any time without deactivating it.

### Activation Modal

When your plugin is the **first** plugin declaring `"core"` email capability to be activated, the admin sees a prompt:

> "This plugin can handle core system emails (contact forms, password resets, etc.) as well as its own. Do you want it to take over core email delivery?"

The admin can choose your plugin or leave the system on Core. This is the only modal — subsequent `"core"`-capable plugins do not prompt again; the existing selection is preserved.

---

## 8. Models

Models follow standard CI4 conventions. Extend `CodeIgniter\Model` and use CI4's Query Builder:

```php
<?php

namespace Plugins\DigitalStore\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table         = 'ds_products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'slug', 'description', 'price', 'is_active',
    ];
}
```

**Table naming:** Prefix all plugin tables with a short prefix to avoid collisions with core tables and other plugins. E.g. `ds_products`, `ds_orders`, `ds_licenses` for the DigitalStore plugin. Choose a 2-3 character prefix unique to your plugin.

### Batch Updates Without a Primary Key

CI4's `Model::update()` expects an `$id` as its first argument. To update multiple rows by condition (e.g., deactivate all licenses for an order), use the `builder()` method to drop down to the query builder:

```php
public function deactivateForOrder(int $orderId): int
{
    $itemIds = (new OrderItemModel())
        ->where('order_id', $orderId)
        ->findColumn('id');

    if (empty($itemIds)) {
        return 0;
    }

    $this->builder()
        ->whereIn('order_item_id', $itemIds)
        ->set(['is_active' => 0])
        ->update();

    return $this->db->affectedRows();
}
```

**Important:** Do not chain `->set()->update()` directly on the Model instance (`$this->whereIn(...)->set(...)->update()`). Without a primary key argument, `Model::update()` behaves unpredictably. Always go through `$this->builder()` for conditional batch updates.

---

## 8.1. Services

Services contain business logic and orchestrate model calls. They are plain PHP classes (no base class required) stored in the `Services/` directory:

```
plugins/DigitalStore/
    Services/
        CartService.php
        LicenseService.php
        SubscriptionService.php
```

**MVC rule:** Services must not access the database directly -- all queries go through models. Services call model methods and return results.

### Transaction Boundaries

The one exception to the "no direct DB access" rule is transaction management. When a service needs to wrap multiple model calls in a single atomic operation, it opens the transaction itself using `db_connect()`:

```php
public function handlePaymentCompleted(array $event): void
{
    $db = db_connect();
    $db->transStart();

    $this->orderModel->markCompleted($orderId, $event);
    $licenses = $this->licenseService->createForOrder($orderId, $items);

    $db->transComplete();

    if ($db->transStatus() === false) {
        log_message('error', 'Transaction failed for order #' . $orderId);
        return;
    }

    // Email sending goes OUTSIDE the transaction --
    // a failed email should not roll back a successful payment.
    $this->notificationService->sendLicenseDelivery($order, $licenses);
}
```

`db_connect()` returns the shared default connection, so all model operations inside the `transStart()` / `transComplete()` block participate in the same transaction. This is the only accepted direct DB call in services.

**Pattern:** Side effects that should not roll back the primary operation (email, logging to external services) go after `transComplete()`, not inside the transaction.

---

## 9. Migrations

Place migrations in `Database/Migrations/` inside your plugin folder. Use the CI4 timestamp naming convention:

```
plugins/DigitalStore/Database/Migrations/
    2026-03-29-000001_CreateDsProducts.php
    2026-03-29-000002_CreateDsOrders.php
```

Migrations run automatically when the plugin is activated from the admin UI. The `PluginManager` calls `Services::migrations()->setNamespace('Plugins\\DigitalStore')->latest()` during activation.

If a migration fails, activation is blocked and the admin sees an error message. Fix the migration and try activating again.

**Use `createTable('name', true)`** (IF NOT EXISTS) for idempotency - this is the project convention.

```php
<?php

namespace Plugins\DigitalStore\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDsProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('ds_products', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ds_products', true);
    }
}
```

---

## 10. Views

### Public Views (.tpl)

Public-facing templates use `.tpl` files and the Pubvana template engine — the same engine used by themes and widgets. Same rendering pattern as widgets: pull the view, merge class data, send to the theme engine for output inside the active theme layout.

Available syntax: `{{ variable }}`, `{% if condition %}`, `{% for item in items %}`, `{{ variable|filter }}`. See `ThemeBuilder.md` for the full template engine reference.

Place public views in `Views/store/` (or similar) inside your plugin.

### Admin Views (.php)

Admin views are plain PHP files using the SB Admin 2 admin layout. They use the `ob_start()` / `ob_get_clean()` buffering pattern (see Section 7). All text must go through `lang()`.

Place admin views in `Views/admin/` inside your plugin.

### Email Views (.php)

Email templates are plain PHP files rendered with CI4's `view()` function. Place them in `Views/emails/`.

---

## 11. Language / i18n

All user-visible text must use `lang()`. Create language files under `Language/{locale}/` in your plugin:

```
plugins/DigitalStore/Language/
    en/DigitalStore.php
    es/DigitalStore.php
    fr/DigitalStore.php
    id/DigitalStore.php
    pt/DigitalStore.php
    sk/DigitalStore.php
```

Language file format (standard CI4):

```php
<?php

return [
    'storeName'    => 'Digital Store',
    'addToCart'    => 'Add to Cart',
    'checkout'     => 'Checkout',
    'orderSuccess' => 'Thank you for your purchase!',
];
```

Usage in controllers and views:

```php
echo lang('DigitalStore.addToCart');

// With parameters
echo lang('DigitalStore.itemCount', [5]);
// Where the lang string is: '{0} items in your cart'
```

Pubvana supports 6 locales: `en`, `es`, `fr`, `id`, `pt`, `sk`. Provide translations for all 6 if distributing through the Pubvana Marketplace.

---

## 12. Styling & JavaScript

Plugins do not ship their own CSS files. The active theme controls all visual styling through class injection.

### Class Injection

All HTML elements in your `.tpl` views must use `cls_` class variables instead of hardcoded CSS classes. This lets themes control the look of your plugin's output without template overrides.

There are two approaches:

**1. Use existing `cls_*` variables** - The theme system already defines common classes like `cls_card`, `cls_btn_primary`, `cls_badge`, etc. Use these for standard UI elements.

```html
<div class="{{ cls_card }}">
    <h3 class="{{ cls_card_title }}">{{ product.name }}</h3>
    <a href="{{ product.url }}" class="{{ cls_btn_primary }}">View</a>
</div>
```

#### Complete `cls_*` Standard Reference

See **[CssClassReference.md](CssClassReference.md)** for the full vocabulary of all standard `cls_*` variables with semantic defaults.

**2. Define plugin-specific `cls_{plugin}_*` variables** - For elements unique to your plugin that don't map to existing theme classes, define your own with a plugin prefix. Ship Bootstrap-based defaults in your controller — at render time, the controller reads `plugin_classes` from the active theme's `theme_info.json` and merges overrides on top (theme wins). Themes override these in `theme_info.json` under `plugin_classes`:

```json
"plugin_classes": {
    "digital_store": {
        "cls_dstore_product_card": "card shadow-sm",
        "cls_dstore_price_badge": "badge bg-success",
        "cls_dstore_btn_cart": "btn btn-primary"
    }
}
```

Use them in your templates:

```html
<div class="{{ cls_dstore_product_card }}">
    <span class="{{ cls_dstore_price_badge }}">{{ product.price }}</span>
    <button class="{{ cls_dstore_btn_cart }}">Add to Cart</button>
</div>
```

Available `cls_{plugin}_*` variables are documented in the plugin's own README.

### JavaScript

Do not bundle JS files with your plugin. Plugin views render inside the theme's layout, which provides template blocks for injecting scripts.

- **`{% block scripts %}`** - Inject JS before `</body>`. Use this for CDN libraries or plugin-specific scripts:
  ```
  {% block scripts %}
  <script src="https://cdn.jsdelivr.net/npm/some-library@1.0/dist/lib.min.js"></script>
  <script>
      // Your plugin JS here
  </script>
  {% endblock %}
  ```
- **`{% block head_extra %}`** - Inject into `<head>`. Use sparingly - only for CSS CDN links or meta tags if absolutely needed.
- **Inline `<script>`** - For small, page-specific JS, inline within your `.tpl` content is fine.

The active theme will vary - your plugin can't know which CSS/JS framework it loads. If your plugin depends on a specific library, load it via CDN in `{% block scripts %}`. Admin views (`.php`) have access to `$extra_scripts` which renders before `</body>` - pass it from your controller.

---

## 13. File Storage

Plugins that handle file uploads (product ZIPs, screenshots, etc.) should store files in `writable/` - never in `public/` for protected files.

**Protected files** (downloads gated by license/purchase): `writable/dstore/products/`
**Public files** (screenshots, thumbnails): `public/plugins/digitalstore/screenshots/`

Serve protected files through a controller that validates access before streaming. Never load entire files into memory - use PHP's streaming functions.

---

## 13.1 URL Helpers

Pubvana supports multiple languages with locale-prefixed URLs. Use the correct helper:

- **`site_url('path')`** — for all URLs (links, form actions, redirects). Includes the locale prefix when active.
- **`base_url('path')`** — for assets only (images, CSS, JS, screenshots). No locale prefix.

```php
// URLs — use site_url()
<a href="<?= site_url('dstore/admin/products') ?>">Products</a>
<form action="<?= site_url('dstore/admin/products/create') ?>">

// Assets — use base_url()
<img src="<?= base_url($product->screenshot_url) ?>">
<link href="<?= base_url('assets/css/plugin.css') ?>">
```

In `.tpl` templates, use the equivalent tag functions: `{% site_url 'path' %}` and `{% base_url 'path' %}`.

---

## 14. Lifecycle

### Installation

1. **Via Marketplace:** Admin → Marketplace → find plugin → Install. The `MarketplaceService` downloads the ZIP, extracts it to `plugins/`, and runs discovery.
2. **Manual:** Drop the plugin folder into `plugins/`. Then Admin → Plugins → "Scan for Plugins".

Plugin ZIPs must contain a root folder matching the plugin name (e.g. `DigitalStore/Plugin.php`). This is the same convention used by theme and widget ZIPs.

### Activation

1. Admin clicks "Activate" in Admin → Plugins.
2. If the plugin is unknown to Pubvana, the admin sees a warning and must confirm.
3. `PluginManager` runs the plugin's **migrations** automatically.
4. `PluginManager` runs all **seeders** in `Database/Seeds/` (if the directory exists).
5. If the plugin ships an `Installer.php`, `PluginManager` calls `Installer::up()`. If `up()` throws, `Installer::down()` is called to roll back, and activation fails.
6. If everything succeeds, the plugin is marked active.
7. If the plugin is the first to declare `"core"` email capability, the admin is prompted to choose whether this plugin should handle core email delivery (see Section 7.2).
8. On the next request, `PluginManager::boot()` loads the plugin: registers its namespace, loads its routes, calls `register()`.

**Activation order:** Migrations → Seeds → Installer. Each step runs only if the plugin has the corresponding files. Migrations create tables, seeds populate default data, Installer handles filesystem setup.

### Database Seeds (optional)

If your plugin needs default data on activation, place seeder classes in `Database/Seeds/`. They extend CI4's `Seeder` class and use `$this->db->table()`.

```php
<?php

namespace Plugins\DigitalStore\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DefaultSettings extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['setting_key' => 'currency', 'setting_value' => 'USD'],
            ['setting_key' => 'tax_rate', 'setting_value' => '0'],
        ];
        foreach ($defaults as $row) {
            if (! $this->db->table('ds_settings')->where('setting_key', $row['setting_key'])->get()->getRow()) {
                $this->db->table('ds_settings')->insert($row);
            }
        }
    }
}
```

### Installer.php (optional)

If your plugin needs non-schema, non-data setup on activation - creating directories, copying files, etc. - ship an `Installer.php` in your plugin root. Migrations handle schema, seeds handle data, the installer handles everything else.

```php
<?php

namespace Plugins\DigitalStore;

class Installer
{
    public function up(): void
    {
        $dirs = [WRITEPATH . 'dstore/products', FCPATH . 'plugins/digitalstore/screenshots'];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function down(): void
    {
        $dirs = [WRITEPATH . 'dstore/products', FCPATH . 'plugins/digitalstore/screenshots'];
        foreach (array_reverse($dirs) as $dir) {
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }
}
```

**Key rules:**
- `up()` runs after migrations succeed. It has full CI4 access — `db_connect()`, `service()`, `helper()`, etc.
- `down()` is only called on failed activation (to roll back partial setup). Deactivation does **not** call `down()` — data is preserved.
- Migrations handle schema (CREATE TABLE). Installer handles everything else (mkdir, seed data, copy files).
- No interface required — just a class with `up()` and `down()` methods.

### Deactivation

Admin clicks "Deactivate". The plugin stops loading - routes, namespace, and `register()` are skipped. Files and database tables remain intact.

### Uninstallation

Remove the plugin folder from disk. Next time "Scan for Plugins" runs (or on next `discover()` call), the orphaned DB record is automatically deleted. Database tables created by the plugin's migrations remain (data preservation) unless the admin manually rolls back the migrations.

### Version Updates

When a new version is detected (version in `plugin_info.json` changes), the plugin's vetting status is reset to `unknown` and re-verified against pubvana.net. This ensures new code is re-vetted before the approval badge returns.

---

## 15. Approval System

We take Pubvana users' data security seriously. We use several technologies to test and limit the destructive scope of plugins, themes, and widgets from unknown sources.

Pubvana checks all installed plugins against the Pubvana vetting service during discovery. This check is handled by `VettingService`. We only test and determine if a plugin is safe to use. The decision to use it is solely up to the site operator. If we find your project on our own we will likely vet it and add it to the system. To request vetting, submit at pubvana.net/vetted/submit.

| Status | Badge | Meaning |
|--------|-------|---------|
| `unknown` | Gray "Unknown" | Check has not completed yet (new plugin or network error) |
| `safe` | Green "Safe" | Plugin is in the Pubvana registry and has been reviewed |
| `known` | Green "Safe" + yellow warning | Plugin is in the registry but has a noted limitation or caution |
| `malicious` | Red "Not Safe" | Plugin has been flagged as harmful |

**Unvetted plugins can still be activated** - the admin must explicitly confirm via a security warning modal. If the plugin has a `malicious` status or a warning note from Pubvana, it is displayed prominently in red in the confirmation modal.

Plugins marked safe activate without the confirmation step.

---

## 16. Security Considerations

- **CSRF:** All POST endpoints are protected by CSRF unless explicitly exempted via `getCsrfExemptions()`. Routes under `api/*` are already globally exempt. Only additionally exempt webhook/callback endpoints outside of `/api/`.
- **Admin auth:** Admin routes must use the `admin_auth` and `totp` filters. Never expose admin functionality on unprotected routes.
- **SQL injection:** Use CI4's Query Builder for all database operations. Never interpolate user input into raw SQL.
- **XSS:** Escape all output with `esc()` in admin views (`.php`). The template engine handles escaping automatically in `.tpl` files.
- **File uploads:** Validate file types and sizes server-side. Store protected files in `writable/`, never in `FCPATH`.
- **No card data:** If processing payments, use hosted checkout pages (Stripe Checkout, PayPal redirect). Never handle card numbers directly.

---

## 17. Distribution

### Pubvana Marketplace

The Pubvana Marketplace distributes Pubvana's own products and **free** third-party plugins. Paid third-party plugins are not sold on the Pubvana Marketplace - if you want to charge for your plugin, you can sell it on your own site and provide users with a ZIP to install manually.

**To submit a paid plugin to our vetting system:** Submit your ZIP at pubvana.net/vetted/submit. We will only vet your product and when done destroy the copy. We will never distribute your product.

**To submit a free plugin to the Marketplace:** Contact the Pubvana team. Your plugin will go through the vetting process (see Section 18) before it's listed.

### Standalone Distribution

You're free to distribute your plugin independently - on your own website, GitHub, etc. Users install it by dropping the folder into `plugins/` and activating from Admin → Plugins. Standalone plugins show as "Unknown" in the admin until they go through the Pubvana vetting process.

---

## 18. Vetting Process

All plugins - whether submitted to the Marketplace or installed manually - are checked against the Pubvana vetting service. This is a non-blocking check: plugins that are not yet marked safe can still be activated, but the admin must explicitly confirm.

### How it works

1. When a plugin is discovered (Scan for Plugins), `VettingService` sends the plugin's folder name, version, and normalized author to the `vetted/v1/check` API endpoint.
2. The API response includes a status (`unknown`, `safe`, `known`, or `malicious`) and an optional warning note.
3. The status and warning are stored locally and displayed as badges in Admin → Plugins.
4. If the network check fails, the plugin stays `unknown` and retries on the next scan.

### Author normalization

The `author` field from `plugin_info.json` is normalized before the vetting lookup: converted to lowercase and spaces replaced with underscores. For example, `"Pubvana Team"` becomes `pubvana_team`. Use a consistent author name across releases so your vetting history carries over.

### Wildcard version support

Official Pubvana releases use a wildcard version (`*`) in the registry, meaning all versions of that plugin are marked safe as a group. Third-party plugins are registered per-version - each new version must be re-vetted individually.

### What gets vetted

- Code review for security issues (SQL injection, XSS, file traversal, etc.)
- No malicious behavior (data exfiltration, backdoors, crypto miners, etc.)
- Follows the PluginInterface contract correctly
- Does not conflict with core CMS functionality

### Status meanings

| Status | Meaning |
|--------|---------|
| `unknown` | Not yet checked, or check failed (network error) |
| `safe` | Reviewed and cleared by the Pubvana team |
| `known` | In the registry with a noted limitation or caution -- warning note is shown to the admin |
| `malicious` | Flagged as harmful -- warning is displayed prominently in red and activation requires explicit confirmation |

### Security warnings

If the Pubvana team discovers malicious or dangerous code in a plugin, a warning note is set in the registry. This warning is:
- Displayed in red in the Admin → Plugins list next to the plugin name
- Shown prominently in the activation confirmation modal
- Persisted locally so it is visible even if the network is unavailable

### Version re-vetting

When a plugin's version changes (new `version` in `plugin_info.json`), its vetting status is automatically reset to `unknown`. The new version must be re-vetted. A safe marking on v1.0.0 does not carry over to v1.1.0 - new code means new review.

### Submitting for vetting

To request vetting of your plugin - whether free or paid - submit it at **pubvana.net/vetted/submit**. Provide a ZIP containing a valid `plugin_info.json`. The Pubvana team will review the code and notify you by email when a decision is made. Subsequent versions must be resubmitted.

---

## 19. Checklist

Before releasing a plugin:

- [ ] `plugin_info.json` has all 4 required fields (`name`, `version`, `description`, `author`)
- [ ] `Plugin.php` implements all 6 `PluginInterface` methods
- [ ] `getName()`, `getSlug()`, `getVersion()` match `plugin_info.json` values
- [ ] `getMenuItems()` returns proper structure with `label`, `icon`, and `children` array (or empty array)
- [ ] `getPublicRoutes()` returns public routes with `label` and `url` (or empty array). Labels use `lang()`
- [ ] All `nav_key` values prefixed with plugin slug (e.g. `dstore_products`)
- [ ] All user-visible strings use `lang()`
- [ ] Translations provided for all 6 locales (`en`, `es`, `fr`, `id`, `pt`, `sk`)
- [ ] All database tables use a plugin-specific prefix (e.g. `ds_`)
- [ ] Migrations use `createTable('name', true)` for idempotency
- [ ] Default data is in `Database/Seeds/`, not migrations or Installer
- [ ] Non-schema, non-data setup (directories, files) is in `Installer.php`
- [ ] `Installer::down()` cleanly reverses everything `up()` does
- [ ] Admin routes use `admin_auth` and `totp` filters
- [ ] Admin views use the `ob_start()` / `ob_get_clean()` pattern (not `$this->extend()`)
- [ ] Admin views use Bootstrap 4 classes (not Bootstrap 5)
- [ ] No raw SQL - all queries use CI4 Query Builder
- [ ] All output escaped (`esc()` in PHP views, engine handles `.tpl`)
- [ ] CSRF exemptions only on external-facing POST endpoints outside `api/*`
- [ ] Protected files stored in `writable/`, not `FCPATH`
- [ ] Spark commands (if any) are in `Commands/`, extend `BaseCommand`, and use a plugin-prefixed name
- [ ] `cron` key in `plugin_info.json` (if used) only references commands in the plugin's own `Commands/` directory
- [ ] Plugin ZIP contains root folder matching plugin name
- [ ] If plugin handles email: `capabilities.email` declared in `plugin_info.json`
- [ ] If plugin handles email: `EmailProviderInterface` implemented and registered in `register()`
- [ ] If plugin handles email: `handleEmail()` returns `false` gracefully when not configured (falls through to core)
