<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\commands;

use flight\commands\AbstractBaseCommand;
use Flight;
use Pubvana\Plugins\Updates\Services\UpdateApplyService;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Plugins\Updates\Services\UpdateService;

/**
 * CLI: apply the pending update (manual path).
 *
 * Usage:
 *   php runway updates:apply
 *   php runway updates:apply --release 3.0.2 --user admin
 *
 * Manual applies may cross breaking changes; the operator is expected to
 * have read them (they are printed by updates:check).
 *
 * @package  Pubvana\Plugins\Updates\commands
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class UpdatesApplyCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('updates:apply', 'Apply the pending Pubvana update (backup, download, copy, migrate)', $config);

        $this
            ->option('--release', 'Specific release version to apply (default: the safe target)', null, '')
            ->option('--user', 'Username for backup attribution', null, 'cli')
            ->usage(
                '<bold>  runway updates:apply</end><eol/>' .
                '<bold>  runway updates:apply --release 3.0.2 --user admin</end><eol/>'
            );
    }

    public function execute(): int
    {
        $io      = $this->io();
        $config  = $this->pluginConfig();
        $service = new UpdateService(Flight::app(), $config);
        $apply   = new UpdateApplyService(Flight::app(), $config);

        $target = trim((string) ($this->release ?? ''));

        if ($target === '') {
            try {
                $state  = $service->check(true);
                $target = (string) ($state['target_version'] ?? '');
            } catch (\Throwable $e) {
                $io->error('Check failed: ' . $e->getMessage(), true);
                return 1;
            }
        }

        if ($target === '') {
            $io->info('No applicable update to apply.', true);
            return 0;
        }

        $io->info('Applying update to ' . $target . ' (a pre-update backup is taken first)...', true);

        $ok = $apply->apply(
            $target,
            (string) ($this->user ?? 'cli'),
            true,
            function (string $label, string $detail = '') use ($io): void {
                $io->write('  ' . $label . ($detail !== '' ? ' - ' . $detail : '') . PHP_EOL);
            }
        );

        if ($ok) {
            $io->ok('Update applied.', true);
            return 0;
        }

        $progress = (new UpdateProgress($service->storageDir()))->read();
        $io->error('Update failed: ' . (string) ($progress['error'] ?? 'unknown error'), true);
        $io->info('Restore the pre-update snapshot from Tools > Backups if the site is broken.', true);

        return 1;
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
