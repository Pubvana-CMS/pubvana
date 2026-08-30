<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Models;

use flight\ActiveRecord;

/**
 * SeoMeta model — per-content SEO data.
 *
 * @property int         $id
 * @property string      $content_type
 * @property int         $content_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $canonical_url
 * @property string|null $robots_directive
 * @property string|null $focus_keywords    JSON array of up to 5 keywords
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image
 * @property string|null $og_type
 * @property string|null $twitter_card
 * @property string|null $schema_type
 * @property int|null    $seo_score
 * @property string|null $hreflang
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SeoMeta extends ActiveRecord
{
    public function __construct($databaseConnection = null)
    {
        parent::__construct($databaseConnection, 'seo_meta');
    }

    /**
     * Find SEO meta for a specific content item.
     */
    public function findByContent(string $contentType, int $contentId): ?self
    {
        $record = new self($this->getDatabaseConnection());
        $record->eq('content_type', $contentType)
            ->eq('content_id', $contentId)
            ->find();

        return $record->id ? $record : null;
    }

    /**
     * Find all SEO meta records for a content type.
     *
     * @return self[]
     */
    public function findByContentType(string $contentType): array
    {
        $record = new self($this->getDatabaseConnection());
        return $record->eq('content_type', $contentType)->findAll();
    }

    /**
     * Get focus keywords as an array.
     *
     * @return string[]
     */
    public function getFocusKeywordsArray(): array
    {
        if (empty($this->focus_keywords)) {
            return [];
        }

        $decoded = json_decode((string) $this->focus_keywords, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set focus keywords from an array.
     *
     * @param string[] $keywords Max 5 keywords
     */
    public function setFocusKeywordsArray(array $keywords): void
    {
        $keywords = array_slice(array_filter(array_map('trim', $keywords)), 0, 5);
        $this->focus_keywords = !empty($keywords) ? json_encode($keywords) : null;
    }

    /**
     * Check if this content is set to noindex.
     */
    public function isNoindex(): bool
    {
        return $this->robots_directive !== null
            && str_contains($this->robots_directive, 'noindex');
    }

    /**
     * Check if this content is set to nofollow.
     */
    public function isNofollow(): bool
    {
        return $this->robots_directive !== null
            && str_contains($this->robots_directive, 'nofollow');
    }

    /**
     * Count records that have a meta_title set.
     */
    public function countWithMetaTitle(): int
    {
        $record = new self($this->getDatabaseConnection());
        $record->isNotNull('meta_title')
            ->notEq('meta_title', '')
            ->find();

        $records = new self($this->getDatabaseConnection());
        $all = $records->isNotNull('meta_title')->notEq('meta_title', '')->findAll();
        return count($all);
    }

    /**
     * Get the average SEO score across all scored content.
     */
    public function averageScore(): int
    {
        $records = new self($this->getDatabaseConnection());
        $scored = $records->isNotNull('seo_score')->findAll();

        if (empty($scored)) {
            return 0;
        }

        $total = 0;
        foreach ($scored as $record) {
            $total += (int) $record->seo_score;
        }

        return (int) round($total / count($scored));
    }
}
