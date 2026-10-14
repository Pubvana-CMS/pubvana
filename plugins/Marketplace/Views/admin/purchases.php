<?php
/**
 * Purchases - admin page.
 *
 * @var string $pageTitle
 * @var bool $connected
 * @var array<int, array<string, mixed>> $records
 * @var bool $syncOk
 * @var string $syncReason
 * @var string $adminBase
 * @var string|null $updatesBase
 * @var string $siteDomain
 */
?>

<?php if (!$connected): ?>
    <div class="alert alert-info">Connect a Pubvana account to verify and manage your purchases.</div>
<?php else: ?>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">Purchases</h1>
        <div>
            <form method="POST" action="<?= $adminBase ?>/verify" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button class="btn btn-outline-primary">Sync with Pubvana Store</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Assigned</th>
                        <th>Tier</th>
                        <th>Installed</th>
                        <th>Version</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4">No purchases yet. Browse the catalog and check out on pubvanacms.com.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($r['product_name'] ?? '')) ?></strong>
                                </td>
                                <td><span class="badge bg-secondary-lt"><?= htmlspecialchars((string) ($r['item_type'] ?? '')) ?></span></td>
                                <td>
                                    <?php if (!empty($r['assigned'])): ?>
                                        <span class="badge bg-success-lt">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-lt">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(ucfirst((string) ($r['tier'] ?? ''))) ?: '-' ?></td>
                                <td>
                                    <?php if (!empty($r['installed'])): ?>
                                        <span class="badge bg-success-lt">Installed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">Not installed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Installed shows what is on disk; not installed
                                    // shows the version an install would use.
                                    $versionLabel = (string) ($r['installed_version'] ?? '');
                                    if ($versionLabel === '') {
                                        $versionLabel = (string) ($r['default_version'] ?? '');
                                    }
                                    ?>
                                    <?= htmlspecialchars($versionLabel) ?: '-' ?>
                                </td>
                                <td>
                                    <?php if (empty($r['license_valid'])): ?>
                                        <?php if (!empty($r['can_install'])): ?>
                                        <div class="form-hint">Available: <?= htmlspecialchars(implode(', ', array_map(static fn ($rel): string => 'v' . (string) ($rel['version'] ?? ''), (array) ($r['releases'] ?? [])))) ?></div>
                                        <?php else: ?>
                                        <div class="form-hint"><?= htmlspecialchars((string) ($r['install_note'] ?? ($syncReason !== '' ? $syncReason : 'No download available from the store.'))) ?></div>
                                        <?php endif; ?>
                                    <?php elseif (!empty($r['can_install'])): ?>
                                        <?php if (!empty($r['already_installed'])): ?>
                                        <div class="form-hint">Already Installed</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="form-hint"><?= htmlspecialchars((string) ($r['install_note'] ?? ($syncReason !== '' ? $syncReason : 'No download available from the store.'))) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (empty($r['license_valid'])): ?>
                                        <?php if (!empty($r['can_install'])): ?>
                                        <form method="POST" action="<?= $adminBase ?>/assign" class="d-inline"
                                              onsubmit="return confirm('Assign <?= htmlspecialchars((string) ($r['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($siteDomain, ENT_QUOTES, 'UTF-8') ?>? You can manage assignments to domains at the Pubvana Store');">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="record_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                            <button class="btn btn-sm btn-primary">Assign to <?= htmlspecialchars($siteDomain) ?></button>
                                        </form>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>None Available</button>
                                        <?php endif; ?>
                                    <?php elseif (!empty($r['can_install'])): ?>
                                        <?php if (empty($r['already_installed'])): ?>
                                            <?php if (!empty($r['installed'])): ?>
                                            <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal" data-bs-target="#reinstall-<?= (int) ($r['id'] ?? 0) ?>">
                                                <i class="ti ti-alert-triangle me-1"></i>Reinstall
                                            </button>
                                            <?php else: ?>
                                            <form method="POST" action="<?= $adminBase ?>/install" class="d-inline"
                                                  onsubmit="return confirm('Install <?= htmlspecialchars((string) ($r['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> v<?= htmlspecialchars((string) ($r['default_version'] ?? ''), ENT_QUOTES, 'UTF-8') ?>? Installing ends your ability to receive a refund. A Backup is performed before installation, please allow a moment to complete');">
                                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="record_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                                <button class="btn btn-sm btn-primary">Install</button>
                                            </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>None Available</button>
                                        <?php endif; ?>
                                        <?php
                                        // Updating addons is the Updates plugin's job. When
                                        // the store answers with a newer covered release for
                                        // an addon installed here, point there instead of
                                        // updating from this page.
                                        $coveredVersion = (string) ($r['default_version'] ?? '');
                                        $hasAddonUpdate = !empty($r['installed'])
                                            && (string) ($r['installed_version'] ?? '') !== ''
                                            && $coveredVersion !== ''
                                            && version_compare($coveredVersion, (string) $r['installed_version'], '>');
                                        ?>
                                        <?php if ($hasAddonUpdate && $updatesBase !== null): ?>
                                        <div class="mt-1">
                                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($updatesBase) ?>">Update available: v<?= htmlspecialchars($coveredVersion) ?></a>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>None Available</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php foreach ($records as $r): ?>
        <?php if (empty($r['license_valid']) || empty($r['can_install']) || empty($r['installed'])): continue; endif; ?>
        <?php
        $installedVersion = (string) ($r['installed_version'] ?? '');
        $releaseVersions = array_map(static fn ($rel): string => (string) ($rel['version'] ?? ''), (array) ($r['releases'] ?? []));
        $selectedVersion = $installedVersion !== '' && in_array($installedVersion, $releaseVersions, true)
            ? $installedVersion
            : (string) ($r['default_version'] ?? '');
        ?>
        <div class="modal modal-blur fade" id="reinstall-<?= (int) ($r['id'] ?? 0) ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="POST" action="<?= $adminBase ?>/install">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="record_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Reinstall <?= htmlspecialchars((string) ($r['product_name'] ?? '')) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <strong>Reinstalling an addon deletes installed files, downloads the chosen version and optionally includes the database tables.</strong> A backup is performed before any changes are made.
                            </div>
                            <div class="mb-3">
                                <div class="form-label">Database</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="database" id="database-preserve-<?= (int) ($r['id'] ?? 0) ?>" value="preserve" checked>
                                    <label class="form-check-label" for="database-preserve-<?= (int) ($r['id'] ?? 0) ?>">Preserve database</label>
                                    <div class="form-hint">Keep this addon's tables and data unchanged.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="database" id="database-clear-<?= (int) ($r['id'] ?? 0) ?>" value="clear">
                                    <label class="form-check-label" for="database-clear-<?= (int) ($r['id'] ?? 0) ?>"><i class="ti ti-alert-triangle me-1 text-danger"></i>Clear database</label>
                                    <div class="form-hint">Deletes all tables and rebuilds them. <b>All data is destroyed</b>.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="version-<?= (int) ($r['id'] ?? 0) ?>">Version</label>
                                <select name="version" id="version-<?= (int) ($r['id'] ?? 0) ?>" class="form-select">
                                    <?php foreach ((array) ($r['releases'] ?? []) as $rel): ?>
                                    <?php $relVersion = (string) ($rel['version'] ?? ''); ?>
                                    <option value="<?= htmlspecialchars($relVersion) ?>"<?= $relVersion === $selectedVersion ? ' selected' : '' ?>>v<?= htmlspecialchars($relVersion) ?><?= !empty($rel['max_pubvana']) ? ' (Pubvana to ' . htmlspecialchars((string) $rel['max_pubvana']) . ')' : '' ?><?= $relVersion === (string) ($r['default_version'] ?? '') ? ' (latest)' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($updatesBase !== null): ?>
                            <div class="form-hint">
                                <a href="<?= htmlspecialchars($updatesBase) ?>">Did you mean to Update?</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Proceed</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!$syncOk): ?>
    <div class="modal modal-blur fade" id="store-unavailable-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Store unavailable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?= htmlspecialchars($syncReason !== '' ? $syncReason : 'The store could not be reached. Try again in a little while.') ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var el = document.getElementById('store-unavailable-modal');
            if (el && window.bootstrap && window.bootstrap.Modal) {
                new bootstrap.Modal(el).show();
            }
        })();
    </script>
    <?php endif; ?>

<?php endif; ?>