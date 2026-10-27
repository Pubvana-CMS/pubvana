<?php
/**
 * Tabbed profile page for the current admin user, or for another user when
 * the viewer holds profile.edit.any.
 *
 * Core tabs: Profile (the profile fields) and Security (own profile only).
 * Plugins add their own tabs through the profile.admin.tab extension type.
 * Only the active tab's body is rendered, so tabs cost nothing until opened.
 *
 * @var object       $profile      Profile entity
 * @var object       $user         Profile owner
 * @var string       $avatarPicker Rendered Media picker, or empty
 * @var string       $returnUrl    Where the profile form returns to
 * @var string       $adminBase    Admin route base, e.g. /admin/profile
 * @var string       $profileUrl   This profile's page URL
 * @var list<array{id: string, label: string, active: bool, output: string}> $tabs
 * @var array{email: ?string, two_factor: bool, sessions: list<array<string, mixed>>}|null $security
 */

$activeTab = $tabs[0] ?? ['id' => 'profile', 'label' => 'Profile', 'output' => ''];
foreach ($tabs as $tab) {
    if (!empty($tab['active'])) {
        $activeTab = $tab;
        break;
    }
}

$securityReturn = $profileUrl . '?tab=security';
?>
<ul class="nav nav-tabs mb-3" role="tablist">
    <?php foreach ($tabs as $tab): ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= !empty($tab['active']) ? 'active' : '' ?>"
               href="<?= htmlspecialchars($profileUrl) ?>?tab=<?= rawurlencode((string) $tab['id']) ?>"
               role="tab"
               <?= !empty($tab['active']) ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars((string) $tab['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($activeTab['id'] === 'profile'): ?>
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
                        <div class="form-hint">Full URL, e.g. https://x.com/username</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="facebook">Facebook</label>
                        <input type="text" class="form-control" id="facebook" name="facebook"
                               value="<?= htmlspecialchars($profile->facebook ?? '') ?>">
                        <div class="form-hint">Full URL, e.g. https://facebook.com/username</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="linkedin">LinkedIn</label>
                        <input type="text" class="form-control" id="linkedin" name="linkedin"
                               value="<?= htmlspecialchars($profile->linkedin ?? '') ?>">
                        <div class="form-hint">Full URL, e.g. https://linkedin.com/in/username</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="job_title">Job Title</label>
                        <input type="text" class="form-control" id="job_title" name="job_title"
                               value="<?= htmlspecialchars($profile->job_title ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="works_for">Works For</label>
                        <input type="text" class="form-control" id="works_for" name="works_for"
                               value="<?= htmlspecialchars($profile->works_for ?? '') ?>">
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php elseif ($activeTab['id'] === 'security' && $security !== null): ?>
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account</h3>
            </div>
            <div class="card-body">
                <form method="post"
                      action="<?= htmlspecialchars($adminBase) ?>/<?= (int) $user->id ?>/security/credentials">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($securityReturn) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars((string) ($security['email'] ?? '')) ?>" autocomplete="email">
                        <div class="form-hint">Used to sign in and to receive password resets.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Change Email</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <div class="card-body">
                <form method="post"
                      action="<?= htmlspecialchars($adminBase) ?>/<?= (int) $user->id ?>/security/password">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($securityReturn) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password"
                               name="current_password" autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" class="form-control" id="password"
                               name="password" autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_confirm">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirm"
                               name="password_confirm" autocomplete="new-password">
                    </div>

                    <div class="form-hint mb-3">
                        Changing the password signs out every other device and revokes remember-me tokens.
                    </div>

                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>

        <?php if ($security['two_factor']): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Two-Factor Authentication</h3>
            </div>
            <div class="card-body">
                <p class="mb-0">Email two-factor authentication is on. Shield emails a code after each sign-in.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Sessions</h3>
                <div class="card-actions">
                    <form method="post"
                          action="<?= htmlspecialchars($adminBase) ?>/<?= (int) $user->id ?>/security/sessions/revoke">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($securityReturn) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sign out other devices</button>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Last Active</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($security['sessions'] === []): ?>
                            <tr>
                                <td colspan="4" class="text-secondary">No live sessions.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($security['sessions'] as $session): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars((string) $session['device']) ?>
                                    <?php if (!empty($session['current'])): ?>
                                        <span class="badge bg-green-lt ms-1">This device</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) $session['ip']) ?></td>
                                <td><?= htmlspecialchars((string) $session['last_active']) ?></td>
                                <td class="text-end">
                                    <?php if (empty($session['current'])): ?>
                                        <form method="post"
                                              action="<?= htmlspecialchars($adminBase) ?>/<?= (int) $user->id ?>/security/sessions/revoke">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($securityReturn) ?>">
                                            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Sign out</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= htmlspecialchars((string) $activeTab['label']) ?></h3>
            </div>
            <div class="card-body">
                <?= $activeTab['output'] ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
