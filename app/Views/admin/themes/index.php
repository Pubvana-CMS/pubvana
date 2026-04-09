<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800"><?= lang('Admin.themesTitle') ?></h1>

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

<div class="row">
<?php foreach ($themes as $theme): ?>
    <?php
    $folder = $theme->folder;
    $jsonFile = THEMES_PATH . $folder . '/theme_info.json';
    $phpFile  = THEMES_PATH . $folder . '/theme_info.php';

    if (is_file($jsonFile)) {
        $info = json_decode(file_get_contents($jsonFile), true) ?? [];
    } elseif (is_file($phpFile)) {
        $info = require $phpFile;
    } else {
        $info = [];
    }

    $isValid = $validation[$folder] ?? true;
    $screenshotUrl = '';
    if (! empty($info['screenshot'])) {
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
        $fcPath  = rtrim(FCPATH, '/\\');
        $prefix  = ($docRoot !== $fcPath) ? 'public/' : '';
        $screenshotUrl = base_url($prefix . 'themes/' . $folder . '/' . $info['screenshot']);
    }
    ?>
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100 <?= $theme->is_active ? 'border-primary' : '' ?>">
            <?php if ($screenshotUrl): ?>
                <img src="<?= esc($screenshotUrl) ?>" class="card-img-top card-thumb-lg obj-cover" alt="<?= esc($theme->name) ?>">
            <?php else: ?>
                <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center card-thumb-lg">
                    <i class="fas fa-palette fa-3x text-white-50"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title">
                        <?= esc($theme->name) ?>
                    </h5>
                    <?php if ($theme->is_active): ?>
                        <span class="badge badge-primary"><?= lang('Admin.themeActive') ?></span>
                    <?php endif; ?>
                </div>
                <p class="card-text text-muted small"><?= esc($theme->description ?? '') ?></p>
                <p class="text-muted small">
                    <?= lang('Admin.themeBy') ?>
                    <?php if (!empty($theme->author_url)): ?>
                        <a href="<?= esc($theme->author_url) ?>" target="_blank" rel="noopener"><?= esc($theme->author ?? 'Unknown') ?></a>
                    <?php else: ?>
                        <?= esc($theme->author ?? 'Unknown') ?>
                    <?php endif; ?>
                    &middot; v<?= esc($theme->version ?? '?') ?>
                    <?php if (!empty($theme->support_url)): ?>
                        &middot; <a href="<?= esc($theme->support_url) ?>" target="_blank" rel="noopener"><?= lang('Admin.themeSupport') ?></a>
                    <?php endif; ?>
                </p>
                <?php if (!empty($info['css_framework']) || !empty($info['js_framework']) || !empty($info['icon_pack'])): ?>
                <p class="text-muted small mb-1">
                    <?php if (!empty($info['css_framework'])): ?>
                        <i class="fab fa-css3"></i> <?= esc($info['css_framework']) ?> <?= esc($info['css_frame_ver'] ?? '') ?>
                    <?php endif; ?>
                    <?php if (!empty($info['js_framework'])): ?>
                        | <i class="fab fa-js-square"></i> <?= esc($info['js_framework']) ?> <?= esc($info['js_framework_ver'] ?? '') ?>
                    <?php endif; ?>
                    <?php if (!empty($info['icon_pack'])): ?>
                        | <i class="fas fa-icons"></i> <?= esc($info['icon_pack']) ?> <?= esc($info['icon_pack_ver'] ?? '') ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if (! $isValid): ?>
                    <div class="alert alert-danger small py-1 px-2 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <?= lang('Admin.themeValidationFailed') ?>
                    </div>
                <?php endif; ?>
                <div class="mt-2">
                    <small class="text-muted"><?= lang('Admin.safetyLabel') ?></small>
                    <?php if ($theme->pv_safe === null): ?>
                        <span class="badge badge-light"><?= lang('Admin.unchecked') ?></span>
                        <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/recheck') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                        </form>
                    <?php elseif ((int) $theme->pv_safe === 2): ?>
                        <span class="badge badge-warning"><?= lang('Admin.safetyUnknown') ?></span>
                        <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/recheck') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                        </form>
                    <?php elseif ((int) $theme->pv_safe === 1): ?>
                        <span class="badge badge-success"><?= lang('Admin.safe') ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger"><?= lang('Admin.malicious') ?></span>
                    <?php endif ?>
                    <?php if (! empty($theme->pv_warning_note)): ?>
                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> <?= esc($theme->pv_warning_note) ?></small>
                    <?php endif ?>
                    <?php if (! empty($theme->disabled)): ?>
                        <div class="alert alert-danger small py-1 px-2 mb-0 mt-1">
                            <i class="fas fa-ban"></i> <?= lang('Admin.addonDisabled') ?>
                            <br><small><?= esc($theme->disabled_reason) ?></small>
                        </div>
                    <?php endif ?>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div>
                    <?php if (! $theme->is_active && $isValid && empty($theme->disabled)): ?>
                    <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/activate') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-primary"><?= lang('Admin.themeActivate') ?></button>
                    </form>
                    <?php endif; ?>
                </div>
                <div>
                    <?php
                        $isPubvana = in_array($theme->author ?? '', ['pubvana', 'pubvana_team'], true);
                        $isBundled = ! empty($theme->bundled);
                        $lic       = $theme->store_product_id ? ($licenses[$theme->store_product_id] ?? null) : null;
                        $licValid  = $lic ? (int) ($lic->license_valid ?? -1) : -1;
                    ?>
                    <?php if ($isBundled): ?>
                        <?php /* Bundled — no license display */ ?>
                    <?php elseif ($isPubvana): ?>
                        <?php
                            $renewUrl = 'https://pubvana.net/dstore/product/' . $theme->folder;
                        ?>
                        <?php if ($lic && $licValid === 1): ?>
                            <span class="badge badge-success"><?= lang('Admin.licenseLicensed') ?></span>
                            <small class="text-muted d-block"><?= esc(substr($lic->license_key, 0, 12)) ?>...</small>
                            <button class="btn btn-xs btn-outline-secondary mt-1" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseChangeKey') ?></button>
                        <?php elseif ($lic && $licValid === 0): ?>
                            <span class="badge badge-danger"><?= lang('Admin.licenseExpired') ?></span>
                            <button class="btn btn-xs btn-outline-danger mt-1" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                            <?php if ($renewUrl): ?>
                                <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mt-1"><?= lang('Admin.licenseRenew') ?></a>
                            <?php endif; ?>
                        <?php elseif ($lic && $licValid === -1): ?>
                            <span class="badge badge-warning"><?= lang('Admin.licenseCheckNow') ?></span>
                            <button class="btn btn-xs btn-outline-warning mt-1" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                        <?php else: ?>
                            <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (! empty($theme->license_validate_url) && $theme->store_product_id): ?>
                            <?php if ($lic && $licValid === 1): ?>
                                <span class="badge badge-success"><?= lang('Admin.licenseLicensed') ?></span>
                                <small class="text-muted d-block"><?= esc(substr($lic->license_key, 0, 12)) ?>...</small>
                                <button class="btn btn-xs btn-outline-secondary mt-1" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseChangeKey') ?></button>
                            <?php elseif ($lic && $licValid === 0): ?>
                                <span class="badge badge-danger"><?= lang('Admin.licenseExpired') ?></span>
                                <button class="btn btn-xs btn-outline-danger mt-1" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                <?php $renewUrl = $theme->store_url ?? ''; ?>
                                <?php if ($renewUrl): ?>
                                    <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mt-1"><?= lang('Admin.licenseRenew') ?></a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#licenseModal-<?= $theme->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-light"><?= lang('Admin.licenseThirdPartyLabel') ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($info['options'])): ?>
                    <a href="<?= base_url('admin/themes/' . $theme->id . '/options') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.themeOptionsBtn') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($themes)): ?>
    <div class="col-12"><p class="text-muted text-center py-4"><?= lang('Admin.noThemesInstalled') ?></p></div>
<?php endif; ?>
</div>

<!-- License key modals -->
<?php foreach ($themes as $theme): ?>
    <?php
        $isPubvana = in_array($theme->author ?? '', ['pubvana', 'pubvana_team'], true);
        $isBundled = ! empty($theme->bundled);
    ?>
    <?php if ((($isPubvana && ! $isBundled) || ! empty($theme->license_validate_url)) && $theme->store_product_id): ?>
    <div class="modal fade" id="licenseModal-<?= $theme->id ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= site_url('admin/themes/save-license') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="store_product_id" value="<?= esc($theme->store_product_id) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= lang('Admin.licenseModalTitle') ?> - <?= esc($theme->name) ?></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p><?= lang('Admin.licenseModalBody') ?></p>
                        <div class="form-group">
                            <input type="text" class="form-control" name="license_key"
                                   value="<?= esc(($licenses[$theme->store_product_id] ?? null)?->license_key ?? '') ?>"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.btnCancel') ?></button>
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.licenseModalSave') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Confirmation modal for unapproved themes -->
<?php if (session('confirm_activate_theme')): ?>
    <?php
        $confirmThemeId = session('confirm_activate_theme');
        $confirmTheme   = null;
        foreach ($themes as $t) {
            if ((int) $t->id === (int) $confirmThemeId) {
                $confirmTheme = $t;
                break;
            }
        }
    ?>
    <?php if ($confirmTheme): ?>
    <div class="modal fade show" id="confirmActivateThemeModal" tabindex="-1" style="display:block;" aria-modal="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><?= lang('Admin.themeUnapprovedTitle') ?></h5>
                    <a href="<?= base_url('admin/themes') ?>" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></a>
                </div>
                <div class="modal-body">
                    <?php if (! empty($confirmTheme->pv_warning_note)): ?>
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> <?= lang('Admin.securityWarning') ?></strong>
                            <?= esc($confirmTheme->pv_warning_note) ?>
                        </div>
                    <?php endif ?>
                    <p><?= lang('Admin.themeNotApproved') ?></p>
                    <p><?= lang('Admin.themeUnapprovedRisk') ?></p>
                    <p><?= lang('Admin.themeActivateConfirm') ?></p>
                </div>
                <div class="modal-footer">
                    <a href="<?= base_url('admin/themes') ?>" class="btn btn-secondary"><?= lang('Admin.cancel') ?></a>
                    <form action="<?= base_url('admin/themes/' . $confirmTheme->id . '/activate') ?>" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-danger"><?= lang('Admin.themeActivateAnyway') ?></button>
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
