<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

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

        // Organization — the site/brand identity, referenced by publisher.
        if ($needsOrg) {
            $org = $this->buildOrganizationNode($orgId);
            if ($org !== null) {
                $graph[] = $org;
            }
        }

        // WebSite — homepage only.
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

        // Main entity.
        if ($contentType === 'post') {
            $graph[] = $this->buildArticleNode($context, 'BlogPosting', $canonical . '#article', $authorId, $orgId);
        } elseif ($contentType === 'page') {
            $graph[] = $this->buildWebPageNode($context, $canonical . '#webpage', $orgId);
        }

        if ($graph === []) {
            return '';
        }

        $document = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
        $siteName = $settings->get('CMS.siteName') ?? '';
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
            if (is_array($profiles) && !empty($profiles)) {
                $node['sameAs'] = array_values(array_filter($profiles));
            }
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
    */
    protected function buildWebSiteNode(string $id): array
    {
        $siteName = $this->app->settings()->get('CMS.siteName') ?? '';
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
        if (!empty($author['sameAs'])) {
            $node['sameAs'] = $author['sameAs'];
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
            '@type'         => $type,
            '@id'           => $id,
            'headline'      => $context['title'] ?? '',
            'url'           => $context['url'] ?? $this->getCurrentUrl(),
            'datePublished' => $context['published_at'] ?? '',
            'dateModified'  => $context['updated_at'] ?? $context['published_at'] ?? '',
            'publisher'     => ['@id' => $orgId],
        ];

        if ($authorId !== null) {
            $node['author'] = ['@id' => $authorId];
        }

        if (!empty($context['description'])) {
            $node['description'] = mb_substr($context['description'], 0, 160);
        }

        if (!empty($context['image'])) {
            $node['image'] = $this->resolveImageUrl($context['image']);
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
    protected function buildWebPageNode(array $context, string $id, string $orgId): array
    {
        $node = [
            '@type'        => 'WebPage',
            '@id'          => $id,
            'name'         => $context['title'] ?? '',
            'url'          => $context['url'] ?? $this->getCurrentUrl(),
            'dateModified' => $context['updated_at'] ?? '',
            'publisher'    => ['@id' => $orgId],
        ];

        if (!empty($context['description'])) {
            $node['description'] = mb_substr($context['description'], 0, 160);
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

        if (empty($breadcrumbs)) {
            return null;
        }

        $items = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'] ?? $crumb['name'] ?? '',
                'item'     => $crumb['url'] ?? '',
            ];
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

    protected function getSiteUrl(): string
    {
        $settings = $this->app->settings();
        $siteUrl = $settings->get('CMS.siteUrl');
        if (!empty($siteUrl)) {
            return rtrim($siteUrl, '/');
        }

        $request = $this->app->request();
        $scheme = $request->secure ? 'https' : 'http';
        $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }

    protected function getCurrentUrl(): string
    {
        $request = $this->app->request();
        $scheme = $request->secure ? 'https' : 'http';
        $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $uri = strtok($request->url ?? ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        return $scheme . '://' . $host . $uri;
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
