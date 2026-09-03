<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\commands;

use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use flight\commands\AbstractBaseCommand;

/**
 * Cron stub for automated broken link scanning.
 *
 * TODO: Wire this into the cron infrastructure when it is built.
 * The cron system should call this command on a configurable schedule
 * (recommended: daily or weekly). The execute() method delegates to
 * the same scan logic used by the CLI command, so wiring requires
 * only a single call in the cron runner.
 */
class BrokenLinksCronCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('broken-links:cron', 'Scan for broken links (cron stub).', $config);
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
