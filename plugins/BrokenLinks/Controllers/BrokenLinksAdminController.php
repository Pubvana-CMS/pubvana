<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * BrokenLinksAdminController - Admin UI for outbound broken link scanning.
 */
class BrokenLinksAdminController extends AdminController
{
    /**
     * List broken link results grouped by source.
     */
    public function index(): void
    {
        $showDismissed = (bool) ($this->app->request()->query->dismissed ?? false);

        $this->render('pubvana/brokenlinks/admin/index', [
            'pageTitle'      => 'Broken Links',
            'grouped'        => $this->app->brokenLinks()->all($showDismissed),
            'total'          => $this->app->brokenLinks()->countBroken(),
            'showDismissed'  => $showDismissed,
            'adminBase'      => $this->adminBase(),
            'editBase'       => $this->editBase(),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/brokenlinks'), '/');
    }

    /**
     * Admin editor base per content source type, for the links in the report.
     *
     * Each value comes from the owning plugin's own route prefix. Pages
     * registers 'page' (singular, plugins/Pages/Config/Config.php), so a
     * hardcoded '/admin/pages' would build a URL with no route behind it.
     *
     * @return array<string, string> Source type => admin URL base, no trailing slash
     */
    private function editBase(): array
    {
        return [
            'post' => '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/blog'), '/'),
            'page' => '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/pages'), '/'),
        ];
    }

    /**
     * Run a full scan of all registered content sources.
     */
    public function scan(): void
    {
        $result = $this->app->brokenLinks()->scan();

        $this->app->session()->flash(
            'success',
            sprintf(
                'Scan complete: checked %d link%s across %d source%s. %d broken.',
                $result['total'],
                $result['total'] !== 1 ? 's' : '',
                $result['sources'],
                $result['sources'] !== 1 ? 's' : '',
                $result['broken']
            )
        );

        $this->app->redirect($this->adminBase());
    }

    /**
     * Recheck a single broken link.
     */
    public function recheck(string $id): void
    {
        $result = $this->app->brokenLinks()->recheck((int) $id);

        if ($result['error'] === 'Entry not found.') {
            $this->app->session()->flash('error', 'Entry not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $label = $result['status'] !== null ? (string) $result['status'] : 'unreachable';

        if ($result['dismissed']) {
            $this->app->session()->flash(
                $this->app->brokenLinks()->isOk($result['status']) ? 'success' : 'error',
                'Dismissed entry left in place (status: ' . $label . ').'
            );
        } elseif ($this->app->brokenLinks()->isOk($result['status'])) {
            $this->app->session()->flash('success', 'Link is now reachable and has been removed.');
        } else {
            $this->app->session()->flash('error', 'Link is still broken (status: ' . $label . ').');
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Permanently dismiss a broken link entry.
     */
    public function dismiss(string $id): void
    {
        if ($this->app->brokenLinks()->dismiss((int) $id) === null) {
            $this->app->session()->flash('error', 'Entry not found.');
        } else {
            $this->app->session()->flash('success', 'Entry dismissed permanently.');
        }

        $this->app->redirect($this->adminBase());
    }
}
