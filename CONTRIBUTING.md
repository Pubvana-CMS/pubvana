# Contributing to Pubvana

Thanks for your interest in contributing. This guide covers how to report bugs, suggest features, and submit code.

---

## Reporting Bugs

Open an issue on [GitHub](https://github.com/Pubvana-CMS/pubvana/issues) with:

- What you expected to happen
- What actually happened
- Steps to reproduce
- PHP version, database, and web server

**Security vulnerabilities** - use [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new) instead of opening a public issue.

---

## Suggesting Features

Open an issue with the **feature request** label. Describe the problem you're trying to solve, not just the solution you want.

---

## Development Setup

```bash
git clone https://github.com/Pubvana-CMS/pubvana.git
cd pubvana
composer install
```

You'll need:

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer

Create a `.env` from the sample and set `APP_ENV=development`. Then run:

```bash
php runway migrate:all
```

Create an admin user:

```bash
php runway shield:user create -n yourusername -e you@example.com
php runway shield:user password -e you@example.com
php runway shield:user addgroup -e you@example.com -g superadmin
```

Point your web server to the `public/` directory.

---

## Submitting Code

1. Fork the repo on GitHub
2. Create a branch from `main` (`git checkout -b fix/something`)
3. Make your changes
4. Test locally
5. Commit with a clear message
6. Push and open a pull request

### What to include in your PR

- What the change does and why
- Screenshots if it changes the UI
- Migration notes if it changes the database

---

## Code Conventions

- **PHP 8.2+** - no 8.3 or 8.4 features (shared host compatibility)
- **PSR-4 namespacing** - follow the existing `App\` namespace structure
- **MVC** - controllers handle requests, models handle data, views handle display
- **Inline JS only** - `.htaccess` blocks direct access to `plugins/`, so no external `.js` files in plugins
- **Vision templates** - public-facing templates use `.tpl` (no PHP execution). Admin templates use `.php`
- **Plugin prefix on views** - `$this->render('pubvana/blog/admin/index', ...)`
- **Service facade** - register with `$app->map('name', ...)` in Plugin.php, access via `$app->name()`
- **Tabler UI** - Bootstrap 5 admin with dark mode toggle
- **All plugins implement `PluginInterface`**
- **Language files** - add user-facing strings to `app/Language/en/` (or the relevant file)

---

## Translations

To help with translations:

1. Fork the repo
2. Edit the files in `app/Language/{locale}/`
3. Submit a pull request

See `app/Language/en/` for the source strings.

---

## Plugin and Theme Development

Developer documentation is in the `docs/` directory:

- [Architecture](docs/architecture.md) - system design, plugin loading, data flow
- [Plugin Development](docs/plugin-development.md) - building plugins
- [Plugin Integration](docs/plugin-integration.md) - converting Composer plugins to app-based
- [Themes](docs/themes.md) - creating themes
- [Vision](docs/vision.md) - template engine reference
- [adext](docs/adext.md) - extension registry
- [Runway](docs/runway.md) - CLI tooling

---

## Project Layout

```
app/                  - Core code (controllers, services, models, views)
plugins/              - One folder per plugin (each has pubvana.json)
themes/               - Theme folders with Vision templates
docs/                 - Developer documentation
```

Plugin and theme assets are served by AssetService at `/assets/{type}/{name}/{path}`. Do not copy assets to `public/`.
