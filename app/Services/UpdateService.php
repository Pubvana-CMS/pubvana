<?php

namespace App\Services;

use App\Models\AdminNotificationModel;

class UpdateService
{
    protected string $apiUrl   = 'https://api.github.com/repos/enlivenapp/pubvana/releases/latest';
    protected string $cacheKey = 'pubvana_update_check';
    protected int    $cacheTtl = 21600; // 6 hours

    /**
     * Check GitHub for the latest release.
     *
     * Returns an array with keys:
     *   available        bool
     *   current_version  string
     *   latest_version   string
     *   release_url      string
     *   release_notes    string
     *   zipball_url      string
     *   error            string|null
     */
    public function checkForUpdate(): array
    {
        $base = [
            'available'       => false,
            'current_version' => APP_VERSION,
            'latest_version'  => APP_VERSION,
            'release_url'     => '',
            'release_notes'   => '',
            'zipball_url'     => '',
            'error'           => null,
        ];

        $cached = cache($this->cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($this->apiUrl, [
                'http_errors' => false,
                'headers'     => [
                    'User-Agent' => 'Pubvana-CMS/' . APP_VERSION,
                    'Accept'     => 'application/vnd.github.v3+json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $base['error'] = 'GitHub returned HTTP ' . $response->getStatusCode();
                return $base;
            }

            $data = json_decode($response->getBody(), true);
            if (! is_array($data) || empty($data['tag_name'])) {
                $base['error'] = 'Unexpected response from GitHub.';
                return $base;
            }

            $latest = ltrim($data['tag_name'], 'v');

            // Find the uploaded release asset (includes vendor/)
            $downloadUrl = '';
            foreach ($data['assets'] ?? [] as $asset) {
                if (str_ends_with($asset['name'] ?? '', '.zip')) {
                    $downloadUrl = $asset['browser_download_url'];
                    break;
                }
            }

            if ($downloadUrl === '') {
                $base['error'] = 'Release has no download asset. Update not available yet.';
                return $base;
            }

            $result = [
                'available'       => version_compare($latest, APP_VERSION, '>'),
                'current_version' => APP_VERSION,
                'latest_version'  => $latest,
                'release_url'     => $data['html_url']    ?? '',
                'release_notes'   => $data['body']        ?? '',
                'zipball_url'     => $downloadUrl,
                'error'           => null,
            ];

            cache()->save($this->cacheKey, $result, $this->cacheTtl);

            // Insert admin notification if update is available
            if ($result['available']) {
                $notifModel = new AdminNotificationModel();
                $notifModel->insert([
                    'source'       => 'system',
                    'source_name'  => 'Pubvana Update',
                    'severity'     => 'info',
                    'message'      => "Pubvana v{$latest} is available. You are running v" . APP_VERSION . ".",
                    'action_url'   => 'admin/updates',
                    'action_label' => 'View Update',
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
     * Fetch and parse CHANGES.json from the GitHub repo.
     * Returns all version entries between $fromVersion and $toVersion.
     */
    public function getChanges(string $fromVersion, string $toVersion): array
    {
        $url = 'https://raw.githubusercontent.com/enlivenapp/pubvana/master/CHANGES.json';

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($url, [
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

            // Filter versions between current and target
            return array_filter($data['versions'], function (array $entry) use ($fromVersion, $toVersion) {
                $v = $entry['version'] ?? '';
                return version_compare($v, $fromVersion, '>') && version_compare($v, $toVersion, '<=');
            });
        } catch (\Throwable $e) {
            log_message('warning', 'UpdateService::getChanges: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Run pre-flight checks for an update.
     *
     * Returns an array of check results:
     *   ['name' => string, 'pass' => bool, 'message' => string, 'hard' => bool]
     */
    public function preFlightChecks(array $changes): array
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

        return $checks;
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
