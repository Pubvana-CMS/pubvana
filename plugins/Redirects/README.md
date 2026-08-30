# Redirects

URL redirect manager with automatic tracking of incoming 404s. Manage 301/302 redirects under **Tools → URL Manager** and review unresolved 404 hits under **Tools → 404 Manager**.

Two tables are used:

- `redirects` — a source path, a target URL, an HTTP status code, and an enabled flag. Exact-path matching only; query strings are ignored when matching but preserved when forwarding.
- `redirects_links` — aggregated incoming 404 records (hits, last seen, last referrer/user-agent). Entries are grouped by `source_path`, so an existing entry is updated rather than duplicated.

## Matching

Redirects run on the live request via a `before('start')` hook. Only `GET` and `HEAD` requests are checked.

- The incoming path is compared against active (`enabled = 1`) redirects by exact path.
- A self-redirect (target path resolves to the current path) is never issued.
- On match, `hit_count` / `last_hit_at` are recorded and the request is redirected with the configured `301` (permanent) or `302` (temporary) status. The original query string is forwarded, appended with `&` when the target already has one.
- Both `redirects.skip_prefixes` and `incoming_404s.skip_prefixes` default to `['/admin', '/api']`, so admin and API traffic is never redirected or logged.

## 404 Manager

When a request ends in a 404, the `before('halt')` hook logs it via `RedirectLinksService::logCurrentRequest()`. Entries start as **Open**, and can be:

- **Create Redirect** — jumps to the new-redirect form pre-filled from the broken path. On save, the matching 404 entry is marked **Resolved**.
- **Ignore** / **Unignore** — silence or restore an entry without creating a redirect.
- **Delete** — drop the entry permanently.

Four views exist: **Active** (default), **Resolved**, **Ignored**, and **All**.

## Services

Register both services on the app engine:

```php
$app->redirects()->all();                              // all redirects, ordered by source_path
$app->redirects()->find($id);                          // one redirect, or null
$app->redirects()->create($data);                      // new redirect (source_path, target_url, status_code, enabled, notes)
$app->redirects()->update($id, $data);                 // update fields, returns redirect or null
$app->redirects()->delete($id);                        // bool
$app->redirects()->getTargetSuggestions();             // grouped pages/blog-post targets for the quick-target picker
$app->redirects()->handleCurrentRequest();             // run the live-request matcher
$app->redirectLinks()->all($status);             // active|ignored|resolved|all
$app->redirectLinks()->recent($status, $limit);  // first N by last seen
$app->redirectLinks()->count($status);           // int
$app->redirectLinks()->delete($id);              // bool
$app->redirectLinks()->setIgnored($id, bool);    // redirect or null
$app->redirectLinks()->markResolved($id, $rid);  // link an entry to a redirect
```

## Notes on normalization

- Source paths and incoming paths are normalized: a leading slash is enforced, duplicate slashes collapse, and a trailing slash is stripped (except for the root `/`).
- Targets without a scheme are treated as internal paths and given a leading slash; full URLs (including `https://`) pass through unchanged.

## Translations

Not yet available — labels are currently hardcoded in the views.
