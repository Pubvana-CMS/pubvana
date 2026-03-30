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

<!-- Progress bar (hidden by default) -->
<div id="update-progress" class="card shadow mb-4" style="display:none;">
    <div class="card-body">
        <h6 class="font-weight-bold" id="progress-label">Starting update...</h6>
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
        <strong>Could not contact GitHub:</strong> <?= esc($update['error']) ?>
    </div>

<?php elseif (!empty($update['available'])): ?>
    <div class="alert alert-info">
        <i class="fas fa-circle-arrow-up mr-1"></i>
        <strong>Pubvana <?= esc($update['latest_version']) ?> is available!</strong>
        You are running <?= esc($update['current_version']) ?>.
    </div>

    <?php if (!empty($breaking_changes)): ?>
    <div class="alert alert-danger">
        <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Breaking Changes</h6>
        <ul class="mb-0">
            <?php foreach ($breaking_changes as $bc): ?>
                <li><?= esc($bc) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($migration_notes)): ?>
    <div class="alert alert-warning">
        <h6 class="font-weight-bold"><i class="fas fa-database mr-1"></i> Migration Notes</h6>
        <ul class="mb-0">
            <?php foreach ($migration_notes as $mn): ?>
                <li><?= esc($mn) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($notices)): ?>
    <div class="alert alert-info">
        <h6 class="font-weight-bold"><i class="fas fa-info-circle mr-1"></i> Notices</h6>
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
            <h6 class="m-0 font-weight-bold text-primary">Pre-flight Checks</h6>
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
                                <?= $check['hard'] ? 'required' : 'optional' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Update Now -->
            <button type="button" class="btn btn-primary btn-lg mb-3" id="updateBtn"
                    <?= (!$all_hard_pass || !empty($is_locked)) ? 'disabled' : '' ?>>
                <i class="fas fa-rocket mr-1"></i> Update to Pubvana <?= esc($update['latest_version']) ?>
            </button>
            <?php if (!$all_hard_pass): ?>
                <p class="text-danger small">One or more required pre-flight checks failed. Please resolve them before updating.</p>
            <?php endif; ?>

            <?php if (!$can_download): ?>
            <div class="alert alert-warning">
                <i class="fas fa-upload mr-1"></i>
                <strong>No download method available.</strong> Neither cURL nor allow_url_fopen is enabled.
                Please download the update manually and upload it:
                <ol class="mt-2 mb-0 small">
                    <li>Download from <a href="<?= esc($update['release_url']) ?>" target="_blank">GitHub</a></li>
                    <li>Upload the ZIP to <code>writable/updates/</code></li>
                    <li>Run <code>php spark pubvana:update</code> from CLI</li>
                </ol>
            </div>
            <?php endif; ?>

            <!-- Release Notes -->
            <?php if (!empty($update['release_notes'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Release Notes</h6>
                </div>
                <div class="card-body">
                    <pre class="ws-pre-wrap" style="font-family:inherit;margin:0"><?= esc($update['release_notes']) ?></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Process</h6>
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2">Clicking "Update" will:</p>
                    <ol class="pl-3 mb-0">
                        <li>Create a full backup</li>
                        <li>Download the release ZIP</li>
                        <li>Extract and apply files</li>
                        <li>Run database migrations</li>
                        <li>Clear caches</li>
                    </ol>
                    <p class="mt-2 mb-0">Your <code>.env</code>, <code>App.php</code>, and <code>Database.php</code> are never overwritten.</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="alert alert-success">
        <i class="fas fa-circle-check mr-1"></i>
        <strong>Pubvana is up to date.</strong>
        You are running version <?= esc($update['current_version']) ?>.
    </div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php
$_applyUrl  = base_url('admin/updates/apply');
$_statusUrl = base_url('admin/updates/status');
$_csrfName  = csrf_token();
$_csrfHash  = csrf_hash();
$extra_scripts = <<<SCRIPT
<script>
(function() {
    var btn  = document.getElementById('updateBtn');
    if (!btn) return;

    var prog = document.getElementById('update-progress');
    var applyUrl  = '{$_applyUrl}';
    var statusUrl = '{$_statusUrl}';
    var csrfName  = '{$_csrfName}';
    var csrfHash  = '{$_csrfHash}';

    btn.addEventListener('click', function() {
        if (!confirm('This will backup your site, download the update, and apply it. Continue?')) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Starting update...';
        prog.style.display = 'block';

        var body = new FormData();
        body.append(csrfName, csrfHash);

        fetch(applyUrl, { method: 'POST', body: body })
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
    });

    function pollUpdateStatus() {
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
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
