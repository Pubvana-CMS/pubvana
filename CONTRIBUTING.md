# Contributing to Pubvana

Thanks for your interest in contributing. This guide covers how to report bugs, suggest features, and submit code.

---

## Reporting Bugs

Open an issue on [GitHub](https://github.com/Pubvana-CMS/pubvana/issues) with:

- What you expected to happen
- What actually happened
- Steps to reproduce
- PHP version, database, and web server

**Security vulnerabilities** - please email **cs@pubvana.net** instead of opening a public issue.

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

Create a `.env` from the sample and set `CI_ENVIRONMENT = development`. Then run:

```bash
php spark key:generate
php spark migrate --all
php spark db:seed DatabaseSeeder
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

## Code conventions

- **PSR-4 namespacing** - follow the existing `App\` namespace structure
- **MVC** - controllers handle requests, models handle data, views handle display. Don't mix them.
- **No inline PHP in themes or widgets** - use `.tpl` templates with the whitelisted tag/filter system. PHP files in theme or widget directories will trigger a validation warning.
- **Language files** - if you add user-facing strings, add them to `app/Language/en/Admin.php` (or the relevant file). Translations follow.
- **Shield tables** - auth uses `users`, `auth_identities`, `auth_groups_users`. `group` is a MySQL reserved word - use backticks in SQL.
- **CI4 conventions** - follow CodeIgniter 4's coding style. If in doubt, look at neighboring files.

---

## Translations

Pubvana ships with 24 languages. Many were AI-translated and need verification from native speakers.

To help:

1. Fork the repo
2. Edit the files in `app/Language/{locale}/`
3. Submit a pull request

See `app/Language/en/` for the source strings.

---

## Themes, Widgets, and Plugins

Developer documentation is in the `BuilderDocs/` directory:

- **[ThemeBuilder.md](BuilderDocs/ThemeBuilder.md)** - theme development
- **[WidgetBuilder.md](BuilderDocs/WidgetBuilder.md)** - widget development
- **[PluginBuilder.md](BuilderDocs/PluginBuilder.md)** - plugin development
- **[ThirdPartyAddons.md](BuilderDocs/ThirdPartyAddons.md)** - third-party addon distribution

---

## Release Checklist (Maintainers)

Before tagging a release, complete the steps in the release checklist. See `../pubvana-dev-local/release-checklist.md`.
