<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Services\UrlService;
use flight\Engine;

/**
 * JSON-LD structured data generation.
 *
 * Emits a single, connected @graph of schema.org nodes (WebSite,
 * Organization, Person, Article/BlogPosting, WebPage, BreadcrumbList)
 * linked via stable @id references rather than isolated blocks.
 */
class SchemaService
{
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * IPTC "Trained Algorithmic Media" code, used as the digitalSourceType
     * value when a page is flagged as AI-generated.
     *
     * @var string
     */
    protected const DIGITAL_SOURCE_TYPE_AI = 'https://schema.org/TrainedAlgorithmicMediaDigitalSource';

    /**
     * Article-family types a post may opt into through its schema_type.
     *
     * @var list<string>
     */
    protected const ARTICLE_TYPES = ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle'];

    /**
     * Web-page-family types a page may opt into through its schema_type.
     *
     * @var list<string>
     */
    protected const PAGE_TYPES = ['WebPage', 'AboutPage', 'ContactPage', 'FAQPage', 'CollectionPage', 'ItemPage'];

    /**
     * @param Engine<object> $app
    */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Render all applicable JSON-LD for the current page as one @graph.
     *
     * @param array<string, mixed> $context SEO context from SeoService
     * @return string HTML <script type="application/ld+json"> block
     */
    public function render(array $context): string
    {
        $siteUrl = $this->getSiteUrl();
        $orgId = $siteUrl . '#org';
        $contentType = $context['content_type'] ?? null;

        $graph = [];
        $needsOrg = $this->isHomepage($context)
            || $contentType === 'post'
            || $contentType === 'page';

        // Organization: the site/brand identity, referenced by publisher.
        if ($needsOrg) {
            $org = $this->buildOrganizationNode($orgId);
            if ($org !== null) {
                $graph[] = $org;
            }
        }

        // WebSite, homepage only.
        if ($this->isHomepage($context)) {
            $graph[] = $this->buildWebSiteNode($siteUrl . '#website');
        }

        $canonical = $context['url'] ?? $this->getCurrentUrl();

        // Author Person node (posts).
        $authorId = null;
        if ($contentType === 'post') {
            $author = $context['author'] ?? null;
            if (is_array($author) && !empty($author['name'])) {
                $authorId = !empty($author['url']) ? $author['url'] . '#person' : $siteUrl . '#author';
                $graph[] = $this->buildAuthorNode($authorId, $author);
            }
        }

        // Breadcrumbs (when the context provides them).
        $breadcrumb = $this->buildBreadcrumbNode($context, $canonical . '#breadcrumb');
        if ($breadcrumb !== null) {
            $graph[] = $breadcrumb;
        }

        // Main entity. A per-content schema_type may override the default
        // when it names a type allowed for this content kind.
        if ($contentType === 'post') {
            $articleType = $this->resolveSchemaType($context, self::ARTICLE_TYPES, 'BlogPosting');
            $graph[] = $this->buildArticleNode($context, $articleType, $canonical . '#article', $authorId, $orgId);
        } elseif ($contentType === 'page') {
            $pageType = $this->resolveSchemaType($context, self::PAGE_TYPES, 'WebPage');
            $graph[] = $this->buildWebPageNode($context, $pageType, $canonical . '#webpage', $orgId);
        }

        if ($graph === []) {
            return '';
        }

        $document = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $json = json_encode(
            $document,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS
        );
        return '<script type="application/ld+json">' . "\n" . $json . "\n</script>\n";
    }

    // -----------------------------------------------------------------
    // Node builders
    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>
    */
    protected function buildOrganizationNode(string $id): ?array
    {
        $settings = $this->app->settings();
        $siteName = $settings->get('CMS.siteName');
        $orgName = $settings->get('Seo.organization_name') ?: $siteName;

        if ($orgName === '') {
            return null;
        }

        $node = [
            '@type' => 'Organization',
            '@id'   => $id,
            'name'  => $orgName,
            'url'   => $this->getSiteUrl(),
        ];

        $logo = $settings->get('Seo.organization_logo');
        if (!empty($logo)) {
            $node['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $this->resolveImageUrl($logo),
            ];
        }

        $socialProfiles = $settings->get('Seo.social_profiles');
        if (!empty($socialProfiles)) {
            $profiles = is_array($socialProfiles) ? $socialProfiles : json_decode($socialProfiles, true);
            $safe = $this->safeSameAs($profiles);
            if ($safe !== []) {
                $node['sameAs'] = $safe;
            }
        }

        return $node;
    }

    /**
     * Keep only full safe http(s) URLs for a schema.org sameAs array.
     *
     * Seo.social_profiles is an admin textarea, so a bare host can be typed
     * in. The law is store-what-you-emit: no scheme is assumed here, the
     * value is dropped, and the admin is expected to enter a full URL.
     *
     * @param mixed $values
     * @return list<string>
     */
    protected function safeSameAs(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $safe = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $candidate = trim($value);
            if ($candidate !== '' && UrlService::isSafeExternalUrl($candidate)) {
                $safe[] = $candidate;
            }
        }

        return $safe;
    }

    /**
     * @return array<string, mixed>
    */
    protected function buildWebSiteNode(string $id): array
    {
        $siteName = $this->app->settings()->get('CMS.siteName');
        $siteUrl = $this->getSiteUrl();

        return [
            '@type' => 'WebSite',
            '@id'   => $id,
            'name'  => $siteName,
            'url'   => $siteUrl,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $siteUrl . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param array{name: string, username?: string, url?: string, sameAs?: list<string>, jobTitle?: ?string, worksFor?: ?string} $author
     * @return array<string, mixed>
     */
    protected function buildAuthorNode(string $id, array $author): array
    {
        $node = [
            '@type' => 'Person',
            '@id'   => $id,
            'name'  => $author['name'],
        ];

        if (!empty($author['url'])) {
            $node['url'] = $author['url'];
        }
        if (!empty($author['jobTitle'])) {
            $node['jobTitle'] = $author['jobTitle'];
        }
        if (!empty($author['worksFor'])) {
            $node['worksFor'] = [
                '@type' => 'Organization',
                'name'  => $author['worksFor'],
            ];
        }
        $sameAs = $this->safeSameAs($author['sameAs'] ?? []);
        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
    */
    protected function buildArticleNode(array $context, string $type, string $id, ?string $authorId, string $orgId): array
    {
        $node = [
            '@type'     => $type,
            '@id'       => $id,
            'url'       => $context['url'] ?? $this->getCurrentUrl(),
            'publisher' => ['@id' => $orgId],
        ];

        $headline = (string) ($context['title'] ?? '');
        if ($headline !== '') {
            $node['headline'] = $headline;
        }

        // Empty date fields are invalid schema.org output, so only emit
        // the ones that carry a real value.
        $published = $this->nonEmptyString($context['published_at'] ?? null);
        if ($published !== null) {
            $node['datePublished'] = $published;
        }

        $modified = $this->nonEmptyString($context['updated_at'] ?? null) ?? $published;
        if ($modified !== null) {
            $node['dateModified'] = $modified;
        }

        if ($authorId !== null) {
            $node['author'] = ['@id' => $authorId];
        }

        if (!empty($context['description'])) {
            $node['description'] = mb_substr((string) $context['description'], 0, 160);
        }

        if (!empty($context['image'])) {
            $node['image'] = $this->resolveImageUrl((string) $context['image']);
        }

        if (!empty($context['ai_generated']) && $this->aiDisclosureEnabled()) {
            $node['digitalSourceType'] = self::DIGITAL_SOURCE_TYPE_AI;
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
    */
    protected function buildWebPageNode(array $context, string $type, string $id, string $orgId): array
    {
        $node = [
            '@type'     => $type,
            '@id'       => $id,
            'url'       => $context['url'] ?? $this->getCurrentUrl(),
            'publisher' => ['@id' => $orgId],
        ];

        $name = (string) ($context['title'] ?? '');
        if ($name !== '') {
            $node['name'] = $name;
        }

        $modified = $this->nonEmptyString($context['updated_at'] ?? null);
        if ($modified !== null) {
            $node['dateModified'] = $modified;
        }

        if (!empty($context['description'])) {
            $node['description'] = mb_substr((string) $context['description'], 0, 160);
        }

        if (!empty($context['ai_generated']) && $this->aiDisclosureEnabled()) {
            $node['digitalSourceType'] = self::DIGITAL_SOURCE_TYPE_AI;
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
    */
    protected function buildBreadcrumbNode(array $context, string $id): ?array
    {
        $breadcrumbs = $context['breadcrumbs'] ?? [];

        if (!is_array($breadcrumbs) || $breadcrumbs === []) {
            return null;
        }

        $siteUrl = $this->getSiteUrl();
        $items = [];

        foreach ($breadcrumbs as $crumb) {
            if (!is_array($crumb)) {
                continue;
            }

            $label = '';
            foreach (['label', 'name'] as $key) {
                if (isset($crumb[$key]) && is_string($crumb[$key])) {
                    $label = $crumb[$key];
                    break;
                }
            }

            $node = [
                '@type'    => 'ListItem',
                'position' => count($items) + 1,
                'name'     => $label,
            ];

            $url = isset($crumb['url']) && is_string($crumb['url']) ? $crumb['url'] : '';
            if ($url !== '') {
                // The last crumb has no URL; item stays omitted rather than empty.
                $node['item'] = str_starts_with($url, '/') ? $siteUrl . $url : $url;
            }

            $items[] = $node;
        }

        if ($items === []) {
            return null;
        }

        return [
            '@type'           => 'BreadcrumbList',
            '@id'             => $id,
            'itemListElement' => $items,
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * A per-content schema_type override, or the default when the stored
     * value is not an allowed type for this content kind.
     *
     * @param array<string, mixed> $context
     * @param list<string>         $allowed
     */
    protected function resolveSchemaType(array $context, array $allowed, string $default): string
    {
        $requested = $context['schema_type'] ?? null;
        if (is_string($requested) && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return $default;
    }

    /**
     * Trim a scalar date/name value, returning null when there is nothing
     * worth emitting.
     */
    protected function nonEmptyString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    protected function aiDisclosureEnabled(): bool
    {
        return (bool) $this->app->settings()->get('Seo.ai_disclosure_enabled', true);
    }

    /**
     * @param array<string, mixed> $context
    */
    protected function isHomepage(array $context): bool
    {
        $url = $context['url'] ?? $this->getCurrentUrl();
        $siteUrl = $this->getSiteUrl();
        return rtrim($url, '/') === rtrim($siteUrl, '/');
    }

    /**
     * Absolute site base URL from the SITE_URL deployment value via
     * UrlService::siteOrigin(); never the request Host header (AUDIT M4).
     */
    protected function getSiteUrl(): string
    {
        return $this->app->url()->siteOrigin();
    }

    /**
     * Get the current request URL (full, with scheme and host).
     *
     * The origin comes from the configured site URL; the request
     * contributes its path only (AUDIT M4).
     */
    protected function getCurrentUrl(): string
    {
        $request = $this->app->request();
        $uri = strtok($request->url ?? ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        return $this->app->url()->siteOrigin() . $uri;
    }

    protected function resolveImageUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return $this->getSiteUrl() . '/' . ltrim($path, '/');
    }
}
