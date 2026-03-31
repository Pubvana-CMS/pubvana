<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.navigationTitle') ?></h1>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['primary' => lang('Admin.navGroupPrimary'), 'footer' => lang('Admin.navGroupFooter')] as $grp => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $group === $grp ? 'active' : '' ?>" href="<?= base_url('admin/navigation?group=' . $grp) ?>"><?= $label ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="row">
    <!-- Add new item -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.navItemLabel') ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= site_url('admin/navigation/store') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nav_group" value="<?= esc($group) ?>">
                    <!-- Quick Add dropdown with fuzzy search -->
                    <div class="form-group">
                        <label><?= lang('Admin.navQuickAdd') ?></label>
                        <div class="position-relative" id="quickAddWrapper">
                            <input type="text" class="form-control" id="quickAddSearch" placeholder="<?= lang('Admin.navQuickAddPlaceholder') ?>" autocomplete="off">
                            <div class="list-group position-absolute w-100 shadow-sm d-none" id="quickAddList" style="z-index: 1000; max-height: 250px; overflow-y: auto;">
                                <?php foreach ($available_routes as $group_name => $routes): ?>
                                    <div class="list-group-item list-group-item-secondary py-1 px-2 small font-weight-bold quick-add-group"><?= esc($group_name) ?></div>
                                    <?php foreach ($routes as $route): ?>
                                    <a href="#" class="list-group-item list-group-item-action py-1 px-3 quick-add-item"
                                       data-label="<?= esc($route['label']) ?>"
                                       data-url="<?= esc($route['url']) ?>"
                                       data-search="<?= esc(strtolower($route['label'] . ' ' . $route['url'])) ?>">
                                        <?= esc($route['label']) ?> <small class="text-muted"><?= esc($route['url']) ?></small>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= lang('Admin.navItemLabel') ?></label>
                        <input type="text" name="label" id="navLabel" class="form-control" required placeholder="e.g. About Us">
                    </div>
                    <div class="form-group">
                        <label><?= lang('Admin.navItemUrl') ?></label>
                        <input type="text" name="url" id="navUrl" class="form-control" required placeholder="/about">
                    </div>
                    <div class="form-group">
                        <label>Parent</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Top level —</option>
                            <?php foreach ($items as $item): ?>
                            <?php if (!$item->parent_id): ?>
                            <option value="<?= $item->id ?>"><?= esc($item->label) ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= lang('Admin.navItemTarget') ?></label>
                        <select name="target" class="form-control">
                            <option value="_self">Same window</option>
                            <option value="_blank">New window</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= lang('Admin.add') ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current items -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Menu Items</h6>
                <small class="text-muted">Drag to reorder</small>
            </div>
            <div class="card-body p-2">
                <?php if (empty($items)): ?>
                    <p class="text-center text-muted py-3">No items in this menu.</p>
                <?php else: ?>
                <ul class="list-group nav-sortable" id="nav-sortable">
                    <?php foreach ($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $item->id ?>">
                        <span>
                            <i class="fas fa-grip-vertical text-muted mr-2 cursor-grab"></i>
                            <?= $item->parent_id ? '<span class="ml-3 text-muted">↳ </span>' : '' ?>
                            <strong><?= esc($item->label) ?></strong>
                            <small class="text-muted ml-2"><?= esc($item->url) ?></small>
                            <?php if ($item->target === '_blank'): ?>
                            <i class="fas fa-arrow-up-right-from-square fa-xs text-muted ml-1"></i>
                            <?php endif; ?>
                        </span>
                        <form method="POST" action="<?= base_url('admin/navigation/' . $item->id . '/delete') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline-danger" onclick="return confirm('<?= lang('Admin.confirmDelete') ?>')"><?= lang('Admin.delete') ?></button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var el = document.getElementById('nav-sortable');
if (el) {
    Sortable.create(el, {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function() {
            var ids = Array.from(el.querySelectorAll('[data-id]')).map(li => li.dataset.id);
            fetch(baseUrl + 'admin/navigation/reorder', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ order: ids })
            });
        }
    });
}
var baseUrl = '<?= base_url() ?>';

// Quick Add fuzzy search
(function() {
    var search = document.getElementById('quickAddSearch');
    var list = document.getElementById('quickAddList');
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
            var match = !q || item.dataset.search.indexOf(q) !== -1;
            item.style.display = match ? '' : 'none';
        });

        // Hide group headers if all their items are hidden
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

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!document.getElementById('quickAddWrapper').contains(e.target)) {
            list.classList.add('d-none');
        }
    });
}());
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
