<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800"><?= lang('Admin.tagsTitle') ?></h1>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light"><tr><th><?= lang('Admin.name') ?></th><th><?= lang('Admin.slug') ?></th><th><?= lang('Admin.tagPostCount') ?></th><th><?= lang('Admin.actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><a href="<?= tag_url($tag->slug) ?>" target="_blank"><?= esc($tag->name) ?></a></td>
                    <td><code><?= esc($tag->slug) ?></code></td>
                    <td><?= $tag->post_count ?></td>
                    <td>
                        <form method="POST" action="<?= base_url('admin/tags/' . $tag->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('<?= lang('Admin.confirmDelete') ?>')">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><?= lang('Admin.delete') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tags)): ?><tr><td colspan="4" class="text-center text-muted py-4"><?= lang('Admin.noTagsYet') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
