<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SocialLinks\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * Social Links Admin Controller
 *
 * Strict MVC: this controller handles HTTP only. List, create, toggle,
 * reorder and delete social links against the service facade.
 *
 * @package Pubvana\Plugins\SocialLinks
 */
class SocialLinksAdminController extends AdminController
{
    public function index(): void
    {
        $this->render('pubvana/social-links/admin/index', [
            'pageTitle' => 'Social Links',
            'links'     => $this->app->socialLinks()->all(),
            'platforms' => $this->app->socialLinks()->platformOptions(),
        ]);
    }

    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        if ($this->app->socialLinks()->create($post) === null) {
            $this->app->session()->flash('error', 'A URL is required and must be a valid http(s) address.');
            $this->app->redirect('/admin/social-links');
            return;
        }

        $this->app->session()->flash('success', 'Social link added.');
        $this->app->redirect('/admin/social-links');
    }

    public function toggle(string $id): void
    {
        if ($this->app->socialLinks()->toggle((int) $id)) {
            $this->app->session()->flash('success', 'Social link updated.');
        } else {
            $this->app->session()->flash('error', 'Social link not found.');
        }
        $this->app->redirect('/admin/social-links');
    }

    public function delete(string $id): void
    {
        if ($this->app->socialLinks()->delete((int) $id)) {
            $this->app->session()->flash('success', 'Social link deleted.');
        } else {
            $this->app->session()->flash('error', 'Social link not found.');
        }
        $this->app->redirect('/admin/social-links');
    }

    public function reorder(string $id): void
    {
        $post = $this->app->request()->data->getData();
        $direction = (string) ($post['direction'] ?? 'down');
        if (!in_array($direction, ['up', 'down'], true)) {
            $direction = 'down';
        }

        if ($this->app->socialLinks()->move((int) $id, $direction)) {
            $this->app->session()->flash('success', 'Social links reordered.');
        } else {
            $this->app->session()->flash('error', 'Social link not found.');
        }
        $this->app->redirect('/admin/social-links');
    }
}