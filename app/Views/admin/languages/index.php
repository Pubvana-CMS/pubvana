<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.languagesTitle') ?></h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.languagesTitle') ?></h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th><?= lang('Admin.languageName') ?></th>
                    <th><?= lang('Admin.languageDefault') ?></th>
                    <th><?= lang('Admin.languageEnabled') ?></th>
                    <th class="text-right text-nowrap"><?= lang('Admin.actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($languages as $lang): ?>
                <tr>
                    <td><?= esc($lang->native_name) ?></td>
                    <td>
                        <?php if ($lang->is_default): ?>
                            <span class="badge badge-primary"><?= lang('Admin.languageDefault') ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($lang->is_active): ?>
                            <span class="badge badge-success"><?= lang('Admin.languageEnabled') ?></span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?= lang('Admin.disabled') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-nowrap">
                        <?php if ($lang->is_active): ?>
                            <form method="POST" action="<?= base_url('admin/languages/disable/' . $lang->id) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-secondary"
                                    <?= $lang->is_default ? 'disabled title="' . lang('Admin.languageCannotDisable') . '"' : '' ?>>
                                    <?= lang('Admin.disable') ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= base_url('admin/languages/enable/' . $lang->id) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-success"><?= lang('Admin.enable') ?></button>
                            </form>
                        <?php endif; ?>

                        <?php if (! $lang->is_default): ?>
                            <form method="POST" action="<?= base_url('admin/languages/make-default/' . $lang->id) ?>" class="d-inline ml-1">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-primary"><?= lang('Admin.languageMakeDefault') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($languages)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4"><?= lang('Admin.noResultsFound') ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
