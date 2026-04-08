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
                    <p class="form-control-plaintext"><?= esc($subject_user->getEmail()) ?></p>
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
                    <label class="custom-control-label" for="active"><?= lang('Admin.accountActive') ?></label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary mr-2"><?= lang('Admin.cancel') ?></a>
                <button type="submit" class="btn btn-primary"><?= lang('Admin.userSaveChanges') ?></button>
            </div>
        </form>
        <?php if ($subject_user->id !== auth()->id()): ?>
        <hr>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold"><?= lang('Admin.banStatus') ?></label>
                <?php if ($subject_user->isBanned()): ?>
                    <div class="alert alert-danger d-flex justify-content-between align-items-center mb-0">
                        <div>
                            <i class="fas fa-ban"></i>
                            <strong><?= lang('Admin.banned') ?></strong>
                            <?php if ($subject_user->getBanMessage()): ?>
                                — <?= esc($subject_user->getBanMessage()) ?>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= base_url('admin/users/' . $subject_user->id . '/ban') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-success"><?= lang('Admin.unban') ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= base_url('admin/users/' . $subject_user->id . '/ban') ?>" class="d-inline-flex align-items-center"
                          onsubmit="return confirm('<?= lang('Admin.confirmBanUser') ?>')">
                        <?= csrf_field() ?>
                        <input type="text" name="ban_reason" class="form-control form-control-sm mr-2" placeholder="<?= lang('Admin.banReasonPlaceholder') ?>" style="width: 300px;">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= lang('Admin.ban') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
