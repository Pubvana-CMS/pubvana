<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\commands;

use Ahc\Cli\IO\Interactor;
use flight\commands\AbstractBaseCommand;
use Flight;
use Pubvana\Plugins\Updates\Services\UpdateService;

/**
 * CLI: check the release feed and report the update state.
 *
 * Usage:
 *   php runway updates:check
 *   php runway updates:check --force
 *
 * @package  Pubvana\Plugins\Updates\commands
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class UpdatesCheckCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('updates:check', 'Check the Pubvana release feed for updates', $config);

        $this
            ->option('--force', 'Bypass the 24-hour check cache', null, false)
            ->usage(
                '<bold>  runway updates:check</end><eol/>' .
                '<bold>  runway updates:check --force</end><eol/>'
            );
    }

    public function execute(): int
    {
        $io      = $this->io();
        $service = new UpdateService(Flight::app(), $this->pluginConfig());

        try {
            $state = $service->check((bool) ($this->force ?? false));
        } catch (\Throwable $e) {
            $io->error('Check failed: ' . $e->getMessage(), true);
            return 1;
        }

        $io->info('Installed version: ' . ($state['current_version'] ?? 'unknown'), true);

        return match ((string) ($state['status'] ?? 'error')) {
            'up_to_date' => $this->reportUpToDate($io, $state),
            'available'  => $this->reportAvailable($io, $state),
            default      => $this->reportError($io, $state),
        };
    }

    /**
     * @param array<string, mixed> $state
     */
    private function reportUpToDate(Interactor $io, array $state): int
    {
        $line = 'Up to date.';
        if (!empty($state['capped_by'])) {
            $line .= ' A newer release exists but is held back by ' . $state['capped_by'] . '.';
        }

        $io->ok($line, true);

        return 0;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function reportAvailable(Interactor $io, array $state): int
    {
        $io->info('Version ' . ($state['target_version'] ?? '?') . ' is available.', true);

        foreach ((array) ($state['breaking_changes'] ?? []) as $line) {
            $io->error('Breaking: ' . $line, true);
        }
        foreach ((array) ($state['notices'] ?? []) as $line) {
            $io->info('Notice: ' . $line, true);
        }
        foreach ((array) ($state['migration_notes'] ?? []) as $line) {
            $io->info('Note: ' . $line, true);
        }

        $io->info('Apply with: php runway updates:apply', true);

        return 0;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function reportError(Interactor $io, array $state): int
    {
        $io->error('Check failed: ' . ($state['error'] ?? 'unknown error'), true);

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
