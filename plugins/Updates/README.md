# Updates

Keeps Pubvana current. Checks the release feed, tells you what is new, and applies core updates: manually when you say so, or automatically when you switch them on.

## Features

- Version check against the project's `releases.json` feed, cached for 24 hours
- Safe-target capping: updates stop below any plugin or theme compatibility limit
- Breaking changes, notices, and migration notes shown before anything runs
- A full backup is taken before every update, and rollback is a restore in Tools → Backups
- Granular live progress through every phase
- Automatic updates are opt-in and off by default, and never cross breaking changes
- Skip list: pass over a troublesome release
- Site Health check and a dashboard card report the local update state

## Usage

The Updates screen is at **Tools → Updates**. Check for updates, review the notices and preflight table, then update. Addons are listed below with their update source.

Command line:

```bash
php runway updates:check                # report the update state
php runway updates:apply                # apply the safe target
php runway updates:auto-update          # check, then apply when allowed
```

A pre-update backup lands in Tools → Backups tagged `pre-update`. If an update fails partway, restore that snapshot to get back to a working site.

Managing updates requires the updates.manage permission.

## License

MIT

Note: extensive details can be found in AGENTS.md
