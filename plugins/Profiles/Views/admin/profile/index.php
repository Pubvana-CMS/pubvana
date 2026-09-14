<?php
/**
 * Self-service profile page for the current admin user.
 *
 * @var object $profile    Profile entity
 * @var object $user       Authenticated user entity
 * @var string $returnUrl
 * @var string $adminBase
 */
?>
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Profile</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $adminBase ?>/<?= (int) $user->id ?>/update">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="display_name">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name"
                               value="<?= htmlspecialchars($profile->display_name ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($profile->bio ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Avatar</label>
                        <?= $avatarPicker ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="website">Website</label>
                        <input type="text" class="form-control" id="website" name="website"
                               value="<?= htmlspecialchars($profile->website ?? '') ?>">
                        <div class="form-hint">Full URL, e.g. https://example.com</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="twitter">Twitter</label>
                        <input type="text" class="form-control" id="twitter" name="twitter"
                               value="<?= htmlspecialchars($profile->twitter ?? '') ?>">
                        <div class="form-hint">Handle or full URL, e.g. @username or https://x.com/username</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="facebook">Facebook</label>
                        <input type="text" class="form-control" id="facebook" name="facebook"
                               value="<?= htmlspecialchars($profile->facebook ?? '') ?>">
                        <div class="form-hint">Handle or full URL, e.g. username or https://facebook.com/username</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="linkedin">LinkedIn</label>
                        <input type="text" class="form-control" id="linkedin" name="linkedin"
                               value="<?= htmlspecialchars($profile->linkedin ?? '') ?>">
                        <div class="form-hint">Handle or full URL, e.g. username or https://linkedin.com/in/username</div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
