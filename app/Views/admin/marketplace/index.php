<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.marketplaceTitle') ?></h1>
    <div>
        <form method="POST" action="<?= base_url('admin/marketplace/refresh') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary mr-2">
                <i class="fas fa-arrows-rotate fa-sm"></i> <?= lang('Admin.marketplaceRefresh') ?>
            </button>
        </form>
    </div>
</div>

<?php if (! empty($updates)): ?>
<div class="alert alert-warning d-flex align-items-center">
    <i class="fas fa-triangle-exclamation mr-2"></i>
    <strong><?= lang('Admin.marketplaceUpdatesAvailable', [count($updates)]) ?></strong>
    <?php foreach ($updates as $u): ?>
    <form method="POST" action="<?= base_url('admin/marketplace/update/' . $u['slug']) ?>" class="ml-2">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-warning"><?= lang('Admin.update') ?> <?= esc($u['name']) ?></button>
    </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($categories)): ?>
    <div class="card shadow mb-4">
        <div class="card-body text-center py-5">
            <i class="fas fa-store fa-4x text-muted mb-4"></i>
            <h4 class="text-muted"><?= lang('Admin.marketplaceNoItems') ?></h4>
            <p class="text-muted"><?= lang('Admin.marketplaceLoadError') ?></p>
        </div>
    </div>
<?php else: ?>

<!-- Category Tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach ($categories as $i => $cat): ?>
    <li class="nav-item">
        <a class="nav-link <?= $i === 0 ? 'active' : '' ?>"
           data-toggle="tab"
           href="#cat-<?= esc($cat->slug) ?>"><?= esc($cat->name) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <?php foreach ($categories as $i => $cat): ?>
    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="cat-<?= esc($cat->slug) ?>">
        <?php if (empty($cat->products)): ?>
            <p class="text-center text-muted py-4"><?= lang('Admin.marketplaceNoItems') ?></p>
        <?php else: ?>
        <div class="row">
            <?php foreach ($cat->products as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100 <?= ! empty($item->installed_version) ? 'border-success' : '' ?>">
                    <?php if (! empty($item->screenshot_url)): ?>
                        <img src="<?= esc($item->screenshot_url) ?>" class="card-img-top card-thumb obj-cover" alt="">
                    <?php else: ?>
                        <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center card-thumb">
                            <i class="fas fa-box fa-3x text-white-50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title mb-1"><?= esc($item->name) ?></h5>
                            <?php if (! empty($item->installed_version)): ?>
                                <span class="badge badge-success"><?= lang('Admin.marketplaceInstalled') ?></span>
                            <?php endif; ?>
                            <?php if (isset($pending_updates[$item->id ?? ''])): ?>
                                <span class="badge badge-info">
                                    <?= lang('Admin.updatesExtBadge', [esc($pending_updates[$item->id ?? ''])]) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (isset($pending_updates[$item->id ?? ''])): ?>
                                <a href="<?= base_url('admin/updates') ?>#ext-row-<?= esc($item->item_type ?? $item->type ?? 'plugin') ?>-<?= esc($item->slug) ?>"
                                   class="btn btn-xs btn-outline-info ml-1">
                                    <i class="fas fa-arrow-up-right-from-square fa-xs"></i> <?= lang('Admin.updatesExtGoToUpdates') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-2"><?= esc($item->description ?? '') ?></p>
                        <p class="text-muted small">
                            <?= lang('Admin.byAuthor', [esc($item->author ?? lang('Admin.unknown'))]) ?> &middot; v<?= esc($item->version ?? '1.0') ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white">
                        <?php $isFree = ! empty($item->is_free); ?>
                        <?php $itemType = $item->item_type ?? $item->type ?? 'plugin'; ?>
                        <?php if ($isFree): ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-success font-weight-bold"><?= lang('Admin.marketplaceFree') ?></span>
                                <?php if (empty($item->installed_version)): ?>
                                <form method="POST" action="<?= base_url('admin/marketplace/install') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="slug" value="<?= esc($item->slug) ?>">
                                    <input type="hidden" name="item_type" value="<?= esc($itemType) ?>">
                                    <input type="hidden" name="download_url" value="<?= esc($item->download_url ?? '') ?>">
                                    <input type="hidden" name="store_product_id" value="<?= esc($item->id) ?>">
                                    <button class="btn btn-sm btn-primary"><?= lang('Admin.marketplaceInstall') ?></button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small"><?= lang('Admin.marketplaceInstalledVersion', [esc($item->installed_version)]) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="font-weight-bold text-dark">$<?= number_format($item->price ?? 0, 2) ?></span>
                                <?php if (! empty($item->store_url)): ?>
                                <a href="<?= esc($item->store_url) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-arrow-up-right-from-square fa-xs"></i> <?= lang('Admin.marketplaceBuyNow') ?>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($item->installed_version)): ?>
                            <form method="POST" action="<?= base_url('admin/marketplace/install-licensed') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="slug" value="<?= esc($item->slug) ?>">
                                <input type="hidden" name="item_type" value="<?= esc($itemType) ?>">
                                <input type="hidden" name="store_product_id" value="<?= esc($item->id) ?>">
                                <div class="input-group">
                                    <input type="text" name="license_key" class="form-control form-control-sm"
                                           placeholder="<?= lang('Admin.licenseKeyPlaceholder') ?>" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download mr-1"></i> <?= lang('Admin.marketplaceInstall') ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small"><?= lang('Admin.marketplaceInstalledVersion', [esc($item->installed_version)]) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
