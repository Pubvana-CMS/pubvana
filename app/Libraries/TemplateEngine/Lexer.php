<?php

namespace App\Libraries\TemplateEngine;

class Lexer
{
    /**
     * Tokenize a template string into segments.
     *
     * Each segment is an array with keys:
     *   'type'    => 'text' | 'output' | 'raw_output' | 'tag'
     *   'content' => string (inner content, delimiters stripped)
     *
     * Comments ({# ... #}) are discarded entirely.
     *
     * @return array<array{type: string, content: string}>
     */
    public function tokenize(string $template): array
    {
        // Match all template tags: {{ }}, {! !}, {% %}, {# #}
        $pattern = '/(\{\{.*?\}\}|\{!.*?!\}|\{%.*?%\}|\{#.*?#\})/s';
        $parts = preg_split($pattern, $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '{{') && str_ends_with($part, '}}')) {
                $segments[] = [
                    'type'    => 'output',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{!') && str_ends_with($part, '!}')) {
                $segments[] = [
                    'type'    => 'raw_output',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{%') && str_ends_with($part, '%}')) {
                $segments[] = [
                    'type'    => 'tag',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{#') && str_ends_with($part, '#}')) {
                // Comment — discard
                continue;
            } else {
                $segments[] = [
                    'type'    => 'text',
                    'content' => $part,
                ];
            }
        }

        return $segments;
    }
}
