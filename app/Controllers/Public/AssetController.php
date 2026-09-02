<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Public;

use flight\Engine;

/**
 * AssetController - Serves assets from themes, plugins, and vendor packages.
 *
 * Handles requests to /assets/{type}/{name}/{path} and delegates to AssetService.
 *
 * @package Pubvana\Controllers\Public
 */
class AssetController
{
    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Serve an asset file.
     *
     * @param string $type Asset type (plugin, theme, vendor)
     * @param string $name Plugin/theme name or vendor/package
     * @param string $path Relative path within assets directory
     */
    public function serve(string $type, string $name, string $path): void
    {
        $assetService = $this->app->asset();

        // Resolve the file path
        $filePath = $assetService->resolve($type, $name, $path);

        if ($filePath === null) {
            $this->app->halt(404, 'Asset not found');
            return;
        }

        // Serve the file
        $assetService->serve($filePath);
    }
}
