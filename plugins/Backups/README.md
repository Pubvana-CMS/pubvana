# Backups

Full-site backup and restore for Pubvana. Zips your files, dumps the database, and lets you roll back to any snapshot.

## Features

- One zip holds your app, public files, vendor, themes, and a SQL dump of the database.
- Restore from any backup. The current state is snapshotted before and after, so a restore is always reversible.
- Retention keeps the newest backups up to your configured limit. The oldest files fall off automatically.
- Config files are never overwritten during a restore, so bookkeeping and connection settings stay safe.
- Database dumps use mysqldump when it is available, and fall back to a pure-PHP export when it is not.
- Long runs report progress to a JSON file, so the admin screen can poll without blocking.
- You can start a backup or restore in the background from admin, or run it from the command line.

## Requirements

- PHP 8.1 or newer
- A Pubvana v3 install (the plugin ships with the essentials: sessions, shield, CSRF)

## Installation

Add the plugin to your `plugins/` folder, and it is ready. Local plugins are enabled by default the first time they are discovered.

If the plugin is disabled for any reason, enable it on Settings → Plugins (`/admin/plugins`).

## Usage

The Backups screen lives at `/admin/backups`. From there you can create a backup, download one, restore from one, or delete one.

From the command line:

```bash
php runway backups:create
php runway backups:create --trigger pre-update --user admin
php runway backups:restore 2026-05-15_221300-full.zip
php runway backups:restore 2026-05-15_221300-full.zip --user admin
```

The `--trigger` flag records why the backup was made (manual, pre-update, pre-rollback, post-rollback) in the backup metadata. `--user` records who started it.

## Configuration

Backups reads its defaults from `plugins/Backups/Config/Config.php`. No environment variables are involved.

| Key | Default | Description |
|-----|---------|-------------|
| `backup_path` | `PROJECT_ROOT/writable/backups` | Where backup zips are stored |
| `max_backups` | `15` | How many backups to keep before deleting the oldest |
| `backup_dirs` | `['app', 'public', 'vendor', 'themes']` | Directories included in each backup |
| `protected_configs` | List of config/env files | Files never overwritten during a restore |

## Contributing

This is a bundled Pubvana plugin, so it follows the repo's plugin conventions. If you change it, run `php -l` on the files you touched and test the create and restore flows from `php runway`.