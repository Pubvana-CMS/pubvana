<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

/**
 * SEO content analysis and scoring.
 *
 * Evaluates content against focus keywords and SEO best practices.
 * Returns a score (0–100) and a list of pass/fail/warning checks.
 */
class ContentAnalysisService
{
    /**
     * Analyze content for SEO quality.
     *
     * @param array<string, mixed> $data Keys: title, content, meta_title, meta_description,
     *                                    focus_keywords (array), slug, has_images, image_alts (array)
     * @return array{score: int, checks: array<int, array<string, string>>}
     */
    public function analyze(array $data): array
    {
        $checks = [];
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $metaTitle = $data['meta_title'] ?? '';
        $metaDescription = $data['meta_description'] ?? '';
        $focusKeywords = $data['focus_keywords'] ?? [];
        $slug = $data['slug'] ?? '';
        $hasImages = $data['has_images'] ?? false;
        $imageAlts = $data['image_alts'] ?? [];

        $primaryKeyword = $focusKeywords[0] ?? '';
        $contentPlain = strip_tags($content);
        $wordCount = str_word_count($contentPlain);

        // --- Title checks ---
        $checks[] = $this->checkTitleLength($metaTitle ?: $title);
        $checks[] = $this->checkTitleKeyword($metaTitle ?: $title, $primaryKeyword);

        // --- Meta description checks ---
        $checks[] = $this->checkDescriptionLength($metaDescription);
        $checks[] = $this->checkDescriptionKeyword($metaDescription, $primaryKeyword);

        // --- Content checks ---
        $checks[] = $this->checkContentLength($wordCount);
        $checks[] = $this->checkKeywordInFirstParagraph($content, $primaryKeyword);
        $checks[] = $this->checkKeywordDensity($contentPlain, $primaryKeyword, $wordCount);
        $checks[] = $this->checkHeadingsPresent($content);
        $checks[] = $this->checkKeywordInHeading($content, $primaryKeyword);
        $checks[] = $this->checkInternalLinks($content);

        // --- URL check ---
        $checks[] = $this->checkSlugKeyword($slug, $primaryKeyword);

        // --- Media checks ---
        $checks[] = $this->checkImagesPresent($hasImages);
        $checks[] = $this->checkImageAlts($imageAlts, $primaryKeyword);

        // --- Readability checks ---
        $checks[] = $this->checkParagraphLength($content);
        $checks[] = $this->checkSentenceLength($contentPlain);

        // Calculate score (15 checks are appended above, so $total > 0 always)
        $passed = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));
        $total = count($checks);
        $score = (int) round(($passed / $total) * 100);

        return [
            'score'  => $score,
            'checks' => $checks,
        ];
    }

    // -----------------------------------------------------------------
    // Individual checks
    // -----------------------------------------------------------------

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkTitleLength(string $title): array
    {
        $len = mb_strlen($title);
        if ($len >= 50 && $len <= 60) {
            return ['id' => 'title_length', 'status' => 'pass', 'message' => "Title length is optimal ({$len} characters)."];
        }
        if ($len > 0 && ($len >= 30 && $len <= 70)) {
            return ['id' => 'title_length', 'status' => 'warning', 'message' => "Title length ({$len} chars) is acceptable but not optimal. Aim for 50–60."];
        }
        if ($len === 0) {
            return ['id' => 'title_length', 'status' => 'fail', 'message' => 'No title set.'];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        return ['id' => 'title_length', 'status' => 'fail', 'message' => "Title length ({$len} chars) is outside the recommended 50–60 range."];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkTitleKeyword(string $title, string $keyword): array
    {
        if (empty($keyword)) {
            return ['id' => 'title_keyword', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (mb_stripos($title, $keyword) !== false) {
            $pos = mb_stripos($title, $keyword);
            if ($pos < mb_strlen($title) / 2) {
                return ['id' => 'title_keyword', 'status' => 'pass', 'message' => 'Focus keyword appears near the beginning of the title.'];
            }
            return ['id' => 'title_keyword', 'status' => 'warning', 'message' => 'Focus keyword is in the title but not near the beginning.'];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        return ['id' => 'title_keyword', 'status' => 'fail', 'message' => 'Focus keyword not found in the title.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkDescriptionLength(string $description): array
    {
        $len = mb_strlen($description);
        if ($len >= 120 && $len <= 160) {
            return ['id' => 'desc_length', 'status' => 'pass', 'message' => "Meta description length is optimal ({$len} characters)."];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        if ($len > 0 && $len < 120) {
            return ['id' => 'desc_length', 'status' => 'warning', 'message' => "Meta description ({$len} chars) is short. Aim for 120–160."];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        if ($len > 160) {
            return ['id' => 'desc_length', 'status' => 'warning', 'message' => "Meta description ({$len} chars) may be truncated. Keep under 160."];
        }
        return ['id' => 'desc_length', 'status' => 'fail', 'message' => 'No meta description set.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkDescriptionKeyword(string $description, string $keyword): array
    {
    /**
     * @return array{id: string, status: string, message: string}
    */
        if (empty($keyword)) {
            return ['id' => 'desc_keyword', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (empty($description)) {
            return ['id' => 'desc_keyword', 'status' => 'fail', 'message' => 'No meta description to check.'];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        if (mb_stripos($description, $keyword) !== false) {
            return ['id' => 'desc_keyword', 'status' => 'pass', 'message' => 'Focus keyword found in meta description.'];
        }
        return ['id' => 'desc_keyword', 'status' => 'fail', 'message' => 'Focus keyword not found in meta description.'];
    }
    /**
     * @return array{id: string, status: string, message: string}
    */

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkContentLength(int $wordCount): array
    {
        if ($wordCount >= 1000) {
            return ['id' => 'content_length', 'status' => 'pass', 'message' => "Content length ({$wordCount} words) is comprehensive."];
        }
        if ($wordCount >= 300) {
            return ['id' => 'content_length', 'status' => 'warning', 'message' => "Content length ({$wordCount} words) is acceptable. Consider expanding to 1000+ for competitive topics."];
        }
        return ['id' => 'content_length', 'status' => 'fail', 'message' => "Content is thin ({$wordCount} words). Aim for at least 300 words."];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkKeywordInFirstParagraph(string $content, string $keyword): array
    {
        if (empty($keyword)) {
    /**
     * @return array{id: string, status: string, message: string}
    */
            return ['id' => 'keyword_first_para', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }

    /**
     * @return array{id: string, status: string, message: string}
    */
        // Get first paragraph text
        $firstPara = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $match)) {
            $firstPara = strip_tags($match[1]);
        } else {
            // No <p> tags — take first 200 chars
            $firstPara = mb_substr(strip_tags($content), 0, 200);
        }

        if (mb_stripos($firstPara, $keyword) !== false) {
            return ['id' => 'keyword_first_para', 'status' => 'pass', 'message' => 'Focus keyword appears in the first paragraph.'];
        }
        return ['id' => 'keyword_first_para', 'status' => 'fail', 'message' => 'Focus keyword not found in the first paragraph.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkKeywordDensity(string $plainText, string $keyword, int $wordCount): array
    {
        if (empty($keyword) || $wordCount === 0) {
            return ['id' => 'keyword_density', 'status' => 'warning', 'message' => 'Cannot calculate keyword density.'];
        }

    /**
     * @return array{id: string, status: string, message: string}
    */
        $keywordCount = mb_substr_count(mb_strtolower($plainText), mb_strtolower($keyword));
        $keywordWords = str_word_count($keyword);
        $density = ($keywordCount * $keywordWords / $wordCount) * 100;

        if ($density >= 0.5 && $density <= 2.5) {
            return ['id' => 'keyword_density', 'status' => 'pass', 'message' => sprintf('Keyword density is %.1f%% (good range: 0.5–2.5%%).', $density)];
        }
        if ($density > 2.5) {
            return ['id' => 'keyword_density', 'status' => 'warning', 'message' => sprintf('Keyword density is %.1f%% — may be over-optimized.', $density)];
        }
        if ($keywordCount === 0) {
            return ['id' => 'keyword_density', 'status' => 'fail', 'message' => 'Focus keyword not found in content.'];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */
        return ['id' => 'keyword_density', 'status' => 'warning', 'message' => sprintf('Keyword density is %.1f%% — consider using the keyword more.', $density)];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkHeadingsPresent(string $content): array
    {
        if (preg_match('/<h[2-6][^>]*>/i', $content)) {
            return ['id' => 'headings_present', 'status' => 'pass', 'message' => 'Content uses subheadings.'];
        }
        return ['id' => 'headings_present', 'status' => 'fail', 'message' => 'No subheadings (H2–H6) found. Use headings to structure content.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkKeywordInHeading(string $content, string $keyword): array
    {
        if (empty($keyword)) {
            return ['id' => 'keyword_heading', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }

        if (preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/is', $content, $matches)) {
            foreach ($matches[1] as $heading) {
                if (mb_stripos(strip_tags($heading), $keyword) !== false) {
                    return ['id' => 'keyword_heading', 'status' => 'pass', 'message' => 'Focus keyword found in a subheading.'];
                }
    /**
     * @return array{id: string, status: string, message: string}
    */
            }
        }
        return ['id' => 'keyword_heading', 'status' => 'warning', 'message' => 'Focus keyword not found in any subheading.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkInternalLinks(string $content): array
    {
        $linkCount = preg_match_all('/<a\s[^>]*href\s*=\s*["\'][^"\']*["\'][^>]*>/i', $content);
        if ($linkCount >= 2) {
    /**
     * @return array{id: string, status: string, message: string}
    */
            return ['id' => 'internal_links', 'status' => 'pass', 'message' => "Content contains {$linkCount} links."];
        }
        if ($linkCount === 1) {
            return ['id' => 'internal_links', 'status' => 'warning', 'message' => 'Only 1 link found. Consider adding more internal links.'];
        }
        return ['id' => 'internal_links', 'status' => 'fail', 'message' => 'No links found. Add internal links to other content.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkSlugKeyword(string $slug, string $keyword): array
    {
        if (empty($keyword)) {
            return ['id' => 'slug_keyword', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (empty($slug)) {
            return ['id' => 'slug_keyword', 'status' => 'warning', 'message' => 'No slug set yet.'];
        }

        $slugWords = str_replace('-', ' ', $slug);
        if (mb_stripos($slugWords, mb_strtolower($keyword)) !== false) {
    /**
     * @return array{id: string, status: string, message: string}
    */
            return ['id' => 'slug_keyword', 'status' => 'pass', 'message' => 'Focus keyword found in URL slug.'];
        }
        return ['id' => 'slug_keyword', 'status' => 'warning', 'message' => 'Focus keyword not found in URL slug.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkImagesPresent(bool $hasImages): array
    {
        if ($hasImages) {
            return ['id' => 'images_present', 'status' => 'pass', 'message' => 'Content includes images.'];
        }
        return ['id' => 'images_present', 'status' => 'warning', 'message' => 'No images found. Visual content improves engagement.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    /**
     * @param array<int, string> $alts
     * @return array{id: string, status: string, message: string}
     */
    protected function checkImageAlts(array $alts, string $keyword): array
    {
        if (empty($alts)) {
            return ['id' => 'image_alts', 'status' => 'warning', 'message' => 'No images to check alt text for.'];
        }
    /**
     * @return array{id: string, status: string, message: string}
    */

        $hasAll = true;
        $hasKeyword = false;
        foreach ($alts as $alt) {
            if (empty(trim($alt))) {
                $hasAll = false;
            }
            if (!empty($keyword) && mb_stripos($alt, $keyword) !== false) {
                $hasKeyword = true;
            }
        }

        if ($hasAll && $hasKeyword) {
            return ['id' => 'image_alts', 'status' => 'pass', 'message' => 'All images have alt text and at least one contains the focus keyword.'];
        }
        if ($hasAll) {
            return ['id' => 'image_alts', 'status' => 'warning', 'message' => 'All images have alt text but none contain the focus keyword.'];
        }
        return ['id' => 'image_alts', 'status' => 'fail', 'message' => 'Some images are missing alt text.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkParagraphLength(string $content): array
    {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        if (empty($matches[1])) {
            return ['id' => 'paragraph_length', 'status' => 'warning', 'message' => 'No paragraphs detected.'];
        }

        $longCount = 0;
        foreach ($matches[1] as $para) {
            $words = str_word_count(strip_tags($para));
            if ($words > 150) {
                $longCount++;
            }
        }

        if ($longCount === 0) {
            return ['id' => 'paragraph_length', 'status' => 'pass', 'message' => 'Paragraph lengths are good for readability.'];
        }
        return ['id' => 'paragraph_length', 'status' => 'warning', 'message' => "{$longCount} paragraph(s) exceed 150 words. Consider breaking them up."];
    }

    /**
     * @return array{id: string, status: string, message: string}
    */
    protected function checkSentenceLength(string $plainText): array
    {
        $sentences = preg_split('/[.!?]+/', $plainText, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($sentences)) {
            return ['id' => 'sentence_length', 'status' => 'warning', 'message' => 'No sentences detected.'];
        }

        $longCount = 0;
        foreach ($sentences as $sentence) {
            if (str_word_count(trim($sentence)) > 25) {
                $longCount++;
            }
        }

        $ratio = $longCount / count($sentences);
        if ($ratio <= 0.2) {
            return ['id' => 'sentence_length', 'status' => 'pass', 'message' => 'Sentence lengths are good for readability.'];
        }
        if ($ratio <= 0.4) {
            return ['id' => 'sentence_length', 'status' => 'warning', 'message' => 'Some sentences are long. Consider shortening for readability.'];
        }
        return ['id' => 'sentence_length', 'status' => 'fail', 'message' => 'Too many long sentences. Break them up for better readability.'];
    }
}
