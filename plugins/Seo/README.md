# SEO

SEO management for Pubvana: meta tags, structured data, sitemaps, robots.txt, Open Graph, and AI/GEO optimization, all under **Settings → SEO**.

## Features

- Meta tags with per-content overrides
- Open Graph and Twitter cards
- Structured data (JSON-LD)
- XML sitemap at `/sitemap.xml`
- robots.txt with per-crawler AI directives
- LLMs.txt for AI discoverability
- In-editor content analysis panel
- Canonical, Open Graph, and JSON-LD URLs built from the configured site URL

## Usage

Open **Settings → SEO** to manage settings and AI crawler directives. The SEO panel appears in the Blog and Pages editors.

## AI Assistant API

With the AI Assistant plugin enabled, an API key can manage the same settings over HTTP. Every request sends `Authorization: Bearer <key>`, and `GET /api/ai/seo/help` returns the field list and the crawler names.

| Grant | Endpoint | Does |
| --- | --- | --- |
| `seo.read` | `GET /api/ai/seo/settings` | Read every setting and the AI crawler stances |
| `seo.read` | `POST /api/ai/seo/analyze` | Score a draft against the SEO checks |
| `seo.manage` | `POST /api/ai/seo/settings` | Change settings. Only the fields sent are written |

Per-content SEO stays on the posts and pages endpoints, in the nested `seo` object.

## License

MIT

Note: extensive details can be found in AGENTS.md
