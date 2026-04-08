<?php

namespace App\Services;

use App\Models\CategoryModel;
use App\Models\PageModel;
use App\Models\TagModel;

class NavigationService
{
    /**
     * Get all available routes grouped by source for the Navigation admin Quick Add dropdown.
     * Also used by nav widgets.
     *
     * @return array<string, array<array{label: string, url: string}>>
     */
    public function getAvailableRoutes(): array
    {
        $routes = [];

        // Core pages
        $routes['Core'] = [
            ['label' => 'Home',    'url' => '/'],
            ['label' => 'Blog',    'url' => '/blog'],
            ['label' => 'Search',  'url' => '/search'],
            ['label' => 'Contact', 'url' => '/contact'],
        ];

        // Published pages
        $pages = (new PageModel())->published()->findAll();
        if (! empty($pages)) {
            $routes['Pages'] = [];
            foreach ($pages as $page) {
                $routes['Pages'][] = ['label' => $page->title, 'url' => '/pages/' . $page->slug];
            }
        }

        // Categories
        $categories = (new CategoryModel())->findAll();
        if (! empty($categories)) {
            $routes['Categories'] = [];
            foreach ($categories as $cat) {
                $routes['Categories'][] = ['label' => $cat->name, 'url' => '/category/' . $cat->slug];
            }
        }

        // Tags
        $tags = (new TagModel())->findAll();
        if (! empty($tags)) {
            $routes['Tags'] = [];
            foreach ($tags as $tag) {
                $routes['Tags'][] = ['label' => $tag->name, 'url' => '/tag/' . $tag->slug];
            }
        }

        // Plugin public routes
        try {
            $pluginRoutes = PluginManager::instance()->getPublicRoutes();
            if (! empty($pluginRoutes)) {
                $routes['Plugins'] = $pluginRoutes;
            }
        } catch (\Throwable $e) {
            // Plugins not loaded or unavailable
        }

        return $routes;
    }

    public function getTree(string $group = 'primary'): array
    {
        $flat = model(\App\Models\NavigationModel::class)->getByGroup($group);

        return $this->buildTree($flat);
    }

    protected function buildTree(array $flat, int $parentId = 0): array
    {
        $tree = [];
        foreach ($flat as $item) {
            $pid = (int) ($item->parent_id ?? 0);
            if ($pid === $parentId) {
                $item->children = $this->buildTree($flat, (int) $item->id);
                $tree[]         = $item;
            }
        }
        return $tree;
    }
}
