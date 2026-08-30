# SEO

SEO management for Pubvana: meta tags, structured data, sitemaps, robots.txt, Open Graph, and AI/GEO optimization, all under **Tools → SEO**.

One table is used:

- `seo_meta` — per-content SEO fields (`meta_title`, `meta_description`, `canonical_url`, `robots_directive`, `focus_keywords` (JSON, up to 5), `og_*`, `twitter_card`, `schema_type`, `seo_score`, `hreflang`), uniquely keyed on `(content_type, content_id)`.

## What it does

- **Meta tags** — builds `<title>`, description, canonical, robots, and hreflang from per-content `seo_meta`, with site-level defaults from `Seo.*` settings (title template/separator, default Open Graph image, default language).
- **Open Graph / Twitter** — renders OG and Twitter card tags for social sharing.
- **Structured data** — emits a single connected JSON-LD `@graph`: Organization, WebSite (with SearchAction), Person, BlogPosting/WebPage, and BreadcrumbList. AI-generated content is flagged with `digitalSourceType`.
- **Sitemap** — `/sitemap.xml` of published pages/posts (plus category/tag archives), skipping `noindex` items.
- **robots.txt** — `/robots.txt`, with per-crawler AI directives. Allow/block GPTBot, ClaudeBot, Google-Extended, PerplexityBot, Bytespider, and more via **Tools → SEO**.
- **LLMs.txt** — `/llms.txt` (llmstxt.org format) for AI/GEO discoverability.
- **Content analysis** — an in-editor panel scores title/content against 14 SEO checks (0-100) and lets you autosave `seo[...]` fields.

## Head output on the public site

`PublicController::render()` detects when the plugin is loaded and seeds the theme's `header` variable with the computed `<title>` and the full head block (meta, canonical, Open Graph, structured data). The default theme emits it via `{! header !}`. `renderHead()` does not itself emit `<title>` (the theme's layout renders the title), though `buildTitle()` supplies the value.

In the default theme layout this is:

```twig
{! header !}
```

Other plugins can inject into the head before rendering:

```php
$app->seo()->addMeta('name', 'content');   // emits <meta name="..." content="...">
$app->seo()->addTag('<link ...>');         // emits the raw HTML verbatim
```

## Content edit panel

The Blog and Pages editors render a **SEO** panel (from `content.edit.panel`). It autosaves fields on blur/change to `POST /admin/seo/meta` and the **Analyze Content** button calls `GET /admin/seo/analyze`. Until a piece of content is first saved the panel shows a create-notice.

## Public endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/sitemap.xml` | XML sitemap |
| GET | `/robots.txt` | robots.txt with AI crawler directives |
| GET | `/llms.txt` | LLMs.txt (404 unless `Seo.llms_txt_enabled`) |

## Services

Six services are registered on the engine:

```php
$app->seo()->renderHead();                       // full <meta>/<link> head block string
$app->seo()->addMeta($name, $content);           // queue a meta tag
$app->seo()->addTag($html);                      // queue a raw tag
$app->seo()->setContext($data);                  // override the detect context
$app->seo()->getMeta($contentType, $contentId);  // SeoMeta or null
$app->seo()->saveMeta($contentType, $contentId, $data);   // SeoMeta
$app->seo()->buildTitle();                       // computed title string
$app->seoSchema()->render($context);             // JSON-LD graph
$app->seoSitemap()->generate();                  // sitemap XML
$app->seoRobots()->generate();                   // robots.txt body
$app->seoRobots()->getAiCrawlerList();           // crawler => default stance
$app->seoLlmsTxt()->generate();                  // llms.txt body
$app->seoAnalysis()->analyze($data);             // ['score','checks']
```

## Dependencies

SEO reads from the Blog and Pages plugins (posts, categories, tags, and published pages) and the Media plugin (image pickers), and uses `$app->profiles()` for author enrichment. Content sections render empty if Blog or Pages are disabled.

For custom content types, pass `breadcrumbs` (`[['label'=>..,'url'=>..]]`) and optionally `author` through `seo()->setContext()` so SchemaService emits BreadcrumbList/Person.

## Translations

Not yet available — labels are currently hardcoded in the views.
