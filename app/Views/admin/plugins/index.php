<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= lang('Plugins.title') ?></h1>
    <form action="/admin/plugins/discover" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-search"></i> <?= lang('Plugins.scanBtn') ?>
        </button>
    </form>
</div>

<?php if (! empty($invalidLicenses)): ?>
<div class="alert alert-danger border-danger">
    <strong><i class="fas fa-triangle-exclamation mr-1"></i> <?= lang('Admin.licenseWarningTitle') ?></strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($invalidLicenses as $lic): ?>
        <li><?= esc($lic->product_name) ?> — <?= lang('Admin.licenseWarningInvalid') ?></li>
        <?php endforeach; ?>
    </ul>
    <a href="<?= base_url('admin/marketplace/licenses') ?>" class="alert-link"><?= lang('Admin.licenseWarningManage') ?></a>
</div>
<?php endif; ?>

<?php if (session('success')): ?>
    <div class="alert alert-success"><?= esc(session('success')) ?></div>
<?php endif ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif ?>
<?php if (session('info')): ?>
    <div class="alert alert-info"><?= esc(session('info')) ?></div>
<?php endif ?>

<?php if (empty($plugins)): ?>
    <div class="alert alert-secondary">
        <?= lang('Plugins.noPlugins') ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th><?= lang('Plugins.colPlugin') ?></th>
                    <th><?= lang('Plugins.colVersion') ?></th>
                    <th><?= lang('Plugins.colStatus') ?></th>
                    <th><?= lang('Plugins.colApproved') ?></th>
                    <th><?= lang('Plugins.colActions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $p): ?>
                <tr>
                    <td>
                        <strong><?= esc($p->name) ?></strong>
                        <?php if ($p->description): ?>
                            <br><small class="text-muted"><?= esc($p->description) ?></small>
                        <?php endif ?>
                        <?php if ($p->pv_warning_note): ?>
                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?= esc($p->pv_warning_note) ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= esc($p->version) ?></td>
                    <td>
                        <?php if ($p->is_active): ?>
                            <span class="badge badge-success"><?= lang('Plugins.statusActive') ?></span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?= lang('Plugins.statusInactive') ?></span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($p->pv_approved === null): ?>
                            <span class="badge badge-light"><?= lang('Plugins.approvedUnchecked') ?></span>
                        <?php elseif ((int) $p->pv_approved === 1): ?>
                            <span class="badge badge-success"><?= lang('Plugins.approvedYes') ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?= lang('Plugins.approvedNo') ?></span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($p->is_active): ?>
                            <form action="/admin/plugins/deactivate" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="folder" value="<?= esc($p->folder) ?>">
                                <button type="submit" class="btn btn-warning btn-sm"><?= lang('Plugins.btnDeactivate') ?></button>
                            </form>
                        <?php else: ?>
                            <form action="/admin/plugins/activate" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="folder" value="<?= esc($p->folder) ?>">
                                <button type="submit" class="btn btn-primary btn-sm"><?= lang('Plugins.btnActivate') ?></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<!-- Confirmation modal for unapproved plugins -->
<?php if (session('confirm_activate')): ?>
    <?php
        $confirmFolder = session('confirm_activate');
        $confirmPlugin = null;
        foreach ($plugins as $p) {
            if ($p->folder === $confirmFolder) {
                $confirmPlugin = $p;
                break;
            }
        }
    ?>
    <?php if ($confirmPlugin): ?>
    <div class="modal fade show" id="confirmActivateModal" tabindex="-1" style="display:block;" aria-modal="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><?= lang('Plugins.modalTitle') ?></h5>
                    <a href="/admin/plugins" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></a>
                </div>
                <div class="modal-body">
                    <?php if (! empty($confirmPlugin->pv_warning_note)): ?>
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> <?= lang('Plugins.modalSecurityWarn') ?></strong>
                            <?= esc($confirmPlugin->pv_warning_note) ?>
                        </div>
                    <?php endif ?>
                    <p><?= lang('Plugins.modalNotApproved') ?></p>
                    <p><?= lang('Plugins.modalRiskWarning') ?></p>
                    <p><?= lang('Plugins.modalConfirm') ?></p>
                </div>
                <div class="modal-footer">
                    <a href="/admin/plugins" class="btn btn-secondary"><?= lang('Plugins.btnCancel') ?></a>
                    <form action="/admin/plugins/activate" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="folder" value="<?= esc($confirmFolder) ?>">
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-danger"><?= lang('Plugins.btnActivateAnyway') ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif ?>
<?php endif ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
