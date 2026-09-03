# Broken Links

Scans outbound links in published posts and pages and reports broken URLs. Manage broken links under **Tools > Broken Links**.

## How it works

1. **Content sources** are registered via adext by each plugin that has content with outbound links. Blog and Pages register their own sources; future plugins follow the same pattern.
2. **Link extraction** parses HTML `<a>` tags, Markdown `[text](url)` links, and bare URLs from content. Only external, checkable URLs are kept (same-site, mailto, tel, javascript, data, and fragment-only links are excluded).
3. **HTTP checking** sends a HEAD request (falling back to GET on 405) with a 10-second timeout and up to 5 redirects.
4. **Results** are stored in the `broken_links` table, keyed on `(source_type, source_id, url_hash)`. Previously broken links that are now OK are removed automatically.

## Admin UI

Under **Tools > Broken Links**:

- **Run Scan** - checks all registered content sources for outbound broken links
- **Recheck** - rechecks a single URL; removes it if it is now reachable
- **Dismiss** - permanently hides a broken link (never re-appears on future scans)
- **Show/Hide Dismissed** - toggle visibility of dismissed entries

Results are grouped by source content (post or page) with the source title linking to the edit form.

## Services

Register the service on the app engine:

```php
$app->brokenLinks()->scan();              // Full scan; returns ['total', 'broken', 'sources']
$app->brokenLinks()->all($showDismissed); // Grouped results
$app->brokenLinks()->recheck($id);        // Recheck one URL
$app->brokenLinks()->dismiss($id);        // Permanent dismiss
$app->brokenLinks()->countBroken();       // Count for dashboard
$app->brokenLinks()->recent($limit);      // Recent entries for dashboard
```

## CLI

```bash
php runway broken-links:check
```

Runs the same scan logic as the admin UI. Exit code 1 if any broken links found, 0 otherwise.

## Extensibility

Plugins register as content sources via adext:

```php
$adext->register('brokenlinks', 'source', 'pubvana.myplugin', [
    'label'    => 'My Plugin Items',
    'callable' => function () use ($app): array {
        return [
            [
                'type'    => 'mytype',       // source type identifier
                'id'      => $item->id,       // ID in your plugin's table
                'title'   => $item->title,    // display title
                'content' => $item->content,  // HTML content to scan for links
            ],
        ];
    },
]);
```

The callable must return an array of items with `type`, `id`, `title`, and `content` keys.

## Cron

A cron stub (`broken-links:cron`) exists for future scheduling integration. When the cron infrastructure is built, it wires up to the same scan logic.

## Notes

- Dismissal is permanent; dismissed entries are never re-enabled by scans.
- URL checking runs sequentially for shared-host safety.
- The `broken_links` table has a unique index on `(source_type, source_id, url_hash)` to prevent duplicates.
- Links to the same site are automatically excluded from scanning.
