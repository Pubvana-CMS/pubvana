<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use flight\Engine;

/**
 * MarketplaceAdminController - admin surface for the Marketplace.
 *
 * Connect/disconnect the Pubvana account, browse the store catalog and push
 * items to the account's cart, and manage purchases (verify, install,
 * reinstall-all).
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

    /**
     * Full admin URL base for the Updates plugin, where addon updates are
     * applied. Null when the plugin cannot be resolved.
     */
    private function updatesBase(): ?string
    {
        try {
            return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/updates'), '/');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The post-install flash: where the admin finishes the job. A new
     * plugin lands disabled (PluginLoader::defaultState) and a new theme
     * lands inactive, so the message points at the menu path that flips
     * each on. Installs are only plugins or themes.
     */
    private function installedMessage(string $itemType): string
    {
        if ($itemType === 'theme') {
            return 'Installed. You can activate it in Appearance > Themes.';
        }

        return 'Installed. You can enable it in Plugins > Manage.';
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
            'cart'         => $svc->cart(),
            'tabs'         => $catalog['tabs'] ?? [],
            'openTab'      => $catalog['open_tab'] ?? '',
            'searchQuery'  => (string) ($_GET['q'] ?? ''),
        ]);
    }

    /**
     * The catalog request the admin asked for, read off the query string.
     *
     * The tab rides along either way: a search runs inside its tab, so the
     * store narrows to it exactly as browsing does.
     *
     * @return array{tab?: string, page?: int, q?: string}
     */
    private function catalogParams(): array
    {
        $params = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $params['q'] = $q;
        }

        // The tab rides along either way: a search runs inside its tab, so
        // the store narrows to it exactly as browsing does.
        $tab = strtolower(trim((string) ($_GET['tab'] ?? '')));
        if (in_array($tab, ['plugins', 'themes', 'sale'], true)) {
            $params['type'] = $tab;
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
        $connected = $svc->connected();

        // Every load syncs against the store. A failed sync disables every
        // assign and install control and says why: unreachable, or whatever
        // the store itself answered (maintenance, most obviously).
        $syncOk = true;
        $syncReason = '';
        if ($connected) {
            $sync = $svc->verifyPurchases();
            $syncOk = !empty($sync['ok']);
            $syncReason = (string) $sync['reason'];
        }

        $this->render('pubvana/marketplace/admin/purchases', [
            'pageTitle'   => 'Purchases',
            'connected'   => $connected,
            'records'     => $connected ? $svc->localInstallRecords($syncOk) : [],
            'syncOk'      => $syncOk,
            'syncReason'  => $syncReason,
            'adminBase'   => $this->adminBase(),
            'updatesBase' => $this->updatesBase(),
            'siteDomain'  => $svc->siteDomain(),
        ]);
    }

    public function verify(): void
    {
        $result = $this->app->marketplace()->verifyPurchases();
        if (!empty($result['ok'])) {
            $this->app->session()->flash('success', 'Synced with Pubvana Store.');
        } else {
            $this->app->session()->flash('danger', (string) $result['reason']);
        }
        $this->app->redirect($this->adminBase() . '/purchases');
    }
    /**
     * POST /admin/marketplace/assign
     *
     * Assign this site's domain to a purchase, no install: the store claims
     * the license and the row shows assigned, ready for the install picker.
     */
    public function assign(): void
    {
        $recordId = (int) ($this->app->request()->data->record_id ?? 0);
        $result = $this->app->marketplace()->assignByRecordId($recordId);
        $this->app->session()->flash(
            !empty($result['ok']) ? 'success' : 'danger',
            !empty($result['ok']) ? 'Assigned to this website.' : (string) $result['reason']
        );
        $this->app->redirect($this->adminBase() . '/purchases');
    }
    public function addToCart(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $tier = (string) ($this->app->request()->data->tier ?? '');
        $currency = (string) ($this->app->request()->data->currency ?? 'USD');
        $result = $this->app->marketplace()->addToCart($productId, $tier, $currency);
        $this->app->json($result);
    }

    public function install(): void
    {
        $recordId = (int) ($this->app->request()->data->record_id ?? 0);
        $version = (string) ($this->app->request()->data->version ?? '');
        $clearDatabase = (string) ($this->app->request()->data->database ?? 'preserve') === 'clear';
        $record = $this->app->marketplace()->installRecordById($recordId);
        if ($record !== null && $this->app->marketplace()->needsDomainMoveForRecord($record)) {
            $this->app->session()->flash('danger', 'This license is assigned to another domain.');
            $this->app->redirect($this->adminBase() . '/purchases');
            return;
        }
        $result = $this->app->marketplace()->installByRecordId($recordId, $version, $clearDatabase);
        if (!empty($result['ok'])) {
            // The fresh record carries the type stamped from the installed
            // manifest, so the message can point at the page that turns the
            // addon on (a new plugin lands disabled, a new theme inactive).
            $installed = $this->app->marketplace()->installRecordById($recordId);
            $this->app->session()->flash('success', $this->installedMessage($installed->item_type ?? ''));
        } else {
            $this->app->session()->flash('danger', $result['reason']);
        }
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    /**
     * The covered releases for a purchase, newest fitting first (JSON): what
     * the install screen's release picker lists.
     */
    public function installReleases(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $this->app->json($this->app->marketplace()->installReleases($productId));
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
        if (!empty($result['ok'])) {
            $installed = $this->app->marketplace()->installRecordForPackage($package);
            $this->app->session()->flash('success', $this->installedMessage($installed->item_type ?? ''));
        } else {
            $this->app->session()->flash('danger', $result['reason']);
        }
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

    /**
     * GET /admin/marketplace/cart-data
     *
     * The cart as JSON, so the modal can refresh itself after an item is
     * added without a page reload.
     */
    public function cartData(): void
    {
        $this->app->json($this->app->marketplace()->cart());
    }

    /**
     * POST /admin/marketplace/cart-remove
     *
     * Drop one line from the store's cart, from the cart modal. The line id
     * is checked against the account's own cart store-side. The modal posts
     * this over fetch and refreshes itself in place; a plain form post
     * still works and lands back on the screen it came from.
     */
    public function removeCartItem(): void
    {
        $cartItemId = (int) ($this->app->request()->data->cart_item_id ?? 0);
        $result = $this->app->marketplace()->removeCartItem($cartItemId);
        if (str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            $this->app->json($result);
            return;
        }
        if (empty($result['ok'])) {
            $this->app->session()->flash('danger', (string) ($result['reason'] ?? 'Could not remove that item'));
        }
        $this->app->redirect($this->adminBase());
    }
}