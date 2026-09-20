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
        $adext = $app->adext();

        // Blocks follow the shared convention: key '{author}.{package}.{name}',
        // template a bare file name under Views/public/blocks/. Provider
        // callables are not needed — RegionManager passes saved options
        // directly as template data when no provider is set.
        $adext->register('block', 'available', 'pubvana.core-blocks.text', [
            'label'       => 'Text',
            'description' => 'Free-form text content',
            'template'    => 'text.tpl',
            'priority'    => 100,
            'options'     => [
                'title'   => ['type' => 'input', 'label' => 'Title', 'default' => ''],
                'content' => ['type' => 'textarea', 'label' => 'Content', 'default' => '', 'wysiwyg' => false],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.core-blocks.html', [
            'label'       => 'HTML',
            'description' => 'Free-form HTML content (unescaped)',
            'template'    => 'html.tpl',
            'priority'    => 110,
            'options'     => [
                'title'   => ['type' => 'input', 'label' => 'Title', 'default' => ''],
                'content' => ['type' => 'textarea', 'label' => 'Content', 'default' => ''],
            ],
        ]);
    }
}