<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * MarkdownService - Converts Markdown to sanitized HTML.
 *
 * Uses league/commonmark with a hardened environment: raw HTML in the
 * source is stripped and unsafe links are rejected. The output is run
 * through HTMLPurifier so the pipeline matches the blog/pages sanitization
 * guarantees.
 *
 * @package Pubvana\Plugins\AiAssistant\Services
 */
class MarkdownService
{
    private MarkdownConverter $converter;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 50,
        ];
        $options = array_replace($defaults, (array) ($config['commonmark'] ?? []));

        $environment = new Environment($options);
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Convert Markdown source to sanitized HTML.
     */
    public function toHtml(string $markdown): string
    {
        $html = $this->converter->convert($markdown)->getContent();
        return $this->purify($html);
    }

    /**
     * Convert stored HTML back to Markdown for reading.
     */
    public function toMarkdown(string $html): string
    {
        return trim((new HtmlConverter())->convert($html));
    }

    /**
     * Sanitize rendered HTML via HTMLPurifier.
     */
    private function purify(string $html): string
    {
        if (!class_exists(\HTMLPurifier_Config::class)) {
            return $html;
        }
        $config = \HTMLPurifier_Config::create(\Flight::get('html_purifier') ?? []);
        return (new \HTMLPurifier($config))->purify($html);
    }
}