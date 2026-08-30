# Site Health

Site health and diagnostics for Pubvana. Surfaces configuration and security problems early so admins can fix issues before they become incidents. Available under **Tools → Site Health**.

## Checks

14 built-in checks across 4 categories:

**Environment** — PHP version, required/recommended PHP extensions, database connectivity and version, disk space on the uploads partition.

**Security** — HTTPS and forced redirect, debug mode in production, environment file (`.env`) permissions (must be readable by the web server, never world-writable), Shield authentication installed, session configuration (httponly, secure, samesite, lifetime).

**Configuration** — required settings populated (`CMS.siteUrl`, `CMS.siteName`), writable directories (`public/uploads`, `writable/cache`, `writable/logs`), `.env` not using placeholder values.

**Plugins** — all plugin migrations current, all composer plugin dependencies satisfied.

Each check returns one of three levels:

| Level | Meaning |
|-------|---------|
| Critical | Something is broken or dangerously misconfigured. Needs immediate attention. |
| Warning | Not ideal but not broken. Should be addressed when convenient. |
| Pass | Meets requirements. No action needed. |

## Dashboard Integration

When issues exist, a card appears on the admin dashboard linking to the detail page. When everything passes, nothing is shown on the dashboard.

## Service

Mapped as `$app->health()`:

- `runAll([bool $force])` — run all registered health checks (returns cached results unless forced)
- `clearCache()` — invalidate cached check results
- `addCheck(CheckInterface $check)` — register additional checks programmatically
- `groupByCategory(array $results)` — group results by category for display
- `dashboardCards()` — dashboard card data when issues exist, empty array when all clear

Results are cached to `writable/cache/sitehealth.json` for `cache_ttl` seconds (default `3600`, set in `Config/Config.php`).

## Extensibility

Other plugins can register their own health checks in their `Plugin.php` `register()` method, either programmatically:

```php
$app->health()->addCheck(new YourCustomCheck());
```

or via adext (the `health` extension type):

```php
$adext->register('health', 'checks', 'vendor.plugin-name', [
    'priority' => 50,
    'callable' => fn() => (new YourCustomCheck())->run(),
]);
```

Custom checks implement `Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface`:

```php
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;
use Pubvana\Plugins\SiteHealth\Services\CheckResult;

class YourCustomCheck implements CheckInterface
{
    public function run(): CheckResult
    {
        return new CheckResult(
            id: 'your-check-id',
            name: 'Your Check Name',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: 'Everything is fine.',
            remediation: 'What to do if it fails.',
        );
    }
}
```

## Admin Routes

- `GET /admin/site-health` — detail page with all checks and remediation
- `POST /admin/site-health/rerun` — clear cache and re-run all checks
