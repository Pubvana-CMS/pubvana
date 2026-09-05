# Updates

Keeps Pubvana current. Checks the release feed, tells you what is new, and
applies core updates: manually when you say so, or automatically when you
switch them on.

## Features

- Version check against the project's `releases.json` feed, cached for 24 hours.
- Safe-target capping: if an installed plugin or theme declares a Pubvana
  compatibility limit (`max_pubvana_version` / `min_pubvana_version` in its
  manifest), the offered update stops below it instead of breaking things.
- Breaking changes, notices, and migration notes for the whole update path
  are shown before anything runs.
- A full backup is taken before every update. The update refuses to start
  unless that backup succeeds, and rollback is a restore in Tools > Backups.
- Granular live progress: preflight, backup, download (with byte counts),
  validate, extract, copy (per directory), migrate, cleanup.
- Automatic updates are opt-in and off by default. When on, opening the
  Updates page (at most once per day) or the cron command applies any
  applicable pending update. Automatic updates never cross breaking changes.
- Skip list: pass over a troublesome release; the next good one is offered
  instead.
- Site Health check and a dashboard card report the local update state.

## Requirements

- A Pubvana v3 install (the plugin ships with the essentials)
- The Backups plugin enabled: it provides the mandatory pre-update snapshot
- The `zip` PHP extension (ZipArchive) for validation and extraction
- PHP 8.2 or newer

## Installation

Add the plugin to your `plugins/` folder, and it is ready. Local plugins are
enabled by default the first time they are discovered.

If the plugin is disabled for any reason, enable it on Settings → Plugins
(`/admin/plugins`).

## Usage

The Updates screen lives at **Tools > Updates** (`/admin/updates`), laid out
like the v2 page:

- **Update Settings** card: the Pubvana Auto-Update toggle (Manual / Automatic)
  with its one-line help, plus the three crontab lines that run Pubvana's cron
  tasks. Saving shows an inline confirmation.
- **Check for updates** refreshes the release feed immediately.
- When an update is available you get a status banner (your version vs the
  target), notices, migration notes, and any breaking changes, followed by a
  preflight table where each check is marked **Required** or **Optional**.
- **Update to version X** opens a confirmation, then runs the whole chain with
  live progress. The button is disabled while any Required check fails.
  Optional failures (like no command-line access) warn but do not block.
- **Skip this version** moves the offer to the next applicable release.

**Addons**: the lower half of the page lists every installed theme, block, and
plugin. Themes and plugins show their version with "No update source" until a
marketplace source exists; the Check All / Update All buttons light up then.
Blocks are version-locked to whatever defines them, so the Blocks card simply
shows which plugin or core component updates each one.

A pre-update backup lands in Tools > Backups tagged `pre-update`. If an
update fails partway, restore that snapshot to get back to a working site.

### Command line

```bash
php runway updates:check                # report the update state
php runway updates:check --force        # bypass the 24h cache
php runway updates:apply                # apply the safe target
php runway updates:apply --release 3.0.2 --user admin
php runway updates:auto-update          # check, then apply when allowed
```

`updates:auto-update` is the cron entry point: it runs the full chain
(check, then apply only when automatic updates are on, a clean target
exists, and no breaking changes are in the path). It is a no-op that exits
cleanly when there is nothing to do, so it is safe to invoke on any
schedule.

### Scheduled (cron)

Updates registers a daily task with Pubvana's core cron system (the 24h
slot, see `docs/Cron.md`). The task runs the same chain as the command
above: it applies a pending update only while automatic updates are
switched on, refuses to cross breaking changes, and aborts unless the
pre-update backup succeeds. With automatic updates off it just refreshes
the release check.

The cron system needs the three crontab lines from `docs/Cron.md` added
to the site's crontab once; every plugin's tasks then run on those
schedules. A run where this task fails lands in
`writable/logs/cron.log` and exits with code 2, so deployment monitoring
can pick it up.

## What an update does

1. Locks the operation (30-minute stale-lock recovery).
2. Preflight: PHP version floor, disk space, writable directories, Backups
   plugin available, no other operation running.
3. Creates a full `pre-update` backup (fails the update if the backup fails).
4. Downloads the release zip from the `download_url` recorded in the feed.
5. Validates every zip entry (path traversal is rejected) and handles
   GitHub-style wrapper directories.
6. Copies `app/`, `public/`, `vendor/`, `plugins/`, `themes/` and root files.
   `.env`, `app/config/shield.php`, and `writable/` are never overwritten.
7. Runs pending migrations (`php runway migrate`, in-process fallback).
8. Cleans up: removes the zip and extraction directory, clears the runtime
   cache, re-reads the new version.

Rollback is not built into the update: restore the pre-update snapshot from
Tools > Backups. Note that updates overwrite `vendor/` with the stock
release, so any composer packages you added yourself are replaced.

## Configuration

Defaults live in `plugins/Updates/Config/Config.php`.

| Key | Default | Description |
|-----|---------|-------------|
| `releases_url` | GitHub raw `releases.json` | The release feed installed sites fetch |
| `user_agent` | `Pubvana-Updates/3.0` | HTTP user agent |
| `check_timeout` | `5` | Seconds for the release check |
| `download_timeout` | `300` | Seconds for the release download |
| `updates_path` | `writable/updates` | Zips, extraction, lock, progress files |
| `protected_paths` | `.env`, `app/config/shield.php`, `writable` | Never overwritten |
| `min_free_disk_mb` | `500` | Preflight floor |
| `check_cache_hours` | `24` | How long a check result is trusted |
| `manifest_path` | `pubvana.json` | Installed-version source |

## Permissions

`updates.manage` (seeded) is required to view the Updates page and run any
operation. Superadmins bypass the check.

## Contributing

Bundled plugin: run `php -l` on files you touch, `composer phpstan`, and
`vendor/bin/phpunit`. The version/target logic and zip validation are covered
by unit tests in `tests/Unit/Plugins/Updates/`.
