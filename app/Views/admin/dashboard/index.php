<?php $layout = 'admin/layouts/main'; ?>
<?php ob_start(); ?>

<?php if (!empty($update['available'])): ?>
    <?= view('admin/partials/update_banner', ['update' => $update]) ?>
<?php endif; ?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.dashboardTitle') ?></h1>
    <a href="<?= base_url('admin/posts/create') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> <?= lang('Admin.newPost') ?>
    </a>
</div>

<!-- Admin Notifications -->
<div id="admin-notifications">
    <?php foreach ($notifications as $notification): ?>
    <?php $alertClass = $notification->severity === 'error' ? 'danger' : $notification->severity; ?>
    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show mb-2" role="alert">
        <strong><?= esc($notification->source_name) ?>:</strong>
        <?= esc($notification->message) ?>
        <?php if ($notification->action_url): ?>
            <a href="<?= esc($notification->action_url) ?>" class="alert-link">
                <?= esc($notification->action_label ?? 'View') ?>
            </a>
        <?php endif; ?>
        <button type="button" class="btn-close-notification close" data-id="<?= $notification->id ?>" aria-label="Dismiss">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endforeach; ?>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang('Admin.dashPosts') ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['posts'] ?></div>
                        <div class="text-xs text-muted"><?= lang('Admin.published_count', [$stats['published_posts']]) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-pen-to-square fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= lang('Admin.dashPages') ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pages'] ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-file-lines fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?= lang('Admin.dashComments') ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['comments'] ?></div>
                        <?php if ($stats['pending_comments'] > 0): ?>
                        <div class="text-xs text-warning"><?= lang('Admin.pending_count', [$stats['pending_comments']]) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?= lang('Admin.dashUsers') ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['users'] ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Posts -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.dashRecentPosts') ?></h6>
                <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-primary"><?= lang('Admin.dashViewAll') ?></a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th><?= lang('Admin.title') ?></th>
                                <th><?= lang('Admin.status') ?></th>
                                <th><?= lang('Admin.date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_posts as $post): ?>
                            <tr>
                                <td><a href="<?= base_url('admin/posts/' . $post->id . '/edit') ?>"><?= esc($post->title) ?></a></td>
                                <td>
                                    <span class="badge badge-<?= $post->status === 'published' ? 'success' : ($post->status === 'draft' ? 'secondary' : 'warning') ?>">
                                        <?= esc($post->status) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('M j, Y', strtotime($post->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_posts)): ?>
                            <tr><td colspan="3" class="text-muted text-center py-3"><?= lang('Admin.noPostsYet', ['<a href="' . base_url('admin/posts/create') . '">' . lang('Admin.dashCreateOne') . '</a>']) ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Comments -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-warning"><?= lang('Admin.dashPendingComments') ?></h6>
                <a href="<?= base_url('admin/comments?status=pending') ?>" class="btn btn-sm btn-outline-warning"><?= lang('Admin.dashViewAll') ?></a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php foreach ($pending_comments as $c): ?>
                    <li class="list-group-item">
                        <strong><?= esc($c->author_name) ?></strong>
                        <span class="text-muted small"> — <?= date('M j', strtotime($c->created_at)) ?></span>
                        <p class="mb-0 text-sm"><?= esc(excerpt($c->content, 80)) ?></p>
                        <div class="mt-1">
                            <form method="POST" action="<?= base_url('admin/comments/' . $c->id . '/approve') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-success"><?= lang('Admin.approve') ?></button>
                            </form>
                            <form method="POST" action="<?= base_url('admin/comments/' . $c->id . '/spam') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-danger"><?= lang('Admin.spam') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($pending_comments)): ?>
                    <li class="list-group-item text-muted text-center py-3"><?= lang('Admin.dashNoPendingComments') ?></li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script>
$(document).on('click', '.btn-close-notification[data-id]', function() {
    var btn = $(this);
    var alertEl = btn.closest('.alert');
    var id = btn.data('id');

    var formData = new FormData();
    formData.append('csrf_test_name', '<?= csrf_hash() ?>');

    $.ajax({
        url: '<?= base_url("admin/notifications/dismiss") ?>/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function() {
            alertEl.fadeOut(300, function() { $(this).remove(); });
        }
    });
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
