<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class PhpExtensionsCheck implements CheckInterface
{
    /** @var array<string, string> extension => why it's needed */
    private array $required = [
        'mbstring'  => 'String encoding and manipulation',
        'json'      => 'JSON encoding/decoding',
        'pdo'       => 'Database connectivity',
        'openssl'   => 'Encryption, HTTPS verification, token generation',
        'curl'      => 'HTTP requests to external services',
        'fileinfo'  => 'MIME type detection for uploads',
        'dom'       => 'HTML parsing and sanitization',
        'tokenizer' => 'Template engine support',
    ];

    /** @var array<string, string> extension => why it's recommended */
    private array $recommended = [
        'intl'    => 'Internationalization and locale formatting',
        'imagick' => 'Advanced image processing (fallback: GD)',
        'gd'      => 'Image resizing and thumbnails',
        'zip'     => 'Backup archive creation and extraction',
        'opcache' => 'PHP opcode caching for performance',
    ];

    public function run(): CheckResult
    {
        $missing = [];
        foreach ($this->required as $ext => $reason) {
            if (!extension_loaded($ext)) {
                $missing[] = "{$ext} ({$reason})";
            }
        }

        if (!empty($missing)) {
            return new CheckResult(
                id: 'php-extensions',
                name: 'Required PHP Extensions',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::CRITICAL,
                message: 'Missing required extensions: ' . implode(', ', $missing),
                remediation: 'Install the missing extensions and restart PHP. Via command line: apt install php-<ext>, or enable them through your hosting control panel.',
            );
        }

        $missingRec = [];
        foreach ($this->recommended as $ext => $reason) {
            if (!extension_loaded($ext)) {
                $missingRec[] = "{$ext} ({$reason})";
            }
        }

        if (!empty($missingRec)) {
            return new CheckResult(
                id: 'php-extensions',
                name: 'Required PHP Extensions',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: 'All required extensions present. Missing recommended: ' . implode(', ', $missingRec),
                remediation: 'Consider installing these extensions for full functionality.',
            );
        }

        return new CheckResult(
            id: 'php-extensions',
            name: 'Required PHP Extensions',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: 'All required and recommended extensions are installed.',
        );
    }
}
