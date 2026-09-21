# AGENTS.md — Core Blocks plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

Core Blocks provides two generic blocks (Text and HTML) that do not belong to any specific content plugin. Both are registered in PHP via adext, the same way every other block plugin registers.

- **Package:** `pubvana/core-blocks` (`pubvana.json:2`), semver `0.1.0`, category `blocks`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code uses only typed signatures and arrow-free closures, staying within that floor
- **Namespace:** `Pubvana\Plugins\CoreBlocks` (`Plugin.php:5`)
- **Runtime dependencies:** core Flight (`flight\Engine`, `flight\net\Router`) and `Pubvana\Services\PluginInterface`; block behavior relies on the core RegionManager and adext block registry, no third-party packages
- **Docs:** none existed; `README.md` added alongside this file

## Project guidelines

1. **Register blocks in `Plugin.php` via adext.** Each block is registered with `$adext->register('block', 'available', ...)` inside `Plugin::register()` (`Plugin.php:21-41`). The manifest `pubvana.json` carries only package metadata with an empty `provides` (`pubvana.json:7`); it holds no block registration. Reason: one registration path across every block plugin.
2. **Do not set a provider callable on a static block.** When no provider is set, RegionManager layers the saved options over the option defaults and passes the result as template data. Reason: a provider would introduce a code path for data that already arrives in the template.
3. **Keep the block key, template value, and file location in lockstep.** The registered key is `pubvana.core-blocks.{name}` (`Plugin.php:21, 32`); author and package for template resolution come from that key (`pubvana` / `core-blocks`). The `template` value is the bare file name `{name}.tpl` and the file lives at `Views/public/blocks/{name}.tpl`. RegionManager resolves the plugin default against the package view path (`Views/public/blocks/`). Reason: a mismatch makes the block unresolvable and it silently renders nothing.
4. **Keep the escaping boundary strict.** `text.tpl` renders `title` and `content` escaped (`{{ content }}`, `Views/public/blocks/text.tpl:3-7`); `html.tpl` renders `content` raw (`{! content !}`, `Views/public/blocks/html.tpl:3`). Never swap the two. Reason: the HTML block is explicitly the unescaped escape hatch for trusted admin markup; the Text block is not.
5. **The Text block's content editor is plain text, not a WYSIWYG editor.** `text` declares `content` as a textarea with `'wysiwyg' => false` (`Plugin.php:28`). The block modal only attaches Jodit to textareas that do not opt out. Reason: the Text block escapes its body, so markup produced by an editor would display as escaped source instead of rendered text. HTML (the editor-managed block) keeps Jodit attached.
6. **Keep options schema scalar and defaulted.** Text takes `title` (input) and `content` (textarea); HTML takes `title` (input) and `content` (textarea) (`Plugin.php:21-41`). Reason: RegionManager falls back to the schema default for any option the template reads.

## Repository layout

```
plugins/CoreBlocks/
├── Plugin.php                 Registers both blocks via adext
├── pubvana.json               Manifest only; provides is empty
└── Views/public/blocks/
    ├── text.tpl               Escaped title + content block
    └── html.tpl               Unescaped HTML block
```

## Core architecture

**Entry point.** `Plugin::register()` registers the two blocks with adext (`Plugin.php:13-42`). There is no service, config, database table, controller, model, or admin page.

**Registration.** Both blocks are registered in PHP:

- `pubvana.core-blocks.text` (`Plugin.php:21-30`): label Text, priority 100, template `text.tpl`, options `title` (input, default `''`) and `content` (textarea, default `''`, `wysiwyg: false`).
- `pubvana.core-blocks.html` (`Plugin.php:32-41`): label HTML, priority 110, template `html.tpl`, options `title` (input, default `''`) and `content` (textarea, default `''`, WYSIWYG attached).

Template resolution derives author `pubvana` and package `core-blocks` from the block key and looks in `Views/public/blocks/` by default, with app and theme overrides available.

**Data flow.** An admin places a block in a region via the core block picker. RegionManager stores the options chosen in the UI, then, because no provider callable exists, feeds those saved options over each schema default directly to the Vision template as data (`title`/`content`).

**Templates.** Both `.tpl` files wrap output in `.block` wrappers. `text.tpl` conditionally prints an `<h6 class="block-title">` for `title` and escapes `content`; `html.tpl` prints an escaped `title` the same way and `content` with Vision's unescaped `{! ... !}` operator.

## Development and testing

This plugin has no `composer.json` and no test suite. Registration is two small adext calls with no additional runtime code paths to execute.

- Lint/static analysis (app-wide, from the repo root; the plugin is in-tree):
  - `composer phpstan` (level 8, sees `app/` plus `plugins/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/CoreBlocks -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Drop a Text block into a region: options render raw text and HTML entities properly escaped
  - [ ] Drop an HTML block into a region: markup renders unescaped and unstyled beyond `.block-html`
  - [ ] The Text block's content field in the block modal is a plain textarea (no toolbar); the HTML block's field keeps the Jodit editor
  - [ ] The block appears in the block picker under both registered labels, in priority order

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of the class file** (`Plugin.php:3`).
2. **Register blocks through `$adext->register('block', 'available', ...)` in `register()`.** The block key is `pubvana.core-blocks.{name}` and `template` is the bare file name; no registration may be added to `pubvana.json`.
3. **Templates stay output-only.** No logic beyond the Vision tags already used (`{% if %}`, `{{ }}`, `{! !}`); keep the escaping boundary from the guidelines.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing features and usage |
| `Plugin.php` | Single source of truth for block keys, labels, options, and priorities |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a new generic block | New `$adext->register('block', 'available', ...)` block in `Plugin.php` plus a `.tpl` under `Views/public/blocks/` |
| Change block labels, priorities, or option schema | `Plugin.php` (block entries) |
| Change block markup | `Views/public/blocks/{text,html}.tpl` |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present where PHP is edited; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] `php -l` passes on the class file; block keys, template values, option defaults, and template data names match exactly
- [ ] New blocks are registered in `Plugin.php` with a `priority` and option defaults, and the matching `.tpl` under `Views/public/blocks/`
- [ ] Escaping boundary preserved: no escaping change without a deliberate reason, documented in the guideline
- [ ] `pubvana.json` stays registration-free; README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No configuration: no `Config/` file, no settings, no environment variables.
- No database tables or migrations, no admin UI beyond the shared block picker.
- No providers, services, controllers, or routes; blocks are purely presentational.
