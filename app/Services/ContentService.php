<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * ContentService - Applies plugin-registered content transforms.
 *
 * Rich-text bodies (pages, posts) run through render() before reaching
 * their template, so plugins can expand shortcodes or other inline
 * markers (for example the Forms plugin's {{ forms : slug }} embeds).
 *
 * Each registered content.render callable receives ['content' => string]
 * and returns the transformed string. Contributions chain in priority
 * order (lowest first), matching adext's sort.
 *
 * @package Pubvana\Services
 */
class ContentService
{
    public function __construct(private Engine $app)
    {
    }

    /**
     * Run the content through every registered content.render transformer.
     *
     * @param string $content Raw content body
     * @return string Transformed content
     */
    public function render(string $content): string
    {
        foreach ($this->app->adext()->get('content.render', 'default') as $item) {
            if (!isset($item['callable']) || !is_callable($item['callable'])) {
                continue;
            }

            $result = call_user_func($item['callable'], ['content' => $content]);
            if (is_string($result)) {
                $content = $result;
            }
        }

        return $content;
    }
}
