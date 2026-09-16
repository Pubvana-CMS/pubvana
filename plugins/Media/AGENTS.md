# AGENTS.md — Media plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Media is the media library for Pubvana: image and video uploads, DVD-style derivatives (original, working, medium, thumb), an in-browser image editor, video posters, embeds, and reusable admin code snippets. Other plugins use the `$app->media()` service facade to embed pickers and Jodit editors.

- **Package:** `pubvana/media` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`match` at `Services/MediaService.php:167` and `Services/GdProcessor.php:22`, `str_starts_with` at `Services/GdProcessor.php:245`, `static` return types on the processor interface at `Services/ImageProcessorInterface.php:9`, typed property `?\GdImage` at `Services/GdProcessor.php:9`)
- **Namespace:** `Pubvana\Plugins\Media` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base); one of the PHP extensions `imagick` or `gd` is required at runtime (`Services/MediaService.php:26-37`); the `ffmpeg` binary is optional for video thumbnails (`Services/VideoThumbnailService.php:9-27`); `exif_read_data` is optional and guarded (`Services/GdProcessor.php:214`); core engine services used as `$app->media()`, `db()`, `adext()`, `auth()`, `request()`, `json()`, `redirect`; the `PROJECT_ROOT` constant (resolved against `public/`, `Services/MediaService.php:19`)
- **Config:** `Config/Config.php`: `routePrepend` (`media`), `upload_path` (`uploads`), `max_image_size` (10 MB), `max_video_size` (100 MB), `allowed_image_ext` (`jpg`, `jpeg`, `png`, `gif`, `webp`), `allowed_video_ext` (`mp4`, `webm`, `mov`), `webp_quality` (85), `thumb_width` (300), `medium_width` (768)
- **Docs:** `README.md`

## Project guidelines

1. **Validate every upload through `validateUpload()` before touching the filesystem.** It rejects files by error code, extension allow-list, size cap, and server-side `finfo` MIME detection (`Services/MediaService.php:405-434`). Reason: the browser-supplied extension and MIME are untrusted; the finfo check is the last line of defense.
2. **Treat the processor as one interchangeable engine.** `createProcessor()` picks Imagick first, GD second (`Services/MediaService.php:26-37`). All image work must go through `ImageProcessorInterface`; never branch on the concrete processor inside business code. Reason: the two backends must behave identically, and a GD/Imagick divergence is a bug.
3. **Keep the storage layout fixed.** Images land at `uploads/Y/m/{hex}.{ext}` with `originals/{hex}.{ext}`, `{hex}.webp` (working w) and `medium/` + `thumbs/` derivatives (`Services/MediaService.php:46-60`). Videos land with a `thumbs/{hex}_poster.jpg` poster when ffmpeg is available. Reason: `delete()`, `revert()`, `applyEdit()`, and the dashboard all derive file paths from this scheme.
4. **Never touch the original except to write it once and to restore from it.** Uploads write `originals/`, edits and reverts regenerate the working file from it (`Services/MediaService.php:197-222`). Reason: edits are destructive to the working copy, so the pristine original is the only way `revert` stays trustworthy.
5. **`delete()` must remove every artifact before the row.** It unlinks working, original, medium, thumb, and poster files when they exist, then deletes the DB record (`Services/MediaService.php:295-336`). Reason: orphaned files on disk would accumulate forever, and the dashboard checks disk state by path.
6. **Never introduce non-inline JS/CSS for admin views.** `plugins/` is blocked by `.htaccess` (documented in `README.md` "Inline JS rule"), so widgets embed a `<script>` tag directly in the returned HTML (`Services/MediaService.php:367-401`). Reason: an external asset under `plugins/` returns 403.
7. **Treat media URLs as already leading-slash.** `mediaToArray()` emits `/uploads/...` paths (`Controllers/MediaAdminController.php:262-268`). Reason: prepending another `/` yields `//uploads/...`, which browsers may resolve as a protocol-relative URL.
8. **Serialize API responses through `mediaToArray()`.** All JSON endpoints route through it, and client code depends on keys like `thumb_url`, `medium_url`, `poster_url`, and `info` (`Controllers/MediaAdminController.php:239-276`). Reason: views and pickers consume this stable shape, not raw ActiveRecord rows.
9. **Make video poster extraction best-effort.** `VideoThumbnailService::extract()` calls ffmpeg via `exec` with escaped args and returns a boolean; a poster is simply omitted on failure (`Services/MediaService.php:87-105`). Reason: uploads must succeed on hosts without ffmpeg.
10. **Cast to string before `str_starts_with()` on possibly-numeric keys.** Image metadata arrays can carry integer keys (`Services/GdProcessor.php:245`, `README.md` "Creating thumbnails"). Reason: `str_starts_with()` throws a `TypeError` on an int.
11. **Gate every edit operation on the reported capabilities.** `applyEdit()` is only dispatched for operations listed by `capabilities()`, and unknown operations throw (`Services/MediaService.php:167-181`, `Controllers/MediaAdminController.php:191-195`). Reason: GD and Imagick support different operation sets and the client must not send unsupported ones.

## Repository layout

```
plugins/Media/
├── Config/Config.php                  routePrepend, upload_path, size caps, allowed exts, widths, quality
├── Controllers/MediaAdminController.php  Library, JSON, uploads, embeds, editor, edits, revert
├── Database/
│   ├── Migrations/2026-04-25-100000_CreateMediaTable.php
│   │                                  media (type enum image/video/embed; indexed on type, uploaded_by)
│   └── Seeds/Seed.php                 Seed: media.manage permission
├── Models/Media.php                   media table; find/paginate/count, whitelisted updateMeta
├── Services/
│   ├── MediaService.php               Service facade mapped as $app->media() (Plugin.php:16-27)
│   ├── ImageProcessorInterface.php    Processor interface (load/resize/crop/rotate/... /toWebp/save/getInfo/getExif/capabilities)
│   ├── GdProcessor.php                GD backend (uses getimagesize, imageX functions, exif_read_data)
│   ├── ImagickProcessor.php           Imagick backend (uses \Imagick throughout)
│   └── VideoThumbnailService.php      Best-effort ffmpeg poster extraction
├── Plugin.php                         Entry point; routes, dashboard card/section
├── pubvana.json                       Manifest; provides admin.menu (Media) and admin.dashboard
├── Views/admin/
│   ├── index.php                      Library grid
│   ├── editor.php                     Image editor UI
│   ├── picker.php                     Media image picker (rendered by MediaService::picker())
│   ├── avatar-picker.php              Avatar picker
│   └── jodit.php                      Jodit init snippet (rendered by MediaService::joditInit())
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:14`). Maps the `media` singleton `MediaService` with the DB, config, and `PROJECT_ROOT/public` as the public path (`Plugin.php:16-27`), then registers admin routes plus dashboard contributions.

**Extension points (adext registrations).**
- Twelve admin routes under `pubvana.media` (`Plugin.php:39-52`): library index, `json` listing, `capabilities`, image editor page, image/video upload, embed store, poster upload, meta update, delete, apply edit, revert.
- `admin.dashboard` card (total items) and section (recent uploads with on-disk thumbnail detection) (`Plugin.php:56-123`).

**Upload path.** `uploadImage()` validates, creates `uploads/Y/m/{hex}.{ext}` and `originals/`, copies the working file, and generates medium/thumbs WebP derivatives before inserting the row (`Services/MediaService.php:41-70`). `uploadVideo()` similarly stores the file and attempts an ffmpeg poster (`Services/MediaService.php:72-106`). `storeEmbed()` records only provider metadata (`youtube`/`vimeo`, null otherwise) (`Services/MediaService.php:108-119`).

**Edit path.** `applyEdit()` loads the working image, dispatches the operation by name, saves the working copy, regenerates derivatives, and refreshes `size`/`updated_at` (`Services/MediaService.php:153-195`). `revert()` copies `originals/` back over the working copy and regenerates (`Services/MediaService.php:197-222`).

**Snippet path.** `picker()`, `avatarPicker()`, and `joditInit()` capture a view partial via `ob_start()` and return its HTML, giving each instance a unique `pickerId`/`joditId` (`Services/MediaService.php:367-401`). The picker talks to `GET /admin/media/json?type=image` and `POST /admin/media/upload/image` from inline JS (`Views/admin/picker.php`).

**Derivative pipeline.** `generateDerivatives()` writes `medium/{hex}.webp` at `medium_width` and `thumbs/{hex}.webp` at `thumb_width`, both at `webp_quality` (`Services/MediaService.php:261-278`). Widths only downscale: `resize()` returns unchanged when the source is not wider (`Services/GdProcessor.php:41-43`).

## Plugin author API

Other plugins use `$app->media()` to embed editors, pickers, and query the library.

**Jodit editor.** `joditInit($selector)` returns a `<script>` block that creates a Jodit instance on the selector. It handles dark mode, CSP-safe plugin disabling, and custom image controls automatically. If you construct Jodit directly instead, disable the `beautify` and `ace` plugins yourself (they violate the app's CSP):

```js
new Jodit('#content', { disablePlugins: 'beautify,ace' });
```

**Media picker.** `picker($inputName, $currentValue)` returns a self-contained image picker (preview plus offcanvas media library) for a form field; `avatarPicker($inputName, $currentValue)` is the avatar variant. The picker talks to `GET /admin/media/json?type=image` and `POST /admin/media/upload/image` from inline JS.

**Featured image pattern.** Hidden inputs (`featured_image` path and `media_id` ID), an offcanvas panel listing images from `/admin/media/json?type=image`, a drag-and-drop upload zone posting to `/admin/media/upload/image`, a select handler that sets the hidden inputs and preview, and a remove button. See `plugins/Blog/Views/admin/edit.php` for a full working example.

**Querying the library.**

```php
$items = $this->app->media()->list($page, $perPage, $type);   // paginated, default 24/page, filter by type
$count = $this->app->media()->countAll($type);                // total count
$recent = $this->app->media()->recent($limit, $type);         // most recent
$media = $this->app->media()->find($id);                      // single item
```

**Admin endpoints.** `GET /admin/media/json` lists media (`?type=image` filters); `POST /admin/media/upload/image` and `POST /admin/media/upload/video` upload files.

## Development and testing

The plugin has no `composer.json` (it is in-tree), but it has a test suite under `tests/Unit/Plugins/Media/` (5 files: `MediaModelTest`, `GdProcessorTest`, `MediaServiceTest`, `AdminViewsEscapingTest`, `MediaUploadFailureGuardsTest`). It is also exercised through the full app and depends on runtime image extensions.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Media -name '*.php' -exec php -l {} \;`
- Tests: `vendor/bin/phpunit --filter Media`
- Manual verification checklist:
  - [ ] Upload a PNG with alpha and a large JPEG; confirm `originals/`, working file, `medium/`, and `thumbs/` all exist with expected names, and the JSON returns slash-prefixed URLs
  - [ ] Upload a `.php` disguised as `.jpg`; confirm rejection via finfo MIME check
  - [ ] Apply crop, rotate, sharpen, brightness, and contrast in the editor; confirm working file changes and derivatives regenerate
  - [ ] Revert an edited image; confirm the working copy returns to the original bytes
  - [ ] Delete an item; confirm all disk artifacts (working, original, medium, thumb, poster) are gone
  - [ ] Upload a video with ffmpeg present and absent; confirm the poster row is populated only in the first case
  - [ ] Store a YouTube and a Vimeo URL; confirm `embed_provider` and that non-provider URLs store null
  - [ ] Open the picker and Jodit editor in a dark-mode admin; confirm both initialize with sites CSS reachable

Coverage: the suite covers the model, the GD processor, the service facade, admin view escaping, and upload failure guards.

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Media.php:7-12`).
3. **Keep the two processors symmetric.** Both implement the interface fully; when changing one, change both, and confirm identical `capabilities()` where possible.
4. **`updateMeta()` must stay whitelisted.** Only `alt_text`, `title`, `poster_path` are writable, and values are trimmed to `null` when empty (`Models/Media.php:67-79`). Never pass raw request data.
5. **Use `DateTimeImmutable` for all timestamp writes** (`Models/Media.php:52, 77`).
6. **Escape every interpolated value in snippet partials** (`Views/admin/picker.php` uses `htmlspecialchars` on all echoed values). Never concatenate a path or name into markup raw.
7. **Respect the no-external-assets rule.** New widgets embed their own inline `<style>`/`<script>` blocks; nothing is registered via `admin.css`/`admin.js`.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `AGENTS.md` "Plugin author API" | Embedding Jodit, pickers, featured-image pattern, library queries, admin endpoints |
| `Services/MediaService.php:26-37` | Processor selection order (Imagick, then GD) |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add an upload type or extension | `Config/Config.php` allow-lists and `validateUpload()` allow-mime lists (`Services/MediaService.php:415-433`) |
| Add an image edit operation | `ImageProcessorInterface.php` + both processors + `applyEdit()` dispatch (`Services/MediaService.php:167-182`) |
| Change derivative sizes or quality | `Config/Config.php` (`thumb_width`, `medium_width`, `webp_quality`) |
| Change the upload folder | `Config/Config.php` (`upload_path`) |
| Add a snippet | New view partial under `Views/admin/` exposed via a `MediaService` method using `ob_start()` |
| Serialize a new field to API consumers | `mediaToArray()` (`Controllers/MediaAdminController.php:239-276`) |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] Uploads validated by extension, size, and finfo MIME; storage layout unchanged; originals preserved
- [ ] Both GD and Imagick paths updated together; no concrete-processor branches in business logic
- [ ] `delete()` removes all artifacts; URLs stay leading-slash; snippet output escaped; no external assets added
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No CDN or remote storage integration; files live under `public/uploads`.
- No watermarking, faces/object detection, or non-WebP derivative formats.
- No localization; labels are hardcoded English.
- GD does not advertise `strip_exif`. GD has no EXIF API and re-encodes on every save (its JPEG encoder never writes EXIF), so it cannot offer a keep-or-strip choice; `stripExif()` stays a no-op to satisfy the interface (`Services/GdProcessor.php`). Imagick advertises `strip_exif` because `stripImage()` is a real opt-in.
