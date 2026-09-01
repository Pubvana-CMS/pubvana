<?php
/**
 * Social Links listing and add form.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\SocialLinks\Models\SocialLink[] $links
 * @var array<string, string> $platforms  platform key => display label
 */
$count = count($links);
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add Link</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/social-links/store">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="platform">Platform</label>
                        <select name="platform" id="platform" class="form-select">
                            <?php foreach ($platforms as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Custom lets you set your own label and icon class.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="url">URL</label>
                        <input type="text" name="url" id="url" class="form-control"
                               placeholder="https://facebook.com/yourpage">
                    </div>
                    <div class="mb-3" id="custom-fields">
                        <label class="form-label" for="label">Label</label>
                        <input type="text" name="label" id="label" class="form-control">
                        <label class="form-label mt-3" for="icon">Font Awesome class</label>
                        <input type="text" name="icon" id="icon" class="form-control"
                               placeholder="fa-brands fa-x-twitter">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Add Link
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Your Links</h3>
                <div class="card-subtitle ms-auto text-secondary"><?= $count ?> total</div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">Icon</th>
                            <th>Platform</th>
                            <th>URL</th>
                            <th class="w-1">Order</th>
                            <th class="w-1">Active</th>
                            <th class="w-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($count === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No social links yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($links as $index => $link): ?>
                                <tr>
                                    <td><i class="<?= htmlspecialchars((string) $link->icon) ?> fa-lg"></i></td>
                                    <td>
                                        <div><?= htmlspecialchars((string) $link->label) ?></div>
                                        <div class="small text-secondary"><?= htmlspecialchars((string) $link->platform) ?></div>
                                    </td>
                                    <td class="text-break text-secondary"><?= htmlspecialchars((string) $link->url) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Reorder">
                                            <form method="POST" action="/admin/social-links/<?= (int) $link->id ?>/reorder" class="d-inline">
                                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="direction" value="up">
                                                <button class="btn btn-outline-secondary btn-icon"
                                                        <?= $index === 0 ? 'disabled' : '' ?> title="Move up">
                                                    <i class="ti ti-arrow-up"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="/admin/social-links/<?= (int) $link->id ?>/reorder" class="d-inline">
                                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="direction" value="down">
                                                <button class="btn btn-outline-secondary btn-icon"
                                                        <?= $index === $count - 1 ? 'disabled' : '' ?> title="Move down">
                                                    <i class="ti ti-arrow-down"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="/admin/social-links/<?= (int) $link->id ?>/toggle" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <?php if ((int) $link->is_active === 1): ?>
                                                <button class="badge bg-success-lt border-0" title="Click to disable">Active</button>
                                            <?php else: ?>
                                                <button class="badge bg-secondary-lt border-0" title="Click to enable">Disabled</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="/admin/social-links/<?= (int) $link->id ?>/delete"
                                              class="d-inline" onsubmit="return confirm('Delete this social link?')">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('platform');
    var custom = document.getElementById('custom-fields');
    function sync() {
        custom.style.display = select.value === 'custom' ? '' : 'none';
    }
    select.addEventListener('change', sync);
    sync();
})();
</script>