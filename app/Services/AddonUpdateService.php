<?php

namespace App\Services;

use App\Models\MarketplaceLicenseModel;

class AddonUpdateService
{
    protected string $cacheKey = 'extension_update_check';
    protected int    $cacheTtl = 86400; // 24 hours

    /**
     * Check all installed addons for updates. Groups by update_url and
     * sends one batch POST per unique URL.
     *
     * Returns ['updates' => [], 'no_update' => [], 'incompatible' => [], 'errors' => []]
     * merged across all update_url endpoints. The 'updates' entries include 'download_url'
     * for immediate use by runAutoUpdates().
     */
    public function checkAllAddons(): array
    {
        $merged = ['updates' => [], 'no_update' => [], 'incompatible' => [], 'errors' => []];

        $extensions = $this->gatherInstalledExtensions();
        if (empty($extensions)) {
            return $merged;
        }

        // Group by update_url
        $grouped = [];
        foreach ($extensions as $ext) {
            $grouped[$ext['update_url']][] = $ext;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($grouped as $updateUrl => $exts) {
            $payload = [
                'pubvana_version' => APP_VERSION,
                'extensions'      => array_map(fn($e) => [
                    'product_id'  => $e['product_id'],
                    'type'        => $e['type'],
                    'version'     => $e['version'],
                    'license_key' => $e['license_key'],
                ], $exts),
            ];

            $response = $this->postJson($updateUrl, $payload);
            if ($response === null) {
                continue;
            }

            // Build product_id -> folder and type lookups from the extensions we sent
            $idToFolder = [];
            $idToType   = [];
            foreach ($exts as $e) {
                $idToFolder[$e['product_id']] = $e['folder'];
                $idToType[$e['product_id']]   = $e['type'];
            }

            // Merge results
            foreach ($response['updates'] ?? [] as $upd) {
                $merged['updates'][] = $upd;
            }
            foreach ($response['no_update'] ?? [] as $noId) {
                $merged['no_update'][] = $noId;
            }
            foreach ($response['incompatible'] ?? [] as $inc) {
                $merged['incompatible'][] = $inc;
            }
            foreach ((array) ($response['errors'] ?? []) as $pid => $msg) {
                $merged['errors'][$pid] = $msg;
            }

            // Write update info back to the respective tables
            foreach ($response['updates'] ?? [] as $upd) {
                $pid    = $upd['product_id'] ?? 0;
                $folder = $idToFolder[$pid] ?? null;
                $type   = $upd['type'] ?? '';
                if (! $this->tableForType($type) || ! $folder) continue;

                $this->addonModel($type)->updateByFolder($folder, [
                    'latest_version'    => $upd['latest_version'],
                    'changelog'         => $upd['changelog'] ?? null,
                    'last_update_check' => $now,
                ]);
            }

            // Clear latest_version for items with no update
            foreach ($response['no_update'] ?? [] as $noId) {
                $folder = $idToFolder[$noId] ?? null;
                $type   = $idToType[$noId] ?? null;
                if ($type && $this->tableForType($type) && $folder) {
                    $this->addonModel($type)->updateByFolder($folder, [
                        'latest_version'    => null,
                        'changelog'         => null,
                        'last_update_check' => $now,
                    ]);
                }
            }

            // Mark last_update_check for incompatible items
            foreach ($response['incompatible'] ?? [] as $inc) {
                $pid    = $inc['product_id'] ?? 0;
                $folder = $idToFolder[$pid] ?? null;
                $type   = $inc['type'] ?? '';
                if ($this->tableForType($type) && $folder) {
                    $this->addonModel($type)->updateByFolder($folder, [
                        'last_update_check' => $now,
                    ]);
                }
            }
        }

        return $merged;
    }

    /**
     * Check a single addon for updates. Used by the per-row "Check" button.
     */
    public function checkSingleAddon(string $type, string $folder): array
    {
        if (! $this->tableForType($type)) {
            return ['error' => 'Invalid type.'];
        }

        $row = $this->addonModel($type)->findByFolder($folder);
        $storeProductId = $row->store_product_id ?? null;

        $ext = $this->readExtensionInfo($type, $folder);
        if (! $ext || empty($ext['update_url'])) {
            return ['error' => 'No update_url configured for this extension.'];
        }

        if (! $storeProductId) {
            return ['error' => 'Extension not linked to store.'];
        }

        $payload = [
            'pubvana_version' => APP_VERSION,
            'extensions'      => [[
                'product_id'  => (int) $storeProductId,
                'type'        => $ext['type'],
                'version'     => $ext['version'],
                'license_key' => $ext['license_key'],
            ]],
        ];

        $response = $this->postJson($ext['update_url'], $payload);
        if ($response === null) {
            return ['error' => 'Could not reach update server.'];
        }

        $now = date('Y-m-d H:i:s');

        foreach ($response['updates'] ?? [] as $upd) {
            if (($upd['product_id'] ?? 0) == $storeProductId) {
                $this->addonModel($type)->updateByFolder($folder, [
                    'latest_version'    => $upd['latest_version'],
                    'changelog'         => $upd['changelog'] ?? null,
                    'last_update_check' => $now,
                ]);
            }
        }

        foreach ($response['no_update'] ?? [] as $noId) {
            if ($noId == $storeProductId) {
                $this->addonModel($type)->updateByFolder($folder, [
                    'latest_version'    => null,
                    'changelog'         => null,
                    'last_update_check' => $now,
                ]);
            }
        }

        foreach ($response['incompatible'] ?? [] as $inc) {
            if (($inc['product_id'] ?? 0) == $storeProductId) {
                $this->addonModel($type)->updateByFolder($folder, [
                    'last_update_check' => $now,
                ]);
            }
        }

        return $response;
    }

    /**
     * Update a single addon. Fetches a fresh download_url from the update API,
     * then downloads and installs.
     */
    public function updateAddon(string $type, string $folder): bool
    {
        if (! $this->tableForType($type)) {
            $this->recordFailure($type, $folder, 'Invalid type.');
            return false;
        }

        $row = $this->addonModel($type)->findByFolder($folder);
        $storeProductId = $row->store_product_id ?? null;

        $ext = $this->readExtensionInfo($type, $folder);
        if (! $ext || empty($ext['update_url'])) {
            $this->recordFailure($type, $folder, 'No update_url configured.');
            return false;
        }

        if (! $storeProductId) {
            $this->recordFailure($type, $folder, 'Extension not linked to store.');
            return false;
        }

        // Get fresh download_url
        $payload = [
            'pubvana_version' => APP_VERSION,
            'extensions'      => [[
                'product_id'  => (int) $storeProductId,
                'type'        => $ext['type'],
                'version'     => $ext['version'],
                'license_key' => $ext['license_key'],
            ]],
        ];

        $response = $this->postJson($ext['update_url'], $payload);
        if ($response === null) {
            $this->recordFailure($type, $folder, 'Could not reach update server.');
            return false;
        }

        $downloadUrl = null;
        foreach ($response['updates'] ?? [] as $upd) {
            if (($upd['product_id'] ?? 0) == $storeProductId) {
                $downloadUrl = $upd['download_url'] ?? null;
                break;
            }
        }

        if (! $downloadUrl) {
            $error = (array) ($response['errors'] ?? []);
            $msg = $error[$storeProductId] ?? 'No update available.';
            $this->recordFailure($type, $folder, $msg);
            return false;
        }

        return $this->downloadAndInstall($type, $folder, $downloadUrl, $ext['license_key']);
    }

    /**
     * Run auto-updates using download_urls from a previous checkAllAddons() call.
     * Only updates extensions with auto_update = 1.
     * Skips any addon flagged as incompatible with the current Pubvana version.
     */
    public function runAutoUpdates(array $checkResults): void
    {
        // Build a set of incompatible product IDs to skip
        $incompatibleIds = [];
        foreach ($checkResults['incompatible'] ?? [] as $inc) {
            $incompatibleIds[] = $inc['product_id'] ?? 0;
        }

        foreach ($checkResults['updates'] ?? [] as $upd) {
            $type      = $upd['type'] ?? '';
            $productId = $upd['product_id'] ?? 0;
            if (! $this->tableForType($type)) continue;

            if (in_array($productId, $incompatibleIds, true)) {
                log_message('info', "AddonUpdateService: auto-update skipped: {$type} store ID {$productId} is incompatible.");
                continue;
            }

            $row = $this->addonModel($type)->findByStoreProductId($productId);
            if (! $row || ! (int) $row->auto_update) {
                continue;
            }

            $downloadUrl = $upd['download_url'] ?? null;
            if (! $downloadUrl) continue;

            // Look up license key
            $license = (new MarketplaceLicenseModel())->where('store_product_id', $productId)->first();
            $licenseKey = $license->license_key ?? null;

            $this->downloadAndInstall($type, $row->folder, $downloadUrl, $licenseKey);
        }
    }

    /**
     * Clear the daily cache so the next request triggers a fresh check.
     */
    public function clearCache(): void
    {
        cache()->delete($this->cacheKey);
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * Gather all installed extensions that have an update_url in their info file.
     * Only includes extensions linked to the store via store_product_id.
     */
    private function gatherInstalledExtensions(): array
    {
        $extensions = [];

        // Pre-load license keys indexed by store_product_id
        $licenses = [];
        $licenseRows = (new MarketplaceLicenseModel())
            ->where('license_key IS NOT NULL')
            ->where('license_key !=', '')
            ->findAll();
        foreach ($licenseRows as $lic) {
            $licenses[$lic->store_product_id] = $lic->license_key;
        }

        foreach (['theme', 'widget', 'plugin'] as $type) {
            $rows = $this->addonModel($type)->getUpdateCheckable();

            foreach ($rows as $row) {
                $extensions[] = [
                    'product_id'  => (int) $row->store_product_id,
                    'folder'      => $row->folder,
                    'type'        => $type,
                    'version'     => $row->version ?? '0.0.0',
                    'update_url'  => $row->update_url,
                    'license_key' => $licenses[$row->store_product_id] ?? null,
                ];
            }
        }

        return $extensions;
    }

    /**
     * Read a single extension's info from the DB and look up its license via store_product_id.
     */
    private function readExtensionInfo(string $type, string $folder): ?array
    {
        if (! $this->tableForType($type)) {
            return null;
        }

        $row = $this->addonModel($type)->findByFolder($folder);
        if (! $row) {
            return null;
        }

        $storeProductId = $row->store_product_id ?? null;
        $licenseKey = null;
        if ($storeProductId) {
            $license = (new MarketplaceLicenseModel())->where('store_product_id', $storeProductId)->first();
            $licenseKey = $license->license_key ?? null;
        }

        return [
            'type'        => $type,
            'version'     => $row->version ?? '0.0.0',
            'update_url'  => $row->update_url ?? null,
            'license_key' => $licenseKey,
        ];
    }

    /**
     * Download a ZIP from the given URL (via POST) and install it.
     */
    private function downloadAndInstall(string $type, string $folder, string $downloadUrl, ?string $licenseKey): bool
    {
        if (! $this->tableForType($type)) {
            return false;
        }

        try {
            $client = \Config\Services::curlrequest(['timeout' => 30]);
            $response = $client->post($downloadUrl, [
                'json'        => [
                    'license_key'     => $licenseKey,
                    'pubvana_version' => APP_VERSION,
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                $body = json_decode($response->getBody(), true);
                $error = $body['error'] ?? 'Download failed (HTTP ' . $response->getStatusCode() . ')';
                $this->recordFailure($type, $folder, $error);
                return false;
            }

            $contentType = $response->header('Content-Type')
                ? $response->header('Content-Type')->getValue()
                : '';

            if (stripos($contentType, 'application/zip') === false) {
                $this->recordFailure($type, $folder, 'Response was not a ZIP file.');
                return false;
            }

            // Save to temp and extract
            $tmpDir  = WRITEPATH . 'tmp/';
            $zipPath = $tmpDir . $folder . '.zip';
            if (! is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }
            file_put_contents($zipPath, $response->getBody());

            $destDir = match ($type) {
                'theme'  => THEMES_PATH,
                'widget' => WIDGETS_PATH,
                'plugin' => PLUGINS_PATH,
            };

            $archive = new \ZipArchive();
            if ($archive->open($zipPath) !== true) {
                @unlink($zipPath);
                $this->recordFailure($type, $folder, 'Failed to open ZIP archive.');
                return false;
            }

            // Security: reject path traversal
            for ($i = 0; $i < $archive->numFiles; $i++) {
                $entry = $archive->getNameIndex($i);
                if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                    $archive->close();
                    @unlink($zipPath);
                    $this->recordFailure($type, $folder, 'ZIP contains unsafe path entries.');
                    return false;
                }
            }

            $archive->extractTo($destDir);
            $archive->close();
            @unlink($zipPath);

            // Post-install hooks
            if ($type === 'theme') {
                service('theme')->publishAssets($folder);
            }
            if ($type === 'plugin') {
                PluginManager::instance()->discover();
                $migrate = \Config\Services::migrations();
                $migrate->setNamespace('Plugins\\' . $folder)->latest();
            }

            // Read new version from the freshly extracted info file
            $infoFile = match ($type) {
                'theme'  => THEMES_PATH . $folder . '/theme_info.json',
                'widget' => WIDGETS_PATH . $folder . '/widget_info.json',
                'plugin' => PLUGINS_PATH . $folder . '/plugin_info.json',
            };
            $newInfo = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];
            $newVersion = $newInfo['version'] ?? null;

            // Record success
            $now = date('Y-m-d H:i:s');
            $this->addonModel($type)->updateByFolder($folder, [
                'version'             => $newVersion,
                'latest_version'      => null,
                'changelog'           => null,
                'last_update_attempt' => 'success',
                'last_update_error'   => null,
                'last_updated_at'     => $now,
                'updated_at'          => $now,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->recordFailure($type, $folder, $e->getMessage());
            return false;
        }
    }

    private function recordFailure(string $type, string $folder, string $error): void
    {
        if (! $this->tableForType($type)) return;

        $this->addonModel($type)->updateByFolder($folder, [
            'last_update_attempt' => 'fail',
            'last_update_error'   => mb_substr($error, 0, 500),
        ]);
    }

    private function tableForType(string $type): ?string
    {
        return match ($type) {
            'theme'  => 'themes',
            'widget' => 'widgets',
            'plugin' => 'plugins',
            default  => null,
        };
    }

    private function addonModel(string $type): \CodeIgniter\Model
    {
        return match($type) {
            'theme'  => model(\App\Models\ThemeModel::class),
            'widget' => model(\App\Models\WidgetModel::class),
            'plugin' => model(\App\Models\PluginModel::class),
        };
    }

    private function postJson(string $url, array $payload): ?array
    {
        try {
            $client   = \Config\Services::curlrequest(['timeout' => 10]);
            $response = $client->post($url, [
                'json'        => $payload,
                'http_errors' => false,
                'headers'     => ['User-Agent' => 'Pubvana-CMS/' . APP_VERSION],
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('warning', 'AddonUpdateService: HTTP ' . $response->getStatusCode() . ' from ' . $url);
                return null;
            }

            $data = json_decode($response->getBody(), true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            log_message('warning', 'AddonUpdateService: ' . $e->getMessage());
            return null;
        }
    }
}
