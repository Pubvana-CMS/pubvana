<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * PermissionsController - Admin CRUD for permission management.
 *
 * Handles listing, creating, editing, and deleting permissions.
 * Permissions are assigned to groups (via GroupsController), not
 * directly to users. All data access goes through flight-shield's
 * management API (auth()->permissions()).
 *
 * Strict MVC: this controller handles HTTP only. Views handle display.
 *
 * @package Pubvana\Controllers\Admin
 */
class PermissionsController extends AdminController
{
    /**
     * Permission listing.
     *
     * @return void
     */
    public function index(): void
    {
        $permissions = $this->app->auth()->permissions()->all();

        $this->render('admin/permissions/index', [
            'pageTitle'   => 'Permissions',
            'permissions' => $permissions,
        ]);
    }

    /**
     * Create permission form.
     *
     * @return void
     */
    public function create(): void
    {
        $this->render('admin/permissions/create', [
            'pageTitle' => 'New Permission',
        ]);
    }

    /**
     * Store a new permission.
     *
     * @return void
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $alias = trim($post['alias'] ?? '');
        $description = trim($post['description'] ?? '');

        if ($alias === '') {
            $this->app->session()->flash('error', 'Alias is required.');
            $this->app->redirect('/admin/permissions/create');
            return;
        }

        if ($this->app->auth()->permissions()->info($alias) !== null) {
            $this->app->session()->flash('error', 'A permission with that alias already exists.');
            $this->app->redirect('/admin/permissions/create');
            return;
        }

        $this->app->auth()->permissions()->create($alias, $description);

        $this->app->session()->flash('success', 'Permission created.');
        $this->app->redirect('/admin/permissions');
    }

    /**
     * Delete a permission.
     *
     * flight-shield removes the permission from all groups and
     * direct user assignments first.
     *
     * @param string $id Permission ID
     * @return void
     */
    public function delete(string $id): void
    {
        $permission = $this->app->auth()->permissions()->findById((int) $id);

        if ($permission !== null) {
            $this->app->auth()->permissions()->delete($permission->alias);
        }

        $this->app->session()->flash('success', 'Permission deleted.');
        $this->app->redirect('/admin/permissions');
    }
}
