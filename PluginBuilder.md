# PluginBuilder — Pubvana Plugin Stub Guide

A plugin extends Pubvana with standalone features: custom routes, admin pages, public-facing pages, database tables, and frontend output that renders inside the active theme layout.

---

## Directory Structure

```
plugins/
  my_plugin/
    plugin_info.json        # Plugin metadata and config
    MyPlugin.php            # Main plugin class (lifecycle hooks)
    Controllers/
      MyPluginController.php
    Models/
      MyPluginModel.php
    Database/
      Migrations/
        2026-01-01-000000_CreateMyPluginTable.php
    Views/
      index.tpl             # Frontend output rendered inside active theme
      admin/
        index.php           # Admin views use plain PHP (SB Admin 2)
    assets/
      css/
      js/
```

---

## plugin_info.json

```json
{
  "name":        "My Plugin",
  "slug":        "my_plugin",
  "version":     "1.0.0",
  "description": "A short description of what this plugin does.",
  "author":      "Your Name",
  "author_url":  "https://example.com",
  "min_pubvana": "2.1.0",
  "routes": "routes.php"
}
```

---

## PHP Backend

All backend logic (routes, controllers, models, migrations) is standard CodeIgniter 4 / PHP:

- **Routes** — define in a `routes.php` file at the plugin root; Pubvana auto-loads it on plugin activation.
- **Controllers** — extend `App\Controllers\BaseController`; follow the same MVC conventions as core controllers.
- **Models** — extend `CodeIgniter\Model`; use the standard CI4 model pattern.
- **Migrations** — place in `Database/Migrations/`; run via `php spark migrate --namespace MyPlugin` or triggered automatically during plugin install.

---

## Frontend Views (.tpl)

Any output rendered inside the active theme layout uses `.tpl` template files — the same sandboxed engine used by themes and widgets.

- Use the same tag syntax and filter functions as themes and widgets (e.g., `{{ variable }}`, `{% if %}`, `{% for %}`).
- `.tpl` files go in the plugin's `Views/` directory.
- Admin views that render inside the SB Admin 2 dashboard use plain `.php` files, not `.tpl`.

---

## What Is Deferred

Full plugin architecture — route registration lifecycle, config discovery, install/uninstall/activate/deactivate hooks, admin menu injection, and inter-plugin APIs — is documented in the separate plugin architecture spec.

---

*This is a stub guide. See `docs/superpowers/specs/` for the full plugin engine specification when available.*
