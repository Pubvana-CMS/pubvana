<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\commands;

use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use flight\commands\AbstractBaseCommand;

/**
 * Manual cron-style trigger for automated broken link scanning.
 *
 * Mirrors the 24h core cron task (registered in Plugin.php); both delegate
 * to the same scan logic. Kept as a standalone runway command so operators
 * can run the daily scan on demand without touching the scheduler.
 */
class BrokenLinksCronCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('broken-links:cron', 'Scan for broken links.', $config);
    }

    /**
     * @param string[] $args
     */
    public function execute(...$args): int
    {
        /** @var BrokenLinksService $service */
        $service = (new \flight\Engine())->brokenLinks();
        $result = $service->scan();

        $this->io()->info(sprintf(
            'Broken links cron scan: %d broken of %d total links across %d sources.',
            $result['broken'],
            $result['total'],
            $result['sources']
        ));

        return $result['broken'] > 0 ? 1 : 0;
    }
}
