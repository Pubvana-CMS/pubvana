<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.activityLogTitle') ?></h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.activityLogTitle') ?></h6>
        <form method="GET" action="<?= base_url('admin/activity-log') ?>" class="form-inline">
            <label class="mr-2 text-muted small"><?= lang('Admin.activityLogType') ?>:</label>
            <select name="type" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="" <?= $type === '' ? 'selected' : '' ?>><?= lang('Admin.activityLogFilterAll') ?></option>
                <option value="post"        <?= $type === 'post'        ? 'selected' : '' ?>><?= lang('Admin.postsTitle') ?></option>
                <option value="page"        <?= $type === 'page'        ? 'selected' : '' ?>><?= lang('Admin.pagesTitle') ?></option>
                <option value="user"        <?= $type === 'user'        ? 'selected' : '' ?>><?= lang('Admin.usersTitle') ?></option>
                <option value="theme"       <?= $type === 'theme'       ? 'selected' : '' ?>><?= lang('Admin.themesTitle') ?></option>
                <option value="setting"     <?= $type === 'setting'     ? 'selected' : '' ?>><?= lang('Admin.settingsTitle') ?></option>
                <option value="marketplace" <?= $type === 'marketplace' ? 'selected' : '' ?>><?= lang('Admin.marketplaceTitle') ?></option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
            <div class="p-4 text-muted text-center"><?= lang('Admin.activityLogEmpty') ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><?= lang('Admin.date') ?></th>
                        <th><?= lang('Admin.activityLogUser') ?></th>
                        <th><?= lang('Admin.activityLogAction') ?></th>
                        <th><?= lang('Admin.activityLogNote') ?></th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td class="text-muted small" title="<?= esc($entry->created_at) ?>">
                            <?= esc($entry->created_at) ?>
                        </td>
                        <td>
                            <?php if ($entry->user_id): ?>
                                <a href="<?= base_url('admin/users/' . $entry->user_id . '/edit') ?>">
                                    <?= esc($entry->username) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted"><?= esc($entry->username ?: '—') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="small"><?= esc($entry->action) ?></code>
                        </td>
                        <td><?= esc($entry->description) ?></td>
                        <td class="text-muted small"><?= esc($entry->ip_address ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if (isset($pager) && $pager): ?>
    <div class="card-footer">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
