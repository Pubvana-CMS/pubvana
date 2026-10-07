<?php
/**
 * Social Links edit form.
 *
 * @var string $pageTitle
 * @var \Pubvana\Plugins\SocialLinks\Models\SocialLink $link
 * @var array<string, string> $platforms  platform key => display label
 */
$platform = (string) $link->platform;
?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Link</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/social-links/<?= (int) $link->id ?>/update">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="platform">Platform</label>
                        <select name="platform" id="platform" class="form-select">
                            <?php foreach ($platforms as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"
                                        <?= $key === $platform ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Custom lets you set your own label and icon class.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="url">URL</label>
                        <input type="text" name="url" id="url" class="form-control"
                               value="<?= htmlspecialchars((string) $link->url) ?>">
                    </div>
                    <div class="mb-3" id="custom-fields">
                        <label class="form-label" for="label">Label</label>
                        <input type="text" name="label" id="label" class="form-control"
                               value="<?= htmlspecialchars((string) $link->label) ?>">
                        <label class="form-label mt-3" for="icon">Font Awesome class</label>
                        <input type="text" name="icon" id="icon" class="form-control"
                               value="<?= htmlspecialchars((string) $link->icon) ?>"
                               placeholder="fa-brands fa-x-twitter">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Save
                    </button>
                    <a href="/admin/social-links" class="btn btn-link">Cancel</a>
                </form>
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
