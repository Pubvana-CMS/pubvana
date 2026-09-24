# Softaculous package for Pubvana CMS v3

This folder is the Softaculous install package. It replaces the stale
[Pubvana 1.0.4](https://www.softaculous.com/apps/blogs/pubvana) listing that
people still install from old cPanel and DirectAdmin panels.

## Files

| File | Purpose |
| --- | --- |
| `info.xml` | Application metadata for the Softaculous app library. |
| `install.xml` | Install form (site name + admin account), MySQL requirement, and the three cron jobs. |
| `install.php` | Softaculous `__install()`. Writes `.env`, runs migrations, creates the superadmin user. |
| `install.js` | Client side form validation. |
| `upgrade.xml` | Upgrade definition. Keeps `.env`, `.runway-config.json` and `writable/`, replaces everything else. |
| `upgrade.php` | Softaculous `__upgrade()`. Runs pending migrations on the existing database. |
| `upgrade.js` | Upgrade form validation (no inputs, always allowed). |
| `fileindex.php` | Top level files and folders to remove on uninstall. |

## Building the app zip

The app zip (`pubvana.zip`) is the GitHub release asset `release.zip`. It
bundles `vendor/`, so no Composer step happens on the server.

```bash
curl -L -o pubvana.zip \
  https://github.com/Pubvana-CMS/pubvana/releases/download/3.0.0-beta.3/release.zip
```

The folder it unzips to must match the top level entries in `fileindex.php`.
You can preview this locally:

```bash
unzip -l pubvana.zip | head -30
```

The package lives under `/var/softaculous/pubvana/` on the Softaculous server:

```text
/var/softaculous/pubvana/
  info.xml
  install.xml
  install.php
  install.js
  fileindex.php
  upgrade.xml
  upgrade.php
  upgrade.js
  pubvana.zip
```

## Submission notes

- Category: Blogs (the v1 listing lives under `/apps/blogs/`).
- The `<space>` value in `info.xml` is the byte size of the current
  `pubvana.zip` (`stat -c%s pubvana.zip`). Update it every time you rebuild
  the zip.
- The `<version>` value in `info.xml` must track `pubvana.json`. Softaculous
  uses it to decide when an upgrade is available for existing installs.
- The install uses `ssh` realm CLI: `php runway migrate:all`, then
  `printf "pass\npass\n" | php runway shield:user create -n ... -e ... -g superadmin`
  (the CLI prompts for the password twice, hence the `printf` pipe).
- The cron jobs call `/usr/local/bin/php -q [[softpath]]/cron {1m,4h,24h}`,
  matching the three intervals the app expects.

## Upgrade flow

When you cut a new release:

1. Rebuild `pubvana.zip` from the new `release.zip`.
2. Bump `<version>` and `<space>` in `info.xml`.
3. Tell Softaculous the new package version is ready. They repackage and offer
   it as an upgrade to existing installs; `upgrade.php` runs the migrations.