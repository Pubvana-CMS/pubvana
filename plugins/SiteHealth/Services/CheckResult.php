<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

class CheckResult
{
    public const PASS     = 'pass';
    public const WARNING  = 'warning';
    public const CRITICAL = 'critical';

    public const CAT_ENVIRONMENT   = 'environment';
    public const CAT_SECURITY      = 'security';
    public const CAT_CONFIGURATION = 'configuration';
    public const CAT_PLUGINS       = 'plugins';

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $status,
        public readonly string $message,
        public readonly string $remediation = '',
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'category'    => $this->category,
            'status'      => $this->status,
            'message'     => $this->message,
            'remediation' => $this->remediation,
        ];
    }
}
