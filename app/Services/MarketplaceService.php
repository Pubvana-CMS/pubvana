<?php

namespace App\Services;

use App\Models\MarketplaceLicenseModel;

class MarketplaceService
{
    protected int    $cacheTtl = 3600; // 1 hour

    protected int    $revalidateDays = 90;
    protected int    $dailyCheckTtl  = 86400;   // 1 day
    protected string $dailyCacheKey  = 'license_due_check';

    private function isDevDomain(): bool
    {
        $host = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        return $host === 'localhost' || str_ends_with($host, '.local');
    }

    /**
     * Fetch categories-with-products from the DigitalStore API, with 1-hour cache.
     * Falls back to empty array if API is unreachable.
     */
    protected function fetchFromApi(): array
    {
        $cacheKey = 'marketplace_api_all';
        $cached   = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $url = PUBVANA_DSTORE_API . 'categories/all';

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($url, ['http_errors' => false]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                if (is_array($data)) {
                    cache()->save($cacheKey, $data, $this->cacheTtl);
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'MarketplaceService API unreachable: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Bust the marketplace API cache.
     */
    public function refreshCache(): void
    {
        cache()->delete('marketplace_api_all');
    }

    /**
     * Returns categories-with-products structure.
     * Derives item_type from category slug, constructs download_url.
     */
    public function fetchAll(): array
    {
        $categories = $this->fetchFromApi();

        foreach ($categories as &$cat) {
            $cat = (object) $cat;
            $products = [];
            foreach ($cat->products ?? [] as $p) {
                $p = (object) $p;
                $p->category_name = $cat->name ?? '';
                $p->category_slug = $cat->slug ?? '';
                $p->item_type = rtrim($cat->slug ?? '', 's'); // themes→theme, widgets→widget, plugins→plugin
                $p->installed_version = null;
                $p->download_url = PUBVANA_STORE_URL . 'dstore/downloads/' . $p->slug;
                $products[] = $p;
            }
            $cat->products = $products;
        }

        return $categories;
    }

    public function installFree(string $downloadUrl, string $type, string $folder, int $storeProductId = 0): bool
    {
        // Only allow downloads from pubvana.net
        $parsed = parse_url($downloadUrl);
        $host   = strtolower($parsed['host'] ?? '');
        if ($host !== 'pubvana.net' && ! str_ends_with($host, '.pubvana.net')) {
            log_message('warning', 'MarketplaceService: rejected download URL not from pubvana.net: ' . $downloadUrl);
            return false;
        }

        // Folder name must be safe (no path traversal)
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $folder)) {
            return false;
        }

        $tmpDir  = WRITEPATH . 'tmp/';
        $zipPath = $tmpDir . $folder . '.zip';
        if ($type === 'plugin') {
            $destDir = PLUGINS_PATH;
        } elseif ($type === 'theme') {
            $destDir = THEMES_PATH;
        } else {
            $destDir = WIDGETS_PATH;
        }

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zip = @file_get_contents($downloadUrl);
        if ($zip === false) {
            return false;
        }
        file_put_contents($zipPath, $zip);

        $archive = new \ZipArchive();
        if ($archive->open($zipPath) !== true) {
            @unlink($zipPath);
            return false;
        }

        // Reject ZIPs containing path traversal entries
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $entry = $archive->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                $archive->close();
                @unlink($zipPath);
                log_message('warning', 'MarketplaceService: ZIP entry contains path traversal: ' . $entry);
                return false;
            }
        }

        $archive->extractTo($destDir);
        $archive->close();
        @unlink($zipPath);

        if ($type === 'theme') {
            service('theme')->publishAssets($folder);
        }

        // Auto-discover new plugin so it appears in the plugins table
        if ($type === 'plugin') {
            \App\Services\PluginManager::instance()->discover();
        }

        $this->registerInstalled($type, $folder, $storeProductId);

        return true;
    }

    protected function registerInstalled(string $type, string $folder, int $storeProductId = 0): void
    {
        if ($type === 'plugin') {
            // PluginManager::discover() handles the DB upsert for plugins.
            // Just update store_product_id if we have one.
            if ($storeProductId) {
                db_connect()->table('plugins')->where('folder', $folder)->update([
                    'store_product_id' => $storeProductId,
                ]);
            }
            return;
        }

        $dir      = ($type === 'theme') ? THEMES_PATH : WIDGETS_PATH;
        $infoFile = $dir . $folder . '/' . ($type === 'theme' ? 'theme_info' : 'widget_info') . '.json';
        $info     = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) ?? [] : [];

        $table = ($type === 'theme') ? 'themes' : 'widgets';
        $db    = db_connect();
        $now   = date('Y-m-d H:i:s');

        $existing = $db->table($table)->where('folder', $folder)->get()->getRowObject();
        if ($existing) {
            $update = ['version' => $info['version'] ?? null, 'updated_at' => $now];
            if ($storeProductId) {
                $update['store_product_id'] = $storeProductId;
            }
            $db->table($table)->where('folder', $folder)->update($update);
        } else {
            $insert = [
                'name'       => $info['name'] ?? $folder,
                'folder'     => $folder,
                'version'    => $info['version'] ?? null,
                'is_active'  => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($storeProductId) {
                $insert['store_product_id'] = $storeProductId;
            }
            $db->table($table)->insert($insert);
        }
    }

    /**
     * Validate a license key via the DigitalStore API, install the item,
     * and persist the license to the marketplace_licenses table.
     */
    public function installLicensed(string $licenseKey, int $storeProductId, string $type): bool
    {
        $client = \Config\Services::curlrequest(['timeout' => 10]);

        try {
            $response = $client->post(PUBVANA_DSTORE_API . 'license/validate', [
                'json' => [
                    'license_key' => $licenseKey,
                    'domain'      => base_url(),
                    'product_id'  => $storeProductId,
                ],
                'http_errors' => false,
            ]);

            $body   = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            if ($status !== 200 || empty($body['valid'])) {
                $error = $body['error'] ?? 'License validation failed (HTTP ' . $status . ')';
                log_message('warning', 'MarketplaceService::installLicensed failed: ' . $error);
                return false;
            }

            $downloadUrl = $body['download_url'] ?? null;
            if (! $downloadUrl) {
                log_message('warning', 'MarketplaceService::installLicensed: no download_url in response');
                return false;
            }

            // Look up the folder (slug) and product name from the cached catalog
            $folder      = null;
            $productName = null;
            foreach ($this->fetchAll() as $cat) {
                foreach ($cat->products ?? [] as $p) {
                    if ((int) ($p->id ?? 0) === $storeProductId) {
                        $folder      = $p->slug;
                        $productName = $p->name ?? $p->slug;
                        break 2;
                    }
                }
            }

            if (! $folder) {
                log_message('warning', 'MarketplaceService::installLicensed: could not determine folder for product ID ' . $storeProductId);
                return false;
            }

            $installed = $this->installFree($downloadUrl, $type, $folder, $storeProductId);
            if (! $installed) {
                return false;
            }

            // Get installed version from local info file
            $version = $this->getInstalledVersion($type, $folder);

            // Upsert into marketplace_licenses
            $licenseModel = new MarketplaceLicenseModel();
            $existing = $licenseModel->where('license_key', $licenseKey)->first();

            $licenseData = [
                'store_product_id'     => $storeProductId,
                'product_name'         => $productName,
                'item_type'            => $type,
                'license_valid'        => 1,
                'license_last_checked' => date('Y-m-d H:i:s'),
                'installed_version'    => $version,
            ];

            if ($existing) {
                $licenseModel->update($existing->id, $licenseData);
            } else {
                $licenseData['license_key'] = $licenseKey;
                $licenseModel->insert($licenseData);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'MarketplaceService::installLicensed exception: ' . $e->getMessage());
            return false;
        }
    }

    protected function getInstalledVersion(string $type, string $slug): ?string
    {
        $infoFile = match ($type) {
            'theme'  => THEMES_PATH . $slug . '/theme_info.json',
            'widget' => WIDGETS_PATH . $slug . '/widget_info.json',
            'plugin' => PLUGINS_PATH . $slug . '/plugin_info.json',
        };

        if (is_file($infoFile)) {
            $info = json_decode(file_get_contents($infoFile), true);
            return $info['version'] ?? null;
        }

        return null;
    }

    public function checkUpdates(): array
    {
        $db = db_connect();
        return $db->table('marketplace_items')
            ->where('installed_version IS NOT NULL')
            ->where('installed_version != version')
            ->get()->getResultArray();
    }

    /**
     * Re-validate all (or overdue) licensed items against the DigitalStore API.
     * Uses a GET /license/check endpoint (read-only).
     *
     * Returns an array of per-item status records:
     *   ['slug' => string, 'status' => 'valid'|'invalid'|'unreachable'|'skipped']
     */
    public function revalidateLicenses(bool $force = false): array
    {
        if ($this->isDevDomain()) {
            return [];
        }

        $licenseModel = new MarketplaceLicenseModel();
        $rows  = $licenseModel
            ->where('license_key IS NOT NULL')
            ->where('license_key !=', '')
            ->findAll();

        $cutoff  = date('Y-m-d H:i:s', strtotime('-' . $this->revalidateDays . ' days'));
        $client  = \Config\Services::curlrequest(['timeout' => 10]);
        $results = [];

        foreach ($rows as $item) {
            if (! $force && $item->license_last_checked !== null && $item->license_last_checked > $cutoff) {
                $results[] = ['store_product_id' => $item->store_product_id, 'status' => 'skipped'];
                continue;
            }

            try {
                $url = PUBVANA_DSTORE_API . 'license/check?'
                    . http_build_query(['key' => $item->license_key, 'product_id' => $item->store_product_id]);

                $response = $client->get($url, ['http_errors' => false]);
                $status   = $response->getStatusCode();
                $body     = json_decode($response->getBody(), true);

                if ($status === 200 && isset($body['valid'])) {
                    $valid   = (bool) $body['valid'];
                    $update  = [
                        'license_last_checked' => date('Y-m-d H:i:s'),
                        'license_valid'        => $valid ? 1 : 0,
                    ];

                    if (isset($body['expires_at'])) {
                        $update['expires_at'] = $body['expires_at'];
                    }
                    if (isset($body['subscription_renews_at'])) {
                        $update['subscription_renews_at'] = $body['subscription_renews_at'];
                    }
                    if (isset($body['is_subscription'])) {
                        $update['is_subscription'] = $body['is_subscription'] ? 1 : 0;
                    }
                    if (isset($body['registered_domain'])) {
                        $update['registered_domain'] = $body['registered_domain'];
                    }

                    $licenseModel->update($item->id, $update);

                    if (! $valid) {
                        $this->enforceLicenseInvalid($item);
                    }

                    $results[] = ['store_product_id' => $item->store_product_id, 'status' => $valid ? 'valid' : 'invalid'];
                } else {
                    $licenseModel->update($item->id, [
                        'license_last_checked' => date('Y-m-d H:i:s'),
                    ]);
                    $results[] = ['store_product_id' => $item->store_product_id, 'status' => 'unreachable'];
                }
            } catch (\Throwable $e) {
                log_message('warning', 'MarketplaceService::revalidateLicenses unreachable for product ID ' . $item->store_product_id . ': ' . $e->getMessage());
                $results[] = ['store_product_id' => $item->store_product_id, 'status' => 'unreachable'];
            }
        }

        return $results;
    }

    protected function enforceLicenseInvalid(object $licenseRow): void
    {
        $db        = db_connect();
        $productId = $licenseRow->store_product_id;
        $type      = $licenseRow->item_type;

        if ($type === 'theme') {
            $theme = $db->table('themes')
                ->where('store_product_id', $productId)
                ->where('is_active', 1)
                ->get()->getRowObject();

            if ($theme) {
                $db->table('themes')->where('id', $theme->id)->update(['is_active' => 0]);
                $db->table('themes')->where('folder', 'default')->update(['is_active' => 1]);
                log_message('notice', "MarketplaceService: deactivated theme (product ID {$productId}) - invalid license. Fell back to default.");
            }
        } elseif ($type === 'plugin') {
            $db->table('plugins')
                ->where('store_product_id', $productId)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
            log_message('notice', "MarketplaceService: deactivated plugin (product ID {$productId}) - invalid license.");
        }
    }

    public function getInvalidLicenses(): array
    {
        return (new MarketplaceLicenseModel())->where('license_valid', 0)->findAll();
    }

    public function getLicenseByProductId(int $storeProductId): ?object
    {
        return (new MarketplaceLicenseModel())->where('store_product_id', $storeProductId)->first();
    }

    public function getAllLicenses(): array
    {
        return (new MarketplaceLicenseModel())->orderBy('product_name', 'ASC')->findAll();
    }

    public function revalidateSingle(int $id): ?string
    {
        if ($this->isDevDomain()) {
            return 'skipped';
        }

        $licenseModel = new MarketplaceLicenseModel();
        $item = $licenseModel->find($id);
        if (! $item) {
            return null;
        }

        $client = \Config\Services::curlrequest(['timeout' => 10]);

        try {
            $url = PUBVANA_DSTORE_API . 'license/check?'
                . http_build_query(['key' => $item->license_key, 'product_id' => $item->store_product_id]);

            $response = $client->get($url, ['http_errors' => false]);
            $body     = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && isset($body['valid'])) {
                $valid  = (bool) $body['valid'];
                $update = [
                    'license_last_checked' => date('Y-m-d H:i:s'),
                    'license_valid'        => $valid ? 1 : 0,
                ];

                if (isset($body['expires_at'])) {
                    $update['expires_at'] = $body['expires_at'];
                }
                if (isset($body['subscription_renews_at'])) {
                    $update['subscription_renews_at'] = $body['subscription_renews_at'];
                }
                if (isset($body['is_subscription'])) {
                    $update['is_subscription'] = $body['is_subscription'] ? 1 : 0;
                }
                if (isset($body['registered_domain'])) {
                    $update['registered_domain'] = $body['registered_domain'];
                }

                $licenseModel->update($id, $update);

                if (! $valid) {
                    $this->enforceLicenseInvalid($item);
                }

                return $valid ? 'valid' : 'invalid';
            }

            return 'unreachable';
        } catch (\Throwable $e) {
            log_message('warning', 'MarketplaceService::revalidateSingle unreachable: ' . $e->getMessage());
            return 'unreachable';
        }
    }

    /**
     * Called on every request (from BaseController). Uses a daily cache gate so
     * actual DB/API work happens at most once per day.
     */
    public function checkAndRevalidateIfDue(): void
    {
        if ($this->isDevDomain()) {
            return;
        }

        if (cache($this->dailyCacheKey) !== null) {
            return;
        }

        // Set the cache key first to prevent concurrent requests double-firing
        cache()->save($this->dailyCacheKey, true, $this->dailyCheckTtl);

        $this->revalidateLicenses(false);
    }
}
