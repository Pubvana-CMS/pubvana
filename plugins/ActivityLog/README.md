# Activity Log

Keeps a record of admin changes so you can see who did what (and clear up confusion later).

## Features

- Records admin changes automatically
- List is under Tools, with filters and pages
- Shows last 24 hours on the dashboard
- Retention setting defaults to 365 days
- Limits viewing to staff with permission

## Usage

Open Tools then Activity Log to browse changes. Filter by user, action, item type, name, or date. You'll see when it happened and from which address. The dashboard card shows how many actions happened in the last 24 hours. Tracking stays on unless you turn off `track_admin_actions` in `plugins/ActivityLog/Config/Config.php`. Retention is set by `retention_days` in the same file.

## License

MIT

Note: extensive details can be found in AGENTS.md
