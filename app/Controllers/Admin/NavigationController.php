<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Pubvana\Services\NavigationService;

/**
 * NavigationController - Admin CRUD for navigation menus.
 *
 * Manages navigation items: listing by group, creating, editing, deleting,
 * and drag-and-drop reordering. The view provides a two-column layout
 * with a Quick Add form (autocomplete from linkable items) and a
 * sortable item list.
 *
 * @package Pubvana\Controllers\Admin
 */
class NavigationController extends AdminController
{
    /**
     * List navigation items for the active group.
     *
     * Reads the ?group= query parameter, validates it against
     * available groups, and renders the management view with
     * items, groups, and linkable items for Quick Add.
     */
    public function index(): void
    {
        $nav = $this->app->navigation();

        $group = $this->app->request()->query->group ?? 'primary';
        $availableGroups = $this->getAvailableGroups();

        if (!in_array($group, $availableGroups, true)) {
            $group = 'primary';
        }

        $items = $nav->getByGroup($group);
        $tree = $nav->getTree($group);
        $linkable = $nav->getLinkableItems();

        $this->render('admin/navigation/index', [
            'pageTitle'  => 'Navigation',
            'items'      => $items,
            'tree'       => $tree,
            'group'      => $group,
            'groups'     => $availableGroups,
            'linkable'   => $linkable,
        ]);
    }

    /**
     * Create a new navigation item from POST data.
     *
     * Strips CSRF token, delegates to the service, then redirects
     * back to the group the item was added to. Input the service
     * rejects is reported instead of written.
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $nav = $this->app->navigation();
        $result = $nav->create($post);

        $group = (string) ($post['nav_group'] ?? 'primary');

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?? 'The item could not be added.');
            $this->app->redirect('/admin/navigation?group=' . urlencode($group));
            return;
        }

        $this->app->session()->flash('success', 'Navigation item added.');
        $this->app->redirect('/admin/navigation?group=' . urlencode($group));
    }

    /**
     * Show the form for editing one navigation item.
     *
     * Label, URL, and target are editable. Parent and group are not.
     */
    public function edit(string $id): void
    {
        $item = $this->app->navigation()->find((int) $id);

        if ($item === null) {
            $this->app->session()->flash('error', 'That navigation item no longer exists.');
            $this->app->redirect('/admin/navigation');
            return;
        }

        $this->render('admin/navigation/edit', [
            'pageTitle' => 'Edit Navigation Item',
            'item'      => $item,
        ]);
    }

    /**
     * Save an edited navigation item and return to its group.
     *
     * Invalid input sends the editor back to the form with the reason.
     */
    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $nav = $this->app->navigation();
        $item = $nav->find((int) $id);

        if ($item === null) {
            $this->app->session()->flash('error', 'That navigation item no longer exists.');
            $this->app->redirect('/admin/navigation');
            return;
        }

        $result = $nav->update((int) $id, $post);

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?? 'The item could not be updated.');
            $this->app->redirect('/admin/navigation/' . (int) $id . '/edit');
            return;
        }

        $this->app->session()->flash('success', 'Navigation item updated.');
        $this->app->redirect('/admin/navigation?group=' . urlencode((string) $item->nav_group));
    }

    /**
     * Delete a navigation item by ID.
     *
     * Children of the deleted item are re-parented to top level.
     */
    public function delete(string $id): void
    {
        $nav = $this->app->navigation();
        $nav->delete((int) $id);

        $group = $this->app->request()->query->group ?? 'primary';
        $this->app->session()->flash('success', 'Navigation item deleted.');
        $this->app->redirect('/admin/navigation?group=' . urlencode($group));
    }

    /**
     * Reorder navigation items via drag-and-drop.
     *
     * Expects a JSON body with an "order" array of item IDs in the
     * desired display order. Returns a JSON success response.
     */
    public function reorder(): void
    {
        $body = json_decode($this->app->request()->getBody(), true);
        $ids = $body['order'] ?? [];

        $nav = $this->app->navigation();
        $nav->reorder($ids);

        $this->app->json(['success' => true]);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Get all available navigation groups.
     *
     * Merges the default groups (primary, footer) with any groups
     * defined by the active theme. Ensures the current group is
     * always available even if no items exist yet.
     *
     * @return string[] Unique group identifiers
     */
    protected function getAvailableGroups(): array
    {
        $groups = ['primary', 'footer'];

        try {
            $nav = $this->app->navigation();
            $existing = $nav->getGroups();
            $groups = array_unique(array_merge($groups, $existing));
        } catch (\Throwable $e) {
            // Navigation service not available yet
        }

        // Ensure the current request group is always listed
        $current = $this->app->request()->query->group ?? 'primary';
        if (!in_array($current, $groups, true)) {
            $groups[] = $current;
        }

        sort($groups);
        return $groups;
    }
}
