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
     *                                    focus_keywords (array), slug, has_images, image_alts (array),
     *                                    author, updated_at
     * @return array{score: int, checks: array<int, array<string, string>>, ai_score: int, ai_checks: array<int, array<string, string>>}
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
        $author = (string) ($data['author'] ?? '');
        $updatedAt = (string) ($data['updated_at'] ?? '');

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

        // AI readiness checks: extraction and citation signals that answer
        // engines reward, kept separate from the classic SEO score.
        $aiChecks = [
            $this->checkAnswerFirstParagraph($content),
            $this->checkQuestionHeadings($content),
            $this->checkFaqBlock($content),
            $this->checkCitedSources($content),
            $this->checkStatistics($contentPlain),
            $this->checkStructuredBlocks($content),
            $this->checkFreshness($updatedAt),
            $this->checkAuthor($author),
        ];
        $aiPassed = count(array_filter($aiChecks, fn($c) => $c['status'] === 'pass'));
        $aiScore = (int) round(($aiPassed / count($aiChecks)) * 100);

        return [
            'score'     => $score,
            'checks'    => $checks,
            'ai_score'  => $aiScore,
            'ai_checks' => $aiChecks,
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
        if ($len > 0 && $len < 120) {
            return ['id' => 'desc_length', 'status' => 'warning', 'message' => "Meta description ({$len} chars) is short. Aim for 120–160."];
        }
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
        if (empty($keyword)) {
            return ['id' => 'desc_keyword', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }
        if (empty($description)) {
            return ['id' => 'desc_keyword', 'status' => 'fail', 'message' => 'No meta description to check.'];
        }
        if (mb_stripos($description, $keyword) !== false) {
            return ['id' => 'desc_keyword', 'status' => 'pass', 'message' => 'Focus keyword found in meta description.'];
        }
        return ['id' => 'desc_keyword', 'status' => 'fail', 'message' => 'Focus keyword not found in meta description.'];
    }

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
            return ['id' => 'keyword_first_para', 'status' => 'warning', 'message' => 'No focus keyword set.'];
        }

        // Get first paragraph text
        $firstPara = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $match)) {
            $firstPara = strip_tags($match[1]);
        } else {
            // No <p> tags, take first 200 chars
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

        $keywordCount = mb_substr_count(mb_strtolower($plainText), mb_strtolower($keyword));
        $keywordWords = str_word_count($keyword);
        $density = ($keywordCount * $keywordWords / $wordCount) * 100;

        if ($density >= 0.5 && $density <= 2.5) {
            return ['id' => 'keyword_density', 'status' => 'pass', 'message' => sprintf('Keyword density is %.1f%% (good range: 0.5–2.5%%).', $density)];
        }
        if ($density > 2.5) {
            return ['id' => 'keyword_density', 'status' => 'warning', 'message' => sprintf('Keyword density is %.1f%%. May be over-optimized.', $density)];
        }
        if ($keywordCount === 0) {
            return ['id' => 'keyword_density', 'status' => 'fail', 'message' => 'Focus keyword not found in content.'];
        }
        return ['id' => 'keyword_density', 'status' => 'warning', 'message' => sprintf('Keyword density is %.1f%%. Consider using the keyword more.', $density)];
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
     * @param array<int, string> $alts
     * @return array{id: string, status: string, message: string}
     */
    protected function checkImageAlts(array $alts, string $keyword): array
    {
        if (empty($alts)) {
            return ['id' => 'image_alts', 'status' => 'warning', 'message' => 'No images to check alt text for.'];
        }

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

    // -----------------------------------------------------------------
    // AI readiness checks
    // -----------------------------------------------------------------

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkAnswerFirstParagraph(string $content): array
    {
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $match)) {
            $text = strip_tags($match[1]);
        } else {
            $text = strip_tags($content);
        }

        $words = str_word_count(trim($text));
        if ($words === 0) {
            return ['id' => 'aeo_answer_first', 'status' => 'fail', 'message' => 'No opening paragraph to answer the question.'];
        }
        if ($words >= 40 && $words <= 80) {
            return ['id' => 'aeo_answer_first', 'status' => 'pass', 'message' => "The opening paragraph answers in {$words} words."];
        }

        return ['id' => 'aeo_answer_first', 'status' => 'warning', 'message' => "The opening paragraph is {$words} words. Aim for 40–80 for a self-contained answer."];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkQuestionHeadings(string $content): array
    {
        if (!preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/is', $content, $matches)) {
            return ['id' => 'aeo_question_headings', 'status' => 'fail', 'message' => 'No H2 or H3 headings to match questions.'];
        }

        foreach ($matches[1] as $heading) {
            $text = trim(strip_tags($heading));
            if ($text === '') {
                continue;
            }

            if (str_ends_with($text, '?') || preg_match('/^(who|what|when|where|why|how|can|do|does|is|are)\b/i', $text)) {
                return ['id' => 'aeo_question_headings', 'status' => 'pass', 'message' => 'Content uses question-style headings.'];
            }
        }

        return ['id' => 'aeo_question_headings', 'status' => 'warning', 'message' => 'No question-style headings. Phrase some H2s as the questions people ask.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkFaqBlock(string $content): array
    {
        if (preg_match('/<details\b[^>]*>.*?<summary\b[^>]*>.*?<\/summary>/is', $content)) {
            return ['id' => 'aeo_faq_block', 'status' => 'pass', 'message' => 'Content has an FAQ block built from details and summary.'];
        }

        if (preg_match('/<h[2-3][^>]*>\s*(FAQ|Frequently Asked Questions)\s*<\/h[2-3]>/i', $content)) {
            return ['id' => 'aeo_faq_block', 'status' => 'pass', 'message' => 'Content has an FAQ section.'];
        }

        return ['id' => 'aeo_faq_block', 'status' => 'warning', 'message' => 'No FAQ block. Add details and summary Q&A for answer engines.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkCitedSources(string $content): array
    {
        $external = preg_match_all('/<a\s[^>]*href\s*=\s*["\']https?:\/\/[^"\']+["\'][^>]*>/i', $content);
        $cites = preg_match_all('/<cite\b[^>]*>/i', $content);
        $count = (int) $external + (int) $cites;

        if ($count >= 2) {
            return ['id' => 'aeo_citations', 'status' => 'pass', 'message' => "Content cites {$count} external sources."];
        }
        if ($count === 1) {
            return ['id' => 'aeo_citations', 'status' => 'warning', 'message' => 'Only 1 external source. Add more named, linked sources.'];
        }

        return ['id' => 'aeo_citations', 'status' => 'fail', 'message' => 'No external sources. Link the evidence behind your claims.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkStatistics(string $plainText): array
    {
        $count = preg_match_all('/\d+(?:\.\d+)?\s?%|\baccording to\b/i', $plainText);

        if ($count >= 2) {
            return ['id' => 'aeo_statistics', 'status' => 'pass', 'message' => "Content includes {$count} statistics or sourcing phrases."];
        }
        if ($count === 1) {
            return ['id' => 'aeo_statistics', 'status' => 'warning', 'message' => 'Only 1 statistic. Add numbers with dates and sources.'];
        }

        return ['id' => 'aeo_statistics', 'status' => 'fail', 'message' => 'No statistics. Add specific numbers with sources.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkStructuredBlocks(string $content): array
    {
        if (preg_match('/<(ul|ol|table)\b/i', $content)) {
            return ['id' => 'aeo_structured_blocks', 'status' => 'pass', 'message' => 'Content uses a list or table.'];
        }

        return ['id' => 'aeo_structured_blocks', 'status' => 'warning', 'message' => 'No list or table. Use one for steps or comparisons.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkFreshness(string $updatedAt): array
    {
        $updated = trim($updatedAt);
        if ($updated === '') {
            return ['id' => 'aeo_freshness', 'status' => 'fail', 'message' => 'No updated date.'];
        }

        $timestamp = strtotime($updated);
        if ($timestamp === false) {
            return ['id' => 'aeo_freshness', 'status' => 'fail', 'message' => 'The updated date could not be read.'];
        }

        $days = (time() - $timestamp) / 86400;
        if ($days <= 183) {
            return ['id' => 'aeo_freshness', 'status' => 'pass', 'message' => 'Updated within the last six months.'];
        }
        if ($days <= 365) {
            return ['id' => 'aeo_freshness', 'status' => 'warning', 'message' => 'Updated within the last year. Refresh within six months for competitive topics.'];
        }

        return ['id' => 'aeo_freshness', 'status' => 'fail', 'message' => 'Last updated more than a year ago. Refresh the content.'];
    }

    /**
     * @return array{id: string, status: string, message: string}
     */
    protected function checkAuthor(string $author): array
    {
        if (trim($author) !== '') {
            return ['id' => 'aeo_author', 'status' => 'pass', 'message' => 'Author byline is set.'];
        }

        return ['id' => 'aeo_author', 'status' => 'warning', 'message' => 'No author byline. Name the author for E-E-A-T.'];
    }
}
