<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\commands;

use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use flight\commands\AbstractBaseCommand;

/**
 * CLI command to scan all registered content sources for broken outbound links.
 */
class BrokenLinksCheckCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('broken-links:check', 'Scan all published content for broken outbound links.', $config);

        $this->option('--force', 'Force a full rescan even if recently checked', null, false);
    }

    /**
     * @param string[] $args
     */
    public function execute(...$args): int
    {
        $this->io()->info('Pubvana Broken Link Checker');
        $this->io()->write(str_repeat('-', 60));

        /** @var BrokenLinksService $service */
        $service = (new \flight\Engine())->brokenLinks();
        $result = $service->scan();

        $this->io()->write(str_repeat('-', 60));
        $this->io()->info(sprintf(
            'Checked %d link%s across %d source%s. %d broken.',
            $result['total'],
            $result['total'] !== 1 ? 's' : '',
            $result['sources'],
            $result['sources'] !== 1 ? 's' : '',
            $result['broken']
        ));

        return $result['broken'] > 0 ? 1 : 0;
    }
}
