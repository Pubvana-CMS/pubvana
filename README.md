# Pubvana

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Release](https://img.shields.io/badge/release-v2.3.1-blue)](https://github.com/enlivenapp/pubvana/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-orange.svg)](https://codeigniter.com)
[![Installs](https://img.shields.io/packagist/dt/enlivenapp/pubvana.svg)](https://packagist.org/packages/enlivenapp/pubvana)
[![Stars](https://img.shields.io/github/stars/enlivenapp/pubvana?style=flat)](https://github.com/enlivenapp/pubvana/stargazers)
[![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg)](https://github.com/enlivenapp/pubvana/issues)

### Blogging and Small Business CMS

Pubvana v2 is a full rewrite of Pubvana v1.x built on CodeIgniter 4, Authentication with Shield, a modern admin UI, dual content editors, theme, plugin & widget system, built-in marketplace, and many new features. We aim for Pubvana to be lean and fast without the bloat of other CMS and Blog software available.

These instructions are for users comfortable with the command line and terminal. If you'd prefer a streamlined, no command line experience, [Download from GitHib](https://github.com/enlivenapp/Pubvana-Web-Installer) or [Pubvana.net](https://pubvana.net).

### For Developers

Developers looking to build themes or widgets for Pubvana can find everything they need in these guides:

- **[ThemeBuilder.md](ThemeBuilder.md)** — Complete theme development guide
- **[WidgetBuilder.md](WidgetBuilder.md)** — Complete widget development guide
- **[PluginBuilder.md](PluginBuilder.md)** — Complete plugin development guide
- **[ThirdPartyAddons.md](ThirdPartyAddons.md)** — Third-party addon distribution, licensing, and API protocol

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

On the command line run these commands separately.

```bash
php spark key:generate
php spark migrate --all
php spark db:seed DatabaseSeeder
```

Then create your admin user:

```bash
php spark shield:user create -n yourusername -e you@example.com
php spark shield:user password -e you@example.com
php spark shield:user addgroup -e you@example.com -g superadmin
```

The first command creates the account, the second prompts you to set a password, and the third assigns the superadmin role.

### 5. Web Server

Point your web server to the `public/` folder either by editing your available sites in Apache, or Nginx config. if you use Apache or Litespeed, your site should be available at `https://your-server/path-to-pubvana` with the provided ,htaccess files. You should see the homepage of your new website.

### 6. Log In

 Visit `https://your-server/login` and sign in with the admin credentials you created in step 4.


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
| Framework | CodeIgniter 4.7+ |
| Authentication | CodeIgniter Shield 1.3+ |
| Admin UI | SB Admin 2 (Bootstrap 4 + jQuery) |
| Public Default theme | Bootstrap 5 + Font Awesome 6 |
| HTML editor | Summernote |
| Markdown editor | EasyMDE |

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
- WP importer (admin UI + `php spark wp:import` CLI)
- Post revision history with one-click restore

## Security

### Reporting a Vulnerability

Please **do not** open a public issue for security vulnerabilities. Email security reports to **cs@pubvana.net**. We aim to respond within 48 hours and will credit reporters in the changelog.

### hCaptcha (Spam Protection)

Pubvana uses [hCaptcha](https://www.hcaptcha.com) (privacy-respecting) to protect comment forms and the contact form from spam bots. hCaptcha is free for most sites.

**Setup:**

1. Sign up at [hcaptcha.com](https://www.hcaptcha.com) (free)
2. Create a new site and copy the site key and secret key
3. Add to your setting in the Admin Panel:

```
HCAPTCHA_SITE_KEY = your-site-key
HCAPTCHA_SECRET_KEY = your-secret-key
```

If these keys are not set, hCaptcha is silently skipped — safe for local development. Once configured, the widget appears automatically on the comment form and contact page.

---

### Production Hardening Checklist

Before deploying to a public server:

- [ ] Set `CI_ENVIRONMENT = production` in `.env` — disables stack traces and debug output
- [ ] Use a strong password for your admin account
- [ ] Set `app.baseURL` to your actual domain in `.env`
- [ ] Set `app.forceGlobalSecureRequests = true` in `app/Config/App.php` to enforce HTTPS and send HSTS headers
- [ ] Enable CSP: set `app.CSPEnabled = true` in `app/Config/App.php` and configure a policy appropriate to your theme - Note, this is often tricky to get right.
- [ ] Verify your web server's DocumentRoot points to `public/` if possible, this keeps `writable/` (sessions, cache, logs) outside the web root automatically
- [ ] Ensure `.env` has permissions `600` and is not committed to version control
- [ ] Run `php spark key:generate` once per installation — do not reuse encryption keys across sites

### Content Security Note

Post, page, and widget content is stored and rendered as raw HTML. This is intentional — administrators are trusted to write HTML directly. If your site allows editors or authors to submit HTML content, consider adding server-side HTML sanitization (e.g. [HTML Purifier](http://htmlpurifier.org/)) to your post-save pipeline before rendering untrusted content.

### Security Fixes Log

| Version | Fix |
|---------|-----|
| 2.2.3 | DB dump escaping fixed: replaced `escapeLikeString()` with `escape()` — old method could corrupt or expose data in backup SQL dumps |
| 2.2.2 | Theme and widget sandboxing: custom `.tpl` template engine with whitelisted filters and tag functions replaces raw PHP execution. No PHP files permitted in theme or widget directories — themes and widgets are pure `.tpl` templates + JSON manifests. PHP validation warnings shown in admin if violations detected. Eliminates arbitrary code execution via uploaded or modified themes/widgets. |
| 2.2.2 | CSRF fields exposed to theme templates; comment form uses proper CSRF tag |
| 2.2.0 | Honeypot spam protection re-enabled on comment and contact form POST routes; field name changed from `honeypot` to `website_url` to reduce bot evasion |
| 2.0.4 | Permissions overhaul: explicit `can()` checks on 14 admin controllers |
| 2.0.4 | Login-gated comments with rate limiting (5 per user per 10 minutes) |
| 2.0.4 | hCaptcha spam protection on comment and contact forms |
| 2.0.4 | Revisions: authors restricted to own post revisions only |
| 2.0.4 | Delete remember tokens on user ban to prevent cookie re-auth |
| 2.0.4 | Site owner protected from modification/deletion by non-owners |
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

[pubvana.net](https://pubvana.net) — Home & Addon Store (Themes, Widgets, Plugins and easy installer)

[User Docs](https://pubvana.net/pvdocs)

[Facebook Page](https://www.facebook.com/pubvana.net)

## License

Pubvana is released under the MIT Open Source License.

## Contributors

- Enliven Applications

## Translations

_Translators Wanted!_

Pubvana ships with 24 languages: English (source), Spanish (Latin American), French, Indonesian, Portuguese, and Slovak. French, Slovak, Indonesian, Bulgarian (bg), Bengali (bn), Czech (cs), German (de), Hindi (hi), Italian (it), Japanese (ja), Korean (ko), Lithuanian (lt), Dutch (nl), Polish (pl), Brazilian Portuguese (pt-BR), Russian (ru), Serbian (sr), Swedish (sv-SE), Turkish (tr), Ukrainian (uk), Chinese Simplified (zh) and Portuguese are partially or fully AI-translated and need verification from native speakers.

If you would like to help verify or add translations, please fork this repo and send a PR.

Many Thanks to the folks who've provided translation. It is very apprciated.

* French — [Paul DUBOT](https://github.com/keeganpa), [Léonard GAURIAU](https://github.com/leoDisjonct), [Clément TRASSOUDAINE](https://github.com/intv0id), [Jean-Baptiste VALLADEAU](https://github.com/ignamarte), [Rhagngahr](https://github.com/Rhagngahr)
* Indonesian — [Suhindra](https://github.com/suhindra)
* Portuguese — [Samuel Fontebasso](https://github.com/fontebasso)
* Slovak — Kristián Feldsam

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
- [ ] Links Manager / Blogroll (display a curated list of external links via widget)
- [x] Scheduled Post Queue (calendar view)
- [x] Content Analytics (page views, popular posts, referrers)
- [x] Advanced SEO (OG image generation, schema breadcrumbs, news sitemap)
- [x] Two-Factor Authentication (TOTP)
- [x] Backup & Export (DB + uploads zip)
- [x] Membership / Paywalled Posts
- [x] Affiliate Link Manager (`/go/` short links + click tracking)
- [x] Broken Link Checker
- [x] Activity / Audit Log
- [x] Author Bio widget
- [x] Ad Unit / Custom HTML widget
- [x] Social Follow Buttons widget


**Todo**
- [ ] Email Notifications / Subscriptions (subscribe to new posts, email verification, unsubscribe)

**Premium Widgets** *(pubvana.net/store)*

- [ ] Reading Progress Bar
- [ ] Countdown Timer
- [ ] Google Calendar & Maps
- [ ] YouTube Channel Feed ( 1 video/widget)

**Premium Plugins** *(pubvana.net/store)*
- [x] PvDocs - Documentation for User & Dev facing docs
- [x] Digital E-commerce (products, cart, checkout, orders)
- [ ] Physical goods store (w/ drop shipping and delivery integration)
- [ ] Enhanced Search (AJAX live preview)
- [ ] Tip Jar / Per-post donations
- [ ] Email Opt-in / Lead Capture
- [ ] Gallery (masonry + lightbox)
- [ ] Google Calendar & Maps
- [ ] YouTube Channel Feed (fully searchable integration)


