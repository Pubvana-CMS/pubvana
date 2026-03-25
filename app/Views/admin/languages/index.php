<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Languages</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Installed Languages</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Language</th>
                    <th>Native Name</th>
                    <th style="width:80px">Code</th>
                    <th style="width:90px">Direction</th>
                    <th style="width:100px">Default</th>
                    <th style="width:90px">Enabled</th>
                    <th style="width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($languages as $lang): ?>
                <tr>
                    <td><?= esc($lang->name) ?></td>
                    <td><?= esc($lang->native_name) ?></td>
                    <td><code><?= esc($lang->code) ?></code></td>
                    <td>
                        <span class="badge badge-<?= $lang->direction === 'rtl' ? 'warning' : 'secondary' ?>">
                            <?= esc(strtoupper($lang->direction)) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($lang->is_default): ?>
                            <span class="badge badge-primary">Default</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($lang->is_active): ?>
                            <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($lang->is_active): ?>
                            <form method="POST" action="<?= base_url('admin/languages/disable/' . $lang->id) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-secondary"
                                    <?= $lang->is_default ? 'disabled title="Cannot disable the default language"' : '' ?>>
                                    Disable
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= base_url('admin/languages/enable/' . $lang->id) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-success">Enable</button>
                            </form>
                        <?php endif; ?>

                        <?php if (! $lang->is_default): ?>
                            <form method="POST" action="<?= base_url('admin/languages/make-default/' . $lang->id) ?>" class="d-inline ml-1">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-primary">Make Default</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($languages)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No languages found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
