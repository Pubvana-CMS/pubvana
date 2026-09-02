# Pubvana Coding Standards & Pre-Push Checklist

This is the single source of truth for how Pubvana code must be written and
what must pass before code leaves your machine. It supplements
`CONTRIBUTING.md` (process) and `AGENTS.md` (agent workflow); where they
conflict, this file wins for concrete, verifiable rules.

Run the whole checklist before pushing. Every check has a command; every line
is mandatory unless marked otherwise.

---

## 1. Static analysis (mandatory, both tools)

PHPStan owns general type/code checking. Psalm owns taint/security analysis.
Run both. If either reports errors you introduced, fix them before pushing.

### PHPStan, Level 8

```bash
composer phpstan        # phpstan analyse  (phpstan.neon, level 8)
```

No errors allowed. The 5 `ignoreErrors` entries in `phpstan.neon` are required;
do not add new ones to hide real problems. `reportUnmatchedIgnoredErrors: true`
stays on so stale ignores are caught.

### Psalm, taint analysis

```bash
composer psalm          # psalm --no-progress --taint-analysis
```

Psalm 6.4.0 is pinned in `require-dev` because newer versions require
PHP >= 8.3.16 (the project floor is PHP ^8.2 and the build image ships
8.3.6). Do not bump Psalm without confirming it runs on the build PHP.

Taint analysis is enabled in `psalm.xml` (`runTaintAnalysis="true"`).
`psalm-stubs.php` declares the Flight request accessors as taint sources so
user input arriving through Flight's abstraction (not `$_GET`/`$_POST`) is
traced. Use `@psalm-taint-source input` and only valid taint kinds; an
invalid kind silently disables the annotation.

`psalm-baseline.xml` whitelists a small set of confirmed false positives
(validated Media uploads). If a baselined occurrence is ever a real bug, fix
the code, do not extend the baseline. Generation: `psalm --taint-analysis
--set-baseline=psalm-baseline.xml`.

### Syntax

```bash
composer lint            # php -l over every PHP file in app/ and plugins/
```

Adds nothing over the syntax check; new files must not introduce parse errors.

---

## 2. Tests (mandatory once PHPUnit is set up)

Test runner is PHPUnit 11 (PHP ^8.2 compatible). A minimal `phpunit.xml` and
`composer test` are part of the scaffolding; flesh out real coverage per test:

```bash
composer test            # phpunit  (phpunit.xml, tests/)
```

- Tests live in `tests/`, mirroring the class under test (`tests/Unit`,
  `tests/Feature`, etc.).
- Unit tests cover services and models in isolation; feature tests exercise
  routes/controllers through the app.
- New or changed behavior must ship with a test that fails without the change
  and passes with it.
- Do not skip a test to make CI green; skip only with a stated reason.

---

## 3. Pre-push checklist

Order matters; later steps assume earlier ones pass.

1. `composer lint`            - no PHP parse errors
2. `composer phpstan`         - Level 8, 0 errors
3. `composer psalm`           - taint analysis clean (baseline excluded)
4. `composer test`            - full suite green (relevant tests at minimum)
5. Review your own diff       - no secrets, no debug, no unrelated changes

On CI (`.github/workflows/`): `phpstan.yml` and `psalm.yml` enforce the two
static-analysis gates on push/pull to `main`. Treat a red CI status like a
blocked push.

---

## 4. Style rules that matter

- PHP floor is **8.2**. No 8.3/8.4 features (shared-host compatibility).
- `declare(strict_types=1);` at the top of every class file.
- PSR-4 autoloading (`Pubvana\` -> `app/`, `Pubvana\Plugins\` -> `plugins/`).
- Controllers: `{Package}AdminController` / `{Package}PublicController`.
- Models: `Models/{Package}Model` extending `Pubvana\Models\AbstractModel`
  with the table string in the constructor.
- Services: `Service/{Package}Service` when singleton; `$app->map()` in
  `Plugin.php`, accessed via `$app->{name}()`. First-class shorthand is fine
  for non-singleton helpers.
- Clean up after yourself: `php -l` every file you touch; no dead code; no
  leftover debug output; no secrets in code, docs, or commits.

---

## 5. Scope

This file is maintained in sync with the actual CI/composer setup. If you add
a new gate (tool, script, workflow), update this checklist and the CI in the
same change, and note it here so the list never drifts from reality.
