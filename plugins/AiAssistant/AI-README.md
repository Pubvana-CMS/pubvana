# AI Assistant Integration Guide

This file is written for the AI itself. Use it to call the Pubvana CMS via the `/ai/*` REST API.

## Setup

An admin creates an API key and grants permissions under **Tools → AI Assistant** (requires the `ai.manage` permission). The plaintext token is revealed once at creation.

Every request:

- Send the key in the `Authorization` header: `Authorization: Bearer <key>`.
- All bodies are JSON with `Content-Type: application/json`.
- Responses use a fixed envelope. `status` is `ok` or `error`:

```json
{
  "status": "ok",
  "data": { ... },
  "errors": []
}
```

On failure `data` is `null` and `errors` is a list like `[{"code": 422, "message": "title is required."}]`.

You may call `GET /ai/help` for the live grant catalog and `GET /ai/help/{permission}` for details on one grant.

## Grants

Grants are deny-all and per key. A request that needs an ungranted permission fails hard with `403`; fix it by asking the admin for the grant. Grants used by the endpoints:

| Grant | Purpose |
| --- | --- |
| `posts.read` | `GET /ai/posts` lists posts; `GET /ai/posts/{slug}` fetches one with full content |
| `posts.create` | `POST /ai/posts` creates a draft post |
| `posts.update` | `POST /ai/posts/{id}/update` |
| `posts.delete` | `POST /ai/posts/{id}/delete` |
| `posts.publish` | status `published` on create/update |
| `posts.schedule` | status `scheduled` + `publish_on` on create/update |
| `posts.tags.read` | `GET /ai/posts/tags` |
| `posts.categories.read` | `GET /ai/posts/categories` |
| `pages.read` | `GET /ai/pages` lists pages; `GET /ai/pages/{slug}` fetches one with full content |
| `pages.create` | `POST /ai/pages` creates a draft page |
| `pages.update` | `POST /ai/pages/{id}/update` |
| `pages.delete` | `POST /ai/pages/{id}/delete` |
| `pages.publish` | status `published` on create/update |
| `comments.read` | `GET /ai/comments?status=pending\|approved\|rejected&page=1&per_page=25` (status optional) |
| `comments.approve` | `POST /ai/comments/{id}/approve` |
| `comments.reject` | `POST /ai/comments/{id}/reject` |
| `comments.delete` | `POST /ai/comments/{id}/delete` |
| `redirects.read` | `GET /ai/redirects` |
| `redirects.create` | `POST /ai/redirects` |
| `redirects.update` | `POST /ai/redirects/{id}/update` |
| `redirects.delete` | `POST /ai/redirects/{id}/delete` |
| `navigation.read` | `GET /ai/navigation` |
| `navigation.create` | `POST /ai/navigation` |
| `navigation.update` | `POST /ai/navigation/{id}/update` |
| `navigation.delete` | `POST /ai/navigation/{id}/delete` |

`GET /ai/broken-links` and `GET /ai/analytics` are stubs and return `501`.

## Reading content

Both lists and single fetches need the matching read grant.

`GET /ai/posts` and `GET /ai/pages` return paginated lists over **every** status, so drafts count too. Query params:

- `page` (default `1`), `per_page` (default `25`, max `100`).
- `status`: posts allow `draft|published|scheduled`; pages allow `draft|published`.
- `search`: substring match across title, slug, excerpt (posts only), and content. When a search term is given, each item includes a `snippet` with plain-text context around the first match in content or, for posts, the excerpt.

List items are light (no body). Post items also carry their `excerpt` so a plain list still has a preview. Response shape:

```json
{
  "status": "ok",
  "data": {
    "items": [{"id": 4, "title": "Temp Post 3", "slug": "temp-post-3", "status": "published", "snippet": null, "...": "..."}],
    "total": 13, "page": 1, "per_page": 25, "status": null, "search": null
  },
  "errors": []
}
```

`GET /ai/posts/{slug}` (and `/ai/pages/{slug}`) return the full record. The `content` field is returned as Markdown (converted from the stored HTML), so you can edit and resubmit it without fighting HTML. Use the list to find a slug, then fetch the piece you want to work on.

## Creating a post

`POST /ai/posts` requires `title` plus either `content_md` (markdown, converted to sanitized HTML) or `content` (already-rendered HTML).

- `status`: `draft` (default), `published`, or `scheduled`.
  - `published` requires `posts.publish` and stamps the current time.
  - `scheduled` requires `posts.schedule` plus `publish_on` (e.g. `"2026-09-01 09:00:00"`), stored as `published_at`.
- `slug`: optional; else generated from the title. If it collides, a dash-timestamp suffix is appended.
- `tags`: string like `"php, cms"` or an array; `categories`: array of category ids.
- Optional: `excerpt`, `featured_image`, `is_featured`, `allow_comments`.
- Optional `seo`: a nested SEO metadata object (see below).

```bash
curl -X POST /ai/posts \
  -H "Authorization: Bearer pvai1_..." \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Hello from the assistant",
    "content_md": "# Hello\n\nDrafting this from the API.",
    "status": "published",
    "tags": "cms",
    "seo": {
      "meta_title": "Hello from the assistant",
      "meta_description": "Drafting this from the API.",
      "robots_directive": "",
      "focus_keywords": ["php", "cms"],
      "og_type": "article",
      "twitter_card": "summary_large_image"
    }
  }'
```

Response `data` contains the serialized post plus `id` and a public `url`. The serialized post includes a `seo` block (or `seo: null` when no SEO meta exists).

Updating (`POST /ai/posts/{id}/update`) is a partial update; only present fields change. Re-applying `published` keeps the original `published_at` if the post was already published; asking for `published` or `scheduled` on an existing post also requires the corresponding grant.

## Creating a page

`POST /ai/pages` uses the same content rule (`content_md` or `content`) plus optional `allow_comments`. Slugs are generated from the title. `status` is `draft` or `published`. Pages also accept the same optional `seo` object.

## SEO metadata

Both posts and pages accept a nested `seo` object on create and update. Only the fields you include are written; omitted fields keep their current value (omit `seo` entirely to leave SEO untouched).

| Field | Type | Notes |
|-------|------|-------|
| `meta_title` | string | Title override; blank clears it |
| `meta_description` | string | Description override |
| `canonical_url` | string | Canonical override |
| `robots_directive` | string | `noindex`, `nofollow`, `noindex, nofollow`, or blank |
| `focus_keywords` | string[] or string | Array, or a comma-separated string (max 5 kept) |
| `og_title` | string | Open Graph title override |
| `og_description` | string | Open Graph description override |
| `og_image` | string | Open Graph image URL/path |
| `og_type` | string | e.g. `article`, `website` |
| `twitter_card` | string | `summary`, `summary_large_image` |
| `hreflang` | string | e.g. `en`, `en-US` |

## Redirects

`POST /ai/redirects` needs `source_path` (normalized: leading slash, no trailing slash) and `target_url`. Optional: `status_code` (301/302), `enabled`, `notes`.

## Navigation

`POST /ai/navigation` needs `label` and `url`; optional `nav_group` (default `primary`), `parent_id`, `sort_order`, `target` (`_self`/`_blank`). `GET /ai/navigation` returns items grouped by menu group. Updates are partial.

## Content safety

Stored HTML is sanitized (Markdown input strips raw HTML; output is HTMLPurified). Do not send raw `<script>` tags; they are stripped, not executed.