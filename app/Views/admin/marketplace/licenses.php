<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.licensesTitle') ?></h1>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (empty($licenses)): ?>
    <div class="card shadow mb-4">
        <div class="card-body text-center py-5">
            <i class="fas fa-key fa-4x text-muted mb-4"></i>
            <h4 class="text-muted"><?= lang('Admin.licensesNone') ?></h4>
            <p class="text-muted">Licensed products will appear here after installation.</p>
        </div>
    </div>
<?php else: ?>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th><?= lang('Admin.licensesProduct') ?></th>
                        <th><?= lang('Admin.licensesKey') ?></th>
                        <th><?= lang('Admin.licensesStatus') ?></th>
                        <th><?= lang('Admin.licensesType') ?></th>
                        <th><?= lang('Admin.licensesExpires') ?></th>
                        <th><?= lang('Admin.licensesDomain') ?></th>
                        <th><?= lang('Admin.licensesInstalled') ?></th>
                        <th><?= lang('Admin.licensesLastChecked') ?></th>
                        <th><?= lang('Admin.licensesActions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licenses as $lic): ?>
                    <?php
                        // Determine status
                        $isExpired = false;
                        if ($lic->expires_at && strtotime($lic->expires_at) < time()) {
                            $isExpired = true;
                        }
                        if ($lic->is_subscription && $lic->subscription_renews_at && strtotime($lic->subscription_renews_at) < time()) {
                            $isExpired = true;
                        }

                        if ((int) ($lic->license_valid ?? -1) === 1 && ! $isExpired) {
                            $statusLabel = lang('Admin.licensesStatusValid');
                            $statusClass = 'success';
                        } elseif ($isExpired) {
                            $statusLabel = $lic->is_subscription
                                ? lang('Admin.licensesStatusSubExpired')
                                : lang('Admin.licensesStatusExpired');
                            $statusClass = 'warning';
                        } elseif ((int) ($lic->license_valid ?? -1) === 0) {
                            $statusLabel = lang('Admin.licensesStatusInvalid');
                            $statusClass = 'danger';
                        } else {
                            $statusLabel = lang('Admin.licensesStatusUnchecked');
                            $statusClass = 'secondary';
                        }

                        // Determine expiry display
                        if ($lic->is_subscription && $lic->subscription_renews_at) {
                            $expiryDisplay = date('M j, Y', strtotime($lic->subscription_renews_at));
                        } elseif ($lic->expires_at) {
                            $expiryDisplay = date('M j, Y', strtotime($lic->expires_at));
                        } else {
                            $expiryDisplay = lang('Admin.licensesPerpetual');
                        }

                        // Mask license key: show first 8 + last 4
                        $maskedKey = strlen($lic->license_key) > 12
                            ? substr($lic->license_key, 0, 8) . '...' . substr($lic->license_key, -4)
                            : $lic->license_key;

                        // Relative time for last checked
                        $lastChecked = $lic->license_last_checked
                            ? \CodeIgniter\I18n\Time::parse($lic->license_last_checked)->humanize()
                            : lang('Admin.licensesNever');
                    ?>
                    <tr>
                        <td>
                            <?= esc($lic->product_name) ?>
                            <span class="badge badge-<?= match($lic->item_type) { 'theme' => 'primary', 'plugin' => 'info', default => 'secondary' } ?> ml-1"><?= ucfirst(esc($lic->item_type)) ?></span>
                        </td>
                        <td><code class="small"><?= esc($maskedKey) ?></code></td>
                        <td><span class="badge badge-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                        <td><?= $lic->is_subscription ? lang('Admin.licensesSubscription') : lang('Admin.licensesOneTime') ?></td>
                        <td><?= esc($expiryDisplay) ?></td>
                        <td><?= $lic->registered_domain ? esc($lic->registered_domain) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $lic->installed_version ? 'v' . esc($lic->installed_version) : '<span class="text-muted">' . lang('Admin.licensesNotInstalled') . '</span>' ?></td>
                        <td class="small"><?= $lastChecked ?></td>
                        <td>
                            <form method="POST" action="<?= base_url('admin/marketplace/licenses/revalidate/' . $lic->id) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary" title="<?= lang('Admin.licensesRevalidate') ?>">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
