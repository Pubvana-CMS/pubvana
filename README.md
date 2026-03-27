# Pubvana

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Release](https://img.shields.io/badge/release-v2.2.0-blue)](https://github.com/enlivenapp/pubvana/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-orange.svg)](https://codeigniter.com)
[![Installs](https://img.shields.io/packagist/dt/enlivenapp/pubvana.svg)](https://packagist.org/packages/enlivenapp/pubvana)
[![Stars](https://img.shields.io/github/stars/enlivenapp/pubvana?style=flat)](https://github.com/enlivenapp/pubvana/stargazers)
[![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg)](https://github.com/enlivenapp/pubvana/issues)

### Blogging and Small Business CMS

Pubvana v2 is a full rewrite of Pubvana v1.x built on CodeIgniter 4, Authentication with Shield, a modern admin UI, dual content editors, theme, plugin & widget system, built-in marketplace, and many new features. We aim for Pubvana to be lean and fast without the bloat of other CMS and Blog software available.

These instructions are for users comfortable with the command line and terminal. If you'd prefer a streamlined experience, [go here: placeholder].

### For Developers

Developers looking to build themes or widgets for Pubvana can find everything they need in these guides:

- **[ThemeBuilder.md](ThemeBuilder.md)** — Complete theme development guide
- **[WidgetBuilder.md](WidgetBuilder.md)** — Complete widget development guide

## Installation

### 1. Prerequisites

  Before installing, make sure you have:
  - PHP 8.2+ with required extensions (see Requirements below)
  - Composer (getcomposer.org)
  - MySQL 5.7+ or MariaDB 10.3+
  - A web server (Apache with mod_rewrite, or Nginx)

  Create an empty MySQL database and a user with full privileges on it. You'll
  need the database name, username, and password for the next steps.

  ### 2. Download

  **For Production** : for site owners deploying Pubvana as is:

 Navigate to the directory you wish to use for the project root.
```
  cd ~/public_html/
```

Install Pubvana from Packagist. (Note the '.' to install into your current directory)
```
composer create-project enlivenapp/pubvana .
```

  **For Development** : for contributors who want to run tests, build additional features and work on the codebase:

```
  git clone https://github.com/enlivenapp/pubvana.git
  cd pubvana
  composer install
```

 The remaining steps apply to production and development environments.


### 3. Configure

  Open the sample environment file in a text editor or Vim/Nano:

```

  Edit these lines at a minimum: (uncomment (remove #))

  CI_ENVIRONMENT = production  # or development

  app.baseURL = 'https://your-domain.com/'

  database.default.hostname = localhost
  database.default.database = your_database_name
  database.default.username = your_database_user
  database.default.password = your_database_password

  Set CI_ENVIRONMENT to production for a live site or development for local
  work.  Leaving this commented defaults to the production environment
```

Save this file as `.env`

### 4. Initialize

On the command line run these three commands separately.

```bash
php spark key:generate
php spark migrate --all
php spark db:seed DatabaseSeeder
```

### 5. Web Server

Point your web server to the `public/` folder. `https://your-server/path-to-pubvana`. You should see the homepage of your new website.

### 6. Log In

 Visit `https://your-server/path-to-pubvana/login`.

**Default admin login** — `admin@example.com` / `Admin@12345` — change password immediately after first login.


### 7. File and Directory Structure

Your web host serves files from the directory where `index.php` lives [Detailed Information](https://codeigniter.com/user_guide/installation/running.html#hosting-with-apache). Pubvana uses the default CodeIgniter `~DOC_ROOT/public/` setup and attempts to forward traffic to `/public/index.php` with clean URLs. To increase security or if an `.htaccess` won't be honored (Nginx), you can change where these files reside on the server or edit your Nginx config file. Check the link above for detailed information how to move core files outside the web root, `index.php` into the root folder `public_html` on shared servers.

### 8. Theme Assets and Media

**Theme Assets and Media:** Theme assets and media uploads are stored inside the web server's document root automatically. No symlinks are needed. Visit **Admin → Themes** to ensure theme assets are published.

Quick troubleshooting: If `writable/sessions`, `writable/cache`, and `writable/logs` are not writable by the web user, CodeIgniter will give the `white screen of death` when the environment is set to production. You may find the exact reason in the web server's logs (not CodeIgniter's). If you're having significant trouble diagnosing the issue, set `CI_ENVIRONMENT = development` temporarily in your `.env` file which will show the debug bar and (likely) the exception causing the issue. [CodeIgniter Doc - Running Your App](https://codeigniter.com/user_guide/installation/running.html#) | [CodeIgniter Troubleshooting](https://codeigniter.com/user_guide/installation/troubleshooting.html)

## CLI Commands

| Command | Description |
|---------|-------------|
| `php spark wp:import <file>` | Import posts/pages/tags from a WordPress WXR export file |
| `php spark posts:publish` | Publish scheduled posts whose publish date has passed |
| `php spark links:check` | Scan all published posts and pages for broken external links |
| `php spark marketplace:revalidate` | Re-validate installed premium item licences against pubvana.net |
| `php spark pubvana:update [--dry-run]` | Check for and apply Pubvana core updates |

### Cron Jobs

Scheduled post publishing requires a cron job. Add to crontab by command line:

```
* * * * * path/to/php /path/to/pubvana/spark posts:publish >> /dev/null 2>&1
```

Often it's easier to create Crons in your web control panel (CPanel/DirectAdmin).  To help:
- `* * * * *` are the time slots. 
- `path/to/php /path/to/pubvana/spark posts:publish`  Command to run
- `dev/null 2>&1`  fancy way to say throw it away. You have more choices in your control panel.


Run `path/to/php /path/to/pubvana/spark links:check` as needed (e.g. weekly) — to automate checking for broken links, results appear in Admin → Broken Links.

---

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer (highly recommended)
- Apache `mod_rewrite` (or Nginx equivalent)
- PHP extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `zip`

## Stack (v2)

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4.7 |
| Authentication | CodeIgniter Shield |
| Admin UI | SB Admin 2 (Bootstrap 4 + jQuery) |
| Public theme | Bootstrap 5 + Font Awesome 6 |
| HTML editor | Summernote |
| Markdown editor | SimpleMDE |

## Features (v2)

- Posts & Pages with draft/published/scheduled workflow
- Dual content editor — WYSIWYG HTML or Markdown, selectable per post
- Theme system with sandboxed .tpl engine, widget areas, theme options, and framework-agnostic widgets
- 8 built-in widgets with drag-and-drop area management
- Configurable front page — blog index or any static page
- Marketplace — browse and install free themes & widgets (live API + cache + mock fallback)
- Role-based access — superadmin, admin, editor, author, subscriber
- Media library with auto-generated thumbnails
- Navigation manager with drag-and-drop reordering
- Comment moderation — approve, spam, or trash
- SEO — per-post meta, sitemap.xml, RSS feed, Google Analytics
- 301/302 redirect manager
- Social links manager
- Author profiles with bio cards on posts
- Social OAuth login (Google, Facebook)
- Social auto-share on publish (Twitter, Facebook)
- WordPress importer (admin UI + `php spark wp:import` CLI)
- Post revision history with one-click restore

## Security

### Reporting a Vulnerability

Please **do not** open a public issue for security vulnerabilities. Email security reports to **cs@pubvana.net**. We aim to respond within 48 hours and will credit reporters in the changelog.

### hCaptcha (Spam Protection)

Pubvana uses [hCaptcha](https://www.hcaptcha.com) (privacy-respecting, non-Google) to protect comment forms and the contact form from spam bots. hCaptcha is free for most sites.

**Setup:**

1. Sign up at [hcaptcha.com](https://www.hcaptcha.com) (free)
2. Create a new site and copy the site key and secret key
3. Add to your `.env`:

```
HCAPTCHA_SITE_KEY = your-site-key
HCAPTCHA_SECRET_KEY = your-secret-key
```

If these keys are not set, hCaptcha is silently skipped — safe for local development. Once configured, the widget appears automatically on the comment form and contact page.

---

### Production Hardening Checklist

Before deploying to a public server:

- [ ] Set `CI_ENVIRONMENT = production` in `.env` — disables stack traces and debug output
- [ ] Change the default admin password (`admin@example.com` / `Admin@12345`) immediately after first login
- [ ] Set `app.baseURL` to your actual domain in `.env`
- [ ] Set `app.forceGlobalSecureRequests = true` in `app/Config/App.php` to enforce HTTPS and send HSTS headers
- [ ] Enable CSP: set `app.CSPEnabled = true` in `app/Config/App.php` and configure a policy appropriate to your theme
- [ ] Ensure only `writable/uploads/` is web-accessible — never expose `writable/` itself to the public, as it contains sessions, cache, and logs
- [ ] Ensure `.env` has permissions `600` and is not committed to version control
- [ ] Run `php spark key:generate` once per installation — do not reuse encryption keys across sites

### Content Security Note

Post, page, and widget content is stored and rendered as raw HTML. This is intentional — administrators are trusted to write HTML directly. If your site allows editors or authors to submit HTML content, consider adding server-side HTML sanitization (e.g. [HTML Purifier](http://htmlpurifier.org/)) to your post-save pipeline before rendering untrusted content.

### Security Fixes Log

| Version | Fix |
|---------|-----|
| 2.0.2 | Marketplace ZIP installs: download URL restricted to `pubvana.net`; ZIP entries checked for path traversal |
| 2.0.2 | WordPress importer: switched to `LIBXML_NONET` to block XXE network fetches |
| 2.0.2 | User profile IDOR: `profile` and `saveProfile` now verify ownership or `users.manage` permission |
| 2.0.2 | Theme options: `options` and `saveOptions` now require `admin.themes` permission |
| 2.0.2 | Navigation: `store`, `delete`, `reorder` now require `admin.navigation` permission |
| 2.0.2 | Settings `.env` writer: key whitelist prevents arbitrary env key injection |
| 2.0.2 | Post list status filter validated against whitelist before use in query |
| 2.0.2 | Comment `parent_id` validated against same post to prevent cross-post injection |
| 2.0.2 | RSS feed: `]]>` escaped inside CDATA sections |
| 2.0.2 | WordPress import: 50 MB file size limit to prevent DoS via XML parse |

---

## Bug Reports & Feature Requests

Please use the [Issues Tracker](https://github.com/enlivenapp/pubvana/issues).

## Links

[pubvana.net](https://pubvana.net) — Home & Addon Store (Themes, Widgets, and other Addons)

[User Docs](https://pubvana.net/docs)

[Facebook Page](https://www.facebook.com/pubvana.net)

## License

Pubvana is released under the MIT Open Source License.

## Contributors & Team Members

- Enliven Applications

## Translators & Translations

_Translators Wanted!_

If you would like to help translate files, please fork this repo and send a PR.

v2 ships with 6 languages: English (source), French, Indonesian, Portuguese, Slovak, and Spanish (Latin American). All non-English translations were AI-generated and need verification from native speakers.

Please include a README.md update under 'Translators' with your name and a link to your site/GitHub (optional).

* French — AI translated, needs native speaker verification
  - v1 contributors: [Paul DUBOT](https://github.com/keeganpa), [Léonard GAURIAU](https://github.com/leoDisjonct), [Clément TRASSOUDAINE](https://github.com/intv0id), [Jean-Baptiste VALLADEAU](https://github.com/ignamarte), [Rhagngahr](https://github.com/Rhagngahr)

* Indonesian — AI translated, needs native speaker verification
  - v1 contributor: [Suhindra](https://github.com/suhindra)

* Portuguese — AI translated, needs native speaker verification
  - v1 contributor: [Samuel Fontebasso](https://github.com/fontebasso)

* Slovak — AI translated, needs native speaker verification

* Spanish (Latin American) — AI translated, needs native speaker verification

## Roadmap / Todo

### Pubvana Core

**Built-in Widgets**
- [x] Recent Posts
- [x] Tag Cloud
- [x] Categories List
- [x] Archive List
- [x] Search Form
- [x] Social Links
- [x] Text Block
- [x] Recent Comments
- [x] Table of Contents
- [x] Related Posts

**Platform Features**
- [x] Author Profiles & Bio Card
- [x] Social OAuth Login (Google, Facebook)
- [x] Social Auto-Share on Publish (Twitter, Facebook)
- [x] Marketplace API with cache + refresh
- [x] WordPress Importer (admin UI + `php spark wp:import` CLI)
- [x] Post Revision History
- [x] Maintenance Mode toggle
- [x] Core update notifications + `php spark pubvana:update` CLI
- [x] Content Preview Links (shareable draft URLs)
- [x] Bulk Post Actions (publish / unpublish / delete many)
- [x] Schema.org Markup (Article, BreadcrumbList, Author JSON-LD)
- [x] Image WebP Auto-Convert on Upload
- [x] Multi-language Support (22 languages, admin enable/disable, `{locale}` URL routing, `lang()` throughout views)
- [x] Honeypot Spam Protection (CI4 built-in filter on comment + contact forms)
- [x] Atom 1.0 Feed (`/atom` alongside existing RSS)
- [ ] Email Notifications / Subscriptions (subscribe to new posts, email verification, unsubscribe)
- [ ] Links Manager / Blogroll (display a curated list of external links via widget)

---

### Pubvana Premium

**Premium Core Features** *(pubvana.net — license required)*
- [x] Scheduled Post Queue (calendar view)
- [x] Content Analytics (page views, popular posts, referrers)
- [x] Advanced SEO (OG image generation, schema breadcrumbs, news sitemap)
- [x] Two-Factor Authentication (TOTP)
- [x] Backup & Export (DB + uploads zip)
- [x] Membership / Paywalled Posts
- [x] Affiliate Link Manager (`/go/` short links + click tracking)
- [x] Broken Link Checker
- [x] Activity / Audit Log

**Premium Widgets** *(pubvana.net/store)*
- [x] Author Bio (sidebar)
- [x] Ad Unit / Custom HTML
- [x] Social Follow Buttons
- [ ] Tip Jar / Per-post donations
- [ ] Reading Progress Bar
- [ ] Enhanced Search (AJAX live preview)
- [ ] Email Opt-in / Lead Capture
- [ ] Countdown Timer
- [ ] Advanced Login
- [ ] Gallery (masonry + lightbox)
- [ ] Google Calendar & Maps
- [ ] YouTube Channel Feed

**Premium Plugins** *(pubvana.net/store)*
- [ ] E-commerce (products, cart, checkout, orders)
