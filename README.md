<p align="center">
  <img src="pubvana-nodrop-nobg.png" alt="Pubvana CMS" width="220">
</p>

# Pubvana CMS

Pubvana is a flexible, shared-host friendly CMS (Content Management System) for personal blogs, small to medium business websites, company intranets and more. v3 offers the flexibility of a CMF (Content Management Framework) to build your own custom plugins faster than starting from scratch. Version 3 has a new administration panel, plugin extensions, faster internals, and more features than you'd expect in a free, open source CMS.

## Status

Pubvana v3 is in beta. The codebase, the database schema, and the plugin API can change between releases. See [CHANGELOG.md](CHANGELOG.md) for what changed in each release.

## v3

[![v3](https://img.shields.io/badge/v3-In%20Beta-blue)](https://github.com/Pubvana-CMS/pubvana)
[![Latest Release](https://img.shields.io/github/v/release/Pubvana-CMS/pubvana)](https://github.com/Pubvana-CMS/pubvana/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[![FlightPHP](https://img.shields.io/badge/FlightPHP-3.0-orange.svg)](https://flightphp.com)
![PHPStan: Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)
[![PHPStan](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml)
[![Psalm](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml)
[![Tests](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml)

[![Daily Downloads](http://poser.pugx.org/enlivenapp/pubvana/d/daily)](https://packagist.org/packages/enlivenapp/pubvana)
[![Monthly Downloads](http://poser.pugx.org/enlivenapp/pubvana/d/monthly)](https://packagist.org/packages/enlivenapp/pubvana)
[![Total Downloads](http://poser.pugx.org/enlivenapp/pubvana/downloads)](https://packagist.org/packages/enlivenapp/pubvana) 

## v2

[![Codeigniter 4](https://img.shields.io/badge/Codeigniter4-4.7-orange.svg)](https://codeigniter.com)
[![status](https://img.shields.io/badge/status-maintenance-blue)](https://github.com/Pubvana-CMS/pubvana)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)


## v1 (Includes Open-Blog and Open-Blog3)

[![status](https://img.shields.io/badge/status-obsolete.unsupported-blue)](https://github.com/Pubvana-CMS/pubvana)
[![Codeigniter 3](https://img.shields.io/badge/Codeigniter-3.x-orange.svg)](https://codeigniter.com)
[![Codeigniter 2](https://img.shields.io/badge/Codeigniter-2.x-orange.svg)](https://codeigniter.com)


---

## Table of Contents

- [Status](#status)
- [Installation](#installation)
- [Features](#features)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Stack](#stack)
- [Development](#development)
- [Testing and Code Quality](#testing-and-code-quality)
- [Configuration](#configuration)
- [Upgrading](#upgrading)
- [Security](#security)
- [Contributing](#contributing)
- [Getting Help](#getting-help)
- [License](#license)
- [Contributors](#contributors)
- [Legacy](#legacy)

## Installation

Requirements and install steps: [User Guide](https://pubvanacms.com/docs/user/v/3.0/getting-started).


## Features

- Publishing with drafts, scheduling, revisions, categories, tags, and WYSIWYG editing (Jodit)
- RSS/Atom feeds, media library with image processing, video posters, embeds
- Nested moderated comments with captcha and sanitization
- AI assistant with fact-checking workflow
- Content blocks in regions with drag-and-drop placement and per-block on/off toggles
- Vision templates for public pages (no PHP execution)
- Theme system with per-theme options
- Nested navigation menus, plugin system with enable/disable
- Admin dashboard with plugin cards
- Role-based access control (Shield), user bans, forced password resets
- Optional email 2FA and email activation
- Activity log audit trail, site health checks, public-side flash messages
- SMTP email with encrypted credentials, cron scheduler
- Full-site backup and restore, core update flow
- Marketplace, digital store with products, orders, and payment webhooks
- Trust Service: plugin, theme, and release safety from the Pubvana trust service, shown as admin trust badges
- Stand-alone JSON API for integrations and addon services
- Security headers, CSRF protection, rate-limited login
- Search across content types, SEO (sitemap, schema, robots, Open Graph)
- Redirect manager with 404 tracking and broken-link scanning
- Form builder, server-side analytics with daily rollups
- Public user profiles, site-wide social links, dark mode admin UI
- Static pages alongside blog posts
- Broken-link scanner for outbound links in posts and pages
- WCAG 2.2 AA accessibility target for the admin UI and the default theme ([details](ACCESSIBILITY.md))

## Documentation

Online documentation for users and developers is at [pubvanacms.com/docs](https://pubvanacms.com/docs).

- [User Guide](https://pubvanacms.com/docs/user): install, content, site management, plugins, maintenance
- [Developer Guide](https://pubvanacms.com/docs/dev): architecture, services, plugin and theme development
- [SECURITY.md](SECURITY.md): report a vulnerability
- [CODING-STANDARDS.md](CODING-STANDARDS.md): code standards and the pre-push checklist
- [CONTRIBUTING.md](CONTRIBUTING.md): report bugs, suggest features, send a PR
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md): community expectations
- [ACCESSIBILITY.md](ACCESSIBILITY.md): accessibility target and audit method
- [CHANGELOG.md](CHANGELOG.md): release history

## Project Structure

```
pubvana/
  app/                  - Core, like Admin, necessary services, etc
    config/             - Bootstrap, routes, services, env handling
    Controllers/        - Admin, Public, and API controllers
    Database/           - Core migrations and seeds
    Models/             - Core models
    Services/           - ExtensionRegistry, PluginLoader, PluginView, etc.
    Views/admin/        - Admin templates (.php)
  plugins/              - Extend functionality
  themes/               - Theme folders with Vision templates
  vendor/               - software from others we use
  writable/             - Automated writing to files from the site
```

## Stack

| Layer | Technology |
|---|---|
| Framework | FlightPHP 3.0 |
| Authentication | enlivenapp/flight-shield |
| Database | enlivenapp/flight-active-record |
| Admin UI | [Tabler](https://tabler.io) (Bootstrap 5 + Alpine.js) |
| Public Templates | Vision (no PHP execution) |
| Content Editor | [Jodit](https://jodit.com) |
| Mail | PHPMailer |

## Development

`composer install` brings in the dev tools: PHPStan, Psalm, PHPUnit, and the Tracy debug bar.

Admin styling comes from Tabler and Sass. Build the stylesheet once, or watch it while you work:

```bash
npm install
npm run build     # one-off compressed build
npm run watch     # rebuild on change
```

## Testing and Code Quality

| Command | Checks |
|---|---|
| `composer lint` | PHP syntax across `app/` and `plugins/` |
| `composer phpstan` | static analysis, level 8 |
| `composer psalm` | taint analysis |
| `composer test` | PHPUnit suite |

Tests and static analysis: see [CODING-STANDARDS.md](CODING-STANDARDS.md).

## Configuration

Deployment values live in `.env` and admin settings live in the database. See [Securing Your Site](https://pubvanacms.com/docs/user/v/3.0/getting-started/secure-site) and [Settings](https://pubvanacms.com/docs/user/v/3.0/managing-your-site/settings).

## Upgrading

The Updates plugin applies core updates. Back up your site and database first. See [Updates](https://pubvanacms.com/docs/user/v/3.0/maintenance/updates) and [CHANGELOG.md](CHANGELOG.md).

## Security

Report vulnerabilities through [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new). See [SECURITY.md](SECURITY.md). Hardening: [Securing Your Site](https://pubvanacms.com/docs/user/v/3.0/getting-started/secure-site).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) to report bugs, request features, or send a PR. Community expectations are in the [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Help

Use the [Issues Tracker](https://github.com/Pubvana-CMS/pubvana/issues) for questions and bug reports. Read the [docs](https://pubvanacms.com/docs). Plugins, themes, and the digital store live at [pubvanacms.com](https://pubvanacms.com).

## License

MIT. [LICENSE](LICENSE)

## Contributors

- Enliven Applications
- Your Name Here

- [FlightPHP](https://github.com/flightphp):  While the good folks over at flightPHP haven't contributed code to this project directly, they have provided the basis of the project with `core`, `active record` and others. Consider using FlightPHP for your next project! 

## Legacy

- Originally brought to github by [Kami](https://github.com/Kami) as [Open Blog](https://github.com/Kami/Open-Blog) around 2010 built on Codeigniter v(2-ish).
- Dec. 2016 Kami released [Open Blog](https://github.com/enlivenapp/Open-Blog) to [Enlivenapp](https://github.com/enlivenapp) along with domains.
- Enlivenapp brought about [Open Blog 3](https://github.com/enlivenapp/Open-Blog-3) updated with Codeigniter3 and more functionality.
- Mar 2018 some copyright considerations caused the renaming of Open Blog to Pubvana and was released under Enlivenapp/pubvana built on Codeigniter4 from Feb 2026 until Aug 2026.
- Aug 2026 Pubvana was moved to its own organisation(github) where [Pubvana v3](https://github.com/Pubvana-CMS/pubvana) replaced Codeigniter with [FlightPHP](https://github.com/flightphp) with updated, modern coding style, security, and a larger feature set. This is a completely different codebase and is not backwards compatible to previous versions of Pubvana and Open Blog.