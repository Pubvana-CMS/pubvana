<?php

declare(strict_types=1);

namespace flight;

use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\ThemeService;
use Pubvana\Services\RegionManager;
use Pubvana\Services\NavigationService;
use Pubvana\Services\SettingsService;
use Pubvana\Services\Mailer;
use Pubvana\Services\ContentService;
use Pubvana\Services\AssetService;
use Pubvana\Services\PluginLoader;
use Pubvana\Services\PluginView;

/**
 * PHPStan stub for Flight\Engine magic methods.
 * Tells PHPStan about service methods registered via $app->map().
 *
 * @method ExtensionRegistry adext()
 * @method ThemeService themes()
 * @method RegionManager regions()
 * @method NavigationService navigation()
 * @method SettingsService settings()
 * @method Mailer mailer()
 * @method ContentService content()
 * @method AssetService asset()
 * @method PluginLoader pluginLoader()
 * @method PluginView view()
 * @method mixed db()
 * @method mixed session()
 * @method mixed auth()
 * @method string slugify(string $text)
 */
class Engine {}
