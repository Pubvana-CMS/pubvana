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
                        <?php if (!empty($info['premium'])): ?>
                            <span class="badge badge-warning text-dark small"><?= lang('Admin.premium') ?></span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($theme->is_active): ?>
                        <span class="badge badge-primary"><?= lang('Admin.themeActive') ?></span>
                    <?php endif; ?>
                </div>
                <p class="card-text text-muted small"><?= esc($info['description'] ?? '') ?></p>
                <p class="text-muted small">
                    <?= lang('Admin.themeBy') ?>
                    <?php if (!empty($info['author_url'])): ?>
                        <a href="<?= esc($info['author_url']) ?>" target="_blank" rel="noopener"><?= esc($info['author'] ?? 'Unknown') ?></a>
                    <?php else: ?>
                        <?= esc($info['author'] ?? 'Unknown') ?>
                    <?php endif; ?>
                    &middot; v<?= esc($theme->version ?? '?') ?>
                    <?php if (!empty($info['support_url'])): ?>
                        &middot; <a href="<?= esc($info['support_url']) ?>" target="_blank" rel="noopener"><?= lang('Admin.themeSupport') ?></a>
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
                    <?php if ($theme->pv_approved === null): ?>
                        <span class="badge badge-light">Unchecked</span>
                    <?php elseif ((int) $theme->pv_approved === 1): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Not Approved</span>
                    <?php endif ?>
                    <?php if (! empty($theme->pv_warning_note)): ?>
                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> <?= esc($theme->pv_warning_note) ?></small>
                    <?php endif ?>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div>
                    <?php if (! $theme->is_active && $isValid): ?>
                    <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/activate') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-primary"><?= lang('Admin.themeActivate') ?></button>
                    </form>
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
                    <h5 class="modal-title">Activate Unapproved Theme?</h5>
                    <a href="<?= base_url('admin/themes') ?>" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></a>
                </div>
                <div class="modal-body">
                    <?php if (! empty($confirmTheme->pv_warning_note)): ?>
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> Security Warning:</strong>
                            <?= esc($confirmTheme->pv_warning_note) ?>
                        </div>
                    <?php endif ?>
                    <p>This theme has not been approved by Pubvana.</p>
                    <p>Activating unapproved themes may introduce security risks or compatibility issues.</p>
                    <p>Are you sure you want to activate it anyway?</p>
                </div>
                <div class="modal-footer">
                    <a href="<?= base_url('admin/themes') ?>" class="btn btn-secondary">Cancel</a>
                    <form action="<?= base_url('admin/themes/' . $confirmTheme->id . '/activate') ?>" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-danger">Activate Anyway</button>
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
