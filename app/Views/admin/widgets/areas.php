<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.widgetsTitle') ?></h1>
</div>

<?php if (! empty($invalidLicenses)): ?>
<div class="alert alert-danger border-danger">
    <strong><i class="fas fa-triangle-exclamation mr-1"></i> <?= lang('Admin.licenseWarningTitle') ?></strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($invalidLicenses as $lic): ?>
        <li><?= esc($lic->product_name) ?> — <?= lang('Admin.licenseWarningInvalid') ?></li>
        <?php endforeach; ?>
    </ul>
    <a href="<?= base_url('admin/marketplace/licenses') ?>" class="alert-link"><?= lang('Admin.licenseWarningManage') ?></a>
</div>
<?php endif; ?>

<?php if (empty($areas)): ?>
    <div class="alert alert-info"><?= lang('Admin.widgetNoAreas') ?></div>
<?php else: ?>

<ul class="nav nav-tabs mb-3" id="areaTabs" role="tablist">
    <?php foreach ($areas as $i => $area): ?>
    <li class="nav-item">
        <a class="nav-link <?= $i === 0 ? 'active' : '' ?>"
           data-toggle="tab" href="#area-<?= $area->slug ?>"><?= esc($area->name) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach ($areas as $i => $area): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="area-<?= $area->slug ?>">
    <div class="row">
        <!-- Active widgets in this area -->
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc($area->name) ?></h6>
                    <small class="text-muted"><?= lang('Admin.dragToReorder') ?></small>
                </div>
                <div class="card-body p-2">
                    <ul class="list-group widget-sortable" id="sortable-<?= $area->slug ?>" data-area="<?= $area->slug ?>">
                        <?php $areaInstances = array_filter($instances, fn($wi) => ($wi->area_slug ?? '') === $area->slug); ?>
                        <?php usort($areaInstances, fn($a, $b) => $a->sort_order - $b->sort_order); ?>
                        <?php if (empty($areaInstances)): ?>
                        <li class="list-group-item text-center text-muted py-4" id="empty-<?= $area->slug ?>">
                            <?= lang('Admin.widgetAreaEmpty') ?>
                        </li>
                        <?php endif; ?>
                        <?php foreach ($areaInstances as $wi): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $wi->id ?>">
                            <span>
                                <i class="fas fa-grip-vertical text-muted mr-2" style="cursor:grab"></i>
                                <strong><?= esc($wi->widget_name ?? $wi->folder) ?></strong>
                                <?php
                                $opts = json_decode($wi->options_json ?? '{}', true);
                                if (!empty($opts['title'])): ?>
                                    <small class="text-muted ml-2"><?= esc($opts['title']) ?></small>
                                <?php endif; ?>
                            </span>
                            <span>
                                <a href="<?= base_url('admin/widgets/' . $wi->id . '/configure') ?>" class="btn btn-xs btn-outline-primary"><?= lang('Admin.widgetConfigure') ?></a>
                                <form method="POST" action="<?= base_url('admin/widgets/' . $wi->id . '/remove') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger" onclick="return confirm('<?= lang('Admin.confirmDelete') ?>')"><?= lang('Admin.delete') ?></button>
                                </form>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Available widgets to add -->
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.widgetAvailable') ?></h6>
                </div>
                <div class="card-body p-2">
                    <?php foreach ($available as $w):
                        $isPubvana = in_array($w->author ?? '', ['pubvana', 'pubvana_team'], true);
                        $isBundled = ! empty($w->bundled);
                        $isFree    = ! empty($w->free);
                        $lic       = $w->store_product_id ? ($licenses[$w->store_product_id] ?? null) : null;
                        $licValid  = $lic ? (int) ($lic->license_valid ?? -1) : -1;
                        $needsLicense = (! $isBundled && ! $isFree) && (! $lic || $licValid !== 1);
                    ?>
                        <?php if (! empty($w->disabled)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-muted">
                                <?= esc($w->name) ?>
                                <span class="badge badge-danger badge-pill" title="<?= esc($w->disabled_reason) ?>"><?= lang('Admin.addonDisabled') ?></span>
                            </li>
                            <?php continue; ?>
                        <?php endif; ?>
                    <form method="POST" action="<?= base_url('admin/widgets/add') ?>" class="mb-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="widget_id" value="<?= $w->id ?>">
                        <input type="hidden" name="widget_area_id" value="<?= $area->id ?>">
                        <button type="submit" class="d-none"></button>
                        <div class="d-flex justify-content-between align-items-center border rounded p-2">
                            <div>
                                <strong><?= esc($w->name) ?></strong>
                                <small class="text-muted"><?= lang('Admin.safetyLabel') ?></small>
                                <?php if ($w->pv_safe === null): ?>
                                    <span class="badge badge-light"><?= lang('Admin.unchecked') ?></span>
                                    <button formaction="<?= base_url('admin/widgets/' . $w->id . '/recheck') ?>" class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                                <?php elseif ((int) $w->pv_safe === 2): ?>
                                    <span class="badge badge-warning"><?= lang('Admin.safetyUnknown') ?></span>
                                    <button formaction="<?= base_url('admin/widgets/' . $w->id . '/recheck') ?>" class="btn btn-sm btn-outline-info ml-1 py-0 px-1" title="<?= lang('Admin.recheckBtn') ?>"><i class="fas fa-sync-alt"></i></button>
                                <?php elseif ((int) $w->pv_safe === 1): ?>
                                    <span class="badge badge-success"><?= lang('Admin.safe') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= lang('Admin.malicious') ?></span>
                                <?php endif ?><br>
                                <?php if (! empty($w->pv_warning_note)): ?>
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> <?= esc($w->pv_warning_note) ?></small><br>
                                <?php endif ?>
                                <small class="text-muted"><?= esc($w->description) ?></small>
                            </div>
                            <div class="d-flex align-items-center ml-2">
                                <?php if ($isBundled): ?>
                                    <?php /* Bundled — no license display */ ?>
                                <?php elseif ($isPubvana): ?>
                                    <?php
                                        $renewUrl = 'https://pubvana.net/dstore/product/' . $w->folder;
                                    ?>
                                    <?php if ($lic && $licValid === 1): ?>
                                        <span class="badge badge-success mr-2"><?= lang('Admin.licenseLicensed') ?></span>
                                    <?php elseif ($lic && $licValid === 0): ?>
                                        <span class="badge badge-danger mr-1"><?= lang('Admin.licenseExpired') ?></span>
                                        <button type="button" class="btn btn-xs btn-outline-danger mr-1" data-toggle="modal" data-target="#licenseModal-widget-<?= $w->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                        <?php if ($renewUrl): ?>
                                            <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mr-1"><?= lang('Admin.licenseRenew') ?></a>
                                        <?php endif; ?>
                                    <?php elseif ($lic && $licValid === -1): ?>
                                        <span class="badge badge-warning mr-1"><?= lang('Admin.licenseCheckNow') ?></span>
                                        <button type="button" class="btn btn-xs btn-outline-warning mr-1" data-toggle="modal" data-target="#licenseModal-widget-<?= $w->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-xs btn-outline-primary mr-1" data-toggle="modal" data-target="#licenseModal-widget-<?= $w->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (! empty($w->license_validate_url)): ?>
                                        <?php if ($lic && $licValid === 1): ?>
                                            <span class="badge badge-success mr-2"><?= lang('Admin.licenseLicensed') ?></span>
                                        <?php elseif ($lic && $licValid === 0): ?>
                                            <span class="badge badge-danger mr-1"><?= lang('Admin.licenseExpired') ?></span>
                                            <button type="button" class="btn btn-xs btn-outline-danger mr-1" data-toggle="modal" data-target="#licenseModal-widget-<?= $w->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                            <?php $renewUrl = $w->store_url ?? ''; ?>
                                            <?php if ($renewUrl): ?>
                                                <a href="<?= esc($renewUrl) ?>" target="_blank" class="btn btn-xs btn-outline-info mr-1"><?= lang('Admin.licenseRenew') ?></a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-xs btn-outline-primary mr-1" data-toggle="modal" data-target="#licenseModal-widget-<?= $w->id ?>"><?= lang('Admin.licenseEnterKey') ?></button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-light mr-2"><?= lang('Admin.licenseThirdPartyLabel') ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" <?= $needsLicense || (int) ($w->pv_safe ?? -1) === 0 ? 'disabled' : '' ?>><?= lang('Admin.widgetAddToArea') ?></button>
                            </div>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Widget license key modals -->
<?php foreach ($available as $w): ?>
    <?php
        $isPubvana = in_array($w->author ?? '', ['pubvana', 'pubvana_team'], true);
        $isBundled = ! empty($w->bundled);
    ?>
    <?php if (($isPubvana && ! $isBundled) || ! empty($w->license_validate_url)): ?>
    <div class="modal fade" id="licenseModal-widget-<?= $w->id ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <?php if ($w->store_product_id): ?>
                <form action="<?= site_url('admin/widgets/save-license') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="store_product_id" value="<?= esc($w->store_product_id) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= lang('Admin.licenseModalTitle') ?> - <?= esc($w->name) ?></h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p><?= lang('Admin.licenseModalBody') ?></p>
                        <div class="form-group">
                            <input type="text" class="form-control" name="license_key"
                                   value="<?= esc(($licenses[$w->store_product_id] ?? null)?->license_key ?? '') ?>"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.btnCancel') ?></button>
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.licenseModalSave') ?></button>
                    </div>
                </form>
                <?php else: ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang('Admin.licenseModalTitle') ?> - <?= esc($w->name) ?></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <?= lang('Admin.licenseNoStoreProduct') ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Admin.btnCancel') ?></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var baseUrl = '<?= base_url() ?>';
var csrfHash = '<?= csrf_hash() ?>';

document.querySelectorAll('.widget-sortable').forEach(function(el) {
    Sortable.create(el, {
        animation: 150,
        handle: '.fa-grip-vertical',
        ghostClass: 'bg-light',
        onEnd: function(evt) {
            var ids = Array.from(el.querySelectorAll('[data-id]')).map(li => li.dataset.id);
            fetch(baseUrl + 'admin/widgets/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfHash
                },
                body: JSON.stringify({ order: ids })
            });
        }
    });
});

// Restore active tab from URL hash
if (window.location.hash) {
    var tab = $('a[href="' + window.location.hash + '"]');
    if (tab.length) tab.tab('show');
}
</script>
<?php $extra_scripts = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
