<?php

declare(strict_types=1);

namespace Pubvana\Services;

/**
 * PerceivedText - the text a visitor perceives from a chunk of HTML.
 *
 * Search matches this, not the raw column. Matching the raw column finds
 * markup (tag and attribute names such as `href` or `class`) that the visitor
 * never sees, so a hit on a tag name returns a result with nothing to score
 * and nothing to highlight.
 *
 * The text is assembled from two parts, in this order:
 *   1. Text nodes: strip_tags() first, then html_entity_decode(). The order
 *      matters. Decoding first turns an escaped sample like `&lt;a href="x"&gt;`
 *      into a real tag that strip_tags() then removes, losing text the visitor
 *      can read.
 *   2. The values of alt, title, and aria-label on real tags. These are what a
 *      screen reader announces and what a tooltip shows, so they count as
 *      perceived text.
 *
 * Nothing else from inside a tag is included: no tag names, no attribute
 * names, and no class, style, src, or data-* values.
 *
 * @package Pubvana\Services
 */
final class PerceivedText
{
    /**
     * Attributes whose values a visitor perceives: alt is read in place of an
     * image, title shows as a tooltip, aria-label is announced.
     */
    private const PERCEIVED_ATTRIBUTES = ['alt', 'title', 'aria-label'];

    /**
     * Perceived text for an HTML fragment.
     */
    public static function fromHtml(string $html): string
    {
        $text       = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $attributes = self::attributeText($html);

        $parts = array_filter(
            [$text, $attributes],
            static fn(string $part): bool => trim($part) !== ''
        );

        return implode(' ', $parts);
    }

    /**
     * Does the term appear in any of the given strings, case-insensitively?
     *
     * A provider calls this after its SQL pre-filter to drop rows whose only
     * hit was markup, so a tag-only term returns nothing.
     */
    public static function contains(string $term, string ...$haystacks): bool
    {
        if ($term === '') {
            return false;
        }

        foreach ($haystacks as $haystack) {
            if ($haystack !== '' && mb_stripos($haystack, $term) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Values of the perceived attributes on real tags.
     *
     * Scans tag-shaped substrings only, so an escaped sample
     * (`&lt;img alt="x"&gt;`) is left to the text-node pass instead of being
     * read as an attribute.
     */
    private static function attributeText(string $html): string
    {
        if (preg_match_all('/<[a-zA-Z][^>]*>/', $html, $tags) < 1) {
            return '';
        }

        $values = [];

        foreach ($tags[0] as $tag) {
            foreach (self::PERCEIVED_ATTRIBUTES as $name) {
                $pattern = '/(?:^|\s)' . preg_quote($name, '/') . '\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';

                if (preg_match_all($pattern, $tag, $matches) < 1) {
                    continue;
                }

                foreach ($matches[1] ?? [] as $raw) {
                    $value = trim($raw, "\"'");
                    if ($value !== '') {
                        $values[] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }

        return implode(' ', $values);
    }
}
