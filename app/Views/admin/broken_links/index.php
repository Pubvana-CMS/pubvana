<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.brokenLinksTitle') ?></h1>
    <div>
        <?php if ($showDismissed): ?>
            <a href="<?= base_url('admin/broken-links') ?>" class="btn btn-sm btn-outline-secondary mr-2">
                <?= lang('Admin.brokenLinkHideDismissed') ?>
            </a>
        <?php else: ?>
            <a href="<?= base_url('admin/broken-links?dismissed=1') ?>" class="btn btn-sm btn-outline-secondary mr-2">
                <?= lang('Admin.brokenLinkShowDismissed') ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Scan instruction card -->
<div class="card shadow mb-4">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap">
        <div class="text-muted small mr-3">
            <i class="fas fa-circle-info mr-1"></i>
            Run a full scan from the command line to populate this report:
            <code class="ml-1">php spark links:check</code>
        </div>
        <span class="badge badge-<?= $total > 0 ? 'danger' : 'success' ?> px-3 py-2 mt-2 mt-sm-0">
            <?= $total ?> issue<?= $total !== 1 ? 's' : '' ?> found
        </span>
    </div>
</div>

<?php if (empty($grouped)): ?>
    <div class="card shadow mb-4">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-circle-check fa-3x text-success mb-3 d-block"></i>
            <?= lang('Admin.brokenLinkNone') ?>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $group): ?>
    <div class="card shadow mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <div>
                <span class="badge badge-<?= $group['source_type'] === 'post' ? 'primary' : 'secondary' ?> mr-2">
                    <?= ucfirst($group['source_type']) ?>
                </span>
                <?php
                $editUrl = $group['source_type'] === 'post'
                    ? base_url('admin/posts/' . $group['source_id'] . '/edit')
                    : base_url('admin/pages/' . $group['source_id'] . '/edit');
                ?>
                <a href="<?= $editUrl ?>" class="font-weight-bold text-dark">
                    <?= esc($group['source_title']) ?>
                </a>
            </div>
            <span class="badge badge-danger">
                <?= count($group['links']) ?> broken
            </span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><?= lang('Admin.brokenLinkUrl') ?></th>
                        <th class="w-10 text-center"><?= lang('Admin.brokenLinkStatus') ?></th>
                        <th class="w-15"><?= lang('Admin.brokenLinkError') ?></th>
                        <th class="w-15 text-muted small"><?= lang('Admin.date') ?></th>
                        <th class="w-15 text-right"><?= lang('Admin.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($group['links'] as $link): ?>
                    <tr class="<?= $link->dismissed ? 'text-muted' : '' ?>">
                        <td class="small text-truncate">
                            <a href="<?= esc($link->url) ?>" target="_blank" rel="noopener"
                               class="text-truncate d-block" title="<?= esc($link->url) ?>">
                                <?= esc($link->url) ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <?php if ($link->http_status): ?>
                                <span class="badge badge-<?= $link->http_status >= 400 ? 'danger' : 'warning' ?>">
                                    <?= $link->http_status ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Timeout</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted text-truncate">
                            <span class="text-truncate d-block" title="<?= esc($link->error_message ?? '') ?>">
                                <?= esc($link->error_message ?? '—') ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= $link->last_checked_at
                                ? date('M j, g:ia', strtotime($link->last_checked_at))
                                : '—' ?>
                        </td>
                        <td class="text-right">
                            <form method="POST"
                                  action="<?= base_url('admin/broken-links/' . $link->id . '/recheck') ?>"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-outline-primary"
                                        title="Re-check this URL">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </form>
                            <?php if (! $link->dismissed): ?>
                            <form method="POST"
                                  action="<?= base_url('admin/broken-links/' . $link->id . '/dismiss') ?>"
                                  class="d-inline ml-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-outline-secondary"
                                        title="Dismiss (hide from results)">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
