<?php

namespace App\Services;

use App\Models\AdminNotificationModel;

class UpdateService
{
    protected string $changesUrl = 'https://raw.githubusercontent.com/enlivenapp/pubvana/master/CHANGES.json';
    protected string $cacheKey   = 'pubvana_update_check';
    protected int    $cacheTtl   = 86400; // 24 hours

    /**
     * Check CHANGES.json for available updates.
     * Gated by cache so we only hit GitHub once per TTL period.
     * If a newer version is found, inserts an admin notification.
     *
     * Returns an array with keys:
     *   available        bool
     *   current_version  string
     *   latest_version   string
     *   safe_target      string|null  (highest version all extensions support, null if none safe)
     *   capped_by        array        (extensions blocking the jump to latest)
     *   versions_data    array        (raw CHANGES.json versions for downstream use)
     *   error            string|null
     */
    public function checkForUpdate(): array
    {
        $base = [
            'available'       => false,
            'current_version' => APP_VERSION,
            'latest_version'  => APP_VERSION,
            'safe_target'     => null,
            'capped_by'       => [],
            'versions_data'   => [],
            'error'           => null,
        ];

        $cached = cache($this->cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($this->changesUrl, [
                'http_errors' => false,
                'headers'     => ['User-Agent' => 'Pubvana-CMS/' . APP_VERSION],
            ]);

            if ($response->getStatusCode() !== 200) {
                $base['error'] = 'Could not fetch version info from GitHub (HTTP ' . $response->getStatusCode() . ').';
                cache()->save($this->cacheKey, $base, $this->cacheTtl);
                return $base;
            }

            $data = json_decode($response->getBody(), true);
            if (! is_array($data) || empty($data['versions'])) {
                $base['error'] = 'Invalid version data from GitHub.';
                cache()->save($this->cacheKey, $base, $this->cacheTtl);
                return $base;
            }

            // Find the highest version in CHANGES.json
            $latest = '0.0.0';
            foreach ($data['versions'] as $entry) {
                $v = $entry['version'] ?? '0.0.0';
                if (version_compare($v, $latest, '>')) {
                    $latest = $v;
                }
            }

            // If local is equal or ahead, we're up to date
            if (version_compare(APP_VERSION, $latest, '>=')) {
                $base['versions_data'] = $data['versions'];
                cache()->save($this->cacheKey, $base, $this->cacheTtl);
                return $base;
            }

            // Check extension compatibility against the latest version
            $incompatible = $this->checkExtensionCompatibility($latest);

            // Determine safe target
            $safeTarget = $latest;
            if (! empty($incompatible)) {
                // Safe ceiling = lowest max_pubvana_version among incompatible extensions
                $ceiling = $latest;
                foreach ($incompatible as $ext) {
                    if (version_compare($ext['max_version'], $ceiling, '<')) {
                        $ceiling = $ext['max_version'];
                    }
                }
                // Only valid if ceiling is above current version
                $safeTarget = version_compare($ceiling, APP_VERSION, '>') ? $ceiling : null;
            }

            $result = [
                'available'       => true,
                'current_version' => APP_VERSION,
                'latest_version'  => $latest,
                'safe_target'     => $safeTarget,
                'capped_by'       => $incompatible,
                'versions_data'   => $data['versions'],
                'error'           => null,
            ];

            cache()->save($this->cacheKey, $result, $this->cacheTtl);

            // Insert admin notification for the effective target version
            $effectiveVersion = $safeTarget ?? $latest;
            $notifModel = new AdminNotificationModel();
            $existing = $notifModel->where('source', 'system')
                ->where('action_url', 'admin/updates')
                ->like('message', "v{$effectiveVersion}")
                ->first();

            if (! $existing) {
                $notifModel->insert([
                    'source'       => 'system',
                    'source_name'  => 'Pubvana ' . lang('Admin.updatesTitle'),
                    'severity'     => 'info',
                    'message'      => lang('Admin.updatesAvailable', ["v{$effectiveVersion}"]),
                    'action_url'   => 'admin/updates',
                    'action_label' => lang('Admin.view'),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            log_message('warning', 'UpdateService: ' . $e->getMessage());
            $base['error'] = $e->getMessage();
            return $base;
        }
    }

    public function clearCache(): void
    {
        cache()->delete($this->cacheKey);
    }

    /**
     * Get CHANGES.json version entries between $fromVersion and $toVersion.
     * Accepts pre-fetched versions data to avoid duplicate HTTP calls.
     */
    public function getChanges(string $fromVersion, string $toVersion, array $versionsData = []): array
    {
        if (empty($versionsData)) {
            // Fallback: fetch from GitHub if no pre-fetched data provided
            try {
                $client   = \Config\Services::curlrequest(['timeout' => 5]);
                $response = $client->get($this->changesUrl, [
                    'http_errors' => false,
                    'headers'     => ['User-Agent' => 'Pubvana-CMS/' . APP_VERSION],
                ]);

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $data = json_decode($response->getBody(), true);
                if (! is_array($data) || empty($data['versions'])) {
                    return [];
                }

                $versionsData = $data['versions'];
            } catch (\Throwable $e) {
                log_message('warning', 'UpdateService::getChanges: ' . $e->getMessage());
                return [];
            }
        }

        return array_values(array_filter($versionsData, function (array $entry) use ($fromVersion, $toVersion) {
            $v = $entry['version'] ?? '';
            return version_compare($v, $fromVersion, '>') && version_compare($v, $toVersion, '<=');
        }));
    }

    /**
     * Run pre-flight checks for an update.
     *
     * Returns an array of check results:
     *   ['name' => string, 'pass' => bool, 'message' => string, 'hard' => bool]
     */
    public function preFlightChecks(array $changes, string $targetVersion = '', array $knownIncompatible = []): array
    {
        $checks = [];

        // Collect minimum requirements from all applicable CHANGES.json entries
        $minPhp    = '8.2';
        $minCi     = '4.7';
        $minShield = '1.2';
        foreach ($changes as $entry) {
            if (! empty($entry['min_php_version']) && version_compare($entry['min_php_version'], $minPhp, '>')) {
                $minPhp = $entry['min_php_version'];
            }
            if (! empty($entry['min_ci_version']) && version_compare($entry['min_ci_version'], $minCi, '>')) {
                $minCi = $entry['min_ci_version'];
            }
            if (! empty($entry['min_shield_version']) && version_compare($entry['min_shield_version'], $minShield, '>')) {
                $minShield = $entry['min_shield_version'];
            }
        }

        // PHP version
        $checks[] = [
            'name'    => 'PHP Version',
            'pass'    => version_compare(PHP_VERSION, $minPhp, '>='),
            'message' => 'Requires PHP ' . $minPhp . ', running ' . PHP_VERSION,
            'hard'    => true,
        ];

        // CI version
        $ciVersion = \CodeIgniter\CodeIgniter::CI_VERSION;
        $checks[] = [
            'name'    => 'CodeIgniter Version',
            'pass'    => version_compare($ciVersion, $minCi, '>='),
            'message' => 'Requires CI ' . $minCi . ', running ' . $ciVersion,
            'hard'    => true,
        ];

        // Shield version — read from composer.lock
        $shieldVersion = $this->getInstalledPackageVersion('codeigniter4/shield');
        $checks[] = [
            'name'    => 'Shield Version',
            'pass'    => $shieldVersion !== null && version_compare($shieldVersion, $minShield, '>='),
            'message' => 'Requires Shield ' . $minShield . ', running ' . ($shieldVersion ?? 'unknown'),
            'hard'    => true,
        ];

        // Writable directories
        foreach (['writable/', 'writable/backups/', 'writable/updates/'] as $dir) {
            $fullPath = ROOTPATH . $dir;
            $writable = is_dir($fullPath) ? is_writable($fullPath) : is_writable(dirname($fullPath));
            $checks[] = [
                'name'    => $dir . ' writable',
                'pass'    => $writable,
                'message' => $writable ? 'OK' : 'Directory is not writable',
                'hard'    => true,
            ];
        }

        // Disk space (require at least 500MB free)
        $free = disk_free_space(ROOTPATH);
        $checks[] = [
            'name'    => 'Disk Space',
            'pass'    => $free > 500 * 1024 * 1024,
            'message' => 'Free: ' . round($free / 1024 / 1024) . ' MB',
            'hard'    => true,
        ];

        // Exec availability (soft check)
        $execOk = function_exists('exec') && ! in_array('exec', array_map('trim', explode(',', ini_get('disable_functions') ?: '')), true);
        $checks[] = [
            'name'    => 'exec() available',
            'pass'    => $execOk,
            'message' => $execOk ? 'Background operations available' : 'Operations will run synchronously',
            'hard'    => false,
        ];

        // Extension compatibility (soft checks)
        $extIncompat = ! empty($knownIncompatible) ? $knownIncompatible
            : ($targetVersion !== '' ? $this->checkExtensionCompatibility($targetVersion) : []);
        foreach ($extIncompat as $ext) {
            $checks[] = [
                'name'    => ucfirst($ext['type']) . ': ' . $ext['name'],
                'pass'    => false,
                'message' => 'Max compatible version: ' . $ext['max_version'],
                'hard'    => false,
            ];
        }

        return $checks;
    }

    /**
     * Check installed themes, widgets, and plugins for compatibility
     * with the target Pubvana version.
     *
     * Returns an array of incompatible extensions:
     *   ['type' => string, 'name' => string, 'max_version' => string]
     */
    public function checkExtensionCompatibility(string $targetVersion): array
    {
        $incompatible = [];

        $sources = [
            'theme'  => [THEMES_PATH, 'theme_info.json'],
            'widget' => [WIDGETS_PATH, 'widget_info.json'],
            'plugin' => [PLUGINS_PATH, 'plugin_info.json'],
        ];

        foreach ($sources as $type => [$basePath, $infoFile]) {
            if (! is_dir($basePath)) {
                continue;
            }

            foreach (glob($basePath . '*/' . $infoFile) as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (! is_array($data) || empty($data['name'])) {
                    continue;
                }

                $maxVersion = $data['max_pubvana_version'] ?? null;
                if ($maxVersion !== null && version_compare($targetVersion, $maxVersion, '>')) {
                    $incompatible[] = [
                        'type'        => $type,
                        'name'        => $data['name'],
                        'max_version' => $maxVersion,
                    ];
                }
            }
        }

        return $incompatible;
    }

    /**
     * Read an installed package version from composer.lock.
     */
    private function getInstalledPackageVersion(string $packageName): ?string
    {
        $lockFile = ROOTPATH . 'composer.lock';
        if (! is_file($lockFile)) {
            return null;
        }

        $lock = json_decode(file_get_contents($lockFile), true);
        foreach ($lock['packages'] ?? [] as $pkg) {
            if ($pkg['name'] === $packageName) {
                return ltrim($pkg['version'] ?? '', 'v');
            }
        }
        return null;
    }
}
