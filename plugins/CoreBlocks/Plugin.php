<?php

declare(strict_types=1);

namespace Pubvana\Plugins\CoreBlocks;

use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        // Blocks are registered via pubvana.json.
        // Provider callables are not needed — RegionManager passes saved
        // options directly as template data when no provider is set.
    }
}
