<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Services;

use Enlivenapp\Migrations\Services\MigrationSetup;
use Pubvana\Models\TrustCache;
use Pubvana\Plugins\Backups\Services\ProgressReporter as BackupProgressReporter;
use Pubvana\Plugins\Marketplace\Models\MarketplaceInstall;
use Pubvana\Services\HtmlPurifierFactory;
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
 *   2. The catalog is browsed here and items are pushed to the account's
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
     * Link this site to a Pubvana account by asking the store to sign in or
     * register through Shield. Shield owns the account; the store hands back
     * the token that links this site to it.
     *
     * Returns a result array with 'ok' and 'reason'.
     *
     * @param string $action 'login' or 'register'
     * @return array{ok: bool, reason?: string}
     */
    public function connectAccount(string $email, string $password, string $passwordConf, string $action = 'login'): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'reason' => 'A valid email address is required.'];
        }
        if ($password === '') {
            return ['ok' => false, 'reason' => 'A password is required.'];
        }
        $action = $action === 'register' ? 'register' : 'login';
        if ($action === 'register' && ($passwordConf === '' || $passwordConf !== $password)) {
            return ['ok' => false, 'reason' => 'The passwords do not match.'];
        }

        $body = $this->httpPostJson($this->apiUrl('auth/token'), [
            'action'        => $action,
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

    private const MAX_REDIRECTS = 3;

    /** Default response-size cap for API/JSON fetches (bytes). */
    private const MAX_BODY_BYTES = 1048576;

    /** Default response-size cap for package zip downloads (bytes). */
    private const MAX_ZIP_BYTES = 26214400;

    /**
     * The store's own refusal reason from the last catalog fetch, when it
     * answered with one (maintenance, throttle). Empty when the store was
     * unreachable or the last fetch succeeded.
     */
    protected string $catalogReason = '';

    /**
     * One page of the Marketplace catalog, with the tab set and pager totals
     * the Browse screen needs.
     *
     * A store that cannot be reached comes back as ok=false with a plain
     * reason, so the screen can say so instead of rendering an empty
     * catalog. Nothing is cached: every view is the store's current answer.
     *
     * @param array{type?: string, q?: string, page?: int, per_page?: int, currency?: string} $params
     * @return array{ok: bool, reason: string, tabs: list<array{key: string, label: string}>, open_tab: string, items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function catalog(array $params = []): array
    {
        $empty = [
            'ok'       => false,
            'reason'   => '',
            'tabs'     => [],
            'open_tab' => 'plugins',
            'items'    => [],
            'total'    => 0,
            'page'     => 1,
            'per_page' => 24,
            'pages'    => 1,
        ];

        if (!$this->withToken()) {
            return ['reason' => 'Not connected to a Pubvana account.'] + $empty;
        }

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min((int) ($params['per_page'] ?? 24), 60));
        $json = $this->fetchCatalogPage(
            (string) ($params['currency'] ?? 'USD'),
            (string) ($params['type'] ?? ''),
            (string) ($params['q'] ?? ''),
            $page,
            $perPage
        );

        if ($json === null) {
            return ['reason' => $this->catalogFailureReason()] + $empty;
        }

        // The store writes descriptions as HTML. Purify them here so the
        // view can print them as markup instead of escaped tags.
        //
        // The image is a site-relative path there too, so it is resolved
        // against the store before the view uses it.
        $items = $json['items'];
        foreach ($items as $index => $item) {
            if (isset($item['description'])) {
                $items[$index]['description'] = $this->purifyDescription((string) $item['description']);
            }
            if (isset($item['image'])) {
                $items[$index]['image'] = $this->storeAssetUrl((string) $item['image']);
            }
        }

        return [
            'ok'       => true,
            'reason'   => '',
            'tabs'     => $json['tabs'],
            'open_tab' => $json['open_tab'],
            'items'    => $items,
            'total'    => $json['total'],
            'page'     => $json['page'],
            'per_page' => $json['per_page'],
            'pages'    => $json['pages'],
        ];
    }

    /**
     * The whole Marketplace catalog, paging through the store.
     *
     * For the update checks, which need every listing to compare versions
     * and find free or unlicensed packages. The Browse screen uses catalog(),
     * which is one page. Returns [] when the store cannot be reached.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $currency = 'USD'): array
    {
        if (!$this->withToken()) {
            return [];
        }

        $out = [];
        $page = 1;
        do {
            $batch = $this->fetchCatalogPage($currency, '', '', $page, 60);
            if ($batch === null) {
                return [];
            }
            foreach ($batch['items'] as $item) {
                $out[] = $item;
            }
            $page++;
        } while ($page <= $batch['pages']);

        return $out;
    }

    /**
     * The store's /items response for one page, or null when the store is
     * unreachable or answered with something that is not a catalog page.
     *
     * A store that answered with a reason (maintenance, throttle) has that
     * reason kept in $catalogReason, so the screen can show it instead of a
     * generic line.
     *
     * @return array{tabs: list<array{key: string, label: string}>, open_tab: string, items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, pages: int}|null
     */
    protected function fetchCatalogPage(string $currency, string $type, string $q, int $page, int $perPage): ?array
    {
        $query = ['currency' => $currency, 'page' => $page, 'per_page' => $perPage];
        if ($type !== '') {
            $query['type'] = $type;
        }
        if ($q !== '') {
            $query['q'] = $q;
        }
        $version = $this->sitePubvanaVersion();
        if ($version !== '') {
            $query['pubvana_version'] = $version;
        }

        $data = $this->decode($this->httpGet($this->apiUrl('items') . '?' . http_build_query($query)));
        if (!is_array($data) || empty($data['ok']) || !is_array($data['items'] ?? null)) {
            // The store answered but refused: keep its reason for the screen.
            $reason = is_array($data) ? trim((string) ($data['reason'] ?? '')) : '';
            if ($reason !== '') {
                $this->catalogReason = $reason;
            }
            return null;
        }

        $this->catalogReason = '';

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'];

        return [
            'tabs'     => is_array($data['tabs'] ?? null) ? array_values($data['tabs']) : [],
            'open_tab' => (string) ($data['open_tab'] ?? ''),
            'items'    => $items,
            'total'    => (int) ($data['total'] ?? 0),
            'page'     => (int) ($data['page'] ?? $page),
            'per_page' => (int) ($data['per_page'] ?? $perPage),
            'pages'    => max(1, (int) ($data['pages'] ?? 1)),
        ];
    }

    /**
     * Why the catalog could not be shown: the store's own reason when it
     * answered (maintenance, throttle), otherwise the unreachable line.
     */
    protected function catalogFailureReason(): string
    {
        return $this->catalogReason !== ''
            ? $this->catalogReason
            : 'The store could not be reached. Try again in a minute.';
    }

    /**
     * An item description from the store, sanitized.
     *
     * Descriptions arrive as HTML. When HTMLPurifier is unavailable, strip
     * tags rather than return the raw input, the same fallback BlogService
     * and CommentService use.
     */
    protected function purifyDescription(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        if (!class_exists(\HTMLPurifier_Config::class)) {
            return strip_tags($html);
        }
        return (new \HTMLPurifier(HtmlPurifierFactory::create()))->purify($html);
    }

    // -----------------------------------------------------------------
    // Cart
    // -----------------------------------------------------------------

    /**
     * Push a product into the account's cart at the store.
     *
     * The cart line carries the update tier the buyer picked; the store
     * prices and licenses from that.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function addToCart(int $productId, string $tier, string $currency = 'USD'): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        if (!in_array($tier, ['patch', 'minor', 'major'], true)) {
            return ['ok' => false, 'reason' => 'Choose an update tier first.'];
        }
        $body = $this->httpPostJson($this->apiUrl('cart/add'), [
            'product_id' => $productId,
            'tier'       => $tier,
            'currency'   => $currency,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not update your cart.'];
        }
        return ['ok' => !empty($data['ok']), 'reason' => (string) ($data['reason'] ?? '')];
    }

    /**
     * The account's cart as the store has it: lines, totals, currency.
     *
     * @return array<string, mixed>
     */
    public function cart(): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.', 'items' => []];
        }
        $data = $this->decode($this->httpGet($this->apiUrl('cart')));
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store did not answer.', 'items' => []];
        }

        return $data;
    }

    /**
     * Remove one line from the store's cart. The line must belong to the
     * connected account, which the store checks.
     *
     * @return array<string, mixed>
     */
    public function removeCartItem(int $cartItemId): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $body = $this->httpPostJson($this->apiUrl('cart/remove'), ['cart_item_id' => $cartItemId]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not update your cart.'];
        }

        return ['ok' => !empty($data['ok']), 'reason' => (string) ($data['reason'] ?? '')];
    }

    /**
     * The store URL that opens checkout for the account's cart.
     */
    public function checkoutUrl(): string
    {
        return rtrim((string) ($this->config['store_url'] ?? ''), '/') . '/store/checkout';
    }

    // -----------------------------------------------------------------
    // Purchases / verification
    // -----------------------------------------------------------------

    /**
     * Verify which owned products license this domain and reconcile the local
     * marketplace_installs against the store's answer.
     *
     * @return array{ok: bool, reason: string, purchases: array<int, array<string, mixed>>}
     */
    public function verifyPurchases(): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.', 'purchases' => []];
        }
        $domain = $this->siteDomain();
        $url = $this->apiUrl('purchases') . '?domain=' . urlencode($domain);
        $data = $this->decode($this->httpGet($url));
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not be reached. Try again in a little while.', 'purchases' => []];
        }
        if (empty($data['ok']) || !is_array($data['purchases'])) {
            // The store's own reason wins: a maintenance message says more
            // than "unreachable".
            return [
                'ok'        => false,
                'reason'    => (string) ($data['reason'] ?? 'The store could not be reached. Try again in a little while.'),
                'purchases' => [],
            ];
        }

        /** @var array<int, array<string, mixed>> $purchases */
        $purchases = $data['purchases'];
        $this->reconcileInstalls($purchases);

        return ['ok' => true, 'reason' => '', 'purchases' => $purchases];
    }

    /**
     * Serialized purchase records the store confirms for this domain.
     *
     * An empty array covers both "nothing owned" and "store unreachable".
     * Callers that must tell those apart use verifyPurchases().
     *
     * @return array<int, array<string, mixed>> Serialized purchase records.
     */
    public function purchases(): array
    {
        return $this->verifyPurchases()['purchases'];
    }

    /**
     * Upsert local install records from a store purchases response.
     *
     * @param array<int, array<string, mixed>> $purchases
     */
    protected function reconcileInstalls(array $purchases): void
    {
        $now = date('Y-m-d H:i:s');

        // One read for the whole sync: per-purchase lookups cost a query per
        // record on every Purchases load.
        $byLicense = [];
        foreach ((new MarketplaceInstall($this->pdo))->allTracked() as $tracked) {
            $byLicense[(string) ($tracked->license_key ?? '')] = $tracked;
        }

        // Records whose synced values did not change keep everything but
        // the timestamps; one bulk UPDATE handles those.
        $stampKeys = [];

        foreach ($purchases as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            // Install records exist for installable addons only; the store
            // names the type, and anything else is not one.
            $type = (string) ($p['item_type'] ?? '');
            if (!in_array($type, ['plugin', 'theme'], true)) {
                continue;
            }
            // One record per purchase, keyed by license: a buyer can own the
            // same product twice, and each purchase keeps its own row.
            $licenseKey = (string) ($p['license_key'] ?? '');
            if ($licenseKey === '') {
                continue;
            }

            $fields = [
                'product_name'      => (string) ($p['name'] ?? ''),
                'slug'              => (string) ($p['slug'] ?? ''),
                'tier'              => in_array($p['tier'] ?? '', ['patch', 'minor', 'major'], true) ? (string) $p['tier'] : null,
                'license_valid'     => !empty($p['licensed']) ? 1 : 0,
                'registered_domain' => (string) ($p['registered_domain'] ?? ''),
            ];

            $row = $byLicense[$licenseKey] ?? null;
            if ($row === null) {
                // New record: the store names the type; folder stays empty
                // until the first install stamps it from the manifest.
                $model = new MarketplaceInstall($this->pdo);
                $model->product_name = $fields['product_name'];
                $model->slug = $fields['slug'];
                $model->license_key = $licenseKey;
                $model->tier = $fields['tier'];
                $model->license_valid = $fields['license_valid'];
                $model->license_last_checked = $now;
                $model->registered_domain = $fields['registered_domain'];
                $model->item_type = $type;
                $model->folder = '';
                $model->store_product_id = $pid;
                $model->created_at = $now;
                $model->updated_at = $now;
                $model->insert();
                continue;
            }

            $changed = (string) ($row->product_name ?? '') !== $fields['product_name']
                || (string) ($row->slug ?? '') !== $fields['slug']
                || (string) ($row->tier ?? '') !== (string) ($fields['tier'] ?? '')
                || (int) $row->license_valid !== (int) $fields['license_valid']
                || (string) ($row->registered_domain ?? '') !== $fields['registered_domain'];

            // item_type/folder are NOT synced from the store payload: they
            // were stamped from the package manifest at install time and the
            // manifest is the source of truth. Backfill the identity from
            // the local manifest for records installed before package_id
            // existed.
            if ((string) ($row->package_id ?? '') === '') {
                $identity = $this->manifestIdentity((string) $row->item_type, (string) ($row->folder ?? ''));
                if ($identity !== null) {
                    $row->package_id = $identity['package'];
                    $changed = true;
                }
            }

            if (!$changed) {
                $stampKeys[] = $licenseKey;
                continue;
            }

            $row->product_name = $fields['product_name'];
            $row->slug = $fields['slug'];
            $row->tier = $fields['tier'];
            $row->license_valid = $fields['license_valid'];
            $row->registered_domain = $fields['registered_domain'];
            $row->license_last_checked = $now;
            $row->updated_at = $now;
            $row->save();
        }

        (new MarketplaceInstall($this->pdo))->stampVerified($stampKeys, $now);
    }

    /**
     * Local install records, serialized for the admin Purchases view.
     *
     * The installed version is read live from the addon's manifest, never
     * from the stored copy, so what the view shows is what is on disk.
     *
     * With $checkStore the store is asked per record whether a download is
     * available (the same claim=0 check the release picker uses), and the
     * answer rides along: a record with nothing downloadable gets no live
     * install control. The Purchases page always checks; the dashboard
     * leaves it off so a card render costs no store calls.
     *
     * @return array<int, array<string, mixed>>
     */
    public function localInstallRecords(bool $checkStore = false): array
    {
        $rows = (new MarketplaceInstall($this->pdo))->allTracked();
        $out = [];
        foreach ($rows as $r) {
            // The version comes from the addon itself, the same live
            // manifest read the catalog and the Updates plugin use. A copy
            // replaced outside Marketplace still shows the truth here.
            $identity = $this->manifestIdentity((string) $r->item_type, (string) ($r->folder ?? ''));
            $record = [
                'id'                => (int) $r->id,
                'store_product_id'  => (int) $r->store_product_id,
                'package'           => (string) ($r->package_id ?? ''),
                'product_name'      => (string) $r->product_name,
                'item_type'         => (string) $r->item_type,
                'folder'            => (string) ($r->folder ?? ''),
                'installed_version' => $identity !== null ? (string) $identity['version'] : '',
                'installed'         => $this->isInstalled((string) $r->item_type, (string) $r->folder),
                'license_valid'     => (int) $r->license_valid === 1,
                // Assigned means assigned anywhere, not just here.
                'assigned'          => trim((string) ($r->registered_domain ?? '')) !== '',
                'tier'              => (string) ($r->tier ?? ''),
            ];
            if ($checkStore) {
                $check = $this->releasesForRecord($r);
                $record['releases'] = $check['releases'];
                $record['default_version'] = (string) $check['version'];
                $record['can_install'] = !empty($check['ok']) && $check['releases'] !== [];
                $record['install_note'] = !empty($check['ok']) ? '' : (string) $check['reason'];
            }
            $out[] = $record;
        }

        // A second record for a product already installed by another record
        // (same store product, one license each) must not offer an install:
        // the folder is on disk, and the click would only abort.
        $installedProducts = [];
        foreach ($out as $record) {
            if (!empty($record['installed'])) {
                $installedProducts[(int) $record['store_product_id']] = true;
            }
        }
        foreach (array_keys($out) as $i) {
            $out[$i]['already_installed'] = empty($out[$i]['installed'])
                && isset($installedProducts[(int) $out[$i]['store_product_id']]);
        }

        return $out;
    }

    public function installRecordForProduct(int $storeProductId): ?MarketplaceInstall
    {
        return (new MarketplaceInstall($this->pdo))->findByProductId($storeProductId);
    }

    public function installRecordById(int $recordId): ?MarketplaceInstall
    {
        return (new MarketplaceInstall($this->pdo))->findById($recordId);
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
     * The version on disk is read live from the manifest, so this never
     * trusts stored DB copies. A licensed record is answered by its own
     * license: the claim=0 store check names the newest release this site
     * may run (newest covered, fitting its Pubvana version), so an update
     * shows even when the store catalog does not list the item. Records
     * with no license keep the free path: the catalog's version is the
     * update answer.
     *
     * The catalog is fetched lazily, only when a free record needs it.
     * When it is loaded, licensed rows also pick up its changelog, and a
     * catalog newest above the purchased tier becomes upgrade_available
     * with a link to the store item rather than an update the tier does
     * not cover.
     *
     * @return array<string, array{item_type: string, latest_version: string, changelog: string|null, upgrade_available: bool, upgrade_url: string}>
     */
    public function checkAddonUpdates(): array
    {
        if (!$this->withToken()) {
            return [];
        }

        // Package-keyed catalog, fetched on the first free record and
        // reused for the rest of the pass.
        $catalogByPackage = null;

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

            $licensed = (string) ($record->license_key ?? '') !== '';
            $item     = null;

            if ($licensed) {
                // The license answers: which release this site may run.
                // No catalog involved, so an unlisted item still gets its
                // update, and a refusal or an answerless reply offers
                // nothing.
                $capped = $this->releasesForRecord($record);
                if (empty($capped['ok']) || (string) $capped['version'] === '') {
                    continue;
                }
                $allowed = (string) $capped['version'];

                // Decorations apply only when a free record already had the
                // catalog fetched in this pass.
                $item = is_array($catalogByPackage) ? ($catalogByPackage[$identity['package']] ?? null) : null;
            } else {
                // Free: no license to ask, so the catalog's version is the
                // update answer.
                if ($catalogByPackage === null) {
                    $catalogByPackage = $this->catalogByPackage();
                }
                $item = $catalogByPackage[$identity['package']] ?? null;
                $allowed = $item !== null ? (string) ($item['version'] ?? $item['semver'] ?? '') : '';
                if ($allowed === '') {
                    continue;
                }
            }

            $changelog = $item !== null && isset($item['changelog']) && is_string($item['changelog'])
                ? $item['changelog']
                : null;

            // A licensed tier can sit below the store's newest: that link
            // is a tier upgrade, not an update this site may run.
            $upgradeUrl = '';
            if ($licensed && $item !== null) {
                $catalogVersion = (string) ($item['version'] ?? $item['semver'] ?? '');
                if ($catalogVersion !== '' && version_compare($catalogVersion, $allowed, '>')) {
                    $upgradeUrl = (string) ($item['url'] ?? '');
                }
            }

            if (version_compare($allowed, $identity['version'], '>')) {
                $updates[$identity['package']] = [
                    'item_type'         => $itemType,
                    'latest_version'    => $allowed,
                    'changelog'         => $changelog,
                    'upgrade_available' => $upgradeUrl !== '',
                    'upgrade_url'       => $upgradeUrl,
                ];
            } elseif ($upgradeUrl !== '') {
                // Nothing inside the tier is newer, but the store has more:
                // a tier upgrade is the only way forward.
                $updates[$identity['package']] = [
                    'item_type'         => $itemType,
                    'latest_version'    => (string) $identity['version'],
                    'changelog'         => null,
                    'upgrade_available' => true,
                    'upgrade_url'       => $upgradeUrl,
                ];
            }
        }

        return $updates;
    }

    /**
     * The store catalog keyed by package identity, [] when the store
     * cannot be reached. The store's `package` field is the manifest
     * package id ('pubvana/blog'); older store payloads keyed on `slug`.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function catalogByPackage(): array
    {
        $byPackage = [];
        foreach ($this->items() as $item) {
            $package = (string) ($item['package'] ?? $item['slug'] ?? $item['name'] ?? '');
            if ($package !== '' && !isset($byPackage[$package])) {
                $byPackage[$package] = $item;
            }
        }

        return $byPackage;
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
     * (scope none), owned elsewhere, or copied without a purchase. Only
     * the store's license answer (the install record, from the verify
     * flow) resolves it. Free items are reported separately
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
                'free' => !empty($item['is_free']) || !empty($item['free_tier']),
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
        // A store record with no identity at all is a catalogue problem, not
        // a malformed id from a caller. Say which it is.
        if ($packageId === '') {
            return [
                'ok'      => false,
                'reason'  => 'The store listed this item without a package identity, so it cannot be installed from here.',
                'version' => null,
            ];
        }
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

        // The version comes from the addon on disk, the same live read
        // used everywhere else.
        $identity = $fresh !== null
            ? $this->manifestIdentity((string) $fresh->item_type, (string) ($fresh->folder ?? ''))
            : null;

        return [
            'ok'      => true,
            'reason'  => $result['reason'],
            'version' => $identity['version'] ?? null,
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
            && (!empty($item['is_free']) || !empty($item['free_tier']));

        if ($item === null || !$isFree) {
            return ['ok' => false, 'reason' => 'This package is not a verified purchase. Verify purchases first.', 'version' => null];
        }

        // Type and folder come out of the package manifest inside
        // installPackage(); the store payload no longer carries them.
        // A re-download replaces only when this item is already tracked
        // as installed; otherwise an existing folder makes it refuse.
        $existing = (new MarketplaceInstall($this->pdo))->findByProductId((int) ($item['id'] ?? 0));
        $replace = $existing !== null && trim((string) ($existing->folder ?? '')) !== '';
        $result = $this->installPackage($this->apiUrl('free') . '?slug=' . urlencode($package), $replace);
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
            $fresh->license_key      = null;
            $fresh->license_valid    = 1;
            $fresh->license_last_checked = $now;
            $fresh->registered_domain = $this->siteDomain();
            $fresh->created_at = $now;
            $fresh->updated_at = $now;
            $fresh->insert();
            return;
        }

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
            $isFree = !empty($item['is_free']) || !empty($item['free_tier']);
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
     * Assign this site's domain to a purchased license, without installing.
     *
     * The same validate call an install makes (claim=1), minus the package:
     * the store claims the domain, and the local record is stamped so the
     * Purchases list shows the row as assigned on its next render.
     *
     * @return array<string, mixed>
     */
    public function assignToSite(int $storeProductId): array
    {
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null) {
            return ['ok' => false, 'reason' => 'No license to assign for this item.'];
        }

        return $this->assignRecord($record);
    }

    /**
     * Assign one record's own license to this site, whatever other licenses
     * for the same product exist.
     *
     * @return array<string, mixed>
     */
    public function assignByRecordId(int $recordId): array
    {
        $record = $this->installRecordById($recordId);
        if ($record === null) {
            return ['ok' => false, 'reason' => 'Unknown purchase. Sync with the store first.'];
        }

        return $this->assignRecord($record);
    }

    /**
     * The claim itself: the same validate call an install makes (claim=1),
     * minus the package.
     *
     * @return array<string, mixed>
     */
    protected function assignRecord(MarketplaceInstall $record): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        if ((string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'No license to assign for this item.'];
        }
        $body = $this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
            'pubvana'     => $this->sitePubvanaVersion(),
            'claim'       => '1',
        ]);
        $data = $this->decode($body);
        if (!is_array($data) || empty($data['ok'])) {
            return [
                'ok'     => false,
                'reason' => is_array($data)
                    ? (string) ($data['reason'] ?? 'The store could not assign this license.')
                    : 'The store could not be reached. Try again in a few minutes.',
            ];
        }
        $record->license_valid = 1;
        $record->registered_domain = $this->siteDomain();
        $record->license_last_checked = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Validate a purchase against the store for this domain and install it.
     * The package type comes from the package manifest at install time.
     *
     * @return array{ok: bool, reason: string}
     */
    public function install(int $storeProductId, string $version = ''): array
    {
        $record = $this->installRecordForProduct($storeProductId);

        return $record === null
            ? ['ok' => false, 'reason' => 'Unknown purchase. Verify your purchases first.']
            : $this->installRecord($record, $version);
    }

    /**
     * @return array{ok: bool, reason: string}
     */
    public function installByRecordId(int $recordId, string $version = '', bool $clearDatabase = false): array
    {
        $record = $this->installRecordById($recordId);

        return $record === null
            ? ['ok' => false, 'reason' => 'Unknown purchase. Verify your purchases first.']
            : $this->installRecord($record, $version, $clearDatabase);
    }

    /**
     * Validate one record's own license for this domain and install it.
     *
     * With $clearDatabase the addon's migration module is purged first (its
     * down() migrations run, its tracking rows go), then rebuilt fresh from
     * the package that replaces it.
     *
     * @return array{ok: bool, reason: string}
     */
    protected function installRecord(MarketplaceInstall $record, string $version = '', bool $clearDatabase = false): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        if ((string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'This item has no license to validate.'];
        }

        // The store picks the release: newest inside the tier that fits this
        // site's Pubvana version, or the version the buyer chose.
        $body = $this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
            'pubvana'     => $this->sitePubvanaVersion(),
            'version'     => $version,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not be reached. Try again in a few minutes.'];
        }
        if (empty($data['ok']) || empty($data['download_url'])) {
            return ['ok' => false, 'reason' => (string) ($data['reason'] ?? 'Validation failed.')];
        }

        // Type and folder come out of the package manifest inside
        // installPackage(); the stored record's folder is not consulted.
        // A reinstall replaces the folder; a first install refuses to
        // land on one that already exists.
        $replace = trim((string) ($record->folder ?? '')) !== '';

        // The backup and the database purge run inside installPackage once
        // the package passes its checks, so an install that aborts never
        // takes one. The purge runs after the backup and before files are
        // replaced, so its down() migrations come from the old files.
        $beforeReplace = $clearDatabase
            ? fn (): array => $this->purgeRecordModule($record)
            : null;
        $result = $this->installPackage((string) $data['download_url'], $replace, $beforeReplace);
        if (!$result['ok']) {
            return ['ok' => false, 'reason' => $result['reason']];
        }

        // Identity comes from the freshly installed manifest.
        $record->item_type = $result['type'];
        $record->folder = $result['folder'];
        $record->package_id = $result['package'];
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        if ($clearDatabase) {
            $rebuild = $this->rebuildRecordModule($record, $result['version']);
            if (!$rebuild['ok']) {
                return ['ok' => false, 'reason' => $rebuild['reason']];
            }
        }

        return ['ok' => true, 'reason' => 'Installed.'];
    }

    /**
     * The migration module name for an installed record ('plugins/Blog'),
     * or null when there is no folder to point at.
     */
    protected function migrationModule(MarketplaceInstall $record): ?string
    {
        $folder = trim((string) ($record->folder ?? ''));
        if ($folder === '') {
            return null;
        }

        return ((string) $record->item_type === 'theme' ? 'themes/' : 'plugins/') . $folder;
    }

    /**
     * Whether a module ships a Database/Migrations directory.
     */
    protected function moduleHasMigrations(string $module): bool
    {
        return is_dir(
            PROJECT_ROOT . \DIRECTORY_SEPARATOR
            . str_replace('/', \DIRECTORY_SEPARATOR, $module)
            . \DIRECTORY_SEPARATOR . 'Database' . \DIRECTORY_SEPARATOR . 'Migrations'
        );
    }

    /**
     * A migration runner scoped to one addon module: its Database/Migrations
     * and Database/Seeds paths, nothing else.
     */
    protected function migrationSetupFor(string $module, string $version = ''): MigrationSetup
    {
        return new MigrationSetup([
            'migrations' => [
                'path_mode' => 'replace',
                'paths'     => [$module . '/Database/Migrations'],
                'seeds'     => ['paths' => [$module . '/Database/Seeds']],
                'versions'  => $version !== '' ? [$module => $version] : [],
            ],
        ], PROJECT_ROOT);
    }

    /**
     * Drop an installed addon's tables: every recorded migration's down()
     * runs, its tracking rows go, and the module is left as if it had never
     * been migrated, so the incoming package can build fresh.
     *
     * @return array{ok: bool, reason: string}
     */
    protected function purgeRecordModule(MarketplaceInstall $record): array
    {
        $module = $this->migrationModule($record);
        if ($module === null || !$this->moduleHasMigrations($module)) {
            return ['ok' => true, 'reason' => ''];
        }
        if (!class_exists(MigrationSetup::class)) {
            return ['ok' => false, 'reason' => 'The migrations package is not available.'];
        }

        try {
            $results = $this->migrationSetupFor($module)->purgeModule($module);
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'Could not clear the database: ' . $e->getMessage()];
        }

        foreach ($results as $result) {
            if (!$result->isSuccess()) {
                return ['ok' => false, 'reason' => 'Could not clear the database: ' . ($result->getMessage() ?? 'a migration failed.')];
            }
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Rebuild a cleared addon from the freshly installed package: pending
     * migrations run and seeds replay, the same as a first install.
     *
     * @return array{ok: bool, reason: string}
     */
    protected function rebuildRecordModule(MarketplaceInstall $record, string $version = ''): array
    {
        $module = $this->migrationModule($record);
        if ($module === null || !$this->moduleHasMigrations($module)) {
            return ['ok' => true, 'reason' => ''];
        }
        if (!class_exists(MigrationSetup::class)) {
            return ['ok' => false, 'reason' => 'The migrations package is not available.'];
        }

        try {
            $results = $this->migrationSetupFor($module, $version)->runMigrate($module);
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'The files were installed, but rebuilding its database failed: ' . $e->getMessage()];
        }

        foreach ($results as $result) {
            if (!$result->isSuccess()) {
                return ['ok' => false, 'reason' => 'The files were installed, but rebuilding its database failed: ' . ($result->getMessage() ?? 'a migration failed.')];
            }
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Take a full backup before an install touches files or tables.
     *
     * The same rule the Updates plugin applies before a core update: a
     * backup failure aborts, and nothing is changed. Restores go through
     * the Backups plugin.
     *
     * @return array{ok: bool, reason: string}
     */
    protected function spawnBackup(): array
    {
        try {
            $backups = $this->app->backups();
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'The Backups plugin is not available, so nothing was installed.'];
        }

        $lock = new BackupProgressReporter('backup', rtrim($backups->getBackupDir(), '/'));
        if (!$lock->acquireLock()) {
            return ['ok' => false, 'reason' => 'A backup or restore operation is already running. Try again when it finishes.'];
        }

        try {
            $backups->createBackup('pre-install', 'marketplace');
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'The pre-install backup failed, so nothing was installed: ' . $e->getMessage()];
        } finally {
            $lock->releaseLock();
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * The releases the buyer's tier covers for this site, newest fitting
     * first, for the install screen's release picker. Nothing is downloaded
     * here.
     *
     * @return array{ok: bool, reason: string, version: string, releases: list<array<string, mixed>>}
     */
    public function installReleases(int $storeProductId): array
    {
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null) {
            return ['ok' => false, 'reason' => 'No license to check for this item.', 'version' => '', 'releases' => []];
        }

        return $this->releasesForRecord($record);
    }

    /**
     * The covered releases for one record's own license: the same claim=0
     * check the install screen shows, so a row answers for itself even when
     * another license for the same product exists.
     *
     * @return array{ok: bool, reason: string, version: string, releases: list<array<string, mixed>>}
     */
    protected function releasesForRecord(MarketplaceInstall $record): array
    {
        $fail = fn (string $reason): array => ['ok' => false, 'reason' => $reason, 'version' => '', 'releases' => []];
        if (!$this->withToken()) {
            return $fail('Not connected to a Pubvana account.');
        }
        if ((string) $record->license_key === '') {
            return $fail('No license to check for this item.');
        }

        // The picker asks with claim=0: it lists what the tier covers
        // without claiming the domain or minting a token. The install itself
        // does the claiming.
        $body = $this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
            'pubvana'     => $this->sitePubvanaVersion(),
            'claim'       => '0',
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return $fail('The store could not be reached. Try again in a few minutes.');
        }
        if (empty($data['ok'])) {
            // Refusal replies carry the newest covered version so the
            // purchases row can still show one.
            return [
                'ok'       => false,
                'reason'   => (string) ($data['reason'] ?? 'Validation failed.'),
                'version'  => (string) ($data['version'] ?? ''),
                'releases' => [],
            ];
        }

        return [
            'ok'       => true,
            'reason'   => '',
            'version'  => (string) ($data['version'] ?? ''),
            'releases' => is_array($data['releases'] ?? null) ? array_values($data['releases']) : [],
        ];
    }

    /**
     * Whether the store says this site may run the product right now: the
     * license is live, and either assigned here (subdomains included) or
     * not yet assigned (the first download assigns it). It never assigns
     * anything itself; this is the activation gate's question.
     *
     * @return array{ok: bool, reason: string, reachable: bool}
     */
    public function verifyLicenseForSite(int $storeProductId): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.', 'reachable' => false];
        }
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null || (string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'No license found for this site.', 'reachable' => true];
        }

        $data = $this->decode($this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
            'claim'       => '0',
        ]));
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not be reached.', 'reachable' => false];
        }

        return [
            'ok'        => !empty($data['ok']),
            'reason'    => (string) ($data['reason'] ?? ''),
            'reachable' => true,
        ];
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
     * Download, verify, and install a package zip. The package's own
     * manifest is the single source of truth: type (plugin/theme), install
     * folder, package id, and version all come out of the extracted
     * pubvana.json, never from stored metadata.
     *
     * The package lands in the folder its own manifest names. When that
     * folder already exists the install refuses, unless $replace is set
     * (a reinstall of an addon this record already installed).
     *
     * Every abort check runs before anything destructive: the download,
     * zip, manifest, trust, and folder checks all pass first, then a full
     * backup is taken, then $beforeReplace runs (the caller's purge step),
     * then the folder is replaced.
     *
     * @param bool            $replace       Replace an existing folder
     * @param (callable(): array{ok: bool, reason: string})|null $beforeReplace  Runs after the backup, before the folder is touched
     * @return array{ok: bool, reason: string, type: string, folder: string, package: string, version: string}
     */
    protected function installPackage(string $downloadUrl, bool $replace = false, ?callable $beforeReplace = null): array
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

        $zipData = $this->httpGet($downloadUrl, $this->byteCap(null, 'max_zip_bytes', self::MAX_ZIP_BYTES));
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
        if (!$this->prepareExtractDir($extractPath)) {
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

        // Identity is known and nothing is written yet: the trust check runs
        // here, so a malicious package never reaches the addon folder.
        $refusal = $this->maliciousRefusal($info);
        if ($refusal !== null) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail($refusal);
        }

        $destRoot = PROJECT_ROOT . \DIRECTORY_SEPARATOR . ($type === 'theme' ? 'themes' : 'plugins');
        $destDir = $destRoot . \DIRECTORY_SEPARATOR . $folder;
        if (is_dir($destDir) && !$replace) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail(($type === 'theme' ? 'themes/' : 'plugins/') . $folder . ' exists. Install aborted.');
        }

        // Every abort check has passed. The backup runs now, not earlier,
        // so an install that refuses never takes one.
        $backup = $this->spawnBackup();
        if (!$backup['ok']) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return $fail($backup['reason']);
        }

        if ($beforeReplace !== null) {
            $preReplace = $beforeReplace();
            if (empty($preReplace['ok'])) {
                $this->rmdir($extractPath);
                @unlink($zipPath);
                return $fail((string) $preReplace['reason']);
            }
        }

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

    /**
     * Refuse a package the trust service has marked malicious.
     *
     * Every install path ends in installPackage(): paid installs, free
     * installs, and the Updates screen's one-click addon update. The check
     * runs after pubvana.json is read and before a file is copied, so a
     * malicious package never reaches the plugins or themes folder.
     *
     * A trust call that cannot be answered is not a verdict, so an outage
     * does not stop an install.
     *
     * @param array<string, mixed> $info Decoded pubvana.json from the package
     * @return string|null The refusal sentence, or null to proceed
     */
    private function maliciousRefusal(array $info): ?string
    {
        try {
            $client = $this->app->trustClient();
            $item = $client->packageItem($info);
            if ($item === null) {
                return null;
            }

            $cached = $client->getCachedStatus($item['type'], $item['slug'], $item['version'], $item['author']);
            if ($cached !== null) {
                $status  = $cached['status'];
                $warning = $cached['warning'];
            } else {
                $answer  = $client->checkAddon($item['type'], $item['slug'], $item['version'], $item['author'], $item['origin']);
                $status  = $answer['status'];
                $warning = $answer['warning'];
            }
        } catch (\Throwable) {
            return null;
        }

        if ($status !== TrustCache::STATUS_MALICIOUS) {
            return null;
        }

        return 'The Pubvana trust service found this package to be malicious'
            . ($warning !== null && $warning !== '' ? ': ' . $warning : '.');
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

    /**
     * An empty extraction directory. Anything an earlier run left behind is
     * removed first, so a package can never pick up stale files from the
     * previous extraction.
     */
    protected function prepareExtractDir(string $dir): bool
    {
        if (is_dir($dir) && !$this->rmdir($dir)) {
            return false;
        }

        return mkdir($dir, 0755, true) || is_dir($dir);
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
    // Domain check
    // -----------------------------------------------------------------

    /**
     * Whether a license assigned to a different domain blocks the install.
     * One purchase is one site: every license carries a domain, and a
     * mismatch means this site is not the license's site.
     */
    public function needsDomainMove(int $storeProductId): bool
    {
        $record = $this->installRecordForProduct($storeProductId);

        return $record !== null && $this->needsDomainMoveForRecord($record);
    }

    public function needsDomainMoveForRecord(MarketplaceInstall $record): bool
    {
        if ((string) $record->license_key === '') {
            return false;
        }
        // Unassigned licenses have nothing to move; the install claims them.
        $assigned = trim((string) ($record->registered_domain ?? ''));

        return $assigned !== '' && $assigned !== $this->siteDomain();
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
     * @param int|null  $maxBytes Response-size cap; null uses the max_bytes config key.
     */
    protected function httpPostJson(string $url, array $payload, ?int $maxBytes = null): ?string
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
        $cap = $this->byteCap($maxBytes, 'max_bytes', self::MAX_BODY_BYTES);

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                $received = 0;
                $body = '';
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $data,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                    CURLOPT_WRITEFUNCTION  => function (mixed $handle, string $chunk) use (&$received, &$body, $cap): int {
                        $received += strlen($chunk);
                        if ($received > $cap) {
                            return 0; // Abort: the body exceeds the cap.
                        }
                        $body .= $chunk;
                        return strlen($chunk);
                    },
                ]);
                $exec = curl_exec($handle);
                curl_close($handle);
                // With a write callback, success returns true (the body is
                // accumulated above); anything else is a network error or
                // the cap abort (CURLE_WRITE_ERROR).
                return $exec === true && $body !== '' ? $body : null;
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
        return $this->streamBodyWithCap($url, $context, $cap)['body'];
    }

    protected function httpGet(string $url, ?int $maxBytes = null): ?string
    {
        $timeout = (int) ($this->config['api_timeout'] ?? 10);
        $headers = $this->connected() ? ['Authorization: Bearer ' . (string) $this->app->settings()->get('Marketplace.account_token')] : [];
        $cap = $this->byteCap($maxBytes, 'max_bytes', self::MAX_BODY_BYTES);

        $current = $url;
        for ($hops = 0; $hops <= self::MAX_REDIRECTS; $hops++) {
            if (!$this->isAllowedStoreUrl($current)) {
                return null;
            }
            $response = $this->httpFetchOnce($current, $headers, $timeout, $cap);
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
     * @param int|null  $maxBytes Response-size cap; null uses the max_bytes config key.
     *
     * @return array{body: ?string, status: int, location: ?string}|null
     */
    protected function httpFetchOnce(string $url, array $headers, int $timeout, ?int $maxBytes = null): ?array
    {
        $cap = $this->byteCap($maxBytes, 'max_bytes', self::MAX_BODY_BYTES);

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                $received = 0;
                $body = '';
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_WRITEFUNCTION  => function (mixed $handle, string $chunk) use (&$received, &$body, $cap): int {
                        $received += strlen($chunk);
                        if ($received > $cap) {
                            return 0; // Abort: the body exceeds the cap.
                        }
                        $body .= $chunk;
                        return strlen($chunk);
                    },
                ]);
                $exec = curl_exec($handle);
                if ($exec !== true) {
                    curl_close($handle);
                    return null;
                }
                curl_close($handle);
                $redirect = curl_getinfo($handle, CURLINFO_REDIRECT_URL);
                return [
                    'body'     => $body !== '' ? $body : null,
                    'status'   => curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                    'location' => is_string($redirect) && $redirect !== '' ? $redirect : null,
                ];
            }
        }

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

        return $this->streamBodyWithCap($url, $context, $cap);
    }

    /**
     * Read a response body over the stream transport with a byte cap.
     * file_get_contents() cannot cap, so the body is drained with fread and
     * the transfer is abandoned (null body) once the cap is exceeded.
     * Header parsing mirrors httpFetchOnce()'s stream tail.
     *
     * @param resource $context
     *
     * @return array{body: ?string, status: int, location: ?string}
     */
    private function streamBodyWithCap(string $url, $context, int $cap): array
    {
        $http_response_header = [];
        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) {
            return ['body' => null, 'status' => 0, 'location' => null];
        }

        $body = '';
        $received = 0;
        $overCap = false;
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $received += strlen($chunk);
            if ($received > $cap) {
                $overCap = true;
                break;
            }
            $body .= $chunk;
        }
        fclose($stream);

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
            'body'     => (!$overCap && $body !== '') ? $body : null,
            'status'   => $status,
            'location' => $location,
        ];
    }

    /**
     * The response-size cap in bytes: an explicit value wins, else the
     * named config key, else the hard default. Floored at 1KB so a
     * misconfigured value cannot choke small responses.
     */
    private function byteCap(?int $maxBytes, string $configKey, int $default): int
    {
        $value = $maxBytes ?? (int) ($this->config[$configKey] ?? $default);

        return max(1024, $value);
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

    /**
     * An item image path from the store as a full URL on the store host.
     *
     * The store sends `/uploads/...` paths. Anything that does not resolve
     * to the store host comes back empty, so the view renders no image.
     */
    protected function storeAssetUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $url = $this->resolveHttpUrl($this->apiUrl('items'), $path);

        return $url !== null && $this->isAllowedStoreUrl($url) ? $url : '';
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

    /**
     * This site's bare domain, as sent to the store for license lookup.
     *
     * Built on the SITE_URL deployment value (the same value
     * UrlService::siteOrigin() reads), never the request Host header.
     * Port, path, and subdomains are dropped: a license covers the whole
     * domain, so staging. and shop. subdomains use the same one.
     *
     * @return non-empty-string
     */
    public function siteDomain(): string
    {
        $siteUrl = trim((string) ($this->app->get('siteUrl') ?? ''));
        if ($siteUrl === '') {
            $siteUrl = 'http://localhost';
        }
        if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $siteUrl)) {
            $siteUrl = 'http://' . $siteUrl;
        }

        $host = strtolower(trim((string) (parse_url($siteUrl, PHP_URL_HOST) ?? '')));
        $host = (string) preg_replace('#^www\.#', '', $host);

        // Keep domain and TLD only: subdomains share one license, so
        // staging.example.com and example.com register as one site. A
        // multipart TLD (example.co.uk) keeps its extra label.
        $labels = explode('.', $host);
        $count = count($labels);
        if ($count > 2) {
            $tld = $labels[$count - 1];
            $secondLevel = $labels[$count - 2];
            $multipart = strlen($tld) === 2
                && in_array($secondLevel, ['ac', 'co', 'com', 'edu', 'firm', 'gen', 'gov', 'id', 'mil', 'ne', 'net', 'nom', 'or', 'org'], true);
            $host = implode('.', array_slice($labels, $multipart ? -3 : -2));
        }

        if ($host === '') {
            $host = 'localhost';
        }

        return $host;
    }
}
