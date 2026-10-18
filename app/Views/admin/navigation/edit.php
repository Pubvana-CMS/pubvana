<?php
/**
 * Edit one navigation item.
 *
 * Label, URL, and target only. Parent, group, and sort order are set when
 * the item is added and are not changed here.
 *
 * @var string                         $pageTitle
 * @var \Pubvana\Models\NavigationItem $item
 */
?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Edit Item</h3>
                <a href="/admin/navigation?group=<?= urlencode($item->nav_group) ?>"
                   class="btn btn-sm btn-outline-secondary">Back to list</a>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/navigation/<?= (int) $item->id ?>/update">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="navLabel">Label</label>
                        <input type="text" name="label" id="navLabel" class="form-control" required
                               maxlength="100" value="<?= htmlspecialchars($item->label) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="navUrl">URL</label>
                        <input type="text" name="url" id="navUrl" class="form-control" required
                               maxlength="500" value="<?= htmlspecialchars($item->url) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="navTarget">Target</label>
                        <select name="target" id="navTarget" class="form-select">
                            <option value="_self" <?= $item->target === '_self' ? 'selected' : '' ?>>Same window</option>
                            <option value="_blank" <?= $item->target === '_blank' ? 'selected' : '' ?>>New window</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
