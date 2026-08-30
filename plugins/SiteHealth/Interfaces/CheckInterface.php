<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Interfaces;

use Pubvana\Plugins\SiteHealth\Services\CheckResult;

interface CheckInterface
{
    public function run(): CheckResult;
}
