<?php

namespace App\Commands;

use App\Models\PluginModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Cron extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'cron';
    protected $description = 'Run scheduled cron tasks by frequency.';
    protected $usage       = 'cron <minute|quarterday|daily>';
    protected $arguments   = [
        'frequency' => 'The task group to run: minute, quarterday, or daily',
    ];

    public function run(array $params): void
    {
        $frequency = $params[0] ?? null;

        switch ($frequency) {
            case 'minute':
                $this->minute();
                break;
            case 'quarterday':
                $this->quarterday();
                break;
            case 'daily':
                $this->daily();
                break;
            default:
                CLI::error('Usage: php spark cron <minute|quarterday|daily>');
                break;
        }
    }

    protected function minute(): void
    {
        $this->call('posts:publish');
        $this->runPluginCron('minute');
    }

    protected function quarterday(): void
    {
        $this->runPluginCron('quarterday');
    }

    protected function daily(): void
    {
        $this->call('pubvana:auto-update');
        $this->call('links:check');
        $this->call('marketplace:revalidate');
        $this->runPluginCron('daily');
    }

    /**
     * Scan active plugins for cron commands registered
     * under the given frequency in plugin_info.json.
     */
    protected function runPluginCron(string $frequency): void
    {
        try {
            $activePlugins = model(PluginModel::class)
                ->where('is_active', 1)
                ->where('disabled IS NULL')
                ->findAll();
        } catch (\Throwable $e) {
            return;
        }

        foreach ($activePlugins as $row) {
            $infoFile = PLUGINS_PATH . $row->folder . '/plugin_info.json';

            if (! is_file($infoFile)) {
                continue;
            }

            $info = json_decode(file_get_contents($infoFile), true);

            if (! is_array($info) || empty($info['cron'][$frequency])) {
                continue;
            }

            foreach ($info['cron'][$frequency] as $command) {
                if (! is_string($command) || $command === '') {
                    continue;
                }

                CLI::write("  [plugin:{$row->folder}] {$command}", 'cyan');
                $this->call($command);
            }
        }
    }
}
