<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\commands;

use flight\commands\AbstractBaseCommand;
use Flight;
use Pubvana\Plugins\Updates\Services\UpdateService;

/**
 * CLI: the automatic update chain (cron target).
 *
 * Usage:
 *   php runway updates:auto-update
 *   php runway updates:auto-update --user cron
 *
 * Runs UpdateService::runAutoUpdateChain(), the same implementation the
 * core cron task (24h slot) invokes: force-checks the release feed, then
 * applies the safe target only when ALL of these hold:
 *   - the Updates.autoUpdate setting is on
 *   - a target release exists (not skipped, not capped)
 *   - no breaking changes are in the update path
 *   - the pre-update backup succeeds
 *
 * Otherwise it reports why nothing ran and exits cleanly, so the same
 * invocation is safe before and after enabling automatic updates.
 *
 * @package  Pubvana\Plugins\Updates\commands
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class UpdatesAutoUpdateCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('updates:auto-update', 'Run the automatic update chain (check, then apply when allowed)', $config);

        $this
            ->option('--user', 'Username for backup attribution', null, 'cron')
            ->usage(
                '<bold>  runway updates:auto-update</end><eol/>' .
                '<bold>  runway updates:auto-update --user cron</end><eol/>'
            );
    }

    public function execute(): int
    {
        $io      = $this->io();
        $service = new UpdateService(Flight::app(), $this->pluginConfig());

        $result = $service->runAutoUpdateChain(
            function (string $label, string $detail = '') use ($io): void {
                $io->write('  ' . $label . ($detail !== '' ? ' - ' . $detail : '') . PHP_EOL);
            },
            (string) ($this->user ?? 'cron')
        );

        $status  = $result['status'];
        $message = $result['message'];

        match ($status) {
            'ok'      => $io->ok($message, true),
            'noop',
            'refused' => $io->info($message, true),
            default   => $io->error($message !== '' ? $message : 'Automatic update failed.', true),
        };

        return $status === 'error' ? 1 : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginConfig(): array
    {
        $config = Flight::get('pubvana.updates');

        return is_array($config) ? $config : [];
    }
}
