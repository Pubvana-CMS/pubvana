<?php

namespace App\Services;

class ExtensionUpdateService
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

        $db = db_connect();
        $now = date('Y-m-d H:i:s');

        foreach ($grouped as $updateUrl => $exts) {
            $payload = [
                'pubvana_version' => APP_VERSION,
                'extensions'      => array_map(fn($e) => [
                    'slug'        => $e['slug'],
                    'type'        => $e['type'],
                    'version'     => $e['version'],
                    'license_key' => $e['license_key'],
                ], $exts),
            ];

            $response = $this->postJson($updateUrl, $payload);
            if ($response === null) {
                continue;
            }

            // Merge results
            foreach ($response['updates'] ?? [] as $upd) {
                $merged['updates'][] = $upd;
            }
            foreach ($response['no_update'] ?? [] as $slug) {
                $merged['no_update'][] = $slug;
            }
            foreach ($response['incompatible'] ?? [] as $inc) {
                $merged['incompatible'][] = $inc;
            }
            foreach ((array) ($response['errors'] ?? []) as $slug => $msg) {
                $merged['errors'][$slug] = $msg;
            }

            // Write update info back to the respective tables
            foreach ($response['updates'] ?? [] as $upd) {
                $table = $this->tableForType($upd['type'] ?? '');
                if (! $table) continue;

                $db->table($table)->where('folder', $upd['slug'])->update([
                    'latest_version'    => $upd['latest_version'],
                    'changelog'         => $upd['changelog'] ?? null,
                    'last_update_check' => $now,
                ]);
            }

            // Clear latest_version for items with no update
            foreach ($response['no_update'] ?? [] as $slug) {
                // Find which ext this belongs to
                foreach ($exts as $e) {
                    if ($e['slug'] === $slug) {
                        $table = $this->tableForType($e['type']);
                        if ($table) {
                            $db->table($table)->where('folder', $slug)->update([
                                'latest_version'    => null,
                                'changelog'         => null,
                                'last_update_check' => $now,
                            ]);
                        }
                        break;
                    }
                }
            }

            // Mark last_update_check for incompatible and error items too
            foreach ($response['incompatible'] ?? [] as $inc) {
                $table = $this->tableForType($inc['type'] ?? '');
                if ($table) {
                    $db->table($table)->where('folder', $inc['slug'])->update([
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
    public function checkSingleAddon(string $type, string $slug): array
    {
        $ext = $this->readExtensionInfo($type, $slug);
        if (! $ext || empty($ext['update_url'])) {
            return ['error' => 'No update_url configured for this extension.'];
        }

        $payload = [
            'pubvana_version' => APP_VERSION,
            'extensions'      => [[
                'slug'        => $ext['slug'],
                'type'        => $ext['type'],
                'version'     => $ext['version'],
                'license_key' => $ext['license_key'],
            ]],
        ];

        $response = $this->postJson($ext['update_url'], $payload);
        if ($response === null) {
            return ['error' => 'Could not reach update server.'];
        }

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $table = $this->tableForType($type);

        // Write results
        foreach ($response['updates'] ?? [] as $upd) {
            if ($upd['slug'] === $slug && $table) {
                $db->table($table)->where('folder', $slug)->update([
                    'latest_version'    => $upd['latest_version'],
                    'changelog'         => $upd['changelog'] ?? null,
                    'last_update_check' => $now,
                ]);
            }
        }

        foreach ($response['no_update'] ?? [] as $noSlug) {
            if ($noSlug === $slug && $table) {
                $db->table($table)->where('folder', $slug)->update([
                    'latest_version'    => null,
                    'changelog'         => null,
                    'last_update_check' => $now,
                ]);
            }
        }

        foreach ($response['incompatible'] ?? [] as $inc) {
            if (($inc['slug'] ?? '') === $slug && $table) {
                $db->table($table)->where('folder', $slug)->update([
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
    public function updateAddon(string $type, string $slug): bool
    {
        $ext = $this->readExtensionInfo($type, $slug);
        if (! $ext || empty($ext['update_url'])) {
            $this->recordFailure($type, $slug, 'No update_url configured.');
            return false;
        }

        // Get fresh download_url
        $payload = [
            'pubvana_version' => APP_VERSION,
            'extensions'      => [[
                'slug'        => $ext['slug'],
                'type'        => $ext['type'],
                'version'     => $ext['version'],
                'license_key' => $ext['license_key'],
            ]],
        ];

        $response = $this->postJson($ext['update_url'], $payload);
        if ($response === null) {
            $this->recordFailure($type, $slug, 'Could not reach update server.');
            return false;
        }

        $downloadUrl = null;
        foreach ($response['updates'] ?? [] as $upd) {
            if ($upd['slug'] === $slug) {
                $downloadUrl = $upd['download_url'] ?? null;
                break;
            }
        }

        if (! $downloadUrl) {
            // Check errors
            $error = (array) ($response['errors'] ?? []);
            $msg = $error[$slug] ?? 'No update available.';
            $this->recordFailure($type, $slug, $msg);
            return false;
        }

        return $this->downloadAndInstall($type, $slug, $downloadUrl, $ext['license_key']);
    }

    /**
     * Run auto-updates using download_urls from a previous checkAllAddons() call.
     * Only updates extensions with auto_update = 1.
     * Skips any addon flagged as incompatible with the current Pubvana version.
     */
    public function runAutoUpdates(array $checkResults): void
    {
        $db = db_connect();

        // Build a set of incompatible slugs to skip
        $incompatibleSlugs = [];
        foreach ($checkResults['incompatible'] ?? [] as $inc) {
            $incompatibleSlugs[] = $inc['slug'] ?? '';
        }

        foreach ($checkResults['updates'] ?? [] as $upd) {
            $type  = $upd['type'] ?? '';
            $slug  = $upd['slug'] ?? '';
            $table = $this->tableForType($type);
            if (! $table) continue;

            if (in_array($slug, $incompatibleSlugs, true)) {
                log_message('info', "Addon auto-update skipped: {$type}/{$slug} is incompatible with current Pubvana version.");
                continue;
            }

            $row = $db->table($table)->where('folder', $slug)->get()->getRowObject();
            if (! $row || ! (int) $row->auto_update) {
                continue;
            }

            $downloadUrl = $upd['download_url'] ?? null;
            if (! $downloadUrl) continue;

            // Look up license key
            $license = $db->table('marketplace_licenses')
                ->where('product_slug', $slug)
                ->get()->getRowObject();
            $licenseKey = $license->license_key ?? null;

            $this->downloadAndInstall($type, $slug, $downloadUrl, $licenseKey);
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
     */
    private function gatherInstalledExtensions(): array
    {
        $extensions = [];
        $db = db_connect();

        $sources = [
            'theme'  => [THEMES_PATH, 'theme_info.json'],
            'widget' => [WIDGETS_PATH, 'widget_info.json'],
            'plugin' => [PLUGINS_PATH, 'plugin_info.json'],
        ];

        // Pre-load all license keys indexed by product_slug
        $licenses = [];
        $licenseRows = $db->table('marketplace_licenses')
            ->where('license_key IS NOT NULL')
            ->where('license_key !=', '')
            ->get()->getResult();
        foreach ($licenseRows as $lic) {
            $licenses[$lic->product_slug] = $lic->license_key;
        }

        foreach ($sources as $type => [$basePath, $infoFile]) {
            if (! is_dir($basePath)) continue;

            foreach (glob($basePath . '*/' . $infoFile) as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (! is_array($data) || empty($data['slug']) || empty($data['update_url'])) {
                    continue;
                }

                $extensions[] = [
                    'slug'        => $data['slug'],
                    'type'        => $type,
                    'version'     => $data['version'] ?? '0.0.0',
                    'update_url'  => $data['update_url'],
                    'license_key' => $licenses[$data['slug']] ?? null,
                ];
            }
        }

        return $extensions;
    }

    /**
     * Read a single extension's info file and look up its license.
     */
    private function readExtensionInfo(string $type, string $slug): ?array
    {
        $infoFile = match ($type) {
            'theme'  => THEMES_PATH . $slug . '/theme_info.json',
            'widget' => WIDGETS_PATH . $slug . '/widget_info.json',
            'plugin' => PLUGINS_PATH . $slug . '/plugin_info.json',
            default  => null,
        };

        if (! $infoFile || ! is_file($infoFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($infoFile), true);
        if (! is_array($data) || empty($data['slug'])) {
            return null;
        }

        $license = db_connect()->table('marketplace_licenses')
            ->where('product_slug', $slug)
            ->get()->getRowObject();

        return [
            'slug'        => $data['slug'],
            'type'        => $type,
            'version'     => $data['version'] ?? '0.0.0',
            'update_url'  => $data['update_url'] ?? null,
            'license_key' => $license->license_key ?? null,
        ];
    }

    /**
     * Download a ZIP from the given URL (via POST) and install it.
     */
    private function downloadAndInstall(string $type, string $slug, string $downloadUrl, ?string $licenseKey): bool
    {
        $table = $this->tableForType($type);
        if (! $table) {
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
                $this->recordFailure($type, $slug, $error);
                return false;
            }

            $contentType = $response->header('Content-Type')
                ? $response->header('Content-Type')->getValue()
                : '';

            if (stripos($contentType, 'application/zip') === false) {
                $this->recordFailure($type, $slug, 'Response was not a ZIP file.');
                return false;
            }

            // Save to temp and extract
            $tmpDir  = WRITEPATH . 'tmp/';
            $zipPath = $tmpDir . $slug . '.zip';
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
                $this->recordFailure($type, $slug, 'Failed to open ZIP archive.');
                return false;
            }

            // Security: reject path traversal
            for ($i = 0; $i < $archive->numFiles; $i++) {
                $entry = $archive->getNameIndex($i);
                if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                    $archive->close();
                    @unlink($zipPath);
                    $this->recordFailure($type, $slug, 'ZIP contains unsafe path entries.');
                    return false;
                }
            }

            $archive->extractTo($destDir);
            $archive->close();
            @unlink($zipPath);

            // Post-install hooks
            if ($type === 'theme') {
                service('theme')->publishAssets($slug);
            }
            if ($type === 'plugin') {
                PluginManager::instance()->discover();
            }

            // Read new version from the freshly extracted info file
            $infoFile = match ($type) {
                'theme'  => THEMES_PATH . $slug . '/theme_info.json',
                'widget' => WIDGETS_PATH . $slug . '/widget_info.json',
                'plugin' => PLUGINS_PATH . $slug . '/plugin_info.json',
            };
            $newInfo = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];
            $newVersion = $newInfo['version'] ?? null;

            // Record success
            $db  = db_connect();
            $now = date('Y-m-d H:i:s');
            $db->table($table)->where('folder', $slug)->update([
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
            $this->recordFailure($type, $slug, $e->getMessage());
            return false;
        }
    }

    private function recordFailure(string $type, string $slug, string $error): void
    {
        $table = $this->tableForType($type);
        if (! $table) return;

        db_connect()->table($table)->where('folder', $slug)->update([
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
                log_message('warning', 'ExtensionUpdateService: HTTP ' . $response->getStatusCode() . ' from ' . $url);
                return null;
            }

            $data = json_decode($response->getBody(), true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            log_message('warning', 'ExtensionUpdateService: ' . $e->getMessage());
            return null;
        }
    }

    private function isDevDomain(): bool
    {
        $host = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        return $host === 'localhost' || str_ends_with($host, '.local');
    }
}
