<?php

namespace App\Libraries\TemplateEngine;

class FilterRegistry
{
    /**
     * Apply a named filter to a value.
     *
     * Unknown filters return the input unchanged (silent fail per spec).
     */
    public function apply(string $name, mixed $value, array $args = []): mixed
    {
        return match ($name) {
            'date'          => $this->filterDate($value, $args),
            'number_format' => $this->filterNumberFormat($value, $args),
            'nl2br'         => nl2br((string) $value),
            'md5'           => md5((string) $value),
            'count'         => is_countable($value) ? count($value) : 0,
            'excerpt'       => $this->filterExcerpt($value, $args),
            'default'       => $this->filterDefault($value, $args),
            'raw'           => $value,  // Marker — Interpreter checks for this
            'strtolower'    => strtolower((string) $value),
            'strip_tags'    => strip_tags((string) $value),
            default         => $value,  // Unknown filter — pass through
        };
    }

    /**
     * Check if a filter name is the 'raw' marker.
     * The Interpreter uses this to skip escaping.
     */
    public function isRawFilter(string $name): bool
    {
        return $name === 'raw';
    }

    private function filterDate(mixed $value, array $args): string
    {
        $format = $args[0] ?? 'Y-m-d';
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($timestamp === false) {
            return (string) $value;
        }
        return date($format, $timestamp);
    }

    private function filterNumberFormat(mixed $value, array $args): string
    {
        $decimals = (int) ($args[0] ?? 0);
        return number_format((float) $value, $decimals);
    }

    private function filterExcerpt(mixed $value, array $args): string
    {
        $length = (int) ($args[0] ?? 150);
        $plain = strip_tags((string) $value);
        if (strlen($plain) <= $length) {
            return $plain;
        }
        return rtrim(substr($plain, 0, $length), ' .,;:') . '…';
    }

    private function filterDefault(mixed $value, array $args): mixed
    {
        if ($value === null || $value === '' || $value === false) {
            return $args[0] ?? '';
        }
        return $value;
    }
}
