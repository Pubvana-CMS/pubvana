<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use flight\Engine;

/**
 * MarketplaceAdminController - admin surface for the Marketplace.
 *
 * Connect/disconnect the Pubvana account, browse the store catalog and push
 * items to the account-bound cart, and manage purchases (verify, install,
 * reinstall-all, domain move).
 *
 * @package Pubvana\Plugins\Marketplace\Controllers
 */
class MarketplaceAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.marketplace');
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/marketplace'), '/');
    }

    public function index(): void
    {
        $svc = $this->app->marketplace();
        // Addons physically installed on this site, keyed by package id,
        // so catalog cards can show what is already here regardless of how
        // it got installed (Marketplace, core shipped, manual upload).
        $installed = $svc->localPackageVersions();

        // One call renders the whole first view: the store returns the tab
        // set, which tab opens, and that tab's first page. Tab switches,
        // search, and paging are their own calls.
        $connected = $svc->connected();
        $catalog = $connected ? $svc->catalog($this->catalogParams()) : null;

        $this->render('pubvana/marketplace/admin/index', [
            'pageTitle'    => 'Marketplace',
            'connected'    => $connected,
            'accountEmail' => $svc->accountEmail(),
            'prefillEmail' => $this->currentUserEmail(),
            'installed'    => $installed,
            'adminBase'    => $this->adminBase(),
            'catalog'      => $catalog,
            'tabs'         => $catalog['tabs'] ?? [],
            'openTab'      => $catalog['open_tab'] ?? '',
            'searchQuery'  => (string) ($_GET['q'] ?? ''),
        ]);
    }

    /**
     * The catalog request the admin asked for, read off the query string.
     *
     * A search drops the tab: the store spans every addon category for a
     * search, so sending a tab would narrow it back down.
     *
     * @return array{tab?: string, page?: int, q?: string}
     */
    private function catalogParams(): array
    {
        $params = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $params['q'] = $q;
        } else {
            $tab = strtolower(trim((string) ($_GET['tab'] ?? '')));
            if (in_array($tab, ['plugins', 'themes', 'sale'], true)) {
                $params['type'] = $tab;
            }
        }

        $page = (int) ($_GET['page'] ?? 1);
        if ($page > 1) {
            $params['page'] = $page;
        }

        return $params;
    }

    public function connect(): void
    {
        $email = $this->currentUserEmail();
        if ($email === '') {
            $this->app->session()->flash('danger', 'No logged-in admin email is available to connect with.');
            $this->app->redirect($this->adminBase());
            return;
        }
        $password = (string) ($this->app->request()->data->password ?? '');
        $passwordConf = (string) ($this->app->request()->data->password_conf ?? '');
        $action = (string) ($this->app->request()->data->action ?? 'login');
        $result = $this->app->marketplace()->connectAccount($email, $password, $passwordConf, $action);
        if (!empty($result['ok'])) {
            $this->app->session()->flash('success', 'Connected to the Pubvana account. Browse the catalog below.');
        } else {
            $this->app->session()->flash('danger', $result['reason'] ?? 'Could not connect.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * The pubvanacms account email for the account connecting: the current
     * admin's email, so each admin owns their own store account.
     */
    private function currentUserEmail(): string
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return '';
        }
        return (string) ($this->app->auth()->users()->getEmail($user) ?? '');
    }

    public function disconnect(): void
    {
        $this->app->marketplace()->disconnectAccount();
        $this->app->session()->flash('info', 'Disconnected from the Pubvana account.');
        $this->app->redirect($this->adminBase());
    }

    public function purchases(): void
    {
        $svc = $this->app->marketplace();
        $records = $svc->connected() ? $svc->localInstallRecords() : [];
        $this->render('pubvana/marketplace/admin/purchases', [
            'pageTitle'   => 'Purchases',
            'connected'   => $svc->connected(),
            'records'     => $records,
            'adminBase'   => $this->adminBase(),
        ]);
    }

    public function verify(): void
    {
        $result = $this->app->marketplace()->verifyPurchases();
        if (!empty($result['ok'])) {
            $this->app->session()->flash('success', 'Purchases verified against pubvanacms.com.');
        } else {
            $this->app->session()->flash('danger', (string) $result['reason']);
        }
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    public function addToCart(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $currency = (string) ($this->app->request()->data->currency ?? 'USD');
        $scope = (string) ($this->app->request()->data->scope ?? 'single_site');
        $result = $this->app->marketplace()->addToCart($productId, $currency, $scope);
        $this->app->json($result);
    }

    public function install(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $record = $this->app->marketplace()->installRecordForProduct($productId);
        if ($record !== null && $this->app->marketplace()->needsDomainMove($productId)) {
            $this->app->session()->flash('warning', 'Your license is bound to another domain. Confirm the transfer in your email, then install again.');
            $this->app->marketplace()->requestDomainMove($productId);
            $this->app->redirect($this->adminBase() . '/purchases');
            return;
        }
        $result = $this->app->marketplace()->install($productId);
        $this->app->session()->flash(!empty($result['ok']) ? 'success' : 'danger', $result['reason']);
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    /**
     * Download and install a free package directly, no cart, no checkout.
     * The store's free endpoint serves the zip; the manifest inside drives
     * the install.
     */
    public function installFree(): void
    {
        $package = (string) ($this->app->request()->data->package ?? '');
        $result = $this->app->marketplace()->installFromPackage($package);
        $this->app->session()->flash(!empty($result['ok']) ? 'success' : 'danger', $result['reason']);
        $this->app->redirect($this->adminBase());
    }

    public function reinstallAll(): void
    {
        $svc = $this->app->marketplace();
        if (!$svc->connected()) {
            $this->app->session()->flash('danger', 'Connect a Pubvana account first.');
            $this->app->redirect($this->adminBase());
            return;
        }
        $result = $svc->reinstallAll();
        $failed = count($result['failed']);
        if ($failed > 0) {
            $this->app->session()->flash('warning', 'Reinstalled ' . (int) $result['ok'] . ', skipped ' . (int) $result['skipped'] . '. Some items failed.');
        } else {
            $this->app->session()->flash('success', 'Reinstalled ' . (int) $result['ok'] . ' items, skipped ' . (int) $result['skipped'] . '.');
        }
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    public function cartOpen(): void
    {
        $this->app->redirect($this->app->marketplace()->checkoutUrl());
    }
}