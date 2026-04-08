<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.navBackup') ?></h1>
    <button type="button" class="btn btn-primary" id="createBackupBtn" <?= !empty($is_locked) ? 'disabled' : '' ?>>
        <i class="fas fa-plus mr-1"></i> <?= lang('Admin.backupCreate') ?>
    </button>
</div>

<!-- Progress bar (hidden by default) -->
<div id="backup-progress" class="card shadow mb-4" style="display:none;">
    <div class="card-body">
        <h6 class="font-weight-bold" id="progress-label"><?= lang('Admin.backupStarting') ?></h6>
        <div class="progress mb-2">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar"
                 role="progressbar" style="width: 0%"></div>
        </div>
        <small class="text-muted" id="progress-detail"></small>
    </div>
</div>

<?php if (empty($backups)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i> <?= lang('Admin.backupNoneYet') ?>
    </div>
<?php else: ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.backupsTitle') ?></h6>
        <small class="text-muted"><?= lang('Admin.backupRetentionNote') ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><?= lang('Admin.colFilename') ?></th>
                        <th><?= lang('Admin.colVersion') ?></th>
                        <th><?= lang('Admin.colTrigger') ?></th>
                        <th><?= lang('Admin.colSize') ?></th>
                        <th><?= lang('Admin.colDate') ?></th>
                        <th class="text-right"><?= lang('Admin.colActions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><i class="fas fa-file-zipper text-secondary mr-1"></i> <?= esc($backup['filename']) ?></td>
                        <td><?= esc($backup['meta']['version'] ?? '—') ?></td>
                        <td><span class="badge badge-secondary"><?= esc($backup['meta']['trigger'] ?? '—') ?></span></td>
                        <td><?= esc($backup['size']) ?></td>
                        <td><?= esc($backup['created']) ?></td>
                        <td class="text-right text-nowrap">
                            <a href="<?= base_url('admin/backups/download/' . esc($backup['filename'])) ?>"
                               class="btn btn-sm btn-outline-primary" title="<?= lang('Admin.download') ?>">
                                <i class="fas fa-download"></i>
                            </a>
                            <form method="POST"
                                  action="<?= base_url('admin/backups/restore/' . esc($backup['filename'])) ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('<?= lang('Admin.backupRestoreConfirm') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="<?= lang('Admin.restore') ?>">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                            </form>
                            <form method="POST"
                                  action="<?= base_url('admin/backups/' . esc($backup['filename']) . '/delete') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('<?= lang('Admin.backupDeleteConfirm') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= lang('Admin.delete') ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php
$_createUrl = base_url('admin/backups/create');
$_statusUrl = base_url('admin/backups/status');
$_csrfName  = csrf_token();
$_csrfHash  = csrf_hash();
$extra_scripts = <<<SCRIPT
<script>
(function() {
    var btn  = document.getElementById('createBackupBtn');
    var prog = document.getElementById('backup-progress');
    var createUrl = '{$_createUrl}';
    var statusUrl = '{$_statusUrl}';
    var csrfName  = '{$_csrfName}';
    var csrfHash  = '{$_csrfHash}';

    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Starting...';
        prog.style.display = 'block';

        // POST to create — returns JSON
        var body = new FormData();
        body.append(csrfName, csrfHash);

        fetch(createUrl, { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'error') {
                    showError(data.message || 'Unknown error');
                    return;
                }
                if (data.status === 'completed') {
                    // Synchronous fallback completed inline
                    showComplete();
                    return;
                }
                // Background job started — poll for progress
                pollBackupStatus();
            })
            .catch(function(err) {
                showError('Request failed: ' + err.message);
            });
    });

    function pollBackupStatus() {
        var interval = setInterval(function() {
            fetch(statusUrl)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'in_progress') {
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
                    }
                });
        }, 500);
    }

    function showComplete() {
        document.getElementById('progress-bar').style.width = '100%';
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-success');
        document.getElementById('progress-label').textContent = 'Backup complete!';
        setTimeout(function() { location.reload(); }, 1500);
    }

    function showError(msg) {
        document.getElementById('progress-label').textContent = 'Error: ' + msg;
        document.getElementById('progress-bar').classList.remove('progress-bar-animated');
        document.getElementById('progress-bar').classList.add('bg-danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus mr-1"></i> Create Backup';
    }
})();
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
