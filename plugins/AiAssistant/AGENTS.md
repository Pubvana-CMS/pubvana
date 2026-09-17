# AGENTS.md — AI Assistant plugin

Guidance for AI agents contributing to this plugin, which is part of the main Pubvana repo.

## Overview

**pubvana/ai** (display name "AI Assistant") is a folder of API-key ingestion endpoints and per-key grants that let an external AI assistant read and write site content over a sessionless REST API, plus a site-level Fact Checking feature.

- **Package:** `pubvana/ai` (local plugin, no Packagist)
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`match`, `mixed`, `str_contains`)
- **Namespace:** `Pubvana\Plugins\AiAssistant` (PSR-4 style, matches the folder path)
- **Runtime dependencies:** pulled from the app's composer.json, not the plugin: `league/commonmark`, `league/html-to-markdown`, `ezyang/htmlpurifier`
- **Peer plugin dependencies:** Blog, Pages, Comments, Redirects, Navigation, SEO, and the Settings service. Every `svc()` call depends on the peer plugin being registered
- **Manifest:** `pubvana.json` (admin menu under Tools: Manage, Fact Checking, Help)
- **Config:** `Config/Config.php` (also `Config/fact-check-prompt.json`, the bundled fact-checking prompt)
- **Docs:** [README.md](./README.md) (human-facing), this file (contributor guide and API reference)

## Project guidelines

1. **Never weaken the key security model.** Only an HMAC-SHA256 hash of a token is stored (`AiService.php:1011`), keyed by a domain key derived from `SESSION_ENCRYPTION_KEY` (`AiService.php:1021`). The plaintext token is revealed exactly once at creation (`AiAdminController.php:60`). Never log, store, or cache a plaintext token.
2. **Grants are deny-all.** A key with no grants can authenticate but nothing else. Every grant decision flows through `helpCatalog()` as the single source of truth (`AiService.php:330`) and `requireGrant()` as the hard gate (`AiApiController.php:137`). Do not add an ungated endpoint or a "grant everything" escape hatch.
3. **Fact checking is site-level, and the toggle is the grant.** Fact-check endpoints open to every authenticated key when the admin has accepted the current prompt's terms and switched the service on (`FactCheckService::gateStatus()`), and refuse everything otherwise. They appear in no per-key grant form and in no `helpCatalog()` row. The prompt endpoint (`GET /api/ai/fact-check/prompt`) is the one exception: any authenticated key may read the terms even while the service is off. Submissions must attest to the current prompt version (`409` otherwise), and post/page submissions additionally require the matching `posts.read`/`pages.read` grant.
4. **Every request goes through the audit log.** `AiService::log()` records ok/denied/error outcomes including unauthenticated attempts (`AiService.php:248`). New endpoints must log with the same shape. Logging is tolerant by design: a missing table must not break the request (`AiService.php:265`).
5. **Keep the response envelope.** All public API responses are `{status, data, errors}` via `ok()` and `fail()` (`AiApiController.php:188`), (`AiApiController.php:197`). Do not return a different shape from a new endpoint.
6. **Reuse peer plugin services instead of writing SQL.** Content operations go through `$this->svc('blog')`, `svc('pages')`, `svc('comments')`, `svc('redirects')`, `svc('navigation')`. When a peer plugin is unavailable the request fails with 503 (`AiApiController.php:175`). Direct DB work belongs only in this plugin's own models (`AiKey`, `AiKeyGrant`, `AiLog`, `AiFactCheck`).
7. **Path-order matters in route registration.** Static taxonomy routes (`/api/ai/posts/tags`, `/api/ai/posts/categories`) must stay registered before the parameterized `/api/ai/posts/@slug` route (`Plugin.php:107`). Flight matches in order; moving the static routes below the parameterized ones breaks them.

## Repository layout

```
AiAssistant/
  Plugin.php                    # Entry point: maps 'ai', 'aiFactCheck', and 'aiMarkdown' services; registers admin + public routes, editor panel, block
  pubvana.json                  # Plugin manifest and admin menu under Tools (Manage, Fact Checking, Help)
  README.md                     # Short human-facing intro and where-to-go-next
  Config/
    Config.php                  # Defaults: key_prefix, max_failed_attempts, block_minutes, log_limit, factcheck URLs/timeout
    fact-check-prompt.json      # Bundled copy of the fact-checking prompt (fallback when the hosted fetch fails)
  Controllers/
    AiAdminController.php       # Admin: manage, createKey, updateGrants, toggleKey, deleteKey, saveAuthor, help
    AiFactCheckAdminController.php # Admin: fact-checks index/show, acceptTerms, toggle, delete
    AiApiController.php         # Sessionless /api/ai/* base: help, auth, grants, audit logging, response envelope, tolerant svc access
    AiPostsApiController.php    # API: /api/ai/posts/* through the Blog service
    AiPagesApiController.php    # API: /api/ai/pages/* through the Pages service
    AiCommentsApiController.php # API: /api/ai/comments/* through the Comments service
    AiRedirectsApiController.php # API: /api/ai/redirects/* through the Redirects service
    AiNavigationApiController.php # API: /api/ai/navigation/* through the Navigation service
    AiBrokenLinksApiController.php # API: /api/ai/broken-links/* through the BrokenLinks service
    AiAnalyticsApiController.php # API: /api/ai/analytics through the Analytics service
    AiFactCheckApiController.php # API: /api/ai/fact-check/* and report reads/submissions
  Services/
    AiService.php               # Keys, grants, auth, audit log, help catalog, content serializers, content/SEO helpers
    FactCheckService.php        # Fact-checking gate, prompt fetch, report validation/storage, staleness, panel + block data
    MarkdownService.php         # Markdown -> sanitized HTML and HTML -> Markdown
  Models/
    AiKey.php                   # ai_keys table model
    AiKeyGrant.php              # ai_key_grants table model
    AiLog.php                   # ai_logs table model
    AiFactCheck.php             # ai_fact_checks table model
  Database/
    Migrations/                 # Creates ai_keys, ai_key_grants, ai_logs, ai_fact_checks
    Seeds/Seed.php              # Seeds the 'ai.manage' permission
  Views/
    admin/manage.php            # Keys, grants, default author, audit log
    admin/fact-checks.php       # Terms acceptance, toggle, report history
    admin/fact-check-detail.php # One full report
    admin/fact-check-panel.php  # Read-only panel in post/page editors (content.edit.panel)
    admin/help.php              # Admin-facing help and endpoint reference
    public/blocks/fact-check-summary.tpl # Public block (Vision)
```

## Core architecture

### Plugin registration

`Plugin.php:51` maps three singletons on the app engine: `ai` (an `AiService` wired to `$app->db()`, the engine, and the plugin config), `aiFactCheck` (a `FactCheckService` with the same wiring), and `aiMarkdown` (a `MarkdownService` with the plugin config). Admin routes are registered under `pubvana.ai` and gated by a `PermissionMiddleware` for the seeded `ai.manage` permission (`Plugin.php:84`). Public REST routes hang off `routePrefix('pubvana/ai')` so the URL prefix is configurable (`Plugin.php:47`).

The CSRF middleware skips `/api/ai/*` (noted at `Plugin.php:34`), because these endpoints carry no session; auth is per-request bearer keys instead.

### Authentication and grants

`AiService::authenticate()` (`AiService.php:199`) hashes the bearer token, looks it up by hash, rejects disabled and blocked keys, and resets failure state on success. Disabled-key probing counts toward a block: after `max_failed_attempts` failures the key is blocked for `block_minutes` (`AiService.php:1041`). Every successful call stamps `last_used_at`.

Each endpoint calls `requireKey()` (`AiApiController.php:109`) for auth and `requireGrant()` (`AiApiController.php:137`) for the specific permission. A held permission is checked against the per-request cached grant set built from `AiKeyGrant::permissionsFor()` (`AiService.php:232`).

### Content flows

Content operations delegate to peer plugins:

- Posts: `svc('blog')` create/update/delete, tags, and categories
- Pages: `svc('pages')` create/update/delete
- Comments: `svc('comments')` list/approve/reject/delete
- Redirects: `svc('redirects')` create/update/delete
- Navigation: `svc('navigation')` create/delete, plus a direct `NavigationItem` model update because NavigationService has no update method (`AiService.php:635`)

Markdown is converted to sanitized HTML at ingest via `MarkdownService::toHtml()` and back to Markdown for reads via `toMarkdown()` (`AiService.php:867`, `AiService.php:617`). AI-created posts and pages are attributed to the configured default author (stored as the `Ai.default_author_id` setting, `AiService.php:306`). An optional nested `seo` block is persisted through the SEO plugin when present (`AiService.php:894`). Content and SEO helpers that more than one resource uses (`resolveContent`, `saveSeo`, `categoryIds`, `searchParam`, `demoteGrant`, `nullableString`) live on the AI service, not on a controller.

### Fact checking

The checking brain is external (the site owner's AI assistant over the API); the plugin owns everything around it (`FactCheckService`):

- **Prompt.** The versioned terms/instructions are fetched from `factcheck_prompt_url` with the bundled `Config/fact-check-prompt.json` as fallback; the result is hashed and cached per request (`currentPrompt()`). The AI must re-fetch it before every check and attest `prompt_version` on submission.
- **Gate.** `Ai.factcheck_enabled`, `Ai.factcheck_terms_version`, `Ai.factcheck_terms_accepted_at` settings drive `gateStatus()`: off is `403`, terms-version mismatch is `409`. Enabling requires terms acceptance plus at least one enabled key (`enableBlockers()`).
- **Reports.** `submitReport()` validates the payload (verdicts `supported|partially_supported|refuted|unverifiable`, claim kinds `fact|opinion`, bounded lengths, capped counts), snapshots content identity (`content_title`, `content_slug`, `content_updated_at`) and key identity, and appends to `ai_fact_checks`. Reports are a history: resubmits add rows, nothing is replaced.
- **Staleness.** `isStale()` compares the snapshot against the live content; stale reports are badged, never deleted.
- **Surfaces.** Read-only editor panel via `content.edit.panel` (`fact-check-panel.php`), the admin history/detail pages, and the `fact-check-summary` block whose provider detects the current post/page from the URL the way SEO's `detectContent()` does, rendering nothing on anything else.

### Audit log

`AiService::log()` writes one row per API request to `ai_logs`, snapshotting the key name so the trail survives key deletion (`AiService.php:248`). Failures to write are swallowed and pushed to `error_log`.

## API reference

The live guide an AI caller reads is served by `GET /api/ai/help`, generated from `helpCatalog()` (`AiService.php:330`). This section is the contributor-side reference for the same surface: envelope, grants, and endpoint rules. Keep it in sync with `helpCatalog()` and the admin help view when you change the API.

### Envelope and auth

- Every request sends the key as `Authorization: Bearer <key>`; bodies are JSON with `Content-Type: application/json`.
- Every response is `{status, data, errors}` via `ok()`/`fail()`. `status` is `ok` or `error`; on failure `data` is `null` and `errors` is a list like `[{"code": 422, "message": "title is required."}]`.
- `GET /api/ai/help` returns the live grant catalog and the grants the current key holds; `GET /api/ai/help/{permission}` returns one grant's details.

### Grants

Grants are deny-all and per key. A request that needs an ungranted permission fails with `403`.

| Grant | Purpose |
| --- | --- |
| `posts.read` | `GET /api/ai/posts` lists posts; `GET /api/ai/posts/{slug}` fetches one with full content |
| `posts.create` | `POST /api/ai/posts` creates a draft post |
| `posts.update` | `POST /api/ai/posts/{id}/update` |
| `posts.delete` | `POST /api/ai/posts/{id}/delete` |
| `posts.publish` | status `published` on create/update, and removing it (published -> draft) on update |
| `posts.schedule` | status `scheduled` + `publish_on` on create/update, and cancelling it (scheduled -> draft) on update |
| `posts.tags.read` | `GET /api/ai/posts/tags` |
| `posts.categories.read` | `GET /api/ai/posts/categories` |
| `pages.read` | `GET /api/ai/pages` lists pages; `GET /api/ai/pages/{slug}` fetches one with full content |
| `pages.create` | `POST /api/ai/pages` creates a draft page |
| `pages.update` | `POST /api/ai/pages/{id}/update` |
| `pages.delete` | `POST /api/ai/pages/{id}/delete` |
| `pages.publish` | status `published` on create/update, and removing it (published -> draft) on update |
| `comments.read` | `GET /api/ai/comments?status=pending\|approved\|rejected&page=1&per_page=25` (status optional) |
| `comments.approve` | `POST /api/ai/comments/{id}/approve` |
| `comments.reject` | `POST /api/ai/comments/{id}/reject` |
| `comments.delete` | `POST /api/ai/comments/{id}/delete` |
| `redirects.read` | `GET /api/ai/redirects` |
| `redirects.create` | `POST /api/ai/redirects` |
| `redirects.update` | `POST /api/ai/redirects/{id}/update` |
| `redirects.delete` | `POST /api/ai/redirects/{id}/delete` |
| `navigation.read` | `GET /api/ai/navigation` |
| `navigation.create` | `POST /api/ai/navigation` |
| `navigation.update` | `POST /api/ai/navigation/{id}/update` |
| `navigation.delete` | `POST /api/ai/navigation/{id}/delete` |
| `brokenlinks.read` | `GET /api/ai/broken-links` lists broken links grouped by source; `?dismissed=1` includes permanently dismissed entries |
| `brokenlinks.scan` | `POST /api/ai/broken-links/scan` runs a full scan |
| `brokenlinks.recheck` | `POST /api/ai/broken-links/{id}/recheck` re-tests one entry |
| `brokenlinks.dismiss` | `POST /api/ai/broken-links/{id}/dismiss` permanently dismisses an entry |
| `analytics.read` | `GET /api/ai/analytics?range=7\|30\|90\|180\|365\|all` returns the dashboard report |

Fact checking has no per-key grants; its endpoints open to every authenticated key when the admin accepts the terms and switches the service on.

### Content rules

- Lists and single fetches need the matching read grant. `GET /api/ai/posts` and `GET /api/ai/pages` paginate over every status, drafts included. Query params: `page` (default 1), `per_page` (default 25, max 100), `status`, `search` (adds a `snippet` around the first match).
- `GET /api/ai/posts/{slug}` and `/api/ai/pages/{slug}` return the full record; `content` is served as Markdown, converted from stored HTML.
- `POST /api/ai/posts` needs `title` plus `content_md` (Markdown, sanitized to HTML) or `content` (already-rendered HTML). `status` is `draft` (default), `published` (needs `posts.publish`), or `scheduled` (needs `posts.schedule` plus `publish_on`). Optional: `slug`, `tags`, `categories`, `excerpt`, `featured_image`, `is_featured`, `allow_comments`, and a nested `seo` object.
- Updates are partial; omitting `status` leaves the current state. Demoting a live item to `draft` takes the grant for the state being torn down (`AiService::demoteGrant()`).
- `POST /api/ai/pages` uses the same content rule; `status` is `draft` or `published`.
- `POST /api/ai/redirects` needs `source_path` (normalized: leading slash, no trailing slash) and `target_url`; optional `status_code` (301/302), `enabled`, `notes`.
- `POST /api/ai/navigation` needs `label` and `url`; optional `nav_group` (default `primary`), `parent_id`, `sort_order`, `target` (`_self`/`_blank`).
- Stored HTML is sanitized: Markdown input strips raw HTML, output is HTMLPurified. Raw `<script>` tags are stripped, not executed.
- `GET /api/ai/broken-links` lists broken link results grouped by source; `?dismissed=1` includes permanently dismissed entries. `POST /api/ai/broken-links/scan` runs a full scan and returns `{total, broken, sources}`; scanning is the only way new links are discovered. `POST /api/ai/broken-links/{id}/recheck` re-tests one entry and returns `{id, http_status, error, resolved}` (the row is removed when `resolved` is true). `POST /api/ai/broken-links/{id}/dismiss` permanently dismisses an entry and returns it.
- `GET /api/ai/analytics?range=30` returns the Analytics dashboard report shape (`range`, `totalViews`, `trends`, `topContent`, `referrers`). Valid `range` values are `7`, `30`, `90`, `180`, `365`, and `all`; anything else is treated as `30`.

### Fact checking flow

- `GET /api/ai/fact-check/prompt` returns the versioned terms; the caller must fetch it before every check.
- Submit `POST /api/ai/posts/{id}/fact-check` or `/api/ai/pages/{id}/fact-check` with `prompt_version`, `summary`, `overall_verdict`, `claims`, `prompt_interference`, `interference_note`.
- Verdicts: `supported`, `partially_supported`, `refuted`, `unverifiable`. Opinions take `kind: "opinion"` with a `determination` and never a verdict.
- `prompt_version` must match the current version or the submission is refused with `409`.
- Read reports back with `GET /api/ai/fact-checks?page=1&per_page=25&content_type=post&content_id=5` and `GET /api/ai/fact-checks/{id}`; `stale: true` means the content was edited after the check.
- Submitting requires the matching read grant (`posts.read` or `pages.read`).

### SEO metadata

Posts and pages accept a nested `seo` object on create and update. Only included fields are written; omit `seo` entirely to leave SEO untouched. A `robots_directive` value outside the listed set is ignored (not written), so an existing directive is never clobbered by a malformed one; a blank value clears it.

| Field | Type | Notes |
|-------|------|-------|
| `meta_title` | string | Title override; blank clears it |
| `meta_description` | string | Description override |
| `canonical_url` | string | Canonical override |
| `robots_directive` | string | `noindex`, `nofollow`, `noindex, nofollow`, or blank |
| `focus_keywords` | string[] or string | Array, or comma-separated string (max 5 kept) |
| `og_title` | string | Open Graph title override |
| `og_description` | string | Open Graph description override |
| `og_image` | string | Open Graph image URL/path |
| `og_type` | string | e.g. `article`, `website` |
| `twitter_card` | string | `summary`, `summary_large_image` |
| `hreflang` | string | e.g. `en`, `en-US` |

## Development and testing

The unit suite lives in `tests/Unit/Plugins/AiAssistant/` and covers the services, models, and grant logic; controllers and views are exercised through the full app.

```bash
php -l plugins/AiAssistant/Plugin.php           # lint every touched file
vendor/bin/phpunit tests/Unit/Plugins/AiAssistant
```

- Verify the admin screens at `/admin/ai/manage`, `/admin/ai/fact-checks`, and `/admin/ai/help` after any controller or view change.
- Generate a key end to end, confirm the plaintext shows once, then disable/delete it from the admin row actions.
- Exercise the public API with a bearer token and confirm 401 (no key), 403 (no grant), 422 (bad input), and the `{status, data, errors}` envelope.
- Confirm a request against a disabled key clicks up `failed_attempts` and blocks when the threshold is crossed, and that a sessionless request still works (no CSRF token involved).
- Fact checking end to end: accept terms, toggle on, `GET /api/ai/fact-check/prompt`, submit a report, see it in the history and editor panel, edit the content and confirm the stale badge, toggle off and confirm endpoints refuse.
- Coverage: the unit suite covers `AiService`, `FactCheckService`, `MarkdownService`, the models, and demote-grant logic; controllers and views stay manual. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

Steps that go beyond the repo-wide style, derived from the existing code:

1. `declare(strict_types=1);` first line in every class file.
2. Class name, file name, and namespace must align: `Pubvana\Plugins\AiAssistant\Services\AiService` lives in `Services/AiService.php`.
3. Endpoints keep the sequence: authenticate, require grant, validate input, act, log, respond through `ok()`/`fail()`. On validation failure, log a specific `error` detail before `fail()`.
4. Every new permission must be added to `helpCatalog()` (`AiService.php:330`) with its route group, label, summary, and endpoints. The catalog drives `/api/ai/help`, the admin help page, and grant-form rendering, so it is the point of truth for grants.
5. All API reads return display-safe arrays; HTML content is served as Markdown and never raw. Serializers (`serializePost`, `serializePage`, `serializeComment`, `serializeRedirect`, `serializeNavigationItem`) must stay in `AiService`.
6. Keep pagination bounded: `per_page` is clamped to `[1, 100]` and `page` to `>= 1` for every list endpoint (`AiPostsApiController.php:30`). Do not introduce an unbounded list.
7. Grant-check before acting: posting a `published` status requires the `publish` grant, not the bare create/update grant. Do not publish or schedule under the write grant alone. State changes gate both ways: demoting a live item (`published`/`scheduled` -> `draft`) takes the grant for the state being torn down (`AiService::demoteGrant()`), and omitting `status` on an update leaves the current state untouched.
8. Use the tolerant `svc()` wrapper for peer services and the tolerant `saveSeo()` for optional SEO, so missing peer plugins degrade to a 503 or a no-op instead of a hard crash.
9. Do not hard-code the `/ai` URL prefix; use `$this->path()` and `routePrefix('pubvana/ai')` the way the existing code does.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [README.md](./README.md) | Human-facing intro for site owners |
| This file | Contributor guide and the API reference (envelope, grants, endpoints) |
| [help.php](./Views/admin/help.php) | Admin-facing plain-language description of each grant |
| [helpCatalog()](./Services/AiService.php) | Single source of truth for grant semantics; drives `/api/ai/help` |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a permission (and its endpoint) | `helpCatalog()` at `AiService.php:330`, then the API methods in the matching `Ai*ApiController.php`, then a route in `Plugin.php:107` |
| Change the fact-checking gate, prompt fetch, report rules, or staleness | `FactCheckService.php` |
| Change the fact-check prompt text or version | `Config/fact-check-prompt.json` (bundled copy) and the hosted source at `factcheck_prompt_url` |
| Change the public fact-check block | provider data in `FactCheckService::blockData()`, template `Views/public/blocks/fact-check-summary.tpl`, registration in `Plugin.php:165` |
| Change the editor fact-check panel | `FactCheckService::panelData()` + `Views/admin/fact-check-panel.php` |
| Change key tuning (prefix, block threshold, block minutes, log limit) | `Config/Config.php:5` |
| Change the audit log shape | `AiService::log()` at `AiService.php:248`, `AiLog.php`, and the manage view at `Views/admin/manage.php:251` |
| Change markdown sanitation options | `MarkdownService.php:31` (commonmark options) |
| Add or change a serializer | `AiService.php:696` and below |
| Change the default-author behavior | `AiService::defaultAuthorId()` at `AiService.php:306` + `AiAdminController::saveAuthor()` at `AiAdminController.php:117` |
| Change the admin grant form | `Views/admin/manage.php:203` |
| Extend the admin help screen | `Views/admin/help.php` |

## PR / contribution checklist

- [ ] Changes fit the project guidelines (no weakened key logic, no ungated endpoint, grants stay deny-all, fact-check gate stays site-level)
- [ ] `php -l` clean on every touched file
- [ ] New permission/endpoint documented in `helpCatalog()` and the admin help view as appropriate (fact-check endpoints: the admin help view only)
- [ ] Endpoint verified for 401, 403, 422, and the `{status, data, errors}` envelope
- [ ] Audit log written and tolerated-failure path intact
- [ ] Static routes still registered before parameterized routes in `Plugin.php`
- [ ] No plaintext or hashed API key values committed anywhere
- [ ] README.md updated if user-facing behavior changed

## Out of scope / non-goals

- A replacement for the admin content editors. This plugin exposes a machine-facing API for an external assistant; admin UI is thin and stays thin.
- Chat or prompt UI, model integrations, or client-side SDKs. The plugin only exposes the REST API and its admin management screens; fact checking is no exception, the checking brain stays external.
- Replacing the peer plugins' write services (Blog, Pages, Comments, Redirects, Navigation). It calls them and serializes their results.
- The hosted fact-check prompt itself (`pubvanacms.com/fact-checking/prompt.json`) is served outside this repo; the bundled JSON is only a fallback.
