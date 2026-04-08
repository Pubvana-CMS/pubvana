<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.usersTitle') ?></h1>
    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-sm btn-primary">
        <i class="fas fa-plus fa-sm"></i> <?= lang('Admin.createUserTitle') ?>
    </a>
</div>

<div class="mb-3">
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm <?= empty($filter) ? 'btn-primary' : 'btn-outline-primary' ?>"><?= lang('Admin.all') ?></a>
    <a href="<?= base_url('admin/users?filter=banned') ?>" class="btn btn-sm <?= ($filter ?? '') === 'banned' ? 'btn-danger' : 'btn-outline-danger' ?>"><?= lang('Admin.banned') ?></a>
</div>
<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th><?= lang('Admin.name') ?></th>
                        <th><?= lang('Admin.email') ?></th>
                        <th><?= lang('Admin.role') ?></th>
                        <th><?= lang('Admin.date') ?></th>
                        <th><?= lang('Admin.status') ?></th>
                        <th><?= lang('Admin.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $userEmail = $user->getEmail(); $userRole = $user->getGroups()[0] ?? 'subscriber'; ?>
                    <tr>
                        <td>
                            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower($userEmail ?? '')) ?>?s=32&d=mp"
                                 class="rounded-circle mr-2" width="32" height="32" alt="">
                            <?= esc($user->username ?? $userEmail) ?>
                        </td>
                        <td><?= esc($userEmail) ?></td>
                        <td>
                            <span class="badge badge-<?= $userRole === 'superadmin' ? 'danger' : ($userRole === 'admin' ? 'warning' : 'secondary') ?>">
                                <?= esc(ucfirst($userRole)) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= date('M j, Y', strtotime($user->created_at)) ?></td>
                        <td>
                            <?php if ($user->isBanned()): ?>
                                <span class="badge badge-danger"><?= lang('Admin.banned') ?></span>
                            <?php elseif ($user->active): ?>
                                <span class="badge badge-success"><?= lang('Admin.active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= lang('Admin.inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_url('admin/users/' . $user->id . '/profile') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.authorProfileTitle') ?></a>
                            <?php if ($user->id !== auth()->id()): ?>
                            <a href="<?= base_url('admin/users/' . $user->id . '/edit') ?>" class="btn btn-sm btn-outline-primary"><?= lang('Admin.edit') ?></a>
                            <form method="POST" action="<?= base_url('admin/users/' . $user->id . '/delete') ?>" class="d-inline"
                                  onsubmit="return confirm('<?= lang('Admin.confirmDeleteUser') ?>')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><?= lang('Admin.delete') ?></button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small"><?= lang('Admin.youLabel') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= lang('Admin.usersNone') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($pager)): ?><?= $pager->links() ?><?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
