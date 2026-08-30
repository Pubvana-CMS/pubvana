# Search

Site-wide search. Content plugins register themselves as search sources. Admins turn each source on or off on the `/admin/search` page (under Content). Sources that are off are left out of results.

## Registering a search source

In your plugin's `Plugin.php`, register with adext type `search`:

```php
$adext->register('search', 'provider', 'pubvana.my-plugin', [
    'label'       => 'My Items',
    'content_type'=> 'Item',
    'description' => 'Short description shown in the admin source list.',
    'callable'    => fn(string $term) => $app->myPlugin()->searchProvider($term, $prefix),
]);
```

`label` and `callable` are required. `description` and `content_type` are optional.

The `callable` receives the raw query string and returns content matches. Your provider finds content. It does not rank it.

## Result shape

Return a list of normalised matches:

```php
[
    'id'           => 12,
    'title'        => 'My Item',
    'url'          => $prefix . '/item-slug',
    'excerpt'      => 'Plain-text snippet, may be pre-truncated around the term',
    'content_type' => 'Item',
    'published_at' => '2026-05-06 12:00:00',
]
```

Ranking is done by the Search service (title first, then excerpt, then full body, with a recency boost). Providers should:

- Return only published or otherwise visible content.
- Give `excerpt` as plain text with HTML stripped. The Search service highlights matched terms and escapes before rendering.
- Optionally give `content` (also stripped) so the service can score the full body.
- Set `published_at` for the recency boost. Use a creation date when there is no publish date (for example, pages use their created date).

## Source management

Admins toggle sources on and off in `/admin/search`. Off sources are skipped during aggregation. New sources are enabled by default and show up on their own; no plugin change is needed to make one manageable.

## Search form block

The Search plugin registers the `pubvana.search.form` block, which can be placed in any theme region. Block options:

- `action` (form URL, default `/search`)
- `label`
- `placeholder`
- `button_text`

## Public endpoint

`GET /search?q=term` renders results with highlighted terms and pagination. `resultsPerPage` and `minQueryLength` are editable under Content → Search.