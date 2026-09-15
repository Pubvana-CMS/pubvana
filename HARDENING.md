# Pubvana Production Hardening

A checklist for securing a Pubvana installation for actual live use. This guide follows the [OWASP Top 10:2025](https://top10.owasp.org/2025/) recommendations. 

Installation instructions are in `INSTALL.md`. Please report security issues using instructions in `SECURITY.md`.

## 1. Environment

Edit `.env` in the Pubvana root and set these values:

```
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
SITE_URL=https://your-domain.com/
SITE_NAME=Your Site
ADMIN_EMAIL=you@example.com
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
SESSION_ENCRYPTION_KEY=<64 hex chars>
```

Generate a new session key in your terminal, then paste it into `SESSION_ENCRYPTION_KEY` in `.env`:

```
php -r 'echo bin2hex(random_bytes(32));'
```

- `APP_ENV=production` and `APP_DEBUG=false` turn off debug output.
- `FORCE_HTTPS=true` makes every page redirect to HTTPS.
- `SITE_URL` must be the real domain, with `https://`.
- Give the database user rights on its own database only, not the whole server.
- Secure `.env` in the terminal: `chmod 600 .env`.
- On hosts with a secrets manager, put these values there instead of `.env`.

## 2. Web root and file access

The web server must only serve the `public/` folder. Everything else (`.env`, `app/`, `vendor/`, `writable/`, `plugins/`) must stay out of reach.

### Standard hosting (VPS, Docker, dedicated)

You control the web server config.

1. Point the domain's DocumentRoot at `public/`.
2. Leave every other folder one level above the web root.
3. Set folder permissions to `755` and files to `644`.
4. Only `writable/*` and `public/uploads/*` need to be writable by the web server.
5. Delete install leftovers.

### Shared hosting (cPanel, Plesk)

You have limited control.

1. If the panel lets you set the document root, point the domain at `public/`.
2. If the web root is locked to the install folder, leave the files exactly as installed.
3. If the host ignores `.htaccess`, this setup is not safe. We strongly recommend a host that honors `.htaccess` or lets you set the document root.
4. Set folder permissions to `755` and files to `644`.
5. Only `writable/*` and `public/uploads/*` need to be writable by the web server.
6. Delete install leftovers.

### Nginx

`.htaccess` does not apply on Nginx.

** We'll get to Nginx config in a separate file **

1. Set the server root to `public/`.
2. Rewrite anything that is not a real file to `index.php`.
3. Deny `/.env`, `/app/`, `/vendor/`, `/writable/`, and `/plugins/`.
4. Forward the `Authorization` header.
5. Set folder permissions to `755` and files to `644`.
6. Only `writable/*` and `public/uploads/*` need to be writable by the web server.

### Check it

Open these URLs in a browser or with `curl`. Each must return `403` or `404`, never the file contents:

- `/.env`
- `/.git/HEAD`
- `/composer.json`
- `/vendor/autoload.php`
- `/writable/logs/`

## 3. HTTPS and security headers

1. Serve the whole site over HTTPS with a valid certificate (Let's Encrypt or your host's).
2. Keep TLS at 1.2 or newer at the server or proxy.
3. Check that `http://your-domain.com/` redirects to `https://your-domain.com/`.
4. Check that the HTTPS response includes HSTS and content security headers.

## 4. Auth, users, and access control

1. Creating the first admin through the terminal is the most secure way, as shown in `INSTALL.md`.
2. Use a long, unique password from a password manager.
3. Elevate a user only to permissions they need.
4. Turn on email 2FA.
5. If the site allows public signups, turn on email activation. If it does not, turn off public registration.
6. Watch login failure logs for password guessing. The built in rate limiter locks out repeated attempts.
7. Review active sessions and end any you do not recognize. Changing the session key signs everyone out and forces everyone to login again.

## 5. Content and uploads

1. Allow only the upload types the site actually needs.
2. Make sure uploaded files cannot run as scripts.
3. Moderate comments where possible, and limit who can post links or HTML.

## 6. Crypto and mail

1. Keep secrets in `.env` or the host secrets manager. Never paste them into settings, posts, or anywhere public.
2. Use SMTP with authentication and encryption (SMTPS or STARTTLS).
3. Never send passwords or reset links over plain HTTP.


## 7. Updates and supply chain

1. Back up files and the database before every update, and confirm the backup restores.
2. Install plugins and themes only from sources you trust.
3. Watch the Updates plugin for core notices and apply security updates promptly.

## 8. Logging, monitoring, and backups

1. Monitor the ActivityLog plugin and review them for guessing logins or reset abuse.
2. Watch for 404 scans and redirect or broken link spikes.
3. Run SiteHealth checks after each update.
4. Copy backups off the server and test a restore on staging.

## 9. Error handling

1. Confirm `.env` has `APP_DEBUG=false`.
2. Open a missing page and confirm visitors see a generic error, not a stack trace or version number.


## Go live checklist

1. `.env` has `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, and the real `SITE_URL`.
2. `http://your-domain.com/` redirects to HTTPS, and the HTTPS response has HSTS and CSP headers.
3. `/.env`, `/app/`, `/vendor/`, and `/writable/` return `403` or `404`.
4. Admin login, logout, and password change all work. No default credentials remain.
5. Logs write to `writable/logs/` and ActivityLog records admin actions.
6. A backup completes and restores on staging server.
