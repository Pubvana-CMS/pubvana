<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * GroupsController - Admin CRUD for group management.
 *
 * Handles listing, creating, editing, deleting groups and
 * assigning permissions to groups. All data access goes through
 * flight-shield's management API (auth()->groups(), auth()->permissions(),
 * auth()->stats()).
 *
 * Strict MVC: this controller handles HTTP only. Views handle display.
 *
 * @package Pubvana\Controllers\Admin
 */
class GroupsController extends AdminController
{
    /**
     * Group listing with user counts.
     *
     * @return void
     */
    public function index(): void
    {
        $groups = $this->app->auth()->groups()->all();
        $userCounts = $this->app->auth()->stats()->usersByGroup();

        $this->render('admin/groups/index', [
            'pageTitle'   => 'Groups',
            'groups'      => $groups,
            'userCounts'  => $userCounts,
        ]);
    }

    /**
     * Create group form.
     *
     * @return void
     */
    public function create(): void
    {
        $this->render('admin/groups/create', [
            'pageTitle' => 'New Group',
        ]);
    }

    /**
     * Store a new group.
     *
     * @return void
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $alias = trim($post['alias'] ?? '');
        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');

        if ($alias === '' || $title === '') {
            $this->app->session()->flash('error', 'Alias and title are required.');
            $this->app->redirect('/admin/groups/create');
            return;
        }

        if ($this->app->auth()->groups()->info($alias) !== null) {
            $this->app->session()->flash('error', 'A group with that alias already exists.');
            $this->app->redirect('/admin/groups/create');
            return;
        }

        $this->app->auth()->groups()->create($alias, $title, $description);

        $this->app->session()->flash('success', 'Group created.');
        $this->app->redirect('/admin/groups');
    }

    /**
     * Edit group form with permission checkboxes.
     *
     * @param string $id Group ID
     * @return void
     */
    public function edit(string $id): void
    {
        $group = $this->app->auth()->groups()->findById((int) $id);

        if ($group === null) {
            $this->app->redirect('/admin/groups');
            return;
        }

        $allPermissions = $this->app->auth()->permissions()->all();
        $assignedPerms = $this->app->auth()->groups()->permissions($group->alias);

        $this->render('admin/groups/edit', [
            'pageTitle'      => 'Edit Group',
            'group'          => $group,
            'allPermissions' => $allPermissions,
            'assignedPerms'  => $assignedPerms,
        ]);
    }

    /**
     * Update a group and sync its permissions.
     *
     * @param string $id Group ID
     * @return void
     */
    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $groupsService = $this->app->auth()->groups();
        $group = $groupsService->findById((int) $id);

        if ($group === null) {
            $this->app->redirect('/admin/groups');
            return;
        }

        // Update group fields
        if (!empty($post['title'])) {
            $group->title = trim($post['title']);
        }
        if (isset($post['description'])) {
            $group->description = trim($post['description']);
        }
        $groupsService->save($group);

        // Sync permissions
        $permissions = $post['permissions'] ?? [];
        $groupsService->syncPermissions(
            $group->alias,
            is_array($permissions) ? array_values($permissions) : []
        );

        $this->app->session()->flash('success', 'Group updated.');
        $this->app->redirect('/admin/groups/' . $id . '/edit');
    }

    /**
     * Delete a group.
     *
     * flight-shield removes all group-user and group-permission
     * associations; users left with no groups fall back to the
     * default 'user' group.
     *
     * @param string $id Group ID
     * @return void
     */
    public function delete(string $id): void
    {
        $group = $this->app->auth()->groups()->findById((int) $id);

        if ($group !== null) {
            $this->app->auth()->groups()->delete($group->alias);
        }

        $this->app->session()->flash('success', 'Group deleted.');
        $this->app->redirect('/admin/groups');
    }
}
