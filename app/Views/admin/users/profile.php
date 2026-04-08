<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.authorProfileTitle') ?> — <?= esc($subject_user->username) ?></h1>
    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.usersTitle') ?></a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.profileDetails') ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/users/' . $subject_user->id . '/profile') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.userDisplayName') ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="display_name" class="form-control"
                                   value="<?= esc($profile->display_name ?? '') ?>"
                                   placeholder="<?= esc($subject_user->username) ?>">
                            <small class="text-muted"><?= lang('Admin.profileDisplayNameHint') ?></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.userBio') ?></label>
                        <div class="col-sm-9">
                            <textarea name="bio" class="form-control" rows="4"
                                      placeholder="A short bio about the author..."><?= esc($profile->bio ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.userAvatar') ?></label>
                        <div class="col-sm-9">
                            <?php if (!empty($profile->avatar)): ?>
                                <div class="mb-2">
                                    <img src="<?= esc(base_url($profile->avatar)) ?>"
                                         alt="Current avatar" class="rounded-circle obj-cover" width="80" height="80">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="avatar" class="form-control-file" accept="image/*">
                            <small class="text-muted"><?= lang('Admin.profileAvatarHint') ?></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.userWebsite') ?></label>
                        <div class="col-sm-9">
                            <input type="url" name="website" class="form-control"
                                   value="<?= esc($profile->website ?? '') ?>"
                                   placeholder="https://example.com">
                        </div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold text-gray-700 mb-3"><?= lang('Admin.profileSocialHandles') ?></h6>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-twitter text-info"></i> <?= lang('Admin.userTwitter') ?></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">@</span></div>
                                <input type="text" name="twitter" class="form-control"
                                       value="<?= esc($profile->twitter ?? '') ?>"
                                       placeholder="username">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-facebook text-primary"></i> <?= lang('Admin.userFacebook') ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="facebook" class="form-control"
                                   value="<?= esc($profile->facebook ?? '') ?>"
                                   placeholder="profile URL or username">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><i class="fab fa-linkedin text-primary"></i> <?= lang('Admin.userLinkedin') ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="linkedin" class="form-control"
                                   value="<?= esc($profile->linkedin ?? '') ?>"
                                   placeholder="profile URL or username">
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.userSaveProfile') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.preview') ?></h6>
            </div>
            <div class="card-body text-center">
                <?php
                $avatarUrl = !empty($profile->avatar)
                    ? base_url($profile->avatar)
                    : 'https://www.gravatar.com/avatar/' . md5(strtolower($subject_user->email ?? '')) . '?s=80&d=mp';
                ?>
                <img src="<?= esc($avatarUrl) ?>" class="rounded-circle mb-3 obj-cover" width="80" height="80" alt="">
                <h6 class="font-weight-bold"><?= esc($profile->display_name ?? $subject_user->username) ?></h6>
                <?php if (!empty($profile->bio)): ?>
                    <p class="text-muted small"><?= nl2br(esc($profile->bio)) ?></p>
                <?php endif; ?>
                <?php if (!empty($profile->website)): ?>
                    <a href="<?= esc($profile->website) ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><?= lang('Admin.website') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// 2FA status — only shown to the user viewing their own profile
$isOwnProfile = auth()->loggedIn() && auth()->id() === (int) $subject_user->id;
$totpEnabled = $isOwnProfile ? ($totp_enabled ?? false) : false;
?>

<?php if ($isOwnProfile): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.twoFactorTitle') ?></h6>
        <?php if ($totpEnabled): ?>
            <span class="badge badge-success px-3 py-2"><i class="fas fa-circle-check mr-1"></i> <?= lang('Admin.enabled') ?></span>
        <?php else: ?>
            <span class="badge badge-secondary px-3 py-2"><?= lang('Admin.disabled') ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($totpEnabled): ?>
            <p class="text-muted small mb-3"><?= lang('Admin.totpActiveDesc') ?></p>
            <form method="POST" action="<?= base_url('admin/users/2fa/disable') ?>">
                <?= csrf_field() ?>
                <div class="form-group row align-items-center mb-2">
                    <label class="col-sm-4 col-form-label font-weight-bold"><?= lang('Admin.totpCurrentCode') ?></label>
                    <div class="col-sm-5">
                        <input type="text" name="totp_code" class="form-control text-center font-monospace tracking-wide"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               placeholder="000000" autocomplete="one-time-code">
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-danger btn-block"><?= lang('Admin.disable') ?></button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <p class="text-muted small mb-3"><?= lang('Admin.totpInactiveDesc') ?></p>
            <a href="<?= base_url('admin/users/2fa/setup') ?>" class="btn btn-primary">
                <i class="fas fa-shield-halved mr-1"></i> <?= lang('Admin.totpEnable') ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
