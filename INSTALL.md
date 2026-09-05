# Installing Pubvana

## Prerequisites

- PHP 8.2+ with required extensions
- MySQL 5.7+ or MariaDB 10.3+
- Composer (getcomposer.org)
- A web server (Apache with mod_rewrite, or Nginx)


Create an empty MySQL database and a user with full privileges on it. You'll need the database name, username, and password for setup.  *Composer* is optional in production builds (see Release Download `.zip` file).

## v2 Automated Installation 

See the Pubvana Website for [v2 web installer](https://pubvana.net/dstore/product/pubvana-easy-installer) or [v2 Docker Compose](https://github.com/Pubvana-CMS/v2-docker) on Github. 

## v3 Automated Installation  

*Browser and Docker based installers coming soon.*

## Manual Installation

Choose your preferred release from [our github repository](https://github.com/Pubvana-CMS/pubvana/releases)

**Production (clone(latest) and install):** 

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