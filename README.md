<p align="center">
  <img src="pubvana-nodrop-nobg.png" alt="Pubvana CMS" width="220">
</p>

# Pubvana CMS

Pubvana is a flexible, full-featured, shared-host friendly CMS (Content Management System) for personal blogs, small to medium business websites, company intranets and more. v3 offers the flexibility of a CMF (Content Management Framework) to build your own custom plugins faster than starting from scratch.  Version 3 has a new administration panel, plugin extensions, faster internals, and more features than you'd expect in a free, open source CMS.

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

## Installation

See [Install](INSTALL.md)


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

## Security

### Reporting a Vulnerability

See [Security](SECURITY.md) Report at [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new) to report them privately.

### Production Hardening

See [HARDENING.md](HARDENING.md) for the production checklist (env, HTTPS, headers, auth, logging, backups).

## Bug Reports and Feature Requests

Use the [Issues Tracker](https://github.com/Pubvana-CMS/pubvana/issues).

## Links

[pubvanacms.com](https://pubvanacms.com)

## License

MIT. [LICENSE](LICENSE)

## Contributors

- Enliven Applications
- Your Name Here

- [FlightPHP](https://github.com/flightphp):  While the good folks over at flightPHP haven't contributed code to this project directly, they have provided the basis of the project with `core`, `active record` and others. Consider using FlightPHP for your next project! 

## Legacy

- Orginally brought to github by [Kami](https://github.com/Kami) as [Open Blog](https://github.com/Kami/Open-Blog) around 2010 built on Codeigniter v(2-ish).
- Dec. 2016 Kami released [Open Blog](https://github.com/enlivenapp/Open-Blog) to [Enlivenapp](https://github.com/enlivenapp) along with domains.
- Enlivenapp brought about [Open Blog 3](https://github.com/enlivenapp/Open-Blog-3) updated with Codeigniter3 and more functionality.
- Mar 2018 some copyright considerations caused the renaming of Open Blog to Pubvana and was release under Enlivenapp/pubvana built on Codeigniter4 from Feb 2026 until Aug of 2026.
- Aug 2026 Pubvana was moved to it's own organisation(github) where [Pubvana v3](https://github.com/Pubvana-CMS/pubvana) replaced Codeigniter with [FlightPHP](https://github.com/flightphp) with updated and modern coding style, security, and a larger feature set. This is a completely different codebase and is not backwards compatable to previous versions of Pubvana and Open Blog. 