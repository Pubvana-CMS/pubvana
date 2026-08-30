# Analytics

Traffic reports for your site: total views, top content, and referrers. Find them under Tools → Analytics.

## Tracking

Tracks real calls to your site and records the referring site and the page they visited. We don't track IP or Location information from your visitors.

Tracking is **on by default** when the plugin is enabled. Flip the "Track page views" switch on the report page to turn it off.

## Report

**Admin -> Tools -> Analytics** :

- **Total Views** stat card for the selected period
- **Views over time** multi-line chart with a 7d / 30d / 90d / 180d / 1y / All range filter
- **Top Content** ranked `#1`, `#2`, ... by hits, with a link to each path
- **Top Referrers** by domain

## Retention

Individual hits are retained for 30 days. Once a day, these are consolidated when they're 30 days old.

## Technical Specs

### `analytics_page_views` (< 30 days)

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `page_path` | varchar(255) | Normalized request path, indexed |
| `page_group` | varchar(50) | First URL segment, indexed |
| `referrer_domain` | varchar(255) | Host of the incoming referrer, nullable |
| `viewed_at` | datetime | Indexed |

### `analytics_views_daily` (> 30 days)

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `day` | varchar(10) | `YYYY-MM-DD` |
| `page_group` | varchar(50) | |
| `page_path` | varchar(255) | |
| `view_count` | int | |

Unique index on `(day, page_group, page_path)`.

### `analytics_referrers_daily` (rolled)

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary | |
| `day` | varchar(10) | `YYYY-MM-DD` |
| `referrer_domain` | varchar(255) | |
| `view_count` | int | |

Unique index on `(day, referrer_domain)`.

## Services

Registered on the app engine as `analytics`:

```php
$app->analytics()->dashboard('30');      // full report dataset for a range
$app->analytics()->totalViews('7');      // int
$app->analytics()->topContent('30', 10); // ranked paths
$app->analytics()->referrers('30', 10);  // ranked domains
$app->analytics()->isTrackingEnabled();  // bool from settings
$app->analytics()->logView();            // record the current request
$app->analytics()->rollup();             // force a rollup now
$app->analytics()->maybeRollup();        // rollup if not run today
```

Ranges are `'7'`, `'30'`, `'90'`, `'180'`, `'365'`, or `'all'`.
