<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.tfaSetupHeading') ?></h1>
    <a href="<?= base_url('admin/users/' . $user_id . '/profile') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.cancel') ?></a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.tfaScanQr') ?></h6>
            </div>
            <div class="card-body text-center">
                <p class="text-muted small mb-3">
                    <?= lang('Admin.totpScanInstructions') ?>
                </p>
                <canvas id="qrcode" class="mb-3"></canvas>
                <div class="text-muted small">
                    <?= lang('Admin.totpManualEntry') ?>
                    <div class="mt-1">
                        <code class="font-monospace text-dark" style="font-size:1rem; letter-spacing:0.15em">
                            <?= esc(chunk_split($secret, 4, ' ')) ?>
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.tfaConfirmBtn') ?></h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    <?= lang('Admin.totpConfirmInstructions') ?>
                </p>
                <form method="POST" action="<?= base_url('admin/users/2fa/confirm') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="font-weight-bold"><?= lang('Admin.tfaCodeLabel') ?></label>
                        <input type="text" name="totp_code"
                               class="form-control text-center font-monospace tracking-wider"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               placeholder="000000" autofocus autocomplete="one-time-code"
                               style="font-size:1.4rem">
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check mr-1"></i> <?= lang('Admin.tfaConfirmBtn') ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-warning small">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            <?= lang('Admin.totpRecoveryWarning') ?>
        </div>
    </div>
</div>

<?php
$uriJson = json_encode($provisioning_uri);
$content = ob_get_clean();
$content .= '<script>window._totpUri=' . $uriJson . ';</script>';
?>
<?php $extra_scripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    QRCode.toCanvas(document.getElementById('qrcode'), window._totpUri, {
        width: 220,
        margin: 2,
        color: { dark: '#1a2744', light: '#ffffff' }
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
