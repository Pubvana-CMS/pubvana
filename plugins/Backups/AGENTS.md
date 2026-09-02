# AGENTS.md — Backups plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

**pubvana/backups** is a full-site backup and restore plugin for Pubvana CMS. It zips the app, public files, vendor, and themes, dumps the database, and can roll back to any snapshot.

- **Package:** `pubvana/backups` (local plugin, no Packagist)
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP:** not declared in the plugin (the repo `README.md:17` claims 8.1); the main project requires PHP `^8.2` (repo `composer.json`), which governs, and the code stays within it (`str_contains`/`str_starts_with` used)
- **Namespace:** `Pubvana\Plugins\Backups` (PSR-4 style, matches the folder path)
- **Runtime dependencies:** Pubvana core (plugin interface, admin controller, adext, sessions, shield, CSRF). No third-party composer packages, no service worker
- **Manifest:** `pubvana.json` (admin menu entry)
- **Config:** `Config/Config.php` (no environment variables)
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Keep all file-system and database work in the services.** The controller (`BackupsAdminController.php`) and the CLI commands exist only to trigger operations and report results. New operations belong in `BackupService`, `RestoreService`, or `ProgressReporter`. Reason: `restore` and `create` are callable from both the admin screen and `php runway`, and duplicating the logic in both entry points guarantees they drift apart.
2. **Stay dependency-free and environment-tolerant.** This plugin must run on shared hosting without shell access. No composer runtime dependencies, no required CLI tools. Reason: the pure-PHP fallbacks exist specifically for hosts that disable `exec`/`shell_exec` (`BackupService.php:212`, `RestoreService.php:101`).
3. **Protect the live install during restores.** A restore must never overwrite `protected_configs` and must never follow `..` paths out of the extraction directory. Reason: both failures brick the site, and the second is a remote-write vulnerability.
4. **Never run two backup or restore operations at once.** The `ProgressReporter` lock file is the single source of truth. Reason: concurrent operations corrupt the zip store and the progress files the admin UI polls.
5. **Keep the small public API surface.** `BackupService`, `RestoreService`, and `ProgressReporter` each expose a handful of public methods. Prefer extending these over adding new service classes. Reason: the admin controller and CLI commands already depend on exactly this surface.

## Repository layout

```
Backups/
  Plugin.php                    # Entry point: registers config, maps the 'backups' singleton, adds admin routes
  pubvana.json                  # Plugin manifest and admin menu (Backups, /backups)
  README.md                     # User-facing install and usage docs
  Config/
    Config.php                  # Defaults: max_backups, backup_path, backup_dirs, protected_configs
  Controllers/
    BackupsAdminController.php  # Admin routes: index/download/create/delete/restore/status
  Services/
    BackupService.php           # Create/list/delete backups; dump and restore the database (CLI + PHP fallback)
    RestoreService.php          # Restore orchestration: snapshot, extract, restore files + DB, snapshot again
    ProgressReporter.php        # JSON progress files and the single operation lock
  commands/
    BackupsCreateCommand.php    # runway backups:create
    BackupsRestoreCommand.php   # runway backups:restore [filename]
  Views/
    admin/index.php             # Backup listing screen with create/restore polling JS
```

`writable/backups/` holds the zip files, `operation.lock`, and progress JSON. It is gitignored (`.gitignore:21`). Do not hard-code content from it, and do not hand-edit the files in it.

## Core architecture

### Plugin registration

`Plugin.php:22` registers the plugin. It stores the config under `pubvana.backups`, maps `backups` as a singleton service (`BackupService` wired to `$app->db()` and the raw database credentials), and registers six admin routes through `adext()` under the same config key. `$authMiddleware` is deliberately `null` here; Pubvana's admin route group already protects the routes.

The `BackupService` is the singleton most code talks to. `RestoreService` and `ProgressReporter` are constructed on demand with the config and the backup directory.

### Create flow

`BackupService::createBackup()` (`BackupService.php:58`) produces `{timestamp}-full.zip`. Steps: zip each `backup_dirs` folder, add `database.sql` from `dumpDatabase()`, add `backup-meta.json` (date, trigger, triggered_by, php_version), close the zip, then run retention cleanup.

`dumpDatabase()` (`BackupService.php:212`) tries `mysqldump` first and falls back to `dumpViaPHP()`, a row-by-row pure-PHP export that writes `DROP TABLE` + `CREATE TABLE` + `INSERT`s wrapped in `SET FOREIGN_KEY_CHECKS`. The admin controller and CLI command both route through this same path.

### Restore flow

`RestoreService::restore()` (`RestoreService.php:36`) is a 5-step reversible rollback: snapshot the current state as a `pre-rollback` backup, extract the zip, restore files, restore the database, then take a `post-rollback` backup of the restored state. The extraction directory is always removed in a `finally` block.

Extraction (`RestoreService.php:101`) tries `ZipArchive`, then `exec unzip`, then `PharData`, and only after `validateZipContents()` confirms no entry contains `..` (`RestoreService.php:157`). File restoration skips `protected_configs` (`RestoreService.php:215`), so config and env files survive a rollback.

`restoreDatabase()` (`BackupService.php:231`) mirrors the dump fallback chain: pipe SQL through the `mysql` CLI when available, otherwise split on `;\n` and run each statement via PDO.

### Progress and locking

`ProgressReporter` (`ProgressReporter.php`) writes `{backup|rollback}_progress.json` into the backup directory and owns `operation.lock`. A lock older than 30 minutes is treated as stale and removed (`ProgressReporter.php:41`). The admin controller and both commands must call `acquireLock()` before any operation and `releaseLock()` in a `finally`.

The admin screen posts to `/admin/backups/create` or `/admin/backups/restore/{filename}` and polls `/admin/backups/status`. When `exec` is available the controller backgrounds the runway command and returns `started` immediately; otherwise it runs synchronously with a 300 second time limit. Keep both branches behaving identically.

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo.

```bash
php -l plugins/Backups/Config/Config.php           # lint syntax on every touched file
php -l plugins/Backups/Plugin.php
cd /var/www/html && php runway backups:create      # exercise the CLI create path
php runway backups:restore 2026-01-01_000000-full.zip  # exercise the CLI restore path
```

- Verify both the `exec` branch (background, polled) and the fallback branch (sync) on the create and restore routes from `/admin/backups`.
- Verify a restore leaves `.env` and the `protected_configs` files untouched.
- Verify that a second concurrent operation is refused while a lock is held.
- Coverage: none configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

Steps that go beyond the repo-wide style, derived from the existing code:

1. `declare(strict_types=1);` first line in every class file.
2. Docblock header on every file: `@package Pubvana\Plugins\Backups`, `@copyright 2026 enlivenapp`, `@license MIT`.
3. Class name, file name, and namespace must align: `Pubvana\Plugins\Backups\Services\BackupService` lives in `Services/BackupService.php`.
4. Keep the progress callback contract. All `$onProgress` params are `?callable = null` with signature `fn(int $step, int $total, string $label, string $detail = '')`. Do not introduce a different one.
5. Guard every shell call with `execAvailable()` and escape every shell argument with `escapeshellarg`. Never interpolate raw user input into a command string.
6. Validate backup filenames against `^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$` before any file access. This is the only accepted form.
7. Normalize zip entry paths to forward slashes when adding them (`str_replace('\\', '/', ...)` at `BackupService.php:390`). Do not weaken this.
8. Keep identifiers/table names in generated SQL backtick-quoted and always take them from the database (SHOW statements), never from user input.
9. Prefer `??` defaults matching `Config/Config.php` when reading config, so the singleton and the CLI commands resolve the same values. Do not invent new config keys without adding them to `Config/Config.php` and the README.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | Install, usage, config table, CLI examples |
| [Config/Config.php](./Config/Config.php) | Current defaults for backup_path, max_backups, backup_dirs, protected_configs |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add or remove a directory from backups | `backup_dirs` in `Config/Config.php:15` |
| Change the retention count | `max_backups` in `Config/Config.php:13` |
| Protect another file from restore | `protected_configs` in `Config/Config.php:16` |
| Change the dump or restore fallback chain | `BackupService.php:212` (dump), `BackupService.php:231` (restore) |
| Tweak the pure-PHP export | `dumpViaPHP()` at `BackupService.php:265` |
| Change restore step order or labels | `RestoreService::restore()` at `RestoreService.php:36` |
| Add an admin route | `Plugin.php:42` |
| Add a runway flag or argument | `commands/BackupsCreateCommand.php` or `commands/BackupsRestoreCommand.php` |
| Change the progress JSON shape | `ProgressReporter.php:77` (`update`) and the polling JS in `Views/admin/index.php:163` |

## PR / contribution checklist

- [ ] Changes fit the project guidelines (no new runtime deps, services own the logic)
- [ ] `php -l` clean on every touched file
- [ ] `php runway backups:create` and restore both exercised against a scratch install
- [ ] The no-shell fallback path still works when `exec` is disabled
- [ ] A restore leaves `.env` and `protected_configs` untouched
- [ ] Lock is acquired and released in `try/finally` for any new operation
- [ ] README.md updated if behavior or config changed
- [ ] No secrets committed; nothing from `writable/backups/` added

## Out of scope / non-goals

- Incremental, partial, or scheduled backups. This plugin produces only full `*-full.zip` snapshots on demand.
- Remote or offsite storage, encryption of zip contents, or streaming backups to a cloud bucket.
- Separate database-only or table-level backup UI (the database always ships inside a full zip).
- A drop-in replacement for the host's own mysqldump tooling when shell access exists (the CLI is used as-is, not wrapped).
