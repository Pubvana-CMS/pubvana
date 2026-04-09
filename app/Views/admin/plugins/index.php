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
                    <th><?= lang('Plugins.colLicense') ?></th>
                    <th><?= lang('Plugins.colSafe') ?></th>
                    <th><?= lang('Plugins.colActions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $p): ?>
                <tr>
                    <td>
                        <strong><?= esc($p->name) ?></strong><?php if ($p->author): ?> <span class="text-muted">- by: <?= esc(ucwords(str_replace('_', ' ', $p->author))) ?></span><?php endif; ?><?php if (! empty($p->support_url)): ?> <a href="<?= esc($p->support_url) ?>" target="_blank" class="btn btn-outline-primary" style="font-size:.65rem;padding:1px 4px;line-height:1.2;"><?= lang('Plugins.support') ?></a><?php endif; ?>
                        <?php if ($p->description): ?>
                            <br><small class="text-muted"><?= esc($p->description) ?></small>
                        <?php endif ?>
                        <?php if ($p->pv_warning_note): ?>
                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?= esc($p->pv_warning_note) ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= esc($p->version) ?></td>
                    <td>
                        <?php if (! empty($p->disabled)): ?>
                            <span class="badge badge-danger"><?= lang('Admin.addonDisabled') ?></span>
                            <br><small class="text-danger"><?= esc($p->disabled_reason) ?></small>
                        <?php elseif ($p->is_active): ?>
                            <span class="badge badge-success"><?= lang('Plugins.statusActive') ?></span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?= lang('Plugins.statusInactive') ?></span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php
                            $authorNorm = strtolower(trim($p->author ?? ''));
                            $isPubvana  = in_array($authorNorm, ['pubvana', 'pubvana_team'], true);
                            $lic        = $p->store_product_id ? ($licenses[$p->store_product_id] ?? null) : null;
                            $licValid   = $lic ? (int) ($lic->license_valid ?? -1) : -1;
                        ?>
                        <?php if ($isPubvana): ?>
                            <?php
                                $renewUrl = 'https://pubvana.net/dstore/product/' . $p->folder;
                            ?>
                            <?php if ($lic && $licValid === 1): ?>
                                <span class="badge badge-success"><?= lang('Plugins.licenseLicensed') ?></span>
                                <small class="text-muted d-block"><?= esc(substr($lic->license_key, 0, 12)) ?>...</small>
                                <button class="btn btn-xs btn-outline-secondary mt-1" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseChangeKey') ?></button>
                            <?php elseif ($lic && $licValid === 0): ?>
                                <span class="badge badge-danger"><?= lang('Plugins.licenseExpired') ?></span>
                                <button class="btn btn-xs btn-outline-danger mt-1" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseEnterKey') ?></button>
                                <?php if ($renewUrl): ?>
                                    <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mt-1"><?= lang('Plugins.licenseRenew') ?></a>
                                <?php endif; ?>
                            <?php elseif ($lic && $licValid === -1): ?>
                                <span class="badge badge-warning"><?= lang('Plugins.licenseCheckNow') ?></span>
                                <button class="btn btn-xs btn-outline-warning mt-1" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseEnterKey') ?></button>
                            <?php else: ?>
                                <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseEnterKey') ?></button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (! empty($p->license_validate_url) && $p->store_product_id): ?>
                                <?php if ($lic && $licValid === 1): ?>
                                    <span class="badge badge-success"><?= lang('Plugins.licenseLicensed') ?></span>
                                    <small class="text-muted d-block"><?= esc(substr($lic->license_key, 0, 12)) ?>...</small>
                                    <button class="btn btn-xs btn-outline-secondary mt-1" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseChangeKey') ?></button>
                                <?php elseif ($lic && $licValid === 0): ?>
                                    <span class="badge badge-danger"><?= lang('Plugins.licenseExpired') ?></span>
                                    <button class="btn btn-xs btn-outline-danger mt-1" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseEnterKey') ?></button>
                                    <?php $renewUrl = $p->store_url ?? ''; ?>
                                    <?php if ($renewUrl): ?>
                                        <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mt-1"><?= lang('Plugins.licenseRenew') ?></a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#licenseModal-<?= $p->id ?>"><?= lang('Plugins.licenseEnterKey') ?></button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-light"><?= lang('Admin.licenseThirdPartyLabel') ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small class="text-muted"><?= lang('Admin.safetyLabel') ?></small>
                        <?php if ($p->pv_safe === null): ?>
                            <span class="badge badge-light"><?= lang('Plugins.safeUnchecked') ?></span>
                            <form method="POST" action="<?= base_url('admin/plugins/' . $p->id . '/recheck') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                            </form>
                        <?php elseif ((int) $p->pv_safe === 2): ?>
                            <span class="badge badge-warning"><?= lang('Plugins.safeUnknown') ?></span>
                            <form method="POST" action="<?= base_url('admin/plugins/' . $p->id . '/recheck') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                            </form>
                        <?php elseif ((int) $p->pv_safe === 1): ?>
                            <span class="badge badge-success"><?= lang('Plugins.safeYes') ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?= lang('Plugins.safeMalicious') ?></span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if (! empty($p->disabled)): ?>
                            <?php /* No actions for disabled plugins */ ?>
                        <?php elseif ($p->is_active): ?>
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

<!-- License key modals -->
<?php foreach ($plugins as $p): ?>
    <?php if ($p->store_product_id && (in_array(strtolower(trim($p->author ?? '')), ['pubvana', 'pubvana_team'], true) || ! empty($p->license_validate_url))): ?>
    <div class="modal fade" id="licenseModal-<?= $p->id ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= site_url('admin/plugins/save-license') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="store_product_id" value="<?= esc($p->store_product_id) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= lang('Plugins.licenseModalTitle') ?> - <?= esc($p->name) ?></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p><?= lang('Plugins.licenseModalBody') ?></p>
                        <div class="form-group">
                            <input type="text" class="form-control" name="license_key"
                                   value="<?= esc(($p->store_product_id ? ($licenses[$p->store_product_id] ?? null) : null)?->license_key ?? '') ?>"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Plugins.btnCancel') ?></button>
                        <button type="submit" class="btn btn-primary"><?= lang('Plugins.licenseModalSave') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

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
                    <p><?= lang('Plugins.modalNotSafe') ?></p>
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
