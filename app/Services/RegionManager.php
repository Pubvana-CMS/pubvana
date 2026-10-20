<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Pubvana\Models\BlockPlacement;
use flight\Engine;

/**
 * RegionManager - Manages content regions and block placements.
 *
 * Regions are named areas in a page layout (header, sidebar, footer, etc.).
 * Blocks are content blocks (recent posts, tag cloud, etc.) that get
 * placed into regions. This service handles:
 *
 *   - Defining available regions (platform + theme)
 *   - Managing block placements (add, remove, reorder, move)
 *   - Rendering regions by executing all placed blocks in order via Vision
 *
 * Regions come from two sources:
 *   1. Platform regions (always available): footer, before-content,
 *      after-content. header and navbar are deliberately not platform
 *      regions: a block placed in either printed outside the layout's
 *      content flow and broke the page. A theme may still render its own
 *      tag for one, but it has to declare that region in its manifest.
 *   2. Theme regions: declared in themes/{name}/pubvana.json
 *
 * Blocks are registered via adext with type 'block' or declared by the
 * active theme in pubvana.json. Both sources provide:
 *   - label: display name
 *   - provider: callable that returns template data (plugins only)
 *   - template: bare Vision .tpl file name (the author and package come
 *     from the registration key; the file lives under public/blocks/)
 *   - options: configurable settings with defaults
 *
 * @package Pubvana\Services
 */
class RegionManager
{
    /** @var array<string, array{id: string, label: string, description: string}> Platform regions always available regardless of theme */
    protected const PLATFORM_REGIONS = [
        'footer'         => ['id' => 'footer',         'label' => 'Footer',         'description' => 'Site footer area'],
        'before-content' => ['id' => 'before-content', 'label' => 'Before Content', 'description' => 'Above main content'],
        'after-content'  => ['id' => 'after-content',  'label' => 'After Content',  'description' => 'Below main content'],
    ];

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /** @var BlockPlacement|null Cached placement model (lazy-loaded) */
    private ?BlockPlacement $placementModel = null;

    /** @var bool Whether the per-request placements map has been filled */
    private bool $placementsCacheLoaded = false;

    /** @var array<string, BlockPlacement[]> Placements keyed by region_id, lazy-loaded once per request */
    private array $placementsByRegion = [];

    /** @var array<string, mixed> Current page context handed to every block provider */
    private array $context = [];

    /** @var array<string, array{id: string, label: string, description: string, source: string}>|null Regions for this request */
    private ?array $regionsCache = null;

    /** @var array<string, array<string, mixed>>|null Available blocks for this request */
    private ?array $blocksCache = null;

    /** @var array<string, mixed> Active theme manifest, read once per request */
    private array $themeManifest = [];

    /** @var bool Whether the active theme manifest has been read */
    private bool $themeManifestLoaded = false;

    /**
     * @param Engine<object> $app Flight application for accessing db(), adext(), view()
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Set the page context available to block providers for this request.
     *
     * Content controllers set this before rendering so that context-aware
     * blocks (e.g. the blog's Related Posts, which needs the current
     * post_id) can build their data.
     *
     * @param array<string, mixed> $context Per-request context values
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    // -----------------------------------------------------------------
    // Region Discovery
    // -----------------------------------------------------------------

    /**
     * Get all available regions: platform + active theme's declared regions.
     *
     * Theme regions are loaded from the active theme's pubvana.json.
     * Platform regions are always included.
     *
     * @return array<string, array{id: string, label: string, description: string, source: string}> Region definitions keyed by ID
     */
    public function getRegions(): array
    {
        if ($this->regionsCache !== null) {
            return $this->regionsCache;
        }

        $regions = [];

        // Platform regions
        foreach (self::PLATFORM_REGIONS as $id => $info) {
            $regions[$id] = [
                'id'          => $id,
                'label'       => $info['label'],
                'description' => $info['description'],
                'source'      => 'platform',
            ];
        }

        // Theme-declared regions from active theme's pubvana.json
        $themeRegions = $this->getThemeDeclaredRegions();
        foreach ($themeRegions as $region) {
            $id = $region['id'];
            if (isset($regions[$id])) {
                continue;
            }
            $regions[$id] = [
                'id'          => $id,
                'label'       => $region['label'],
                'description' => $region['description'],
                'source'      => 'theme',
            ];
        }

        $this->regionsCache = $regions;

        return $regions;
    }

    /**
     * Read theme-declared regions from the active theme's pubvana.json.
     *
     * Malformed region entries are skipped so callers get the declared
     * shape unconditionally.
     *
     * @return array<array{id: string, label: string, description: string}> Theme regions
     */
    protected function getThemeDeclaredRegions(): array
    {
        $manifest = $this->getThemeManifest();
        $regions = $manifest['provides']['regions'] ?? [];
        if (!is_array($regions)) {
            return [];
        }

        $declared = [];
        foreach ($regions as $region) {
            if (!is_array($region)
                || !isset($region['id'])
                || !is_string($region['id'])
                || $region['id'] === '') {
                continue;
            }
            $declared[] = [
                'id'          => $region['id'],
                'label'       => is_string($region['label'] ?? null) ? $region['label'] : $region['id'],
                'description' => is_string($region['description'] ?? null) ? $region['description'] : '',
            ];
        }

        return $declared;
    }

    /**
     * Read the active theme's manifest once per request.
     *
     * Called lazily at render time. The active theme lives in the database,
     * so boot-time reads would fail on a fresh install before the themes
     * table exists.
     *
     * @return array<string, mixed> Manifest data, or [] when unavailable
     */
    protected function getThemeManifest(): array
    {
        if ($this->themeManifestLoaded) {
            return $this->themeManifest;
        }
        $this->themeManifestLoaded = true;

        $active = $this->app->themes()->getActive();
        if (!$active) {
            return [];
        }

        $manifestPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $active->folder . DIRECTORY_SEPARATOR . 'pubvana.json';

        if (!is_file($manifestPath)) {
            return [];
        }

        $raw = file_get_contents($manifestPath);
        if ($raw === false) {
            return [];
        }

        $manifest = json_decode($raw, true);
        if (!is_array($manifest)) {
            return [];
        }

        /** @var array<string, mixed> $manifest */
        $this->themeManifest = $manifest;

        return $manifest;
    }

    /**
     * Read theme-declared blocks from the active theme's pubvana.json.
     *
     * Each entry in provides.blocks becomes a block definition keyed by
     * '{author}.{slug}.{id}', the same key form plugins use. Theme blocks
     * carry no provider: RegionManager passes saved values layered over the
     * option defaults to the template.
     *
     * @return array<string, array<string, mixed>> Block definitions keyed by registration key
     */
    protected function getThemeDeclaredBlocks(): array
    {
        $manifest = $this->getThemeManifest();
        $blocks = $manifest['provides']['blocks'] ?? [];
        if (!is_array($blocks)) {
            return [];
        }

        $author = is_string($manifest['author'] ?? null) && $manifest['author'] !== ''
            ? $manifest['author']
            : 'pubvana';
        $slug = is_string($manifest['slug'] ?? null) ? $manifest['slug'] : '';
        if ($slug === '') {
            return [];
        }

        $declared = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $id = $block['id'] ?? null;
            $label = $block['label'] ?? null;
            $template = $block['template'] ?? null;

            if (!is_string($id) || $id === ''
                || !is_string($label) || $label === ''
                || !is_string($template)) {
                continue;
            }

            $name = $this->templateName($template);
            if ($name === '') {
                continue;
            }

            $key = $author . '.' . $slug . '.' . $id;
            if (isset($declared[$key])) {
                continue;
            }

            /** @var array<string, array<string, mixed>> $options */
            $options = is_array($block['options'] ?? null) ? $block['options'] : [];

            $declared[$key] = [
                'label'       => $label,
                'description' => is_string($block['description'] ?? null) ? $block['description'] : '',
                'template'    => $name . '.tpl',
                'priority'    => is_int($block['priority'] ?? null) ? $block['priority'] : 50,
                'options'     => $options,
            ];
        }

        return $declared;
    }

    // -----------------------------------------------------------------
    // Block Discovery
    // -----------------------------------------------------------------

    /**
     * Get all blocks registered via adext.
     *
     * @return array<string, array<string, mixed>> Block definitions keyed by block_key
     */
    public function getAvailableBlocks(): array
    {
        if ($this->blocksCache !== null) {
            return $this->blocksCache;
        }

        $blocks = $this->app->adext()->get('block', 'available');

        // Theme blocks enter the same pipeline as plugin blocks. A plugin
        // key wins when both declare the same key.
        foreach ($this->getThemeDeclaredBlocks() as $key => $block) {
            if (!isset($blocks[$key])) {
                $blocks[$key] = $block;
            }
        }

        uasort($blocks, fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        $this->blocksCache = $blocks;

        return $blocks;
    }

    // -----------------------------------------------------------------
    // Placement CRUD
    // -----------------------------------------------------------------

    /**
     * Get all placements for a specific region, ordered by sort_order.
     *
     * Lazy-memoizes the per-region map on first call so a page that renders
     * five regions triggers one getAll() instead of five getForRegion() calls.
     *
     * @param string $regionId Region identifier
     * @return BlockPlacement[] Placements ordered by sort_order
     */
    public function getPlacements(string $regionId): array
    {
        $this->loadPlacementsCache();
        return $this->placementsByRegion[$regionId] ?? [];
    }

    /**
     * Fill the placements cache once per request (one query for all regions).
     */
    protected function loadPlacementsCache(): void
    {
        if ($this->placementsCacheLoaded) {
            return;
        }
        $this->placementsCacheLoaded = true;
        $this->placementsByRegion = $this->getAllPlacements();
    }

    /**
     * Drop the memoized placement map.
     *
     * Every write below calls this, so a read later in the same request sees
     * the change instead of the list loaded before it.
     */
    protected function invalidatePlacementsCache(): void
    {
        $this->placementsCacheLoaded = false;
        $this->placementsByRegion = [];
    }

    /**
     * Get all placements grouped by region.
     *
     * @return array<string, BlockPlacement[]> Placements keyed by region_id
     */
    public function getAllPlacements(): array
    {
        $all = $this->placements()->getAll();
        $grouped = [];

        foreach ($all as $placement) {
            $grouped[$placement->region_id][] = $placement;
        }

        return $grouped;
    }

    /**
     * Place a block in a region.
     *
     * Creates a new placement at the next available sort_order. The same
     * block can be placed in a region more than once; each placement is
     * its own instance with its own sort_order and options.
     *
     * @param string $regionId Region identifier
     * @param string $blockKey Block key from adext registration
     * @return BlockPlacement The created placement
     */
    public function savePlacement(string $regionId, string $blockKey): BlockPlacement
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->region_id = $regionId;
        $placement->block_key = $blockKey;
        $placement->sort_order = $this->placements()->nextSortOrder($regionId);
        $placement->created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $placement->insert();
        $this->invalidatePlacementsCache();

        return $placement;
    }

    /**
     * Remove a placement by ID.
     *
     * @param int $placementId Placement primary key
     */
    public function removePlacement(int $placementId): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if ($placement->isHydrated()) {
            $placement->delete();
            $this->invalidatePlacementsCache();
        }
    }

    /**
     * Reorder placements within a region.
     *
     * @param string $regionId Region identifier
     * @param int[]  $placementIds Placement IDs in desired order
     */
    public function reorderPlacements(string $regionId, array $placementIds): void
    {
        foreach ($placementIds as $index => $placementId) {
            $placement = new BlockPlacement($this->app->db());
            $placement->eq('id', (int) $placementId)
                      ->eq('region_id', $regionId)
                      ->find();

            if ($placement->isHydrated()) {
                $placement->sort_order = $index;
                $placement->save();
            }
        }

        $this->invalidatePlacementsCache();
    }

    /**
     * Move a placement from one region to another.
     *
     * A move is a reparent: the placement keeps its options, and a block
     * type already present in the target region stays. Repeats are allowed.
     *
     * @param int    $placementId Placement to move
     * @param string $newRegionId Destination region
     */
    public function movePlacement(int $placementId, string $newRegionId): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return;
        }

        $placement->region_id = $newRegionId;
        $placement->sort_order = $this->placements()->nextSortOrder($newRegionId);
        $placement->save();
        $this->invalidatePlacementsCache();
    }

    /**
     * Get placements whose region_id doesn't match any current region.
     *
     * These are orphaned when a theme switch removes theme-declared regions.
     *
     * @return BlockPlacement[]
     */
    public function getOrphanedPlacements(): array
    {
        $regions = $this->getRegions();
        $all = $this->placements()->getAll();
        $orphaned = [];

        foreach ($all as $placement) {
            if (!isset($regions[$placement->region_id])) {
                $orphaned[] = $placement;
            }
        }

        return $orphaned;
    }

    // -----------------------------------------------------------------
    // Block Option Values (JSON column)
    // -----------------------------------------------------------------

    /**
     * Get saved values for a placement as a nested array.
     *
     * @param int $placementId Placement ID
     * @return array<string, mixed> Nested values
     */
    public function getPlacementValues(int $placementId): array
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return [];
        }

        return $placement->getOptions();
    }

    /**
     * Save values for a placement.
     *
     * @param int                  $placementId Placement ID
     * @param array<string, mixed> $values      Key => value pairs (nested or flat)
     */
    public function savePlacementValues(int $placementId, array $values): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return;
        }

        $placement->setOptions($values);
        $placement->save();
    }

    // -----------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------

    /**
     * Render all placed blocks for a single region.
     *
     * @param string $regionId Region to render
     * @return string Concatenated HTML output from all blocks in the region
     */
    public function buildRegion(string $regionId): string
    {
        $blocks = $this->getAvailableBlocks();
        return $this->renderRegion($regionId, $blocks);
    }

    /**
     * Render all blocks in a region via Vision.
     *
     * @param string                              $regionId Region identifier
     * @param array<string, array<string, mixed>> $blocks   Available block definitions
     * @return string Rendered HTML
     */
    protected function renderRegion(string $regionId, array $blocks): string
    {
        $placements = $this->getPlacements($regionId);

        if (empty($placements)) {
            return '';
        }

        $view = $this->app->view();
        $vision = ($view instanceof PluginView) ? $view->vision() : null;

        if ($vision === null) {
            return '';
        }

        $html = '';

        foreach ($placements as $placement) {
            $block = $blocks[$placement->block_key] ?? null;

            if ($block === null) {
                continue;
            }

            $options = $placement->getOptions();
            $rendered = $this->renderBlock($block, $options, $vision, $view, $placement->block_key);
            if ($rendered !== '') {
                $html .= $rendered;
            }
        }

        return $html;
    }

    /**
     * Render a single block: call provider with saved values, resolve template, render with Vision.
     *
     * @param array<string, mixed> $block    Block definition from adext
     * @param array<string, mixed> $options  Saved placement options (JSON-decoded)
     * @param \Enlivenapp\Vision\Engine $vision Vision template engine instance
     * @param PluginView                $view    PluginView instance for template resolution
     * @param string                    $blockKey Block registration key ('{author}.{package}.{name}')
     * @return string Rendered HTML, or empty string on failure
     */
    protected function renderBlock(array $block, array $options, object $vision, PluginView $view, string $blockKey): string
    {
        // Call the data provider with saved placement values and the
        // current page context.
        $data = [];
        if (isset($block['provider']) && is_callable($block['provider'])) {
            try {
                $data = $block['provider']($options, $this->context);
                if (!is_array($data)) {
                    $data = [];
                }
            } catch (\Throwable $e) {
                return '';
            }
        } else {
            // No provider: layer the saved options over the option schema
            // defaults so every template variable carries a value.
            $data = $this->defaultOptions($block, $options);
        }

        // Resolve template path through the override chain
        $templatePath = $this->resolveBlockTemplate((string) ($block['template'] ?? ''), $blockKey, $view);

        if ($templatePath === '' || !is_file($templatePath)) {
            return '';
        }

        try {
            return $vision->render($templatePath, $data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Merge a block's saved option values over its schema defaults.
     *
     * A block without a provider hands its options straight to the
     * template, so every option the template reads must carry a value
     * even when the placement was saved with an empty options object.
     *
     * @param array<string, mixed> $block   Block definition from adext
     * @param array<string, mixed> $options Saved placement values
     * @return array<string, mixed> Defaults overlaid with saved values
     */
    protected function defaultOptions(array $block, array $options): array
    {
        $defaults = [];
        foreach (($block['options'] ?? []) as $key => $def) {
            if (is_array($def) && array_key_exists('default', $def)) {
                $defaults[$key] = $def['default'];
            }
        }
        return array_replace($defaults, $options);
    }

    /**
     * Resolve a block template path through the three-tier override chain.
     *
     * A block registration declares only the bare template file name
     * (e.g. 'author-card.tpl'); the owning plugin's author and package
     * come from the block registration key (e.g. 'pubvana.profiles.author-card'
     * -> author 'pubvana', package 'profiles'). The file always lives under
     * public/blocks/. Resolution:
     *   1. app/Views/{author}/{package}/public/blocks/{name}.tpl  (app override)
     *   2. themes/{active}/Views/{author}/{package}/public/blocks/{name}.tpl  (theme override)
     *   3. plugins/{Plugin}/Views/public/blocks/{name}.tpl  (plugin default, via the registered plugin view path)
     *
     * @param string     $template Bare template file name (may carry a trailing .tpl)
     * @param string     $blockKey Block registration key ('{author}.{package}.{name}')
     * @param PluginView $view     PluginView for theme/plugin path lookups
     * @return string Resolved absolute path, or empty string if not found
     */
    protected function resolveBlockTemplate(string $template, string $blockKey, PluginView $view): string
    {
        $name = $this->templateName($template);

        if ($name === '') {
            return '';
        }

        $key = explode('.', $blockKey);
        if (count($key) < 2 || $key[0] === '' || $key[1] === '') {
            return '';
        }

        $package = $key[0] . '/' . $key[1];
        $prefixedPath = $package . '/public/blocks/' . $name . '.tpl';

        // 1. App-level override
        $appViewsPath = $this->app->get('flight.views.path') ?? PROJECT_ROOT . '/app/Views';
        $appOverride = $appViewsPath . DIRECTORY_SEPARATOR . $prefixedPath;
        if (is_file($appOverride)) {
            return $appOverride;
        }

        // 2. Theme override
        $themePath = $view->getThemePath();
        if ($themePath !== null) {
            $themeOverride = $themePath . DIRECTORY_SEPARATOR . $prefixedPath;
            if (is_file($themeOverride)) {
                return $themeOverride;
            }
        }

        // 3. Plugin default. The package-prefix stripped path under the
        // plugin's Views/ dir: {author}/{package} -> Views/public/blocks/.
        $pluginViewPath = $view->getPluginPath($package);
        if ($pluginViewPath !== null) {
            $pluginFile = $pluginViewPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR . $name . '.tpl';
            if (is_file($pluginFile)) {
                return $pluginFile;
            }
        }

        return '';
    }

    /**
     * Normalize the registered template value into a bare template file name.
     *
     * Only a bare file name is accepted; the value may carry a trailing
     * .tpl which is stripped before the tiers append their own extension.
     *
     * @param string $template Registered template value
     * @return string Bare name without .tpl, or '' when not a bare file name
     */
    protected function templateName(string $template): string
    {
        $template = trim($template, '/');
        if ($template === '' || str_contains($template, '/')) {
            return '';
        }
        return str_ends_with($template, '.tpl') ? substr($template, 0, -4) : $template;
    }

    // -----------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------

    /**
     * Get the placement model instance (lazy-loaded).
     *
     * @return BlockPlacement
     */
    protected function placements(): BlockPlacement
    {
        if ($this->placementModel === null) {
            $this->placementModel = new BlockPlacement($this->app->db());
        }
        return $this->placementModel;
    }
}
