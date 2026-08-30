<?php
/**
 * Navigation admin page.
 *
 * Two-column layout: add form (left), sortable list (right).
 * Group tabs at top to switch between primary/footer/custom groups.
 *
 * @var string    $pageTitle
 * @var \Pubvana\Models\NavigationItem[] $items   Flat list of all items in group
 * @var array     $tree       Nested tree for parent dropdown
 * @var string    $group      Active nav group
 * @var string[]  $groups     Available nav groups
 * @var array     $linkable   Grouped linkable items for Quick Add
 */

$topLevelItems = array_filter($items, fn($i) => empty($i->parent_id));
?>

<!-- Group tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach ($groups as $grp): ?>
    <li class="nav-item">
        <a class="nav-link <?= $group === $grp ? 'active' : '' ?>"
           href="/admin/navigation?group=<?= htmlspecialchars($grp) ?>">
            <?= htmlspecialchars(ucfirst($grp)) ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="row">
    <!-- Add new item -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Add Item</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/navigation/store">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nav_group" value="<?= htmlspecialchars($group) ?>">

                    <!-- Quick Add -->
                    <?php if (!empty($linkable)): ?>
                    <div class="mb-3">
                        <label class="form-label">Quick Add</label>
                        <div class="position-relative" id="quickAddWrapper">
                            <input type="text" class="form-control" id="quickAddSearch"
                                   placeholder="Search pages, routes..." autocomplete="off">
                            <div class="list-group position-absolute w-100 shadow-sm d-none card"
                                 id="quickAddList" style="z-index: 1000; max-height: 250px; overflow-y: auto;">
                                <?php foreach ($linkable as $groupName => $routes): ?>
                                    <div class="list-group-item list-group-item-secondary py-1 px-2 small fw-bold quick-add-group">
                                        <?= htmlspecialchars($groupName) ?>
                                    </div>
                                    <?php foreach ($routes as $route): ?>
                                    <a href="#" class="list-group-item list-group-item-action py-1 px-3 quick-add-item"
                                       data-label="<?= htmlspecialchars($route['label']) ?>"
                                       data-url="<?= htmlspecialchars($route['url']) ?>"
                                       data-search="<?= htmlspecialchars(strtolower($route['label'] . ' ' . $route['url'])) ?>">
                                        <?= htmlspecialchars($route['label']) ?>
                                        <small class="text-secondary"><?= htmlspecialchars($route['url']) ?></small>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" id="navLabel" class="form-control" required
                               placeholder="e.g. About Us">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" id="navUrl" class="form-control" required
                               placeholder="/about">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Top level</option>
                            <?php foreach ($topLevelItems as $item): ?>
                            <option value="<?= (int) $item->id ?>"><?= htmlspecialchars($item->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="target" class="form-select">
                            <option value="_self">Same window</option>
                            <option value="_blank">New window</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current items -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Menu Items</h3>
                <small class="text-secondary">Drag to reorder</small>
            </div>
            <div class="card-body p-2">
                <?php if (empty($items)): ?>
                    <p class="text-center text-secondary py-4">No items in this menu.</p>
                <?php else: ?>
                <ul class="list-group" id="nav-sortable">
                    <?php foreach ($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-id="<?= (int) $item->id ?>">
                        <span>
                            <i class="ti ti-grip-vertical text-secondary me-2" style="cursor: grab;"></i>
                            <?php if ($item->parent_id): ?>
                                <span class="ms-3 text-secondary">&rdsh; </span>
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($item->label) ?></strong>
                            <small class="text-secondary ms-2"><?= htmlspecialchars($item->url) ?></small>
                            <?php if ($item->target === '_blank'): ?>
                            <i class="ti ti-external-link text-secondary ms-1" style="font-size: 0.75rem;"></i>
                            <?php endif; ?>
                        </span>
                        <form method="POST" action="/admin/navigation/<?= (int) $item->id ?>/delete"
                              class="d-inline" onsubmit="return confirm('Delete this item?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function() {
    var el = document.getElementById('nav-sortable');
    if (el) {
        Sortable.create(el, {
            animation: 150,
            handle: '.ti-grip-vertical',
            onEnd: function() {
                var ids = Array.from(el.querySelectorAll('[data-id]')).map(function(li) {
                    return li.dataset.id;
                });
                fetch('/admin/navigation/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order: ids })
                });
            }
        });
    }

    // Quick Add fuzzy search
    var search = document.getElementById('quickAddSearch');
    var list = document.getElementById('quickAddList');
    if (!search || !list) return;

    var items = list.querySelectorAll('.quick-add-item');
    var groups = list.querySelectorAll('.quick-add-group');
    var labelInput = document.getElementById('navLabel');
    var urlInput = document.getElementById('navUrl');

    search.addEventListener('focus', function() {
        list.classList.remove('d-none');
    });

    search.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        list.classList.remove('d-none');

        items.forEach(function(item) {
            item.style.display = (!q || item.dataset.search.indexOf(q) !== -1) ? '' : 'none';
        });

        groups.forEach(function(grp) {
            var next = grp.nextElementSibling;
            var hasVisible = false;
            while (next && !next.classList.contains('quick-add-group')) {
                if (next.classList.contains('quick-add-item') && next.style.display !== 'none') {
                    hasVisible = true;
                }
                next = next.nextElementSibling;
            }
            grp.style.display = hasVisible ? '' : 'none';
        });
    });

    items.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            labelInput.value = this.dataset.label;
            urlInput.value = this.dataset.url;
            search.value = '';
            list.classList.add('d-none');
            labelInput.focus();
        });
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('quickAddWrapper').contains(e.target)) {
            list.classList.add('d-none');
        }
    });
}());
</script>
