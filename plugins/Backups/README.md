# Backups

Back up your whole site and restore it later. One file holds your site files and your database, so you can roll back to any saved point.

## Features

- Restore from any backup. Your site is saved again before and after, so a restore can always be undone.
- Keeps the newest backups up to your limit. The oldest ones drop off automatically.
- Start a backup or restore from the admin screen, or from the command line.
- A restore takes a pre-restore snapshot first, so the restore itself can be undone.

## Usage

Open **Tools → Backups**. From there you can create a backup, download one, restore from one, or delete one.

From the command line:

```bash
php runway backups:create
php runway backups:create --trigger manual --user admin
php runway backups:restore 2026-05-15_221300-full.zip
php runway backups:restore 2026-05-15_221300-full.zip --user admin
```

`backups:create --trigger` records why the backup was taken: `manual`,
`pre-update`, `pre-rollback` or `post-rollback`. Both commands take `--user` to
record who started them.

## License

MIT

Note: extensive details can be found in AGENTS.md