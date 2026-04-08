<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.importWpHeading') ?></h1>
</div>

<?php if (!empty($results)): ?>
    <?php $dryLabel = !empty($dry_run) ? ' ' . lang('Admin.importDryRunLabel') : ''; ?>
    <div class="alert alert-<?= empty($results['errors']) ? 'success' : 'warning' ?>">
        <h5 class="font-weight-bold"><?= lang('Admin.importComplete') ?><?= esc($dryLabel) ?></h5>
        <div class="row mt-3">
            <?php foreach (['authors', 'categories', 'tags', 'posts', 'pages', 'comments'] as $type): ?>
            <div class="col-md-2 text-center mb-3">
                <h4 class="font-weight-bold text-primary"><?= $results[$type]['created'] ?></h4>
                <small class="text-muted"><?= ucfirst($type) ?> <?= lang('Admin.importCreated') ?></small><br>
                <small class="text-muted"><?= $results[$type]['skipped'] ?> <?= lang('Admin.importSkipped') ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($results['errors'])): ?>
            <hr>
            <h6><?= lang('Admin.importErrors') ?></h6>
            <ul>
                <?php foreach ($results['errors'] as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.importWpHeading') ?></h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    <?= lang('Admin.importInstructions') ?>
                </p>

                <form method="POST" action="<?= base_url('admin/import') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="font-weight-bold"><?= lang('Admin.importChooseFile') ?></label>
                        <input type="file" name="wxr_file" class="form-control-file" accept=".xml" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="dry_run" id="dry_run" class="form-check-input" value="1">
                        <label class="form-check-label" for="dry_run">
                            <?= lang('Admin.importDryRun') ?>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> <?= lang('Admin.importRunBtn') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.importCliTitle') ?></h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2"><?= lang('Admin.importCliHint') ?></p>
                <pre class="bg-light p-3 rounded"><code>php spark wp:import /path/to/export.xml</code></pre>
                <pre class="bg-light p-3 rounded"><code>php spark wp:import /path/to/export.xml --dry-run</code></pre>
                <p class="text-muted small mt-2"><?= lang('Admin.importCliDryRunHint') ?></p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.importWhatTitle') ?></h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small text-muted mb-0">
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemPosts') ?></li>
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemPages') ?></li>
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemCategories') ?></li>
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemTags') ?></li>
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemAuthors') ?></li>
                    <li><i class="fas fa-check text-success mr-1"></i> <?= lang('Admin.importItemComments') ?></li>
                    <li><i class="fas fa-xmark text-muted mr-1"></i> <?= lang('Admin.importItemMedia') ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
