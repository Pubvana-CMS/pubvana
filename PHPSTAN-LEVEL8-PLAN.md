# PHPStan Level 8 Plan (Pubvana v3)

Goal: `phpstan.neon` at level 8, analyzing `app/` and `plugins/`, with no broad
blanket ignores, only targeted, justified ones.

Work methodically top to bottom. Check items off as they are completed and
verified. Do not skip the verify step of any phase.

---

## Current status (2026-09-02, COMPLETE)

This plan is complete. `app/` and `plugins/` both pass PHPStan at level 8
with 0 errors, `reportUnmatchedIgnoredErrors: true` stays green, and every
`ignoreErrors` entry in `phpstan.neon` was re-verified as still matched and
justified (see Phase 5). Keep the standing rules at the bottom: no new
ignores without a justification comment, and no scripted bulk docblock edits.

- `phpstan.neon`: level 8, `app/` + `plugins/` in paths.
- Result: `[OK] No errors` on the full run (verified 2026-09-02).
- All repo PHP files lint clean.
- `composer phpstan` alias present (composer.json scripts).
- CI: `.github/workflows/phpstan.yml` runs level 8 on PHP 8.2.

---

## Baseline (measured 2026-09-01, PHPStan 2.x, PHP 8.2 target)

### Error counts

| Scope                                        | L4 | L5 | L6 | L7 | L8  |
|----------------------------------------------|----|----|----|----|-----|
| `app/` with current `phpstan.neon` ignores   | 4  | 4  | 119| 144| 147 |
| `app/` with all ignores removed              |    |    |    |    | 336 |
| `app/` + `plugins/`, all ignores removed     |    |    |    |    | ~1583 (336 app, ~1247 plugins) |

### `app/` breakdown at L8, ignores removed

| Identifier                | Count | Cause                                                        |
|---------------------------|-------|--------------------------------------------------------------|
| method.notFound           | 161   | Service facades (`db()`, `auth()`, `session()`, ...) and ActiveRecord query magic |
| missingType.iterableValue | 80    | Arrays without value types in params, props, returns         |
| missingType.generics      | 28    | `flight\Engine` has `@phpstan-template EngineTemplate`, callers must specify it |
| argument.type             | 20    | Mostly `file_get_contents()`/`file()` returning `string|false` |
| constant.notFound         | 11    | `PROJECT_ROOT` defined at runtime                            |
| return.type               | 7     | Wrong/missing docblock shapes (incl. `Mailer::test()`)       |
| missingType.parameter     | 7     | Params with no type at all                                   |
| property.notFound         | 6     | ActiveRecord dynamic properties                              |
| Real logic bugs           | ~8    | See Phase 1                                                  |

### `plugins/` breakdown at L8 (approximate)

method.notFound 501, missingType.iterableValue 298, property.notFound 146,
argument.type 78, missingType.generics 57, function.notFound 43 (`csrf_token()`
and friends in views), missingType.parameter 23, variable.undefined 15,
constant.notFound 12, plus ~6 `arguments.count`, ~8 always-true/false
conditions, and assorted singletons.

Key insight: the same two fixes (facade stubs + ActiveRecord `@method`
annotations) erase roughly 650 of the ~1583 errors across the whole project.

---

## Verify command

Run from the project root after every change:

```
vendor/bin/phpstan analyse --no-progress
```

With an explicit level while climbing:

```
vendor/bin/phpstan analyse --no-progress --level=6
```

---

## Phase 0: Housekeeping

- [x] 0.1 Fix the stale ignore. `#on left side of \?\? always exists and is not nullable#`
      no longer matches PHPStan 2.x wording ("... on left side of ?? is not
      nullable"). It already leaks 1 error at L4 (`ThemeService.php:140`).
      Either update the regex or delete the line once 1.3 is fixed.
      DONE: line removed after 1.3; the removal exposed 6 more dead-`??`
      sites hidden by the old wording (Mailer, RegionManager, SettingsService),
      all fixed as part of Phase 1.
- [x] 0.2 Keep `reportUnmatchedIgnoredErrors: false` for now. Flip it to
      `true` in Phase 5. It hides ignore rot until the ignore list is stable.
- [x] 0.3 Save a baseline snapshot of counts for comparison:
      `vendor/bin/phpstan analyse --no-progress --level=8 --error-format=raw > /tmp/phpstan-l8-before.txt`

## Phase 1: Fix real bugs first (independent of level)

These are genuine defects, not typing chores. Fix and verify each.

- [x] 1.1 `app/Controllers/Admin/PluginsController.php:143`
      `instanceof ModuleResult` is evaluated against an array of results, so
      the check is always false and the `&&` is always false. Decide the
      intended behavior (iterate the array? drop the check?) and fix.
      DONE: runMigrate() returns array<string, ModuleResult>; now iterates
      and fails if any module has a migration failure.
- [x] 1.2 `app/Services/ExtensionRegistry.php:580`
      `!== null` on a value that is never null. Remove or rework the branch.
      DONE: Router::map() always returns Route; null check removed.
      is_object() middleware filter kept (null placeholders are by design,
      see app/config/core-admin.php).
- [x] 1.3 `app/Services/ThemeService.php:140`
      `??` fallback on non-nullable property `Theme::$name`. Remove the dead
      fallback or make the property actually nullable if the fallback matters.
      DONE: fallback removed; $name is a non-nullable string property.
- [x] 1.4 `app/Services/Mailer.php` `test()`
      Docblock shape says `debug: string` but the method returns
      `debug: string|null` on both branches. Align the docblock (or guarantee
      the string) so both return branches match the declared shape.
      DONE: transport() no longer takes a by-ref ?string buffer; new
      captureDebug(PHPMailer, string &$buffer) helper attaches debug capture
      in test(). $buffer stays string, shape now true.
- [x] 1.5 Unguarded `file_get_contents()` / `file()` results, `app/` only,
      about 10 sites: `json_decode` (5), `str_starts_with` (3),
      `str_contains` (3), `substr` (2), `trim` (1), `rtrim` (1), foreach over
      `file()` false case (3), `gmdate` (1), `max` (1), one
      `assign.propertyType`. Grep for them:
      `grep -rn 'file_get_contents\|file(' app/ | grep -v Views`
      Each site needs a real false check (early return or explicit handling),
      not a cast and not an assert.
      DONE: json_decode sites (ThemesController, PluginLoader x2,
      RegionManager, ThemeService) now check false; parse_url sites
      (PluginView x2, PublicController) handle false; ThemeService theme
      PHP-scan fails closed on unreadable file; AssetService bails 404 when
      filemtime() is false.
- [x] 1.6 Plugin-level real bugs, discovered while analyzing plugins.
      DONE (completed with 4.5, as noted below). All 6 `arguments.count`
      mismatches, the ~8 always-true/false conditions, and the
      `property.onlyWritten` / `identical.alwaysFalse` / `greater.alwaysTrue`
      hits were fixed during the plugin-by-plugin march. Bug fixes and the
      14 remaining category hits at that writing are logged under 4.3 and
      resolved in 4.5. Confirmed 0 errors on the full run (2026-09-02).
- [x] 1.7 Verify: `vendor/bin/phpstan analyse --no-progress --level=4` reports
      0 errors.
      DONE: L4 = 0, L5 = 0. L8 went 147 -> 125.

## Phase 2: Replace the broad ignores with real type information

This phase is what makes level 6-8 possible. Two work streams.

### 2A. ActiveRecord `@method` annotations (kills `method.notFound` and `property.notFound` on models)

Pattern (from PLAN.md, follow it exactly):

```php
/**
 * @method self order(string $clause)
 * @method self limit(int $count)
 * @method self eq(string $field, mixed $value)
 * @method self notEq(string $field, mixed $value)
 * @method self like(string $field, string $value)
 * @method self select(string $columns)
 * @property int $id
 * @property string $name
 */
class Mail extends \flight\ActiveRecord
```

Only annotate what the codebase actually uses (check usage per model, add
`findAll()`, `first()`, `count()`, `insert()`, `update()`, `delete()` and the
`@property` fields as needed).

- [x] 2A.1 Core models, `app/Models/`: `BlockPlacement.php`, `Mail.php`,
      `NavigationItem.php`, `PluginState.php`, `Setting.php`,
      `Theme.php`, `ThemeOption.php`.
      DONE: all 7 extend new `AbstractModel` and carry per-model @method
      tags for the query magic they use. AbstractModel (app/Models/)
      overrides findAll() with @return array<int, static> because vendor
      hydration uses `new $called_class` but vendor PHPDoc types the
      result as the base ActiveRecord. A find() override was added later
      (Phase 4, when plugin code needed typed find() results); it is the
      one exception to the no-ignores rule, backed by a narrowly scoped
      ignore in phpstan.neon with justification (PHPStan cannot see the
      static truth through parent::find()).
- [x] 2A.2 Plugin models (22 files; the earlier "30" was a miscount).
      DONE during Phase 4 alongside 4.4: all extend AbstractModel with
      per-model @method tags, @property tags matched to migrations, and
      constructor docblocks. SeoMeta and SocialLink were hand-converted
      (non-standard constructors / existing typed constructors).
- [x] 2A.3 Verify: grep the error output for leftover ActiveRecord errors.
      DONE: 0 model-level errors remain in the full plugins+app run.
      (Verified again 2026-09-02: 0 `Models/` errors, 0
      `method.notFound` on query magic.)

### 2B. Flight facade stub file (kills the `db()`, `auth()`, `session()`, ... errors)

Flight's `flight\Engine` resolves these via `__call()`. PHPStan cannot see
them. Solution: a stub file that adds `@method` annotations to
`flight\Engine`, loaded via `stubFiles` in `phpstan.neon`.

Create `phpstan-stubs.php` at project root:

```php
<?php

namespace flight;

/**
 * @method \PDO db()
 * @method \Pubvana\Services\ExtensionRegistry adext()
 * @method callable slugify()
 * @method \Pubvana\Services\ThemeService themes()
 * @method \Pubvana\Services\RegionManager regions()
 * @method \Pubvana\Services\NavigationService navigation()
 * @method \Pubvana\Services\SettingsService settings()
 * @method \Pubvana\Services\Mailer mailer()
 * @method \Pubvana\Services\ContentService content()
 * @method \Pubvana\Services\AssetService asset()
 * @method \Pubvana\Services\PluginLoader pluginLoader()
 * @method mixed session()
 * @method mixed auth()
 */
class Engine
{
}
```

Notes:
- Confirm each return type against the closure in `app/config/services.php`
  (db, adext, slugify, themes, regions, navigation, settings, mailer,
  content, asset, pluginLoader are all mapped there; session and auth come
  from enlivenapp/flight-sessions and flight-shield, check their service
  registration for the real class names).
- Plugin facades (`media()`, `blog()`, `pages()`, `search()`, `seo()`,
  `seoSchema()`, `seoSitemap()`, `seoRobots()`, `seoLlmsTxt()`,
  `seoAnalysis()`, `forms()`, `comments()`, `redirects()`,
  `redirectLinks()`, `analytics()`, `backups()`, `health()`, `profiles()`,
  `socialLinks()`, `ai()`, `aiMarkdown()`) are registered by each plugin's
  `Plugin.php`. Stubs for them go in Phase 4, one shared stub section per
  plugin, because those services only exist when the plugin is loaded.
- The `error` map is called internally, no stub needed unless analysis says so.

- [x] 2B.1 Create `phpstan-stubs.php` with the core facade methods above.
      DONE, with important changes to the approach:
      - Stub docblocks REPLACE the vendor class docblock for PHPStan. The
        full @phpstan-template + @phpstan-method set from Flight's Engine
        had to be carried forward verbatim, otherwise redirect()/request()/
        halt() etc. became unknown again.
      - Use @phpstan-method, not @method. PHPStan resolves class references
        in @phpstan-method fully; plain @method tags in stubs only resolve
        partially and generate spurious "unknown class" self-check errors.
      - Stub self-check errors CANNOT be ignored (documented PHPStan rule).
        Every class referenced from a stub must itself be declared in the
        stub as an empty shell (shells merge with the real classes, real
        methods are preserved). Shells live at the bottom of the stub file.
        scanFiles entries do NOT fix this.
      - Plugin facades used by app/ (pages, media, seo, comments) are in
        the stub already, with shells.
      - The scoped ignore `identifier: class.notFound, path:
        phpstan-stubs.php` added alongside this proved INEFFECTIVE (stub
        self-check errors are unignorable per PHPStan docs); the shell
        declarations were the actual fix. Candidate for deletion in 5.1.
- [x] 2B.2 Wire it up in `phpstan.neon`:
      stubFiles + bootstrapFiles entries added.
- [x] 2B.3 Replace `PROJECT_ROOT` handling.
      DONE: stub-file constants do not work (define() or const). Declared
      in phpstan-bootstrap.php via bootstrapFiles instead.
- [x] 2B.4 Narrow and remove the broad ignores one at a time. After 2A/2B,
      delete from `phpstan.neon`:
      - `#Call to an undefined method#`
      - `#Access to an undefined property#`
      - `#Constant PROJECT_ROOT not found#`
      DONE: all three removed, plus `#should return array.*Pubvana#`
      (obsolete thanks to AbstractModel). One new narrowly scoped ignore
      added for the stub self-check quirk, with justification comment
      (may be removable when shells suffice; kept while it documents the
      Engine facade surface). Exposed and fixed: Shield Result has
      reason() not getMessage() (UsersController, would fatal at runtime),
      dead ?? fallbacks in SettingsController/RegionManager, Collection
      query prop access idiom.
- [x] 2B.5 Verify: `method.notFound` in `app/` dropped from 161 to 3
      (remaining 3 are object:: calls to be typed in Phase 3).

## Phase 3: Climb the ladder, `app/` only

Bump one level at a time. Fix, then bump. Never fix by casting, asserting, or
widening types. Per current PHPStan guidance: no baseline entries, no
`@phpstan-ignore` comments, no `assert()`, no inline `@var` overrides, no
casts to silence.

- [x] 3.1 Level 4. Remaining after Phase 1 should be near zero.
      DONE: 0 errors.
- [x] 3.2 Level 5. Near zero (same error families as L4).
      DONE: 0 errors.
- [x] 3.3 Level 6. DONE: 0 errors. Work done file by file: model
      constructors (DatabaseInterface|\PDO|\mysqli|null for $pdo,
      array<string, mixed> for $config), PluginLoader properties/params
      (array<string, PluginInterface>, array<string, array<string, mixed>>
      for plugin info maps), ExtensionRegistry routes/extensions shapes,
      AdminController dashboard helpers (list<array<string, mixed>>
      entries), PublicController data maps, Mailer opts shapes
      (array{from?, fromName?, alt?, replyTo?}), NavigationService tree
      typing, RegionManager block shapes. A typed getActive(): ?Theme,
      vision(): ?Engine, and PluginInterface registration typing removed
      the object:: unknown-method family. Two dead guards flagged by the
      new types were removed (is_array in normalizeDashboardEntries,
      is_string in Mailer opts), and one real bug fixed
      (PublicController::$candidates[1] fallback out of range when
      routePrepend is empty).
- [x] 3.4 Level 7. DONE: 0 errors. glob() false guards (?: []),
      max() empty-array guard in groupDashboardEntries (real ValueError
      risk with empty groups), dot-notation theme options vivification,
      json_encode false handling in BlockPlacement::setOptions,
      instanceof PluginInterface guard before storing in $loaded.
- [x] 3.5 Level 8. DONE: 0 errors. Engine generics: all Engine-typed
      properties/params now use Engine<object> (the EngineTemplate
      parameter describes container-resolved services; Pubvana registers
      no container handler, so object is truthful). PluginView::render()
      resolves the Vision engine once instead of double-checking
      hasVision().
- [x] 3.6 Verify. DONE: `phpstan.neon` bumped to level 8, 0 errors at
      every level 4-8.
- [x] 3.7 Update PLAN.md "PHPStan Level Target" section: current level
      becomes 8 for app/, plugins pending.
      DONE: PLAN.md rewritten with achieved status and the new conventions
      (stubs file, bootstrap file, AbstractModel, Engine<object>, ignore
      policy). Refresh it again at 5.5 when plugins/ finishes.

## Phase 4: Bring `plugins/` into analysis

Only after `app/` is green at 8.

- [x] 4.1 Update `phpstan.neon` paths: add `plugins/`. Add excludes:
      DONE. Wildcard directory excludes need a `/**` suffix to match files
      inside (`plugins/*/Views/**`); a bare `plugins/*/Views` does not work.
- [x] 4.2 Add plugin facade stubs to `phpstan-stubs.php`.
      DONE: all 21 plugin facades declared with shells (pages, media, seo,
      seoSchema, seoSitemap, seoRobots, seoLlmsTxt, seoAnalysis, comments,
      blog, search, forms, profiles, redirects, redirectLinks, analytics,
      backups, health, socialLinks, ai, aiMarkdown).
- [x] 4.3 Knock out the plugin-side real bugs from 1.6.
      DONE. Fixed: Pages `limit($offset, $perPage)` built
      MySQL's LIMIT o,c through the variadic magic (rewritten to
      limit()/offset()); `neq()` call renamed to `notEq()`; Page template
      fallback `$candidates[1]` out of range (fixed in app/ Phase 3);
      GdProcessor hardened (requireImage() call-order guard, decode-failure
      throws, rotate-failure keeps original, zero-dimension guards that
      previously hit GD ValueErrors); AiService SearchService typing;
      BlogPublicController stop(404, msg) dropped both args at runtime,
      changed to halt(404, msg). False-return guards (strtotime, filemtime,
      PDOStatement, ob_get_clean, glob, preg_split, shell_exec) applied
      across the plugin-by-plugin march. The remaining always-true/false,
      arguments.count, and onlyWritten hits were resolved inside 4.5.
- [x] 4.4 Annotate the plugin models.
      DONE: all 22 extend `AbstractModel` with per-model @method tags,
      @property tags matched to migrations, and constructor docblocks.
      Note: tag generation was scripted and initially corrupted docblock
      placement in several files; all repaired and audited (grep-based
      audit for misplaced docblock fragments). Model-level errors are 0.
- [x] 4.5 Plugin-by-plugin march, worst first, down to 0.
      DONE. All remaining error families resolved: HealthService,
      Redirects/RedirectLinks, the whole Seo group (SeoService, Sitemap,
      LlmsTxt, RobotsTxt, Plugin, PublicController), Search stragglers,
      BlogPublicController RSS/Atom date guards, Profiles controllers,
      Forms controllers, all SiteHealth check classes, and the various
      small tails. Final full run (app/ + plugins/, level 8) reports 0
      errors. Patterns applied throughout: Engine<object> docblocks, array
      value types, false guards, @property additions.

- [x] 4.6 Verify: full run, both paths, level 8, 0 errors.
      DONE: `[OK] No errors`, re-verified after every Phase 5 change.
- [x] 4.7 Update each plugin's `AGENTS.md` with a one-line note: model
      annotations required, facade stub required in `phpstan-stubs.php`.
      DONE: every plugin AGENTS.md now carries the PHPStan level-8 note
      (models extend `Pubvana\Models\AbstractModel` with @property/@method,
      facades stubbed in phpstan-stubs.php, run `composer phpstan`).
      Seo, Comments, Media, and all others now reference AbstractModel, not
      \flight\ActiveRecord.

## Phase 5: Lock it in

- [x] 5.1 Remove now-unnecessary ignores. Attempt to delete every remaining
      entry, run, re-add only what is truly needed with a justification
      comment.
      DONE, re-verified 2026-09-02. Every candidate was tested by editing
      the active phpstan.neon and rerunning the full analysis:
      - The `class.notFound, path: phpstan-stubs.php` entry is no longer in
        the file (remnant of the ineffective stub self-check approach,
        replaced by the shell declarations in 2B.1/2B.4).
      - Both Flight middleware docblock ignores (Route::addMiddleware @
        ExtensionRegistry.php, Router::group @ PluginLoader.php) are still
        matched: removing them reports the vendor argument.type errors
        again, so both stay, justified.
      - The CommentService `$allowComments` dead-branch ignore is still
        matched (4 errors return if removed): stays, justified.
      - Both AbstractModel ignores (find(), findAll()) are still matched
        (return.type errors return if removed): stay, permanent and
        justified.
      - All stale entries (`should return array.*Pubvana`, `$ds`,
        `unused use`, `trailing whitespace`) are already gone from the file.
      Final phpstan.neon has exactly 5 ignoreErrors entries, each scoped
      by message and path with a justification comment.
- [x] 5.2 Set `reportUnmatchedIgnoredErrors: true`.
      DONE: already true, and the full run stays green, confirming no
      ignore entry is stale/unmatched.
- [x] 5.3 Add a composer script for convenience (composer.json):

```json
"scripts": {
    "phpstan": "phpstan analyse --no-progress"
}
```

      DONE: present and verified working (`composer phpstan` reports
      `[OK] No errors`).
- [x] 5.4 GitHub Actions CI.
      DONE: `.github/workflows/phpstan.yml` runs PHPStan on PHP 8.2 via
      `phpstan analyse --no-progress` (picks up phpstan.neon) on push to
      main and pull requests.
- [x] 5.5 Update PLAN.md.
      DONE: PLAN.md "PHPStan Level Target: 8" section now reflects the
      achieved state, the conventions (stubs file, bootstrap file,
      AbstractModel with @property/@method, Engine<object>, ignore policy),
      and the five permanent ignores. No outstanding PHPStan work remains.

---

## Standing rules

- One level at a time, one phase at a time. Verify between steps.
- Never silence errors with casts, `assert()`, inline `@var` overrides,
  `@phpstan-ignore` comments, or baselines. Fix the underlying type or logic.
- New `ignoreErrors` entries only with a comment explaining why, narrowly
  scoped by message and path.
- Model annotations: only annotate methods/properties actually used.
- Stubs are for vendor/runtime magic only, never for our own classes; our
  code gets real types.
- No scripted bulk edits for docblock insertion. They corrupted files
  twice this phase (misplaced fragments, eaten docblock closers). Make
  docblock changes with single targeted edits and lint after each file.
- After every batch of changes: lint every touched file (`php -l`) and
  re-run the analysis before checking anything off.
