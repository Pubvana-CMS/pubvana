<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.editUserTitle') ?></h1>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left fa-sm"></i> <?= lang('Admin.usersTitle') ?>
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/users/' . $subject_user->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label><?= lang('Admin.username') ?></label>
                    <p class="form-control-plaintext"><?= esc($subject_user->username ?? '') ?></p>
                </div>
                <div class="form-group col-md-6">
                    <label><?= lang('Admin.email') ?></label>
                    <p class="form-control-plaintext"><?= esc($subject_user->email) ?></p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label><?= lang('Admin.userRoleLabel') ?></label>
                    <select name="role" class="form-control">
                        <?php foreach (['subscriber', 'author', 'editor', 'admin', 'superadmin'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($current_group ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label><?= lang('Admin.userPasswordLabel') ?> <small class="text-muted"><?= lang('Admin.userPasswordOptional') ?></small></label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                           <?= ($subject_user->active ?? true) ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="active">Account active</label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary mr-2"><?= lang('Admin.cancel') ?></a>
                <button type="submit" class="btn btn-primary"><?= lang('Admin.userSaveChanges') ?></button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
