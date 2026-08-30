<?php
/**
 * Theme listing — card grid with screenshot, name, activate button.
 *
 * @var object[] $themes      All themes from DB
 * @var array    $validation  folder => isValid
 * @var array    $theme_info  folder => pubvana.json data
 */
?>

<div class="row row-cards">
<?php foreach ($themes as $theme): ?>
    <?php
    $isValid = $validation[$theme->folder] ?? true;
    $info = $theme_info[$theme->folder] ?? [];
    $screenshotUrl = '';
    if (!empty($theme->screenshot)) {
        $screenshotUrl = '/themes/' . $theme->folder . '/' . $theme->screenshot;
    } elseif (!empty($info['icon'])) {
        $screenshotUrl = '/themes/' . $theme->folder . '/' . $info['icon'];
    }

    $regions = $info['provides']['regions'] ?? [];
    $options = $info['provides']['options'] ?? [];
    ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card<?= $theme->is_active ? ' border-primary' : '' ?>">
            <?php if ($screenshotUrl): ?>
                <img src="<?= $screenshotUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($theme->name) ?>" style="height:200px;object-fit:cover">
            <?php else: ?>
                <div class="card-img-top bg-primary-lt d-flex align-items-center justify-content-center" style="height:200px">
                    <i class="ti ti-palette" style="font-size:3rem;opacity:.5"></i>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h3 class="card-title mb-0"><?= htmlspecialchars($theme->name) ?></h3>
                    <?php if ($theme->is_active): ?>
                        <span class="badge bg-primary-lt">Active</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($theme->description)): ?>
                    <p class="text-secondary small"><?= htmlspecialchars($theme->description) ?></p>
                <?php endif; ?>

                <p class="text-secondary small mb-2">
                    <?php if (!empty($theme->author)): ?>
                        By <?= htmlspecialchars($theme->author) ?>
                    <?php endif; ?>
                    <?php if (!empty($theme->version)): ?>
                        &middot; v<?= htmlspecialchars($theme->version) ?>
                    <?php endif; ?>
                </p>

                <?php if (!empty($regions)): ?>
                <div class="mb-2">
                    <small class="text-secondary d-block mb-1">Regions</small>
                    <?php foreach ($regions as $region): ?>
                        <span class="badge bg-azure-lt me-1 mb-1"><?= htmlspecialchars($region['label'] ?? $region['id']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($options)): ?>
                <div class="mb-2">
                    <small class="text-secondary d-block mb-1">Options</small>
                    <?php foreach ($options as $key => $opt): ?>
                        <span class="badge bg-purple-lt me-1 mb-1"><?= htmlspecialchars($opt['label'] ?? $key) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!$isValid): ?>
                    <div class="alert alert-danger small py-1 px-2 mt-2 mb-0">
                        <i class="ti ti-alert-triangle"></i> PHP detected in theme files — activation blocked.
                    </div>
                <?php endif; ?>

                <?php if (!empty($theme->disabled)): ?>
                    <div class="alert alert-danger small py-1 px-2 mt-2 mb-0">
                        <i class="ti ti-ban"></i> Disabled: <?= htmlspecialchars($theme->disabled_reason) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <div>
                    <?php if (!$theme->is_active && $isValid && empty($theme->disabled)): ?>
                    <form method="post" action="/admin/themes/<?= (int) $theme->id ?>/activate">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($options)): ?>
                    <a href="/admin/themes/<?= (int) $theme->id ?>/options" class="btn btn-outline-secondary btn-sm">Options</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($themes)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-secondary py-4">
                No themes found. Add a theme to the <code>themes/</code> directory.
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
