# Pubvana Coding Standards & Pre-Push Checklist

We use the processes below to write Pubvana. When sending a PR we ask that you follow it too by running the checklist below.

---

## 1. Static analysis

We use PHPStan for general code analysis, Psalm for security, and PHPunit for tests, and lint for syntax. Run all of these before sending your PR.

### PHPStan, Level 8

```bash
composer phpstan        # phpstan analyse  (phpstan.neon, level 8)
```
If you're adding functionality that requires phpstan.neon to be updated or changed:

The 5 `ignoreErrors` entries in `phpstan.neon` are required for our framework. `reportUnmatchedIgnoredErrors: true` should not be changed.

### Psalm, taint analysis

```bash
composer psalm          # psalm --no-progress --taint-analysis
```

Psalm 6.4.0 is pinned in `require-dev` because newer versions need PHP >= 8.3.16; the minimum is PHP ^8.2 and the build image ships 8.3.6. Before bumping Psalm, confirm it runs on the build PHP.

Taint analysis is enabled in `psalm.xml` (`runTaintAnalysis="true"`). `psalm-stubs.php` declares the Flight request accessors as taint sources so user input arriving through Flight's abstraction (not `$_GET`/`$_POST`) is traced. We use `@psalm-taint-source input` and only valid taint kinds; an invalid kind silently disables the annotation.

`psalm-baseline.xml` whitelists a small set of confirmed false positives
(validated Media uploads). If a baselined occurrence turns out to be a real
bug, fix the code, don't extend the baseline. Regenerate with
`psalm --taint-analysis --set-baseline=psalm-baseline.xml`.

### Syntax

```bash
composer lint            # php -l over every PHP file in app/ and plugins/
```

A parse check. New files parse clean.

---

## 2. Tests

Tests use PHPUnit 11. `composer test` runs them.

```bash
composer test            # phpunit  (phpunit.xml, tests/)
```

Two suites, `tests/Unit` and `tests/Feature`, under the `Pubvana\Tests\`
namespace. The base test case in `tests/Support/TestCase.php` provides
`invoke()` for calling private/protected methods and `app()` for a fresh
Flight engine with services mapped. DB-backed tests use an in-memory SQLite
database (`tests/Support/Sqlite.php`) because ActiveRecord accepts any PDO
and the migration runner is MySQL-only; call `recreate()` in setUp() when a
test needs a clean slate.

If you have to skip a test, tell us why.

---

## 3. Pre-push checklist
Before a PR goes out we run these in order. Later steps assume earlier ones
pass.

1. `composer lint`            - no PHP parse errors
2. `composer phpstan`         - Level 8, 0 errors
3. `composer psalm`           - taint analysis clean (baseline excluded)
4. `composer test`            - full suite green (relevant tests at minimum)
5. Review your own diff       - no secrets, no debug, no unrelated changes

On CI (`.github/workflows/`): `phpstan.yml` and `psalm.yml` run the two
static-analysis checks and `test.yml` runs the PHPUnit suite on push/pull to
`main`. A red CI status blocks the push.
---

## 4. Standards we strive for

These are the PSRs we target:

- PSR-12: Coding Style
- PSR-4: Autoloading
- PSR-7: HTTP Messages
- PSR-3: Logging

---

## 5. Our Coding Conventions

- PHP 8.2. No 8.3/8.4 features (shared-host compatibility).
- `declare(strict_types=1);` at the top of every class file.
- MVC layering: controllers handle requests, models handle data, views handle display.
- Controllers: `{Package}AdminController` / `{Package}PublicController`.
- Models: `Models/{Package}Model` extending `Pubvana\Models\AbstractModel` with the table string in the constructor.
- Services: `Service/{Package}Service` when singleton; `$app->map()` in `Plugin.php`, accessed via `$app->{name}()`. First-class shorthand is fine for non-singleton helpers.
- Every plugin implements `PluginInterface`.
- Vision templates: public-facing templates use `.tpl` (no PHP execution); admin templates use `.php`.
- Plugin view prefix: `$this->render('pubvana/blog/admin/index', ...)`.
- UI follows Tabler (Bootstrap 5 admin) with dark mode toggle.
- Inline JS only in plugins; `.htaccess` blocks direct access to `plugins/`, so no external `.js` files in plugins.
- User-facing strings go to `app/Language/en/` (or the relevant locale file).
- We keep the tree clean: `php -l` what you touch, no dead code, no debug
  leftovers, no secrets in code, docs, or commits.
