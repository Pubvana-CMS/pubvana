<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Models\TrustCache;
use Pubvana\Plugins\Updates\Services\UpdateApplyService;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Plugins\Updates\Services\UpdateService;
use flight\Engine;
use Throwable;

/**
 * Admin controller for the Updates screen.
 *
 * All logic lives in the services; this controller triggers operations
 * and reports results. Web apply and CLI commands share the exact same
 * service path.
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
final class UpdatesAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.updates');
    }

    /**
     * The Updates screen.
     */
    public function index(): void
    {
        if (!$this->guard()) {
            return;
        }

        $service    = $this->service();
        $progress   = (new UpdateProgress($service->storageDir()))->read();
        $locked     = $this->isLocked();
        $inProgress = is_array($progress) && ($progress['status'] ?? '') === 'in_progress';

        // A run is live only while its lock is held. A killed process leaves
        // 'in_progress' behind with a free lock, which the old check read as
        // a live update and polled forever. Surface that once as an
        // interrupted run, then clear the leftover payload.
        $running     = $locked && $inProgress;
        $interrupted = !$locked && $inProgress;
        if ($interrupted) {
            (new UpdateProgress($service->storageDir()))->clearIfUnlocked();
        }

        $state = $service->lastCheck();

        // Web check trigger: refresh the feed when the cache is stale
        // (bounded by the configured timeout), unless a run is active.
        if (!$running && $service->isDue()) {
            try {
                $state = $service->check(true);
            } catch (Throwable $e) {
                $state['status'] = 'error';
                $state['error']  = $e->getMessage();
            }
        }

        // Web auto-apply trigger: when auto updates are on and an
        // applicable release waits, visiting this page starts the run
        // in the background (once per cache window, never through
        // breaking changes).
        if (!$running
            && $service->autoUpdateEnabled()
            && ($state['status'] ?? '') === 'available'
            && ((array) ($state['breaking_changes'] ?? []) === [])
            && ($state['capped_by'] ?? null) === null
            && $this->execAvailable()
            && !$locked
        ) {
            $this->startBackgroundAutoUpdate();
        }

        $targetVersion = (string) ($state['target_version'] ?? '');

        // Addon trust standings come from one cache read; a trust-layer
        // failure degrades every row to 'not checked'.
        $statusMap = [];
        try {
            $statusMap = $this->app->trustClient()->statusesForAll();
        } catch (Throwable) {
            $statusMap = [];
        }

        // Stamp each addon inventory row with its trust standing (cache
        // reads only; the live ask belongs to the apply paths).
        $addons = $service->addons();
        try {
            foreach ($addons['themes'] as $index => $row) {
                $addons['themes'][$index] = $this->stampAddonTrust(
                    $row,
                    $row['folder'] !== null ? $this->app->trustClient()->themeItem($row['folder']) : null,
                    $statusMap
                );
            }

            $discovered = array_merge(
                $this->app->pluginLoader()->discoverLocal(),
                $this->app->pluginLoader()->discoverVendor()
            );
            foreach ($addons['plugins'] as $index => $row) {
                $package = (string) ($row['package'] ?? '');
                $addons['plugins'][$index] = $this->stampAddonTrust(
                    $row,
                    $package !== '' ? $this->trustItemForPlugin($package, $discovered[$package] ?? []) : null,
                    $statusMap
                );
            }
        } catch (Throwable) {
            // Rows without a stamped standing render as 'not checked'.
        }

        // Marketplace surface state is owned by the addons inventory; the
        // controller only forwards it to the view.
        // Marketplace admin surface (Tools > Marketplace), for pointers on
        // Unlicensed rows. null when the Marketplace plugin is absent.
        $marketplaceAdmin = null;
        try {
            $marketplaceAdmin = '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/marketplace'), '/');
        } catch (Throwable) {
            $marketplaceAdmin = null;
        }

        $this->render('pubvana/updates/admin/index', [
            'pageTitle'            => 'Updates',
            'state'                => $state,
            'auto'                 => $service->autoUpdateEnabled(),
            'skipped'              => $service->skippedVersions(),
            'preflight'            => $targetVersion !== '' ? $service->preFlight($targetVersion) : [],
            'addons'               => $addons,
            'progress'             => $progress,
            'running'              => $running,
            'is_locked'            => $locked,
            'interrupted'          => $interrupted,
            'changelog_url'        => $this->changelogUrl(),
            'adminBase'            => $this->adminBase(),
            'marketplaceConnected' => $addons['marketplaceConnected'],
            'marketplaceAdmin'     => $marketplaceAdmin,
        ]);
    }

    /**
     * Copy the addon row and attach its trust standing, defaulting to
     * 'not checked' when the addon has no checkable identity. Lookups run
     * against the page-level status map, not the cache table directly.
     *
     * @param array<string, mixed> $row
     * @param array{type: string, slug: string, version: string, author: string, origin: string}|null $item
     * @param array<string, array{status: string, warning: ?string, checked_at: string}> $statusMap
     * @return array<string, mixed>
     */
    private function stampAddonTrust(array $row, ?array $item, array $statusMap): array
    {
        $row['trust_status'] = 'none';
        $row['trust_warning'] = null;

        if ($item === null) {
            return $row;
        }

        $cached = $statusMap[TrustCache::compositeKey(
            $item['type'],
            $item['slug'],
            $item['version'],
            $item['author']
        )] ?? null;

        if ($cached !== null) {
            $row['trust_status'] = $cached['status'];
            $row['trust_warning'] = $cached['warning'];
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $info
     * @return array{type: string, slug: string, version: string, author: string, origin: string}|null
     */
    private function trustItemForPlugin(string $id, array $info): ?array
    {
        if ($info === []) {
            return null;
        }
        try {
            return $this->app->trustClient()->pluginItem($id, $info);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/updates'), '/');
    }

    /**
     * Force a release check (POST).
     */
    public function check(): void
    {
        if (!$this->guard()) {
            return;
        }

        try {
            $state = $this->service()->check(true);

            // Same action refreshes addon trust standings, one batch. A
            // trust outage must not mask the feed result.
            if ($state['status'] !== 'error') {
                try {
                    $this->app->trustClient()->recheckAll();
                } catch (Throwable) {
                    // Page re-renders with the previous or 'not checked' badges.
                }
            }

            if ($state['status'] === 'available') {
                $this->app->session()->flash('info', 'Version ' . $state['target_version'] . ' is available.');
            } elseif (!empty($state['capped_by'])) {
                $this->app->session()->flash('info', 'Version ' . (string) ($state['latest_version'] ?? '') . ' is available but held back by an installed addon.');
            } elseif ($state['status'] === 'up_to_date') {
                $this->app->session()->flash('success', 'You are running the latest version.');
            } else {
                $this->app->session()->flash('danger', 'Check failed: ' . ($state['error'] ?? 'unknown error'));
            }
        } catch (Throwable $e) {
            $this->app->session()->flash('danger', 'Check failed: ' . $e->getMessage());
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Apply the pending update (POST, AJAX).
     *
     * Prefers a backgrounded pubvana process; falls back to running
     * synchronously when exec is unavailable.
     */
    public function apply(): void
    {
        if (!$this->guard()) {
            return;
        }

        $locked = $this->isLocked();

        if ($locked) {
            $this->app->json(['status' => 'error', 'message' => 'An update or backup operation is already in progress.']);
            return;
        }

        $data    = $this->app->request()->data->getData();
        $confirm = isset($data['confirm_breaking']);
        $user    = $this->app->auth()->user();
        $by      = is_object($user) && isset($user->username) ? (string) $user->username : 'admin';

        $state  = $this->service()->lastCheck();
        $target = (string) ($state['target_version'] ?? '');

        if ($target === '') {
            $this->app->json(['status' => 'error', 'message' => 'No update is available to apply.']);
            return;
        }

        if ((array) ($state['breaking_changes'] ?? []) !== [] && !$confirm) {
            $this->app->json([
                'status'  => 'confirm_breaking',
                'message' => 'This update path contains breaking changes. Review them and confirm to apply.',
            ]);
            return;
        }

        if ($this->execAvailable()) {
            // Pin the version the admin confirmed: the child re-checks the
            // feed, so without --release a release published in between would
            // be applied instead of the one on the button.
            $cmd = sprintf(
                'php %s updates:apply --release %s --user %s > /dev/null 2>&1 &',
                escapeshellarg(PROJECT_ROOT . '/pubvana'),
                escapeshellarg($target),
                escapeshellarg($by)
            );
            exec($cmd);

            $this->app->json(['status' => 'started', 'method' => 'exec']);
            return;
        }

        @set_time_limit(600);

        $apply  = new UpdateApplyService($this->app, $this->pluginConfig());
        $result = $apply->apply($target, $by, true);

        if ($result) {
            $this->app->json(['status' => 'completed', 'method' => 'sync']);
            return;
        }

        $progress = (new UpdateProgress($this->service()->storageDir()))->read();
        // apply() writes its own failure into the progress file while it holds
        // the lock, so a missing error means it could not take the lock.
        $message  = is_array($progress) && !empty($progress['error'])
            ? (string) $progress['error']
            : 'The update could not start. Another operation may be running.';

        $this->app->json(['status' => 'error', 'method' => 'sync', 'message' => $message]);
    }

    /**
     * Poll update progress (GET, AJAX).
     */
    public function status(): void
    {
        $progress = (new UpdateProgress($this->service()->storageDir()))->read();
        $locked   = $this->isLocked();

        // A backgrounded update writes its first progress line only after the
        // process boots, so right after "started" the file can still hold the
        // previous run's payload, or nothing at all. While the lock is held
        // the run is live, so report a starting state instead of letting the
        // poll act on a stale payload or stop.
        if ($locked && (!is_array($progress) || ($progress['status'] ?? '') !== 'in_progress')) {
            $this->app->json([
                'status'      => 'in_progress',
                'phase_label' => 'Starting',
                'detail'      => '',
                'percent'     => 0,
                'phases'      => [],
            ]);
            return;
        }

        // The lock is gone but the payload still claims a run, so that
        // process died. Report a failure and stop the poll.
        if (!$locked && is_array($progress) && ($progress['status'] ?? '') === 'in_progress') {
            $this->app->json([
                'status'      => 'error',
                'phase_label' => (string) ($progress['phase_label'] ?? 'Update'),
                'detail'      => '',
                'percent'     => (int) ($progress['percent'] ?? 0),
                'phases'      => is_array($progress['phases'] ?? null) ? $progress['phases'] : [],
                'error'       => 'The update process stopped before it finished.',
            ]);
            return;
        }

        $this->app->json($progress ?? ['status' => 'idle']);
    }

    /**
     * Save the auto-update setting (POST, AJAX). The view shows the
     * confirmation inline, v2-style; no redirect.
     */
    public function settings(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $this->service()->setAutoUpdate(!empty($data['auto_update']));

        $this->app->json(['status' => 'ok', 'message' => 'Update settings saved.']);
    }

    /**
     * Skip the pending target version (POST).
     */
    public function skip(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data    = $this->app->request()->data->getData();
        $version = isset($data['version']) && is_string($data['version']) ? $data['version'] : '';

        if ($version !== '' && preg_match('/^[0-9A-Za-z.\-\+]+$/', $version) === 1) {
            $this->service()->skipVersion($version);
            $this->service()->check(true);
            $this->app->session()->flash('success', 'Version ' . $version . ' skipped. The next applicable release will be offered instead.');
        } else {
            $this->app->session()->flash('danger', 'Invalid version to skip.');
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Remove a version from the skip list (POST).
     */
    public function unskip(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data    = $this->app->request()->data->getData();
        $version = isset($data['version']) && is_string($data['version']) ? $data['version'] : '';

        if ($version !== '') {
            $this->service()->unskipVersion($version);
            $this->service()->check(true);
            $this->app->session()->flash('success', 'Version ' . $version . ' will be offered again.');
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Update a plugin or theme through the Marketplace (POST).
     *
     * The controller carries nothing but the package identity: the
     * Marketplace service owns the install contract (license validation,
     * download URL derivation, zip safety, extraction, record stamping).
     *
     * @package string the addon's manifest pubvana.json name ('pubvana/blog')
     */
    public function addonUpdate(): void
    {
        if (!$this->guard()) {
            return;
        }

        $package = trim((string) ($this->app->request()->data->package ?? ''));

        if ($package === '') {
            $this->app->session()->flash('danger', 'No package given for the update.');
            $this->app->redirect($this->adminBase());
            return;
        }

        try {
            $marketplace = $this->app->marketplace();
            $result = $marketplace->installFromPackage($package);
        } catch (Throwable $e) {
            $this->app->session()->flash('danger', 'The Marketplace is not available: ' . $e->getMessage());
            $this->app->redirect($this->adminBase());
            return;
        }

        if (!empty($result['ok'])) {
            $this->app->session()->flash('success', 'Package updated' . ($result['version'] !== null ? ' to version ' . $result['version'] : '') . '.');
        } else {
            $this->app->session()->flash('danger', $result['reason']);
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Force a fresh Marketplace catalog fetch (POST, "Check all").
     *
     * The controller only fans the request out to the Marketplace facade;
     * catalog refresh rules live there.
     */
    public function addonCheck(): void
    {
        if (!$this->guard()) {
            return;
        }

        try {
            $this->app->session()->flash('success', 'Checked the Marketplace catalog for addon updates.');
        } catch (Throwable $e) {
            $this->app->session()->flash('danger', 'The Marketplace is not available: ' . $e->getMessage());
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Apply every addon update the Marketplace reports as available
     * (POST, "Update all").
     *
     * Synchronous by design, matching Marketplace's own reinstall-all
     * loop: each item runs the single shared install path
     * installFromPackage(), so a failure is reported per item and never
     * stops the batch.
     */
    public function addonUpdateAll(): void
    {
        if (!$this->guard()) {
            return;
        }

        @set_time_limit(600);

        try {
            $marketplace = $this->app->marketplace();
        } catch (Throwable $e) {
            $this->app->session()->flash('danger', 'The Marketplace is not available: ' . $e->getMessage());
            $this->app->redirect($this->adminBase());
            return;
        }

        $updates = $marketplace->checkAddonUpdates();

        // Batch = exactly what rows offer the one-click Update button:
        // every tracked package with a license-path update, plus free
        // packages whose store version beats the one on disk.
        $packages = [];
        foreach ($updates as $package => $meta) {
            $packages[] = $package;
        }
        foreach ($this->service()->addons() as $section) {
            if (!is_array($section) || $section === []) {
                continue;
            }
            foreach ($section as $row) {
                $package = $row['package'] ?? null;
                $update = $row['update'] ?? null;
                if (is_string($package) && $package !== '' && is_array($update) && ($update['latest_version'] ?? '') !== ''
                    && !in_array($package, $packages, true)) {
                    $packages[] = $package;
                }
            }
        }

        if ($packages === []) {
            $this->app->session()->flash('info', 'No addon updates are available.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $ok = 0;
        $failed = [];
        foreach ($packages as $package) {
            $result = $marketplace->installFromPackage($package);

            if (!empty($result['ok'])) {
                $ok++;
            } else {
                $failed[] = ['package' => $package, 'reason' => (string) $result['reason']];
            }
        }

        if ($failed !== []) {
            $message = 'Updated ' . $ok . ' of ' . count($packages) . ' packages. Failed: ';
            foreach ($failed as $index => $f) {
                $message .= ($index > 0 ? '; ' : '') . $f['package'] . ' (' . $f['reason'] . ')';
            }
            $this->app->session()->flash('warning', $message);
        } else {
            $this->app->session()->flash('success', 'Updated ' . $ok . ' package' . ($ok === 1 ? '' : 's') . '.');
        }

        $this->app->redirect($this->adminBase());
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function service(): UpdateService
    {
        return $this->app->updates();
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginConfig(): array
    {
        $config = $this->app->get($this->configPrepend);

        return is_array($config) ? $config : [];
    }

    /**
     * Permission gate: flash + redirect, never halt.
     */
    private function guard(): bool
    {
        $user = $this->app->auth()->user();

        if ($user === null || !$user->can('updates.manage')) {
            $this->app->session()->flash('error', 'You do not have permission to manage updates.');
            $this->app->redirect('/admin');
            return false;
        }

        return true;
    }

    private function isLocked(): bool
    {
        return UpdateProgress::isLockedInDir($this->service()->storageDir());
    }

    /**
     * Kick the auto-update chain as a background pubvana process.
     */
    private function startBackgroundAutoUpdate(): void
    {
        $user = $this->app->auth()->user();
        $by   = is_object($user) && isset($user->username) ? (string) $user->username : 'auto';

        $cmd = sprintf(
            'php %s updates:auto-update --user %s > /dev/null 2>&1 &',
            escapeshellarg(PROJECT_ROOT . '/pubvana'),
            escapeshellarg($by)
        );
        exec($cmd);
    }

    private function changelogUrl(): string
    {
        return 'https://github.com/Pubvana-CMS/pubvana/blob/main/CHANGELOG.md';
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions') ?: '';

        return !in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }
}
