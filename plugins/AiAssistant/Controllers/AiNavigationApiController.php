<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * AiNavigationApiController - /api/ai/navigation/* endpoints.
 *
 * Reads and writes navigation items through the Navigation plugin.
 * Updates go through the NavigationItem model because NavigationService
 * has no update method.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiNavigationApiController extends AiApiController
{
    /**
     * List navigation grouped by nav_group.
     */
    public function navigation(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.read');

        $groups = $this->app->ai()->listNavigationByGroup();
        $this->log($key, 'ok', 'navigation', null, 'Listed navigation.');
        $this->ok($groups);
    }

    /**
     * Create a navigation item.
     */
    public function createNavigation(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.create');

        $payload = $this->payload();
        $label = trim((string) ($payload['label'] ?? ''));
        if ($label === '' || trim((string) ($payload['url'] ?? '')) === '') {
            $this->log($key, 'error', 'navigation', null, 'Missing label or url.');
            $this->fail(422, 'label and url are required.');
        }

        $item = $this->svc('navigation')->create([
            'label'      => $label,
            'url'        => (string) $payload['url'],
            'nav_group'  => (string) ($payload['nav_group'] ?? 'primary'),
            'parent_id'  => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'target'     => in_array((string) ($payload['target'] ?? '_self'), ['_self', '_blank'], true) ? (string) $payload['target'] : '_self',
        ]);

        $this->log($key, 'ok', 'navigation', (int) $item->id, "Created navigation item #{$item->id}.");
        $this->ok($this->app->ai()->serializeNavigationItem($item));
    }

    /**
     * Update a navigation item with a partial payload.
     */
    public function updateNavigation(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.update');

        $payload = $this->payload();
        if (array_key_exists('label', $payload)) {
            $payload['label'] = trim((string) $payload['label']);
        }
        if (array_key_exists('target', $payload)) {
            $payload['target'] = in_array((string) $payload['target'], ['_self', '_blank'], true) ? (string) $payload['target'] : '_self';
        }

        $item = $this->app->ai()->updateNavigationItem((int) $id, $payload);
        if ($item === null) {
            $this->log($key, 'error', 'navigation', (int) $id, 'Navigation item not found.');
            $this->fail(404, 'Navigation item not found.');
        }

        $this->log($key, 'ok', 'navigation', (int) $id, "Updated navigation item #{$id}.");
        $this->ok($item);
    }

    /**
     * Delete a navigation item.
     */
    public function deleteNavigation(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.delete');

        if (!$this->svc('navigation')->delete((int) $id)) {
            $this->log($key, 'error', 'navigation', (int) $id, 'Navigation item not found.');
            $this->fail(404, 'Navigation item not found.');
        }

        $this->log($key, 'ok', 'navigation', (int) $id, "Deleted navigation item #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }
}