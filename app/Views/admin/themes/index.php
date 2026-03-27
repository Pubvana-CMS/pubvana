<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800"><?= lang('Admin.themesTitle') ?></h1>

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
        $screenshotUrl = base_url('themes/' . $folder . '/' . $info['screenshot']);
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
                <p class="text-muted small"><?= lang('Admin.themeBy') ?> <?= esc($info['author'] ?? 'Unknown') ?> &middot; v<?= esc($theme->version ?? '?') ?></p>
                <?php if (! $isValid): ?>
                    <div class="alert alert-danger small py-1 px-2 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <?= lang('Admin.themeValidationFailed') ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <?php if (! $theme->is_active && $isValid): ?>
                <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/activate') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary"><?= lang('Admin.themeActivate') ?></button>
                </form>
                <?php endif; ?>
                <?php if (!empty($info['options'])): ?>
                <a href="<?= base_url('admin/themes/' . $theme->id . '/options') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.themeOptionsBtn') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($themes)): ?>
    <div class="col-12"><p class="text-muted text-center py-4"><?= lang('Admin.noThemesInstalled') ?></p></div>
<?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
