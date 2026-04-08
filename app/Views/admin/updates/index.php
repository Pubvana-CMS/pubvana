<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.updatesTitle') ?></h1>
    <form method="POST" action="<?= base_url('admin/updates/check') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrows-rotate fa-sm"></i> <?= lang('Admin.updatesCheckBtn') ?>
        </button>
    </form>
</div>

<!-- Update Settings -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-gear fa-sm mr-1"></i> <?= lang('Admin.updatesSettingsTitle') ?></h6>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <!-- Auto-Update Toggle -->
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="d-block font-weight-bold small mb-1"><?= lang('Admin.updatesAutoUpdateLabel') ?></label>
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-sm btn-outline-secondary <?= !$auto_update ? 'active' : '' ?>">
                        <input type="radio" name="auto_update" value="0" <?= !$auto_update ? 'checked' : '' ?>> <?= lang('Admin.updatesAutoUpdateManual') ?>
                    </label>
                    <label class="btn btn-sm btn-outline-secondary <?= $auto_update ? 'active' : '' ?>">
                        <input type="radio" name="auto_update" value="1" <?= $auto_update ? 'checked' : '' ?>> <?= lang('Admin.updatesAutoUpdateAuto') ?>
                    </label>
                </div>
                <small class="form-text text-muted"><?= lang('Admin.updatesAutoUpdateHelp') ?></small>
            </div>

            <!-- Check Method Toggle -->
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="d-block font-weight-bold small mb-1"><?= lang('Admin.updatesCheckMethodLabel') ?></label>
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-sm btn-outline-secondary <?= $check_method === 'pageload' ? 'active' : '' ?>">
                        <input type="radio" name="check_method" value="pageload" <?= $check_method === 'pageload' ? 'checked' : '' ?>> <?= lang('Admin.updatesCheckMethodPageload') ?>
                    </label>
                    <label class="btn btn-sm btn-outline-secondary <?= $check_method === 'cron' ? 'active' : '' ?>">
                        <input type="radio" name="check_method" value="cron" <?= $check_method === 'cron' ? 'checked' : '' ?>> <?= lang('Admin.updatesCheckMethodCron') ?>
                    </label>
                </div>
                <small class="form-text text-muted"><?= lang('Admin.updatesCheckMethodHelp') ?></small>
            </div>

            <!-- Cron Command (shown when cron selected) -->
            <div class="col-md-4" id="cronCommandBlock" style="<?= $check_method === 'cron' ? '' : 'display:none;' ?>">
                <label class="d-block font-weight-bold small mb-1"><?= lang('Admin.updatesCronCommand') ?></label>
                <?php $phpBin = PHP_BINARY ?: (PHP_BINDIR . '/php'); ?>
                <code class="d-block bg-light p-2 rounded small user-select-all mb-1">* * * * * <?= esc($phpBin) ?> <?= esc(ROOTPATH) ?>spark cron minute >> /dev/null 2>&1</code>
                <code class="d-block bg-light p-2 rounded small user-select-all mb-1">0 */6 * * * <?= esc($phpBin) ?> <?= esc(ROOTPATH) ?>spark cron quarterday >> /dev/null 2>&1</code>
                <code class="d-block bg-light p-2 rounded small user-select-all">0 2 * * * <?= esc($phpBin) ?> <?= esc(ROOTPATH) ?>spark cron daily >> /dev/null 2>&1</code>
                <small class="form-text text-muted"><?= lang('Admin.updatesCronHelp') ?></small>
            </div>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-sm btn-primary" id="saveUpdateSettingsBtn">
                <i class="fas fa-save fa-sm mr-1"></i> <?= lang('Admin.save') ?>
            </button>
            <span class="ml-2 small text-success" id="settingsSavedMsg" style="display:none;">
                <i class="fas fa-check-circle"></i> <?= lang('Admin.updatesSettingsSaved') ?>
            </span>
        </div>
    </div>
</div>

<!-- Progress bar (hidden by default) -->
<div id="update-progress" class="card shadow mb-4" style="display:none;">
    <div class="card-body">
        <h6 class="font-weight-bold" id="progress-label"><?= lang('Admin.updateStarting') ?></h6>
        <div class="progress mb-2">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar"
                 role="progressbar" style="width: 0%"></div>
        </div>
        <small class="text-muted" id="progress-detail"></small>
    </div>
</div>

<?php if (!empty($update['error'])): ?>
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation mr-1"></i>
        <strong><?= lang('Admin.updateCheckLabel') ?></strong> <?= esc($update['error']) ?>
    </div>

<?php elseif (!empty($update['available'])): ?>
    <?php if ($update['safe_target'] === null): ?>
        <!-- State 3: No safe target — all paths have incompatible extensions -->
        <div class="alert alert-warning">
            <i class="fas fa-circle-arrow-up mr-1"></i>
            <strong><?= lang('Admin.updateAvailable', [esc($update['latest_version'])]) ?></strong>
            — <?= lang('Admin.updateRunning', [esc($update['current_version'])]) ?>
            <?= lang('Admin.compatNotCompatible') ?>
        </div>
    <?php elseif ($capped_by_extensions): ?>
        <!-- State 2: Safe target is lower than latest -->
        <div class="alert alert-info">
            <i class="fas fa-circle-arrow-up mr-1"></i>
            <strong><?= lang('Admin.updateAvailable', [esc($effective_target)]) ?></strong>
            <?= lang('Admin.updateRunning', [esc($update['current_version'])]) ?>
        </div>
        <div class="alert alert-secondary">
            <i class="fas fa-info-circle mr-1"></i>
            <strong><?= lang('Admin.updateAvailable', [esc($update['latest_version'])]) ?></strong>
            <?= lang('Admin.compatRequiresUpdate') ?>
            <ul class="mb-0 mt-1">
                <?php foreach ($incompatible as $ext): ?>
                    <li><?= esc(ucfirst($ext['type'])) ?>: <strong><?= esc($ext['name']) ?></strong>
                        (<?= ! empty($ext['min_version'])
                            ? lang('Admin.compatRequiresMin', [esc($ext['min_version'])])
                            : lang('Admin.compatSupportsUpTo', [esc($ext['max_version'])]) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <!-- State 1: Safe target = latest, no issues -->
        <div class="alert alert-info">
            <i class="fas fa-circle-arrow-up mr-1"></i>
            <strong><?= lang('Admin.updateAvailable', [esc($effective_target)]) ?></strong>
            <?= lang('Admin.updateRunning', [esc($update['current_version'])]) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($breaking_changes)): ?>
    <div class="alert alert-danger">
        <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> <?= lang('Admin.updateBreakingChanges') ?></h6>
        <ul class="mb-0">
            <?php foreach ($breaking_changes as $bc): ?>
                <li><?= esc($bc) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($migration_notes)): ?>
    <div class="alert alert-warning">
        <h6 class="font-weight-bold"><i class="fas fa-database mr-1"></i> <?= lang('Admin.updateMigrationNotes') ?></h6>
        <ul class="mb-0">
            <?php foreach ($migration_notes as $mn): ?>
                <li><?= esc($mn) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($notices)): ?>
    <div class="alert alert-info">
        <h6 class="font-weight-bold"><i class="fas fa-info-circle mr-1"></i> <?= lang('Admin.updateNotices') ?></h6>
        <ul class="mb-0">
            <?php foreach ($notices as $n): ?>
                <li><?= esc($n) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Pre-flight Checks -->
    <?php if (!empty($checks)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.updatePreflightTitle') ?></h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                <?php foreach ($checks as $check): ?>
                    <tr>
                        <td class="pl-3" style="width:30px;">
                            <?php if ($check['pass']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-<?= $check['hard'] ? 'danger' : 'warning' ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($check['name']) ?></td>
                        <td><?= esc($check['message']) ?></td>
                        <td class="text-right pr-3">
                            <span class="badge badge-<?= $check['hard'] ? 'danger' : 'secondary' ?>">
                                <?= $check['hard'] ? lang('Admin.required') : lang('Admin.optional') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Update Now -->
    <button type="button" class="btn btn-primary btn-lg mb-3" id="updateBtn"
            <?= (!$all_hard_pass || !empty($is_locked)) ? 'disabled' : '' ?>>
        <i class="fas fa-rocket mr-1"></i> <?= lang('Admin.updateToVersion', [esc($effective_target)]) ?>
    </button>
    <?php if (!$all_hard_pass): ?>
        <p class="text-danger small"><?= lang('Admin.updatePreflightFailed') ?></p>
    <?php endif; ?>

    <?php if (!$can_download): ?>
    <div class="alert alert-warning">
        <i class="fas fa-upload mr-1"></i>
        <strong><?= lang('Admin.updatesNoDownloadMethod') ?? 'No download method available.' ?></strong>
    </div>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-success">
        <i class="fas fa-circle-check mr-1"></i>
        <?= lang('Admin.updateUpToDate', [esc($update['current_version'])]) ?>
    </div>
<?php endif; ?>

<!-- Compatibility Warning Modal (State 3: no safe target) -->
<?php if (!empty($incompatible) && $update['safe_target'] === null): ?>
<div class="modal fade" id="compatibilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-1"></i> <?= lang('Admin.compatWarningTitle') ?></h5>
                <button class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p><?= lang('Admin.compatNotDeclared', [esc($effective_target)]) ?></p>
                <table class="table table-sm mb-3">
                    <thead class="bg-light">
                        <tr><th><?= lang('Admin.compatColType') ?></th><th><?= lang('Admin.compatColName') ?></th><th><?= lang('Admin.compatColVersion') ?></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($incompatible as $ext): ?>
                        <tr>
                            <td><span class="badge badge-secondary"><?= esc(ucfirst($ext['type'])) ?></span></td>
                            <td><?= esc($ext['name']) ?></td>
                            <td><?= ! empty($ext['min_version'])
                                ? lang('Admin.compatRequiresMin', [esc($ext['min_version'])])
                                : lang('Admin.compatSupportsUpTo', [esc($ext['max_version'])]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="small text-muted mb-0"><?= lang('Admin.compatRemoveHint') ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.cancel') ?></button>
                <button class="btn btn-warning" id="confirmUpdateBtn"><?= lang('Admin.updateAnyway') ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CMS Update Confirmation Modal (normal path, no incompatible extensions) -->
<div class="modal fade" id="confirmUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-rocket mr-1"></i> <?= lang('Admin.updatesConfirmTitle') ?></h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p><?= lang('Admin.updatesConfirmBody') ?></p>
                <p class="small text-muted mb-0"><?= lang('Admin.updatesConfirmSafe') ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.cancel') ?></button>
                <button class="btn btn-primary" id="confirmCmsUpdateBtn"><i class="fas fa-rocket mr-1"></i> <?= lang('Admin.updatesConfirmBtn') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Extension Update All Confirmation Modal -->
<div class="modal fade" id="updateAllAddonsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-download mr-1"></i> <?= lang('Admin.updatesExtAllTitle') ?></h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p><?= lang('Admin.updatesExtAllBody') ?></p>
                <p class="small text-muted mb-0"><?= lang('Admin.updatesExtAllNote') ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.cancel') ?></button>
                <button class="btn btn-primary" id="confirmUpdateAllAddonsBtn"><i class="fas fa-download mr-1"></i> <?= lang('Admin.updatesExtAllBtn') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Extension Updates -->
<!-- ============================================================ -->
<hr class="my-5">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0 text-gray-800"><?= lang('Admin.updatesExtTitle') ?></h2>
    <div>
        <button class="btn btn-sm btn-outline-primary mr-1" id="checkAllAddonsBtn">
            <i class="fas fa-arrows-rotate fa-sm"></i> <?= lang('Admin.updatesExtCheckAll') ?>
        </button>
        <button class="btn btn-sm btn-primary" id="updateAllAddonsBtn">
            <i class="fas fa-download fa-sm"></i> <?= lang('Admin.updatesExtUpdateAll') ?>
        </button>
    </div>
</div>

<?php
function renderExtensionTable(string $label, string $type, array $rows, array $meta): void {
    $lcType = strtolower($type);
?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><?= $label ?></h6>
        <div>
            <button class="btn btn-xs btn-outline-secondary ext-check-type" data-type="<?= $lcType ?>">
                <i class="fas fa-arrows-rotate fa-xs"></i> <?= lang('Admin.updatesExtCheckAllType', [$label]) ?>
            </button>
            <button class="btn btn-xs btn-outline-primary ext-update-type" data-type="<?= $lcType ?>">
                <i class="fas fa-download fa-xs"></i> <?= lang('Admin.updatesExtUpdateAllType', [$label]) ?>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-3 mb-0"><?= lang('Admin.updatesExtNoInstalled', [strtolower($label)]) ?></p>
        <?php else: ?>
        <table class="table table-sm table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="pl-3"><?= lang('Admin.updatesExtColName') ?></th>
                    <th><?= lang('Admin.updatesExtColVersion') ?></th>
                    <th><?= lang('Admin.updatesExtColLatest') ?></th>
                    <th><?= lang('Admin.updatesExtColAutoUpdate') ?></th>
                    <th><?= lang('Admin.updatesExtColStatus') ?></th>
                    <th class="text-right pr-3"><?= lang('Admin.updatesExtColActions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row):
                $folder    = $row->folder;
                $hasMeta   = isset($meta[$folder]);
                $hasUrl    = ! empty($meta[$folder]['update_url'] ?? null);
                $isBundled = ! empty($meta[$folder]['bundled'] ?? false);
                $supportUrl = $meta[$folder]['support_url'] ?? null;
                $hasUpdate = $hasUrl && ! $isBundled && ! empty($row->latest_version) && version_compare($row->latest_version, $row->version ?? '0.0.0', '>');
            ?>
                <tr id="ext-row-<?= esc($lcType) ?>-<?= esc($folder) ?>" data-type="<?= $lcType ?>" data-slug="<?= esc($folder) ?>">
                    <td class="pl-3 align-middle"><?= esc($row->name) ?></td>
                    <td class="align-middle"><?= esc($row->version ?? '-') ?></td>
                    <td class="align-middle">
                        <?php if ($hasUpdate): ?>
                            <span class="text-success font-weight-bold"><?= esc($row->latest_version) ?></span>
                            <?php if (! empty($row->changelog)): ?>
                            <a href="#" class="ml-1 text-muted" data-toggle="popover" data-trigger="hover"
                               data-content="<?= esc($row->changelog) ?>" title="Changelog">
                                <i class="fas fa-file-lines fa-xs"></i>
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <?php if ($isBundled): ?>
                            <span class="badge badge-secondary"><?= lang('Admin.updatesExtBundled') ?></span>
                        <?php elseif ($hasUrl): ?>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input ext-auto-toggle"
                                   id="auto-<?= esc($lcType) ?>-<?= esc($folder) ?>"
                                   data-type="<?= $lcType ?>" data-slug="<?= esc($folder) ?>"
                                   <?= (int)($row->auto_update ?? 0) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="auto-<?= esc($lcType) ?>-<?= esc($folder) ?>"></label>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle ext-status">
                        <?php if (! $hasUrl): ?>
                            <span class="text-muted small"><?= lang('Admin.updatesExtNoSource') ?></span>
                        <?php elseif (($row->last_update_attempt ?? '') === 'fail'): ?>
                            <span class="text-danger small" title="<?= esc($row->last_update_error ?? '') ?>">
                                <i class="fas fa-times-circle"></i> <?= lang('Admin.updatesExtFailed') ?>
                            </span>
                        <?php elseif (($row->last_update_attempt ?? '') === 'success' && ! empty($row->last_updated_at)): ?>
                            <span class="text-success small">
                                <i class="fas fa-check-circle"></i> <?= lang('Admin.updatesExtUpdatedAt', [esc($row->last_updated_at)]) ?>
                            </span>
                        <?php elseif ($hasUpdate): ?>
                            <span class="text-info small"><i class="fas fa-circle-arrow-up"></i> <?= lang('Admin.updatesExtAvailable') ?></span>
                        <?php else: ?>
                            <span class="text-muted small"><?= lang('Admin.updatesExtUpToDate') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right pr-3 align-middle">
                        <?php if (! $isBundled && $hasUrl): ?>
                        <button class="btn btn-xs btn-outline-secondary ext-check-btn"
                                data-type="<?= $lcType ?>" data-slug="<?= esc($folder) ?>">
                            <i class="fas fa-arrows-rotate fa-xs"></i>
                        </button>
                        <?php if ($hasUpdate): ?>
                        <button class="btn btn-xs btn-primary ext-update-btn"
                                data-type="<?= $lcType ?>" data-slug="<?= esc($folder) ?>">
                            <i class="fas fa-download fa-xs"></i> <?= lang('Admin.updatesExtUpdate') ?>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php } ?>

<?php renderExtensionTable('Themes', 'theme', $ext_themes, $ext_meta); ?>
<?php renderExtensionTable('Widgets', 'widget', $ext_widgets, $ext_meta); ?>
<?php renderExtensionTable('Plugins', 'plugin', $ext_plugins, $ext_meta); ?>

<?php $content = ob_get_clean(); ?>
<?php
$_settingsUrl   = base_url('admin/updates/save-settings');
$_applyUrl      = base_url('admin/updates/apply');
$_statusUrl     = base_url('admin/updates/status');
$_showModal     = (!empty($incompatible) && $update['safe_target'] === null) ? 'true' : 'false';
$_csrfName      = csrf_token();
$_csrfHash      = csrf_hash();
$_addonBaseUrl  = base_url('admin/updates/');
$_langChecking  = lang('Admin.updatesExtChecking');
$_langUpdating  = lang('Admin.updatesExtUpdating');
$_langUpdated   = lang('Admin.updatesExtUpdated');
$_langFailed    = lang('Admin.updatesExtFailed');
$extra_scripts = <<<SCRIPT
<script>
(function() {
    var btn  = document.getElementById('updateBtn');
    if (!btn) return;

    var prog         = document.getElementById('update-progress');
    var applyUrl     = '{$_applyUrl}';
    var statusUrl    = '{$_statusUrl}';
    var showModal    = {$_showModal};

    btn.addEventListener('click', function() {
        if (showModal) {
            $('#compatibilityModal').modal('show');
        } else {
            $('#confirmUpdateModal').modal('show');
        }
    });

    var confirmBtn = document.getElementById('confirmUpdateBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            $('#compatibilityModal').modal('hide');
            startUpdate();
        });
    }

    var confirmCmsBtn = document.getElementById('confirmCmsUpdateBtn');
    if (confirmCmsBtn) {
        confirmCmsBtn.addEventListener('click', function() {
            $('#confirmUpdateModal').modal('hide');
            startUpdate();
        });
    }

    function startUpdate() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> <?= lang("Admin.updateStarting") ?>';
        prog.style.display = 'block';

        fetch(applyUrl, { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'error') {
                    showError(data.message || 'Unknown error');
                    return;
                }
                if (data.status === 'completed') {
                    showComplete();
                    return;
                }
                pollUpdateStatus();
            })
            .catch(function(err) {
                showError('Request failed: ' + err.message);
            });
    }

    function pollUpdateStatus() {
        var pollStart = Date.now();
        var sawProgress = false;
        var interval = setInterval(function() {
            fetch(statusUrl)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'in_progress') {
                        sawProgress = true;
                        var pct = data.steps_total > 0
                            ? Math.round((data.steps_completed / data.steps_total) * 100)
                            : 0;
                        document.getElementById('progress-bar').style.width = pct + '%';
                        document.getElementById('progress-label').textContent = data.step_label || 'Working...';
                        document.getElementById('progress-detail').textContent = data.detail || '';
                    } else if (data.status === 'completed') {
                        clearInterval(interval);
                        showComplete();
                    } else if (data.status === 'error') {
                        clearInterval(interval);
                        showError(data.error || 'Unknown error');
                    } else if (data.status === 'idle') {
                        // Background process hasn't written progress yet — or failed silently
                        var elapsed = Date.now() - pollStart;
                        if (elapsed > 15000) {
                            clearInterval(interval);
                            showError('The background update process did not start. Check server logs for details.');
                        }
                    }
                });
        }, 500);
    }

    function showComplete() {
        document.getElementById('progress-bar').style.width = '100%';
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-success');
        document.getElementById('progress-label').textContent = 'Update complete!';
        setTimeout(function() { location.reload(); }, 2000);
    }

    function showError(msg) {
        document.getElementById('progress-label').textContent = 'Error: ' + msg;
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rocket mr-1"></i> Retry Update';
    }
})();

// ============================================================
// Extension addon updates
// ============================================================
(function() {
    var csrfName  = '{$_csrfName}';
    var csrfHash  = '{$_csrfHash}';
    var baseUrl   = '{$_addonBaseUrl}';

    function post(endpoint, data, callback) {
        data[csrfName] = csrfHash;
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);

        fetch(baseUrl + endpoint, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                // Update CSRF hash for next request
                if (json.csrf_hash) csrfHash = json.csrf_hash;
                callback(json);
            })
            .catch(function(err) { console.error(endpoint, err); });
    }

    function setRowStatus(type, slug, html) {
        var row = document.getElementById('ext-row-' + type + '-' + slug);
        if (row) {
            var cell = row.querySelector('.ext-status');
            if (cell) cell.innerHTML = html;
        }
    }

    function setRowSpinner(type, slug) {
        setRowStatus(type, slug, '<i class="fas fa-spinner fa-spin text-muted"></i>');
    }

    // Auto-update toggles
    document.querySelectorAll('.ext-auto-toggle').forEach(function(el) {
        el.addEventListener('change', function() {
            post('toggle-auto-update', {
                type: this.dataset.type,
                slug: this.dataset.slug,
                enabled: this.checked ? '1' : '0'
            }, function() {});
        });
    });

    // Per-row Check button
    document.querySelectorAll('.ext-check-btn').forEach(function(el) {
        el.addEventListener('click', function() {
            var type = this.dataset.type, slug = this.dataset.slug;
            setRowSpinner(type, slug);
            post('check-addon', { type: type, slug: slug }, function() {
                location.reload();
            });
        });
    });

    // Per-row Update button
    document.querySelectorAll('.ext-update-btn').forEach(function(el) {
        el.addEventListener('click', function() {
            var type = this.dataset.type, slug = this.dataset.slug;
            setRowSpinner(type, slug);
            this.disabled = true;
            post('update-addon', { type: type, slug: slug }, function(json) {
                if (json.status === 'ok') {
                    setRowStatus(type, slug, '<span class="text-success small"><i class="fas fa-check-circle"></i> {$_langUpdated}</span>');
                } else {
                    setRowStatus(type, slug, '<span class="text-danger small"><i class="fas fa-times-circle"></i> ' + (json.message || '{$_langFailed}') + '</span>');
                }
                setTimeout(function() { location.reload(); }, 1500);
            });
        });
    });

    // Check All (global)
    var checkAllBtn = document.getElementById('checkAllAddonsBtn');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function() {
            checkAllBtn.disabled = true;
            checkAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm"></i> {$_langChecking}';
            post('check-all-addons', {}, function() {
                location.reload();
            });
        });
    }

    // Update All (global)
    var updateAllBtn = document.getElementById('updateAllAddonsBtn');
    if (updateAllBtn) {
        updateAllBtn.addEventListener('click', function() {
            $('#updateAllAddonsModal').modal('show');
        });
    }

    var confirmUpdateAllBtn = document.getElementById('confirmUpdateAllAddonsBtn');
    if (confirmUpdateAllBtn) {
        confirmUpdateAllBtn.addEventListener('click', function() {
            $('#updateAllAddonsModal').modal('hide');
            updateAllBtn.disabled = true;
            updateAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm"></i> {$_langUpdating}';
            post('update-all-addons', {}, function() {
                location.reload();
            });
        });
    }

    // Per-type Check All / Update All
    document.querySelectorAll('.ext-check-type').forEach(function(el) {
        el.addEventListener('click', function() {
            // For per-type, we just do a full check and reload
            el.disabled = true;
            post('check-all-addons', {}, function() { location.reload(); });
        });
    });

    document.querySelectorAll('.ext-update-type').forEach(function(el) {
        el.addEventListener('click', function() {
            el.disabled = true;
            post('update-all-addons', {}, function() { location.reload(); });
        });
    });

    // Init Bootstrap popovers for changelogs
    $('[data-toggle="popover"]').popover();
})();

// ============================================================
// Update Settings (auto-update + check method toggles)
// ============================================================
(function() {
    var csrfName   = '{$_csrfName}';
    var csrfHash   = '{$_csrfHash}';
    var settingsUrl = '{$_settingsUrl}';
    var cronBlock   = document.getElementById('cronCommandBlock');
    var saveBtn     = document.getElementById('saveUpdateSettingsBtn');
    var savedMsg    = document.getElementById('settingsSavedMsg');

    // Show/hide cron command when check method changes
    document.querySelectorAll('input[name="check_method"]').forEach(function(el) {
        el.addEventListener('change', function() {
            cronBlock.style.display = this.value === 'cron' ? '' : 'none';
        });
    });

    // Save settings
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var autoUpdate  = document.querySelector('input[name="auto_update"]:checked').value;
            var checkMethod = document.querySelector('input[name="check_method"]:checked').value;

            var fd = new FormData();
            fd.append(csrfName, csrfHash);
            fd.append('auto_update', autoUpdate);
            fd.append('check_method', checkMethod);

            saveBtn.disabled = true;
            fetch(settingsUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (json.csrf_hash) csrfHash = json.csrf_hash;
                    saveBtn.disabled = false;
                    savedMsg.style.display = 'inline';
                    setTimeout(function() { savedMsg.style.display = 'none'; }, 3000);
                })
                .catch(function() { saveBtn.disabled = false; });
        });
    }
})();
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
