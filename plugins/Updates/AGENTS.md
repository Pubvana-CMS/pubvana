# AGENTS.md — Updates plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

**pubvana/updates** checks `releases.json` for new Pubvana versions and applies core updates, manually or (opt-in) automatically. The pre-update backup through the Backups plugin is a hard requirement and is the rollback path.

- **Package:** `pubvana/updates` (`pubvana.json:2`), semver `0.1.0`, category `tools`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** the main project requires PHP `^8.2` (repo `composer.json`); the code stays within it (no 8.3/8.4-only syntax)
- **Namespace:** `Pubvana\Plugins\Updates` (`Plugin.php:5`), with `Controllers`, `Services`, `commands` (lowercase, PSR-4 must match the directory), and `Database\Seeds` sub-namespaces
- **Runtime dependencies:** Pubvana core (`Engine`, `AdminController`, `PluginInterface`, adext, settings, shield, sessions, CSRF), the Backups plugin (`BackupService`, `ProgressReporter` for its lock), `enlivenapp/migrations` (in-process migration fallback), curl extension with `file_get_contents` fallbacks everywhere
- **No database tables.** State lives in the settings store (`Updates.*` keys: `autoUpdate`, `lastCheckAt`, `lastCheckResult`, `skippedVersions`) and on disk under `writable/updates/`
- **Config:** `Config/Config.php`: `releases_url`, `user_agent`, `check_timeout`, `download_timeout`, `updates_path`, `protected_paths`, `min_free_disk_mb`, `check_cache_hours`, `manifest_path`
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **One code path for web and CLI.** `updates:apply`, `updates:auto-update`, and the admin controller all call `UpdateApplyService::apply()`; nothing duplicates the flow. Reason: divergent apply paths is how sites get half-updated.
2. **Backup is non-negotiable.** `runBackup()` must succeed before download/copy start (`UpdateApplyService.php`). A backup failure, or the Backups plugin being missing, aborts the update. Reason: the backup is the only rollback mechanism; v2 learned this the hard way.
3. **Never construct download URLs client-side.** The `download_url` comes from the release feed entry (`runDownload()`). Reason: v2 hardcoded a release host that later moved and broke every deployed updater.
4. **Validate every zip entry before extraction.** `zipEntriesAreSafe()` rejects `..`, absolute paths, drive letters, and NUL bytes; `detectInnerDir()` handles GitHub wrapper dirs. Do not weaken either. Reason: remote zip + overwrite flow is a remote-write vulnerability.
5. **Protect the deployment's identity.** `protected_paths` (`.env`, `app/config/shield.php`, `writable/`) is never overwritten by the copy phase. Reason: credentials, HMAC keys, and runtime data must survive updates.
6. **Automatic updates never cross breaking changes.** Only `updates:apply` (manual) may pass `$ignoreBreaking = true`, and the web UI forces explicit confirmation first. Reason: unattended breakage is worse than a delayed update.
7. **Keep progress granular.** `UpdateProgress` writes a phase checklist plus a free-form detail line (download bytes via `CURLOPT_XFERINFOFUNCTION`, per-directory copy counts). The admin UI polls `update_progress.json`. Reason: v2's "starting..." then silence was the most complained-about UX gap.
8. **The apply flow holds the update lock before preflight; preflight must not check locks itself.** `preFlight($version, false)` from the apply path; `locksCheck()` runs only for display on the index page. Reason: the flow would flag its own lock (real bug found in testing).
9. **Check state is cached 24h in settings, never on dashboard renders with network.** `dashboardCards()` and the Site Health check read `lastCheck()` only. Reason: no network on every dashboard load; SiteHealth forbids network in checks.
10. **Controllers strip `_csrf_token` before POST data use; permission gate is flash + redirect, never `halt()`.** Standard v3 patterns.

## Repository layout

```
Updates/
├── pubvana.json                          Manifest: Tools > Updates menu, dashboard hooks
├── Plugin.php                            Entry: maps 'updates' facade, admin routes, dashboard card, Site Health check
├── Config/Config.php                     Defaults (see table in README)
├── Controllers/
│   └── UpdatesAdminController.php        index, check, apply (AJAX), status (poll), settings, skip, unskip
├── Services/
│   ├── UpdateService.php                 Feed fetch, 24h check cache, safe-target capping, preflight, skip list
│   ├── UpdateApplyService.php            8-phase apply: preflight, backup, download, validate, extract, copy, migrate, cleanup
│   └── UpdateProgress.php                Phase checklist + detail line JSON, operation lock (30-min stale)
├── commands/
│   ├── UpdatesCheckCommand.php           runway updates:check [--force]
│   ├── UpdatesApplyCommand.php           runway updates:apply [--release X --user Y]
│   └── UpdatesAutoUpdateCommand.php      runway updates:auto-update (cron target; shares the chain)
├── Database/
│   └── Seeds/Seed.php                    Seeds updates.manage permission
├── Views/
│   └── admin/index.php                   v2-style layout: Update Settings card (3 groups: auto-update toggle, check info, crontab lines), status banners, preflight table (Required/Optional), confirm modal, progress polling, Addons section (Themes/Plugins inventory tables + Blocks "Updates with" table), skip list
├── README.md
└── AGENTS.md                             This file
```

Repo-level companions owned by this feature: root `releases.json` (machine feed, per-entry `download_url`), `CHANGELOG.md` (human log), `.github/workflows/release.yml` (tag/semver guard, CHANGELOG+releases.json sync guards, builds `release.zip` with vendor/ included), `.gitignore` entry for `/writable/updates/*`.

## Core architecture

### Check flow

`UpdateService::check()` fetches and sorts the feed (newest first), reads the installed version from `pubvana.json`, then `pickTarget()` chooses the highest release above current that is not skipped and not rejected by any plugin/theme manifest constraint (`min_pubvana_version` / `max_pubvana_version`; absent = no constraint). The state (status, target, breaking changes, notices, migration notes, capped_by, error) is persisted to `Updates.lastCheckAt` + `Updates.lastCheckResult` and reused for 24h.

### Apply flow (8 phases, `UpdateApplyService::apply()`)

Lock → preflight (PHP floor from `min_php_version` across the range, disk, writables, Backups availability) → backup via `$app->backups()->createBackup('pre-update', ...)` while holding the Backups lock → download with byte-level progress → validate + detect wrapper dir → extract to `writable/updates/extract` → copy everything except protected paths (dirs 0755, per-directory counts) → migrate (`php runway migrate` subprocess, `MigrationSetup` in-process fallback; failure is reported, never fatal at this point) → cleanup (zip, extract dir, `writable/cache` clear) → `complete()` with a structured result.

Migration failure does not abort the update: files are already in place, and the result carries `migrations_error` so the admin can run `php runway migrate` manually.

### Automatic chain

`UpdateService::runAutoUpdateChain()` is the single implementation: force-check, then apply only when `Updates.autoUpdate` is on, a target exists, and no breaking changes are in range. It returns `status` (ok|noop|refused|error), `message`, and `version`. Two consumers share it: the `updates:auto-update` command (prints, exit 1 on error) and the core cron task. The web equivalent is the index page: when the check is stale it refreshes; when auto is on and the target is clean, it backgrounds `php runway updates:auto-update --user {name}`. Both web paths are 24h cache-gated.

**Core cron wiring:** the plugin registers a `24h` task (`pubvana.updates`) with the core cron system (`docs/cron.md`, `CronService`). The task callable throws only on `error`, so a real failure shows as FAILED in `writable/logs/cron.log` and exits the run with code 2; `noop` and `refused` return quietly. Never register additional intervals; long-running update work belongs in `24h` only.

## Development and testing

Unit tests live in `tests/Unit/Plugins/Updates/` (version/target math, zip safety, copy/lock/progress, phase list) and run with the suite. The apply flow needs a live feed and filesystem; exercise it in a scratch copy:

```bash
php -l <touched files>                 # lint
composer phpstan                       # level 8
composer psalm                         # taint analysis
vendor/bin/phpunit                     # full suite
php runway updates:check --force       # CLI exercise (graceful error until the feed is published)
```

Scratch end-to-end (done once for v1; repeat when changing the apply flow): copy the repo to /tmp, point `releases_url` at a local `releases.json` + zip (file:// URLs work), run `php runway updates:apply --user e2e`, verify version bump, backup zip, marker file, progress JSON `completed`, then `updates:check` reports up to date. Also verify: auto refused while setting off, auto refused through breaking changes, skip/unskip behavior, and the lock self-conflict does not reappear.

## Coding standards

- **PHPStan (level 8):** the `updates()` facade has an `@phpstan-method` entry and an `UpdateService` shell in `phpstan-stubs.php`. Engine parameters/properties use the `Engine<object>` generic spelling. Run `composer phpstan` before committing.

1. `declare(strict_types=1);` first line in every class file.
2. Docblock header on every file: `@package Pubvana\Plugins\Updates`, `@copyright 2026 enlivenapp`, `@license MIT`.
3. Namespace matches directory exactly (`commands` lowercase for CLI commands).
4. All HTTP calls: curl first, `file_get_contents` fallback, both with timeouts and a non-empty user agent. Escape every shell argument; guard `exec` with an availability check.
5. Timestamps via `date('c')`; status strings come from the fixed vocabularies in `UpdateProgress` (`pending|active|done`, `in_progress|completed|error`).
6. Views escape everything with `htmlspecialchars`; JS is inline (repo convention), polling via `fetch`, no external files.
7. New settings keys must use the `Updates.` namespace and be read through the settings store, not the config file.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | User-facing behavior, config table, CLI + cron usage |
| `releases.json` (repo root) | Feed schema: `version`, `release_date`, `min_php_version`, `breaking_changes`, `migration_notes`, `notices`, `download_url` |
| `.github/workflows/release.yml` | Release packaging and the three guards |
| `plugins/Backups/AGENTS.md` | The backup/restore contract this plugin depends on |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a preflight check | `UpdateService::preFlight()` (set the `hard` flag: Required checks block the Apply button and abort the run; Optional failures become warnings) |
| Change the phase list | `UpdateApplyService::phases()` + the view's rendering is data-driven |
| Change cron task behavior | `UpdateService::runAutoUpdateChain()` + the registration in `Plugin.php` |
| Tune safe-target behavior | `UpdateService::pickTarget()` (pure static, unit-tested) |
| Protect another path from updates | `protected_paths` in `Config/Config.php` |
| Change the feed | Repo root `releases.json`; keep `CHANGELOG.md` in sync (workflow guards it) |
| Add a CLI flag | The matching `commands/` class; avoid `--version` (runway registers it globally) |
| Change admin UI | `Views/admin/index.php`; keep polling JS inline |

## PR / contribution checklist

- [ ] `php -l` clean on every touched file; `composer phpstan` and `composer psalm` clean
- [ ] `vendor/bin/phpunit` green
- [ ] Backup still aborts the update on failure; protected paths still skipped
- [ ] Automatic updates still refuse breaking changes; manual still requires confirmation
- [ ] Progress JSON phases/detail render in the admin UI; no external JS added
- [ ] Lock is acquired/released in `try/finally`; stale recovery intact
- [ ] README updated if user-facing behavior or config changed

## Out of scope / non-goals

- Plugin and theme updates from a store (Marketplace/Digital Store territory); this plugin reads their manifests only for compatibility capping.
- In-place rollback: rollback is a Backups restore of the pre-update snapshot.
- Delta/patch updates: every release is a full-file zip including `vendor/`.
- Deleting files removed between releases (a full-file copy cannot know what to remove).
- The cron system itself (runner, schedules, registry type): owned by core. This plugin only ships the chain the scheduler will invoke.
- Email notifications on new releases; the dashboard card and Site Health check are the notification surface.
