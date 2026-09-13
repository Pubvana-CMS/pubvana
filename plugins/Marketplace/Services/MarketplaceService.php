<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Services;

use Pubvana\Plugins\Marketplace\Models\MarketplaceInstall;
use flight\Engine;

/**
 * MarketplaceService - the Marketplace facade, mapped as $app->marketplace().
 *
 * The Marketplace is the companion app for the Digital Store on
 * pubvanacms.com. It talks to the store over a server-to-server API using an
 * account token, never a hand-entered key:
 *
 *   1. The admin links this site to a Pubvana account using their admin
 *      email plus a password (create or sign-in on the store).
 *   2. The catalog is browsed here and items are pushed to the account-bound
 *      cart at the store.
 *   3. "Purchase on pubvanacms.com" opens the checkout in a new tab with the
 *      cart already populated.
 *   4. "Purchases" verifies ownership back at the store for this domain and
 *      lists every owned item with its license state, ready to install or
 *      reinstall.
 *
 * Self-verification (phone-home) runs on a ~2 week cadence and is disclosed
 * up front when the account is first connected.
 *
 * @package Pubvana\Plugins\Marketplace\Services
 */
class MarketplaceService
{
    /**
     * @param Engine<object>       $app
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected \PDO $pdo,
        protected Engine $app,
        protected array $config,
    ) {
    }

    // -----------------------------------------------------------------
    // Account / connection
    // -----------------------------------------------------------------

    /**
     * Whether an account token is configured for this site.
     */
    public function connected(): bool
    {
        return (string) ($this->app->settings()->get('Marketplace.account_token') ?? '') !== '';
    }

    /**
     * The connected account email, if known.
     */
    public function accountEmail(): string
    {
        return (string) ($this->app->settings()->get('Marketplace.account_email') ?? '');
    }

    /**
     * Bind this site to a Pubvana account by signing in with the store email
     * + password, or creating a new account (email activation) and returning
     * the token that links this site to the account.
     *
     * Returns a result array with 'ok' and 'reason'.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function connectAccount(string $email, string $password, string $passwordConf): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'reason' => 'A valid email address is required.'];
        }
        if ($password === '') {
            return ['ok' => false, 'reason' => 'A password is required.'];
        }
        if ($passwordConf === '' || $passwordConf !== $password) {
            return ['ok' => false, 'reason' => 'The passwords do not match.'];
        }

        $body = $this->httpPostJson($this->apiUrl('auth/token'), [
            'email'         => $email,
            'password'      => $password,
            'password_conf' => $passwordConf,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not be reached. Please try again.'];
        }
        if (!empty($data['activation_required'])) {
            return ['ok' => false, 'reason' => 'A new Pubvana account (' . $email . ') needs activation. Check your inbox for the activation email, then connect again.'];
        }
        if (empty($data['ok']) || empty($data['token'])) {
            return ['ok' => false, 'reason' => (string) ($data['reason'] ?? 'Could not connect to the Pubvana account. Please try again.')];
        }

        $this->app->settings()->set('Marketplace.account_token', (string) $data['token']);
        $this->app->settings()->set('Marketplace.account_email', $email);
        $this->app->settings()->set('Marketplace.connected_at', date('c'));
        $this->app->settings()->set('Marketplace.verify_disclosed_at', date('c'));

        return ['ok' => true];
    }

    /**
     * Disconnect the account token (does not revoke the store account).
     */
    public function disconnectAccount(): void
    {
        $this->app->settings()->set('Marketplace.account_token', '');
        $this->app->settings()->set('Marketplace.account_email', '');
    }

    // -----------------------------------------------------------------
    // Catalog
    // -----------------------------------------------------------------

    private const SETTING_CATALOG_REFRESHED_AT = 'Marketplace.catalog_refreshed_at';

    private const MAX_REDIRECTS = 3;

    /**
     * The last moment the cached catalog was declared stale by a user
     * action ("Check all" on the Updates screen). Cache entries generated
     * before this moment are treated as expired even inside their TTL.
     */
    public function refreshCatalog(): void
    {
        $this->app->settings()->set(self::SETTING_CATALOG_REFRESHED_AT, date('c'));
    }

    /**
     * Fetch categories with their products from the store, cached.
     *
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        if (!$this->withToken()) {
            return [];
        }
        return $this->getCachedCatalog('categories', function () {
            $data = $this->decode($this->httpGet($this->apiUrl('categories')));
            if (!is_array($data) || empty($data['ok']) || !is_array($data['categories'])) {
                return [];
            }
            return $data['categories'];
        });
    }

    /**
     * Fetch the marketplace-listed item catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $currency = 'USD'): array
    {
        if (!$this->withToken()) {
            return [];
        }
        return $this->getCachedCatalog('items_' . $currency, function () use ($currency) {
            // pubvana_version filters out items whose latest release does
            // not support this site's Pubvana version (store-side compat
            // check, see StoreApiController::items).
            $pubvanaVersion = $this->sitePubvanaVersion();
            $url = $this->apiUrl('items') . '?currency=' . urlencode($currency)
                . ($pubvanaVersion !== '' ? '&pubvana_version=' . urlencode($pubvanaVersion) : '');
            $data = $this->decode($this->httpGet($url));
            if (!is_array($data) || empty($data['ok']) || !is_array($data['items'])) {
                return [];
            }
            return $data['items'];
        });
    }

    /**
     * Get cached catalog data or fetch and cache it.
     *
     * A cached entry is trusted only while its TTL runs AND it was written
     * after the last explicit refresh; an older entry is refetched.
     *
     * @param string   $key      Cache key
     * @param callable $callback Callback to fetch fresh data
     * @return array<int, array<string, mixed>>
     */
    protected function getCachedCatalog(string $key, callable $callback): array
    {
        $cacheKey = 'Marketplace.catalog_cache.' . $key;
        $ttl = (int) ($this->config['catalog_cache_ttl'] ?? 3600);
        $now = time();
        $refreshedAt = strtotime((string) ($this->app->settings()->get(self::SETTING_CATALOG_REFRESHED_AT) ?? ''));

        $cached = $this->app->settings()->get($cacheKey);
        if (is_array($cached) && isset($cached['data'], $cached['expires']) && $cached['expires'] > $now) {
            $data = $cached['data'];
            $stillValid = !is_int($refreshedAt)
                || (isset($cached['generated_at']) && is_int($cached['generated_at']) && $cached['generated_at'] >= $refreshedAt);
            if (is_array($data) && $data !== [] && $stillValid) {
                return $data;
            }
        }

        $data = $callback();

        // Never cache a miss: an unreachable store must not look like an
        // (empty) catalog for the whole TTL.
        if ($data !== []) {
            $this->app->settings()->set($cacheKey, [
                'data'         => $data,
                'expires'      => $now + $ttl,
                'generated_at' => $now,
            ]);
        }

        return $data;
    }

    // -----------------------------------------------------------------
    // Cart
    // -----------------------------------------------------------------

    /**
     * Push a product into the account-bound cart at the store.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function addToCart(int $productId, string $currency = 'USD', string $scope = 'single_site'): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $body = $this->httpPostJson($this->apiUrl('cart/add'), [
            'product_id' => $productId,
            'scope'      => $scope === 'multi_site' ? 'multi_site' : 'single_site',
            'currency'   => $currency,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not update your cart.'];
        }
        return ['ok' => !empty($data['ok']), 'reason' => (string) ($data['reason'] ?? '')];
    }

    /**
     * The store URL that opens checkout for the account-bound cart.
     */
    public function checkoutUrl(): string
    {
        return rtrim((string) ($this->config['store_url'] ?? ''), '/') . '/checkout';
    }

    // -----------------------------------------------------------------
    // Purchases / verification
    // -----------------------------------------------------------------

    /**
     * Verify which owned products license this domain and reconcile the local
     * marketplace_installs against the store's answer.
     *
     * @return array<int, array<string, mixed>> Serialized purchase records.
     */
    public function purchases(): array
    {
        if (!$this->withToken()) {
            return [];
        }
        $domain = $this->siteDomain();
        $url = $this->apiUrl('purchases') . '?domain=' . urlencode($domain);
        $data = $this->decode($this->httpGet($url));
        if (!is_array($data) || empty($data['ok']) || !is_array($data['purchases'])) {
            return [];
        }

        $this->reconcileInstalls($data['purchases']);
        return $data['purchases'];
    }

    /**
     * Upsert local install records from a store purchases response.
     *
     * @param array<int, array<string, mixed>> $purchases
     */
    protected function reconcileInstalls(array $purchases): void
    {
        $model = new MarketplaceInstall($this->pdo);
        $now = date('Y-m-d H:i:s');
        $domain = $this->siteDomain();
        foreach ($purchases as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $row = $model->findByProductId($pid);
            $data = [
                'product_name'     => (string) ($p['name'] ?? ''),
                'slug'             => (string) ($p['slug'] ?? ''),
                'license_key'      => (string) ($p['license_key'] ?? ''),
                'license_scope'    => in_array($p['scope'] ?? '', ['single_site', 'multi_site', 'none'], true) ? (string) $p['scope'] : 'single_site',
                'license_valid'    => !empty($p['licensed']) ? 1 : 0,
                'license_last_checked' => $now,
                'expires_at'       => ($p['expires'] ?? '') !== '' ? (string) $p['expires'] : null,
                'renews_at'        => ($p['renews'] ?? '') !== '' ? (string) $p['renews'] : null,
                'is_subscription'  => ($p['renews'] ?? '') !== '' ? 1 : 0,
                'registered_domain'=> $domain,
                'updated_at'       => $now,
            ];
            if ($row === null) {
                // New record: type/folder stay empty until the first
                // install stamps them from the package manifest.
                $data['item_type'] = 'plugin';
                $data['folder'] = '';
                $data['store_product_id'] = $pid;
                $data['created_at'] = $now;
                $model = new MarketplaceInstall($this->pdo);
                foreach ($data as $k => $v) {
                    $model->$k = $v;
                }
                $model->insert();
            } else {
                foreach ($data as $k => $v) {
                    $row->$k = $v;
                }
                // item_type/folder are NOT synced from the store payload:
                // they were stamped from the package manifest at install
                // time and the manifest is the source of truth. Backfill
                // the identity from the local manifest on the next verify
                // for records installed before package_id existed.
                $identity = $this->manifestIdentity((string) $row->item_type, (string) ($row->folder ?? ''));
                if ($identity !== null && (string) ($row->package_id ?? '') === '') {
                    $row->package_id = $identity['package'];
                }
                $row->save();
            }
        }
    }

    /**
     * Local install records, serialized for the admin Purchases view.
     *
     * @return array<int, array<string, mixed>>
     */
    public function localInstallRecords(): array
    {
        $rows = (new MarketplaceInstall($this->pdo))->allTracked();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'store_product_id'  => (int) $r->store_product_id,
                'package'           => (string) ($r->package_id ?? ''),
                'product_name'      => (string) $r->product_name,
                'item_type'         => (string) $r->item_type,
                'folder'            => (string) ($r->folder ?? ''),
                'installed_version' => (string) ($r->installed_version ?? ''),
                'installed'         => $this->isInstalled((string) $r->item_type, (string) $r->folder),
                'license_valid'     => (int) $r->license_valid === 1,
                'scope'             => (string) $r->license_scope,
                'expires'           => (string) ($r->expires_at ?? ''),
                'renews'            => (string) ($r->renews_at ?? ''),
            ];
        }
        return $out;
    }

    public function installRecordForProduct(int $storeProductId): ?MarketplaceInstall
    {
        return (new MarketplaceInstall($this->pdo))->findByProductId($storeProductId);
    }

    /**
     * Every addon physically installed on this site, keyed by its manifest
     * package identity ('pubvana/blog'), with the live version from that
     * manifest. Covers addons installed outside the Marketplace (core
     * shipped, manual upload), not just tracked installs.
     *
     * @return array<string, array{type: string, folder: string, version: string}>
     */
    public function localPackageVersions(): array
    {
        $out = [];
        $roots = ['plugins' => 'plugin', 'themes' => 'theme'];
        foreach ($roots as $dirName => $type) {
            $base = PROJECT_ROOT . DIRECTORY_SEPARATOR . $dirName;
            foreach ((glob($base . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'pubvana.json') ?: []) as $manifestFile) {
                $info = json_decode((string) file_get_contents((string) $manifestFile), true);
                if (!is_array($info)) {
                    continue;
                }
                $package = null;
                foreach (['name', 'slug'] as $key) {
                    if (isset($info[$key]) && is_string($info[$key]) && $info[$key] !== '') {
                        $package = $info[$key];
                        break;
                    }
                }
                $version = $info['semver'] ?? null;
                if (!is_string($package) || !is_string($version) || $version === '') {
                    continue;
                }
                $folder = basename(dirname((string) $manifestFile));
                $out[$package] = ['type' => $type, 'folder' => $folder, 'version' => $version];
            }
        }
        return $out;
    }

    // -----------------------------------------------------------------
    // Addon updates
    // -----------------------------------------------------------------

    /**
     * The installed identity of an addon, read live from its manifest.
     *
     * Identity contract: an addon IS its pubvana.json `name`
     * ('pubvana/blog'). Install destination folders are filesystem
     * bookkeeping and are never a lookup key across plugins.
     *
     * @return array{package: string, version: string}|null
     */
    protected function manifestIdentity(string $itemType, string $folder): ?array
    {
        $folder = trim($folder);
        if ($folder === '') {
            return null;
        }

        $path = PROJECT_ROOT . \DIRECTORY_SEPARATOR
            . ($itemType === 'theme' ? 'themes' : 'plugins') . \DIRECTORY_SEPARATOR
            . $folder . \DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($path)) {
            return null;
        }

        $info = json_decode((string) file_get_contents($path), true);
        if (!is_array($info)) {
            return null;
        }

        // Plugins carry `name`, themes carry `slug` (forward-compatible `name`).
        $package = null;
        foreach (['name', 'slug'] as $key) {
            if (isset($info[$key]) && is_string($info[$key]) && $info[$key] !== '') {
                $package = $info[$key];
                break;
            }
        }
        $version = $info['semver'] ?? null;

        if (!is_string($package) || !is_string($version) || $version === '') {
            return null;
        }

        return ['package' => $package, 'version' => $version];
    }

    /**
     * Available updates for installed plugins/themes, keyed by package.
     *
     * Store contract: a catalog item whose `slug` matches the installed
     * manifest `name` is the same addon. Version on disk is read live from
     * the manifest, so this never trusts stored DB copies.
     *
     * @return array<string, array{item_type: string, latest_version: string, changelog: string|null}>
     */
    public function checkAddonUpdates(): array
    {
        if (!$this->withToken()) {
            return [];
        }

        $catalog = $this->items();
        if ($catalog === []) {
            return [];
        }

        $catalogByPackage = [];
        foreach ($catalog as $item) {
            // The store's `package` field is the manifest package id
            // ('pubvana/blog'); older store payloads keyed on `slug`.
            $package = (string) ($item['package'] ?? $item['slug'] ?? $item['name'] ?? '');
            if ($package !== '' && !isset($catalogByPackage[$package])) {
                $catalogByPackage[$package] = $item;
            }
        }

        $updates = [];
        foreach ((new MarketplaceInstall($this->pdo))->allTracked() as $record) {
            $itemType = (string) $record->item_type;
            if (!in_array($itemType, ['plugin', 'theme'], true)) {
                continue;
            }

            $identity = $this->manifestIdentity($itemType, (string) ($record->folder ?? ''));
            if ($identity === null) {
                continue;
            }

            $item = $catalogByPackage[$identity['package']] ?? null;
            if ($item === null) {
                continue;
            }

            $catalogVersion = (string) ($item['version'] ?? $item['semver'] ?? '');
            if ($catalogVersion === '') {
                continue;
            }

            if (version_compare($catalogVersion, $identity['version'], '>')) {
                $updates[$identity['package']] = [
                    'item_type'      => $itemType,
                    'latest_version' => $catalogVersion,
                    'changelog'      => isset($item['changelog']) && is_string($item['changelog']) ? $item['changelog'] : null,
                ];
            }
        }

        return $updates;
    }

    public function installRecordForPackage(string $packageId): ?MarketplaceInstall
    {
        return (new MarketplaceInstall($this->pdo))->findByPackageId($packageId);
    }

    /**
     * Packages the store catalog sells that this site holds no verified
     * purchase record for (sellable here, unlicensed here).
     *
     * This is a disclosure, not a verdict: the addon may be genuinely free
     * (scope none), owned elsewhere, mid-transfer, or copied without a
     * purchase. Only the store's license answer (the install record, from
     * the verify flow) resolves it. Free items are reported separately
     * (`free: true`) so downstream surfaces do not treat them as pirated.
     *
     * @return array<string, array{free: bool}>
     */
    public function unlicensedPackages(): array
    {
        if (!$this->withToken()) {
            return [];
        }

        $catalog = $this->items();
        if ($catalog === []) {
            return [];
        }

        $tracked = $this->trackedPackages();

        $unlicensed = [];
        foreach ($catalog as $item) {
            // Key by the package identity (manifest name), the same key the
            // Updates plugin looks up. Fall back to slug for old payloads.
            $package = (string) ($item['package'] ?? $item['slug'] ?? '');
            if ($package === '' || isset($tracked[$package])) {
                continue;
            }
            $unlicensed[$package] = [
                'free' => !empty($item['is_free']) || !empty($item['free_tier']) || (($item['license_scope'] ?? '') === 'none'),
            ];
        }

        return $unlicensed;
    }

    /**
     * Package identities this site holds store install records for, keyed by
     * package. An addon tracked here is Marketplace-sourced; everything else
     * updates with a Pubvana release (core) or the vendor package.
     *
     * @return array<string, string>
     */
    public function trackedPackages(): array
    {
        $tracked = [];
        foreach ((new MarketplaceInstall($this->pdo))->allTracked() as $record) {
            $package = (string) ($record->package_id ?? '');
            if ($package !== '' && in_array((string) $record->item_type, ['plugin', 'theme'], true)) {
                $tracked[$package] = (string) $record->item_type;
            }
        }
        return $tracked;
    }

    /**
     * Install or reinstall an addon from the store by its package identity.
     *
     * Single store-install path: the download URL is re-derived server-side
     * from the license, never accepted from a client. Shared by the
     * Marketplace admin (Purchases) and the Updates addon-update surface.
     *
     * Free packages (is_free / scope none) need no license: they are free to
     * use anywhere, so any connected site can install or update them for
     * free through the store's free endpoint.
     *
     * @return array{ok: bool, reason: string, version: ?string}
     */
    public function installFromPackage(string $packageId): array
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\/_\-\.]*$/', $packageId)) {
            return ['ok' => false, 'reason' => 'Invalid package.', 'version' => null];
        }

        $record = $this->installRecordForPackage($packageId);
        if ($record === null || (string) $record->license_key === '') {
            return $this->installFreePackage($packageId);
        }

        $result = $this->install((int) $record->store_product_id);
        if (!$result['ok']) {
            return ['ok' => false, 'reason' => $result['reason'], 'version' => null];
        }

        $fresh = $this->installRecordForPackage($packageId);

        return [
            'ok'      => true,
            'reason'  => $result['reason'],
            'version' => $fresh !== null && $fresh->installed_version !== null ? (string) $fresh->installed_version : null,
        ];
    }

    /**
     * Install or update a free package: no purchase record, no license.
     *
     * @return array{ok: bool, reason: string, version: ?string}
     */
    protected function installFreePackage(string $package): array
    {
        $item = null;
        foreach ($this->items() as $entry) {
            // Match on the package id (manifest identity) or the store slug.
            if ((string) ($entry['package'] ?? '') === $package
                || (string) ($entry['slug'] ?? '') === $package) {
                $item = $entry;
                break;
            }
        }

        $isFree = $item !== null
            && (!empty($item['is_free']) || !empty($item['free_tier']) || (($item['license_scope'] ?? '') === 'none'));

        if ($item === null || !$isFree) {
            return ['ok' => false, 'reason' => 'This package is not a verified purchase. Verify purchases first.', 'version' => null];
        }

        // Type and folder come out of the package manifest inside
        // installPackage(); the store payload no longer carries them.
        $result = $this->installPackage($this->apiUrl('free') . '?slug=' . urlencode($package));
        if (!$result['ok']) {
            return ['ok' => false, 'reason' => $result['reason'], 'version' => null];
        }

        $identity = ['package' => $result['package'], 'version' => $result['version']];
        $this->trackFreeInstall($item, $result['type'], $result['folder'], $identity);

        return ['ok' => true, 'reason' => 'Installed.', 'version' => $result['version']];
    }

    /**
     * Record a free install so future checks see a Marketplace item: same
     * bookkeeping as a purchase, minus the license.
     *
     * @param array<string, mixed>                    $item      Store catalog item payload.
     * @param array{package: string, version: string}|null $identity Manifest identity.
     */
    protected function trackFreeInstall(array $item, string $itemType, string $folder, ?array $identity): void
    {
        $productId = (int) ($item['id'] ?? 0);
        if ($productId <= 0) {
            return;
        }

        $model = new MarketplaceInstall($this->pdo);
        $row = $model->findByProductId($productId);
        $now = date('Y-m-d H:i:s');

        if ($row === null) {
            $fresh = new MarketplaceInstall($this->pdo);
            $fresh->store_product_id = $productId;
            $fresh->package_id       = $identity['package'] ?? null;
            $fresh->product_name     = (string) ($item['name'] ?? '');
            $fresh->slug             = (string) ($item['slug'] ?? '');
            $fresh->item_type        = $itemType;
            $fresh->folder           = $folder;
            $fresh->installed_version = $identity['version'] ?? null;
            $fresh->license_key      = '';
            $fresh->license_scope    = 'none';
            $fresh->license_valid    = 1;
            $fresh->license_last_checked = $now;
            $fresh->is_subscription = 0;
            $fresh->registered_domain = $this->siteDomain();
            $fresh->created_at = $now;
            $fresh->updated_at = $now;
            $fresh->insert();
            return;
        }

        $row->installed_version = $identity['version'] ?? null;
        $row->package_id = $identity['package'] ?? $row->package_id;
        $row->updated_at = $now;
        $row->save();
    }

    /**
     * Latest store versions for free packages, keyed by package. Free items
     * update without a license, so untracked installs of them are still
     * updatable from the store.
     *
     * @return array<string, string>
     */
    public function freePackageVersions(): array
    {
        if (!$this->withToken()) {
            return [];
        }

        $versions = [];
        foreach ($this->items() as $item) {
            // Free-downloadable: fully free, or any tier priced 0 (the zip
            // is identical for every tier).
            $isFree = !empty($item['is_free']) || !empty($item['free_tier']) || (($item['license_scope'] ?? '') === 'none');
            // Key by the package identity, matching how Updates looks it up.
            $package = (string) ($item['package'] ?? $item['slug'] ?? '');
            $version = (string) ($item['version'] ?? '');
            if ($isFree && $package !== '' && $version !== '') {
                $versions[$package] = $version;
            }
        }

        return $versions;
    }

    // -----------------------------------------------------------------
    // Install
    // -----------------------------------------------------------------

    /**
     * Validate a purchase against the store for this domain and install it.
     * The package type comes from the package manifest at install time.
     *
     * @return array{ok: bool, reason: string}
     */
    public function install(int $storeProductId): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null) {
            return ['ok' => false, 'reason' => 'Unknown purchase. Verify your purchases first.'];
        }
        if ((string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'This item has no license to validate.'];
        }

        $body = $this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
        ]);
        $data = $this->decode($body);
        if (!is_array($data) || empty($data['ok']) || empty($data['download_url'])) {
            return ['ok' => false, 'reason' => is_array($data) ? (string) ($data['reason'] ?? 'Validation failed.') : 'Validation failed.'];
        }

        // Type and folder come out of the package manifest inside
        // installPackage(); the stored record's folder is not consulted.
        $result = $this->installPackage((string) $data['download_url']);
        if (!$result['ok']) {
            return ['ok' => false, 'reason' => $result['reason']];
        }

        // Identity and version come from the freshly installed manifest.
        $record->item_type = $result['type'];
        $record->folder = $result['folder'];
        $record->package_id = $result['package'];
        $record->installed_version = $result['version'];
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        return ['ok' => true, 'reason' => 'Installed.'];
    }

    /**
     * Install or reinstall all owned and licensed items whose versions align
     * with the catalog (reinstall-all). Returns a per-item result summary.
     *
     * @return array<string, mixed>
     */
    public function reinstallAll(string $currency = 'USD'): array
    {
        $purchases = $this->purchases();
        $results = ['ok' => 0, 'skipped' => 0, 'failed' => []];
        foreach ($purchases as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            if ($pid <= 0 || empty($p['license_key'])) {
                $results['skipped']++;
                continue;
            }
            $record = $this->installRecordForProduct($pid);
            if ($record === null) {
                $results['skipped']++;
                continue;
            }
            $result = $this->install($pid);
            if (!empty($result['ok'])) {
                $results['ok']++;
            } else {
                $results['failed'][] = ['product_id' => $pid, 'reason' => $result['reason']];
            }
        }
        return $results;
    }

    /**
     * Download and safely extract a store package zip into the install folder
     * (plugins/ or themes/), rejecting path-traversal entries.
     */
    /**
     * Download, verify, and install a package zip. The package's own
     * manifest is the single source of truth: type (plugin/theme), install
     * folder, package id, and version all come out of the extracted
     * pubvana.json, never from stored metadata.
     *
     * @return array{ok: bool, reason: string, type: string, folder: string, package: string, version: string}
     */
    protected function installPackage(string $downloadUrl): array
    {
        $fail = fn(string $reason): array => ['ok' => false, 'reason' => $reason, 'type' => '', 'folder' => '', 'package' => '', 'version' => ''];

        if (!$this->isAllowedStoreUrl($downloadUrl)) {
            return $fail('Download host is not allowed.');
        }

        // Stage the download in writable/tmp. Nothing here is ever read
        // back; every file is deleted before installPackage() returns,
        // success or failure. The directory is created on demand by the
        // running process (web or CLI), so permissions follow whoever is
        // actually serving the request.
        $tmpDir = PROJECT_ROOT . \DIRECTORY_SEPARATOR . 'writable' . \DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
            return $fail('Could not create the install staging directory.');
        }
        $zipPath = $tmpDir . \DIRECTORY_SEPARATOR . 'pkg-' . bin2hex(random_bytes(4)) . '.zip';

        $zipData = $this->httpGet($downloadUrl);
        if ($zipData === null) {
            return $fail('The package could not be downloaded.');
        }
        if (file_put_contents($zipPath, $zipData) === false) {
            return $fail('The package could not be saved.');
        }

        $archive = new \ZipArchive();
        if ($archive->open($zipPath) !== true) {
            @unlink($zipPath);
            return $fail('The package is not a valid zip.');
        }

        if (!$this->zipEntriesAreSafe($archive)) {
            $archive->close();
            @unlink($zipPath);
            return $fail('The package contains unsafe entries.');
        }

        $extractPath = $tmpDir . \DIRECTORY_SEPARATOR . 'extract';
        if (!is_dir($extractPath) && !mkdir($extractPath, 0755, true) && !is_dir($extractPath)) {
            $archive->close();
            @unlink($zipPath);
            return $fail('Could not extract the package.');
        }

        if (!$archive->extractTo($extractPath)) {
            $archive->close();
            @unlink($zipPath);
            $this->rmdir($extractPath);
            return $fail('Could not extract the package.');
        }
        $archive->close();

        $source = $this->resolveZipRoot($extractPath);

        // The manifest drives everything: type, folder, package id, version.
        $manifestPath = $source . \DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($manifestPath)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('The package has no pubvana.json manifest.');
        }
        $info = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($info)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('The package manifest is invalid.');
        }

        $type = ($info['type'] ?? '') === 'theme' ? 'theme' : 'plugin';
        $folder = trim((string) basename(rtrim((string) $source, \DIRECTORY_SEPARATOR)));
        $package = null;
        foreach (['name', 'slug'] as $key) {
            if (isset($info[$key]) && is_string($info[$key]) && $info[$key] !== '') {
                $package = $info[$key];
                break;
            }
        }

        // If the same package identity already exists locally under a
        // different folder name (case variants, renamed dirs), replace THAT
        // folder instead of creating a duplicate copy of the addon.
        foreach ($this->localPackageVersions() as $localPackage => $local) {
            if ($localPackage === $package && $local['type'] === $type && $local['folder'] !== $folder) {
                $folder = $local['folder'];
                break;
            }
        }
        $version = (string) ($info['semver'] ?? '');

        if ($package === null || $version === '') {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('The package manifest is missing its identity.');
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $folder)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('Invalid package destination.');
        }

        $destRoot = PROJECT_ROOT . \DIRECTORY_SEPARATOR . ($type === 'theme' ? 'themes' : 'plugins');
        $destDir = $destRoot . \DIRECTORY_SEPARATOR . $folder;
        if (is_dir($destDir) && !$this->rmdir($destDir)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('Could not replace the existing package.');
        }
        if (!mkdir($destDir, 0755, true)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('Could not create the package directory.');
        }
        if (!$this->copyTree($source, $destDir)) {
            $this->rmdir($destDir);
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail('Could not copy the package into place.');
        }

        $this->rmdir($extractPath);
        @unlink($zipPath);

        return ['ok' => true, 'reason' => 'Installed.', 'type' => $type, 'folder' => $folder, 'package' => $package, 'version' => $version];
    }

    protected function zipEntriesAreSafe(\ZipArchive $archive): bool
    {
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = (string) $archive->getNameIndex($i);
            if ($name === '') {
                continue;
            }
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, "\0") || preg_match('#^[A-Za-z]:#', $name)) {
                return false;
            }

            // Reject Unix symlink entries: extraction could restore a link
            // that the install/cleanup recursion would follow out of the
            // target tree.
            $opsys = 0;
            $attr  = 0;
            if ($archive->getExternalAttributesIndex($i, $opsys, $attr)
                && $opsys === \ZipArchive::OPSYS_UNIX
                && (($attr >> 16) & 0170000) === 0120000
            ) {
                return false;
            }
        }
        return true;
    }

    protected function resolveZipRoot(string $extractPath): string
    {
        $entries = array_values(array_filter(scandir($extractPath) ?: [], static fn(string $f): bool => !in_array($f, ['.', '..'], true)));
        if (count($entries) === 1 && is_dir($extractPath . \DIRECTORY_SEPARATOR . $entries[0])) {
            return $extractPath . \DIRECTORY_SEPARATOR . $entries[0];
        }
        return $extractPath;
    }

    protected function copyTree(string $from, string $to): bool
    {
        $items = scandir($from) ?: [];
        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $src = $from . \DIRECTORY_SEPARATOR . $item;
            $dst = $to . \DIRECTORY_SEPARATOR . $item;

            // Naive copy: replicate links instead of traversing them.
            if (is_link($src)) {
                $linkTarget = @readlink($src);
                if (!is_string($linkTarget) || !@symlink($linkTarget, $dst)) {
                    return false;
                }
                continue;
            }

            if (is_dir($src)) {
                if (!mkdir($dst, 0755, true) || !$this->copyTree($src, $dst)) {
                    return false;
                }
            } else {
                if (!copy($src, $dst)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function dirList(string $path): array
    {
        $entries = scandir($path);
        return $entries === false ? [] : $entries;
    }

    protected function rmdir(string $dir): bool
    {
        if (is_link($dir)) {
            return @unlink($dir);
        }
        if (!is_dir($dir)) {
            return !is_file($dir) || @unlink($dir);
        }
        foreach ($this->dirList($dir) as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . \DIRECTORY_SEPARATOR . $entry;

            // Unlink-only for links: is_dir() follows a link and would let a
            // crafted symlink turn cleanup into a delete outside the tree.
            if (is_link($path)) {
                if (!@unlink($path)) {
                    return false;
                }
                continue;
            }

            if (is_dir($path) && !$this->rmdir($path)) {
                return false;
            }
            if (is_file($path) && !@unlink($path)) {
                return false;
            }
        }
        return @rmdir($dir);
    }

    protected function isInstalled(string $itemType, string $folder): bool
    {
        if ($folder === '') {
            return false;
        }
        $root = PROJECT_ROOT . \DIRECTORY_SEPARATOR;
        return is_dir($root . ($itemType === 'theme' ? 'themes' : 'plugins') . \DIRECTORY_SEPARATOR . $folder);
    }

    // -----------------------------------------------------------------
    // Domain move / transfer
    // -----------------------------------------------------------------

    /**
     * Whether a single-site license bound to a different domain should trigger
     * the domain-move prompt on install.
     */
    public function needsDomainMove(int $storeProductId): bool
    {
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null || (string) $record->license_scope !== 'single_site') {
            return false;
        }
        return (string) $record->registered_domain !== $this->siteDomain();
    }

    /**
     * Request a domain transfer (rebind) for a single-site license. The store
     * emails a confirmation link that finalizes the move.
     *
     * @return array{ok: bool, reason: string}
     */
    public function requestDomainMove(int $storeProductId): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null || (string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'No license to move.'];
        }
        $body = $this->httpPostJson($this->apiUrl('license/transfer-request'), [
            'license_key' => (string) $record->license_key,
            'new_domain'  => $this->siteDomain(),
        ]);
        $data = $this->decode($body);
        if (!is_array($data) || empty($data['ok'])) {
            return ['ok' => false, 'reason' => is_array($data) ? (string) ($data['reason'] ?? 'Transfer could not start.') : 'Transfer could not start.'];
        }
        return ['ok' => true, 'reason' => 'Check your email and confirm the transfer, then install again.'];
    }

    // -----------------------------------------------------------------
    // Cron / phone-home
    // -----------------------------------------------------------------

    /**
     * Verify purchases and licenses against the store. Called by the 24h cron
     * task; this method holds the cadence gate so the store is actually hit
     * every `verify_days` (default 14) rather than every cron tick.
     */
    public function verifyIfDue(): void
    {
        if (!$this->connected()) {
            return;
        }
        $verifyDays = max(1, (int) ($this->config['verify_days'] ?? 14));
        $last = (string) ($this->app->settings()->get('Marketplace.last_verify_at') ?? '');
        if ($last !== '' && strtotime($last) !== false) {
            $due = strtotime('+ ' . $verifyDays . ' days', (int) strtotime($last));
            if ($due !== false && time() < $due) {
                return;
            }
        }

        $this->purchases();
        $this->app->settings()->set('Marketplace.last_verify_at', date('c'));
    }

    // -----------------------------------------------------------------
    // HTTP helpers
    // -----------------------------------------------------------------

    protected function withToken(): bool
    {
        return $this->connected();
    }

    protected function apiUrl(string $path): string
    {
        $base = rtrim((string) ($this->config['store_url'] ?? ''), '/');
        return $base . '/api/store/' . $path;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function httpPostJson(string $url, array $payload): ?string
    {
        $data = json_encode($payload) ?: '{}';
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->connected()) {
            $headers[] = 'Authorization: Bearer ' . (string) $this->app->settings()->get('Marketplace.account_token');
        }

        if (!$this->isAllowedStoreUrl($url)) {
            return null;
        }
        $timeout = (int) ($this->config['api_timeout'] ?? 10);

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $data,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                ]);
                $body = curl_exec($handle);
                curl_close($handle);
                return is_string($body) && $body !== '' ? $body : null;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $data,
                'timeout'       => $timeout,
                'ignore_errors' => false,
                'user_agent'    => $this->userAgent(),
                'follow_location' => 0,
                'max_redirects'   => 0,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return is_string($body) && $body !== '' ? $body : null;
    }

    protected function httpGet(string $url): ?string
    {
        $timeout = (int) ($this->config['api_timeout'] ?? 10);
        $headers = $this->connected() ? ['Authorization: Bearer ' . (string) $this->app->settings()->get('Marketplace.account_token')] : [];

        $current = $url;
        for ($hops = 0; $hops <= self::MAX_REDIRECTS; $hops++) {
            if (!$this->isAllowedStoreUrl($current)) {
                return null;
            }
            $response = $this->httpFetchOnce($current, $headers, $timeout);
            if ($response === null) {
                return null;
            }
            $status = $response['status'];
            if (in_array($status, [301, 302, 303, 307, 308], true) && $hops < self::MAX_REDIRECTS) {
                $location = $response['location'] ?? null;
                if (!is_string($location) || $location === '') {
                    return null;
                }
                $next = $this->resolveHttpUrl($current, $location);
                if ($next === null || !$this->isAllowedStoreUrl($next)) {
                    return null;
                }
                $current = $next;
                continue;
            }
            $body = $response['body'];
            return is_string($body) && $body !== '' ? $body : null;
        }

        return null;
    }

    /**
     * A single no-redirect HTTP GET. Redirects are followed by httpGet()
     * after re-validating each hop against the store host policy.
     *
     * @param list<string> $headers
     *
     * @return array{body: ?string, status: int, location: ?string}|null
     */
    protected function httpFetchOnce(string $url, array $headers, int $timeout): ?array
    {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                    CURLOPT_HTTPHEADER     => $headers,
                ]);
                $body = curl_exec($handle);
                if (!is_string($body)) {
                    curl_close($handle);
                    return null;
                }
                curl_close($handle);
                $redirect = curl_getinfo($handle, CURLINFO_REDIRECT_URL);
                return [
                    'body'     => $body,
                    'status'   => curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                    'location' => is_string($redirect) && $redirect !== '' ? $redirect : null,
                ];
            }
        }

        $http_response_header = [];
        $context = stream_context_create([
            'http' => [
                'timeout'         => $timeout,
                'follow_location' => 0,
                'max_redirects'   => 0,
                'user_agent'      => $this->userAgent(),
                'header'          => implode("\r\n", $headers),
                'ignore_errors'   => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        $status = 0;
        $location = null;
        $statusLine = (string) ($http_response_header[0] ?? '');
        if (preg_match('#\s(\d{3})\b#', $statusLine, $m) === 1) {
            $status = (int) $m[1];
        }
        foreach ($http_response_header as $line) {
            if (stripos($line, 'Location:') === 0) {
                $location = trim(substr($line, strlen('Location:')));
                break;
            }
        }

        return [
            'body'     => is_string($body) && $body !== '' ? $body : null,
            'status'   => $status,
            'location' => is_string($location) && $location !== '' ? $location : null,
        ];
    }

    /**
     * Resolve a Location value against the URL that produced it.
     */
    protected function resolveHttpUrl(string $base, string $location): ?string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }
        if (str_starts_with($location, '//')) {
            $scheme = (string) parse_url($base, PHP_URL_SCHEME);
            return $scheme !== '' ? $scheme . ':' . $location : null;
        }
        $scheme = (string) parse_url($base, PHP_URL_SCHEME);
        $host = (string) parse_url($base, PHP_URL_HOST);
        if ($scheme === '' || $host === '') {
            return null;
        }
        $port = is_int($p = parse_url($base, PHP_URL_PORT)) ? ':' . $p : '';
        if (str_starts_with($location, '/')) {
            return $scheme . '://' . $host . $port . $location;
        }
        $path = (string) parse_url($base, PHP_URL_PATH);
        $pos = strrpos($path, '/');
        $dir = $pos === false ? '/' : substr($path, 0, $pos + 1);
        return $scheme . '://' . $host . $port . '/' . ltrim($dir . $location, '/');
    }

    /**
     * Whether an outbound store URL is safe to call: http/https plus a host
     * on the store allow-list.
     */
    protected function isAllowedStoreUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }
        return $this->isAllowedStoreHost((string) parse_url($url, PHP_URL_HOST));
    }

    /**
     * Store host allow-list: pubvanacms.com (apex or any subdomain), plus the
     * loopback/dev hosts localhost and plugindev which are development-only.
     */
    protected function isAllowedStoreHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }
        if ($host === 'pubvanacms.com' || str_ends_with($host, '.pubvanacms.com')) {
            return true;
        }
        if (in_array($host, ['localhost', 'plugindev'], true)) {
            return $this->environment() === 'development';
        }
        return false;
    }

    protected function environment(): string
    {
        return (string) ($this->app->get('environment') ?? 'production');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decode(?string $body): ?array
    {
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * This site's Pubvana version, sent to the store so it can filter out
     * incompatible releases. Overridden in tests.
     */
    protected function sitePubvanaVersion(): string
    {
        return (string) ($this->app->pluginLoader()->coreSemver() ?? '');
    }

    /**
     * @return non-empty-string
     */
    protected function userAgent(): string
    {
        return 'Pubvana-Marketplace/3.0';
    }

    protected function siteDomain(): string
    {
        $domain = (string) ($this->app->get('CMS.siteUrl') ?? $this->app->request()->host ?? '');
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', (string) $domain);
        $domain = preg_replace('#^www\.#', '', (string) $domain);
        return (string) preg_replace('#[/\s]#', '', (string) $domain);
    }
}
