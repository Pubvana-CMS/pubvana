<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.revisionsTitle', [esc($post->title)]) ?></h1>
    <div>
        <a href="<?= base_url('admin/posts/' . $post->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.revisionsBackToPost') ?></a>
        <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-secondary ml-1"><?= lang('Admin.postsTitle') ?></a>
    </div>
</div>

<?php if (empty($revisions)): ?>
    <div class="alert alert-info"><?= lang('Admin.noRevisionsYet') ?></div>
<?php else: ?>
<div class="card shadow mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th><?= lang('Admin.revisionOn') ?></th>
                    <th><?= lang('Admin.revisionBy') ?></th>
                    <th><?= lang('Admin.status') ?></th>
                    <th><?= lang('Admin.title') ?></th>
                    <th class="text-right"><?= lang('Admin.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revisions as $i => $rev): ?>
                <tr>
                    <td class="text-muted small"><?= count($revisions) - $i ?></td>
                    <td><?= esc($rev->created_at) ?></td>
                    <td><?= esc($rev->author_name ?? '—') ?></td>
                    <td><span class="badge badge-<?= $rev->status === 'published' ? 'success' : 'secondary' ?>"><?= esc($rev->status) ?></span></td>
                    <td><?= esc($rev->title) ?></td>
                    <td class="text-right">
                        <a href="<?= base_url('admin/posts/revisions/' . $rev->id) ?>" class="btn btn-xs btn-outline-primary"><?= lang('Admin.view') ?></a>
                        <form method="POST" action="<?= base_url('admin/posts/revisions/' . $rev->id . '/restore') ?>" class="d-inline"
                              onsubmit="return confirm('<?= lang('Admin.revisionRestoreBtn') ?>')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-outline-warning"><?= lang('Admin.restore') ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
