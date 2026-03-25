<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.socialTitle') ?></h1>
</div>

<div class="row">
    <!-- Add new link -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialTitle') ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/social/store') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label><?= lang('Admin.socialPlatform') ?></label>
                        <input type="text" name="platform" class="form-control" required placeholder="e.g. Twitter">
                    </div>
                    <div class="form-group">
                        <label><?= lang('Admin.socialUrl') ?></label>
                        <input type="url" name="url" class="form-control" required placeholder="https://twitter.com/yourhandle">
                    </div>
                    <div class="form-group">
                        <label><?= lang('Admin.socialIcon') ?></label>
                        <input type="text" name="icon" class="form-control" placeholder="fab fa-twitter">
                        <small class="form-text text-muted">Use FA5 class e.g. <code>fab fa-facebook</code></small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= lang('Admin.add') ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current links -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialTitle') ?></h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:20%"><?= lang('Admin.socialPlatform') ?></th>
                            <th><?= lang('Admin.socialUrl') ?></th>
                            <th style="width:18%"><?= lang('Admin.socialIcon') ?></th>
                            <th style="width:10%"><?= lang('Admin.status') ?></th>
                            <th style="width:10%"><?= lang('Admin.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <i class="<?= esc($link->icon) ?> fa-fw fa-lg me-2 text-primary"></i><?= esc($link->platform) ?>
                            </td>
                            <td>
                                <a href="<?= esc($link->url) ?>" target="_blank" rel="noopener"
                                   class="text-truncate d-inline-block" style="max-width:220px">
                                    <?= esc($link->url) ?>
                                </a>
                            </td>
                            <td><small class="text-muted"><?= esc($link->icon) ?></small></td>
                            <td>
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $link->is_active ? 'btn-success' : 'btn-outline-secondary' ?>">
                                        <?= $link->is_active ? lang('Admin.active') : lang('Admin.inactive') ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('<?= lang('Admin.confirmDelete') ?>')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger"><?= lang('Admin.delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('Admin.noResultsFound') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
