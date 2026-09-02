<p align="center">
  <img src="pubvana-nodrop-nobg.png" alt="Pubvana CMS" width="220">
</p>

# Pubvana CMS

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![FlightPHP](https://img.shields.io/badge/FlightPHP-3.0-orange.svg)](https://flightphp.com)
[![Release](https://img.shields.io/github/v/release/Pubvana-CMS/pubvana)](https://github.com/Pubvana-CMS/pubvana/releases)
[![PHPStan](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml)
[![Psalm](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml)
[![Tests](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml)

[![v3](https://img.shields.io/badge/v3-In%20Development-blue)](https://github.com/Pubvana-CMS/pubvana)
[![Status](https://img.shields.io/github/v/tag/Pubvana-CMS/pubvana?label=Status)](https://github.com/Pubvana-CMS/pubvana/tags)

Pubvana v3 is a full-featured CMS built on the FlightPHP micro framework with plugins, Vision template engine, and shared-host friendly setup for personal blogs and small to medium businesses.

## Notice: 
v3 is currently in Alpha developement. Thing change rapidly, and no update ability is available yet(you'd be stuck with composer for updates). If you need a stable release *right now* consider [Pubvana v2](https://github.com/Pubvana-CMS/pubvana/releases/tag/v2.3.6).  Thanks for considering Pubvana!


## Prerequisites

- PHP 8.2+ with required extensions
- MySQL 5.7+ or MariaDB 10.3+
- Composer (getcomposer.org)
- A web server (Apache with mod_rewrite, or Nginx)

Create an empty MySQL database and a user with full privileges on it. You'll need the database name, username, and password for setup.  *Composer* is optional in production builds (see Release Download `.zip` file).

## Automated Installation

See the Pubvana Website for [v2 web installer](https://pubvana.net/dstore/product/pubvana-easy-installer) or [v2 Docker Compose](https://github.com/Pubvana-CMS/v2-docker) on Github. 

## Manual Installation (v3)  

**Production (clone and install):** *Browser and Docker based installers coming soon.*

```bash
cd ~/public_html/
git clone https://github.com/Pubvana-CMS/pubvana.git .
composer install
```

**Development:**

```bash
git clone https://github.com/Pubvana-CMS/pubvana.git
cd pubvana
composer install
```

## Configuration

Generate a session key if you don't have one (don't reuse keys):

```bash
php -r 'echo bin2hex(random_bytes(32));'
```

Copy the sample environment file and edit it:

```bash
cp .env.example .env
```

Edit `.env`: At minimum, set these values:

```
APP_ENV=production
DB_HOST=127.0.0.1
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
SITE_URL=https://your-domain.com/
SESSION_ENCRYPTION_KEY=<64 hex characters>
```


## Initialize the Database

```bash
php runway migrate:all
```

This runs the foundation packages (sessions, Shield, CSRF), core migrations, and every enabled plugin's migrations and seeds.

## Create an Admin User

```bash
php runway shield:user create -n yourusername -e you@example.com
php runway shield:user password -e you@example.com
php runway shield:user addgroup -e you@example.com -g superadmin
```

## Web Server

Point your web server to the `public/` directory. If you use Apache or LiteSpeed, the included `.htaccess` handles clean URLs and security. For Nginx, add a location block that rewrites to `public/index.php`.

## Log In

Visit `https://your-server/auth/login` and sign in with the admin credentials you created.

## CLI Commands

| Command | Description |
|---------|-------------|
| `php runway migrate:all` | Run all pending migrations and seeds |
| `php runway routes` | List all registered routes |
| `php runway shield:user create -n <name> -e <email>` | Create a user |
| `php runway shield:user password -e <email>` | Set a user's password |
| `php runway shield:user addgroup -e <email> -g <group>` | Assign a user to a group |

## Project Structure

```
pubvana/
  app/
    config/             - Bootstrap, routes, services, env handling
    Controllers/        - Admin and public controllers
    Database/           - Core migrations and seeds
    Models/             - Core models
    Services/           - ExtensionRegistry, PluginLoader, PluginView, etc.
    Views/admin/        - Admin templates (.php)
  plugins/              - One folder per plugin
  themes/               - Theme folders with Vision templates
  docs/                 - Architecture and developer docs
```

Plugin and theme assets are served by AssetService at `/assets/{type}/{name}/{path}`. Nothing gets copied to `public/`.

## Developer Documentation

- Coming Soon


## Features

- Posts and pages with draft/published/scheduled workflow
- WYSIWYG editor (Jodit) for all content types
- Plugin system with enable/disable from the admin panel
- Vision templates for public pages (no PHP execution)
- Region and block system with drag-and-drop placement
- Media library with image editing
- Built-in SEO (meta, sitemaps, schema, LLMs.txt)
- Comment system with moderation
- URL redirect manager with 404 tracking
- Form builder with submissions
- Server-side analytics with daily rollups
- Search across all content types
- Role-based access control (Shield)
- SMTP email with encrypted credentials
- Dark mode admin UI (Tabler/Bootstrap 5)

## Stack

| Layer | Technology |
|---|---|
| Framework | FlightPHP 3.0 |
| Authentication | enlivenapp/flight-shield |
| Database | enlivenapp/flight-active-record |
| Admin UI | Tabler (Bootstrap 5 + Alpine.js) |
| Public Templates | Vision (no PHP execution) |
| Content Editor | Jodit |
| Mail | PHPMailer |

## Security

### Reporting a Vulnerability

Do not open a public issue for security vulnerabilities. Use [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new) to report them privately. We aim to respond within 48 hours and will credit reporters in the changelog.

### Production Hardening

- Set `APP_ENV=production` in `.env`
- Set `FORCE_HTTPS=true` in `.env`
- Use a strong password for your admin account
- Set `SITE_URL` to your actual domain
- Ensure `.env` has permissions `600` and is not committed to version control
- Point your web server's DocumentRoot to `public/`

## Bug Reports and Feature Requests

Use the [Issues Tracker](https://github.com/Pubvana-CMS/pubvana/issues).

## Links

[pubvanacms.com](https://pubvanacms.com)

## License

Pubvana is released under the MIT Open Source License. See [LICENSE](LICENSE) for details.

## Contributors

- Enliven Applications
