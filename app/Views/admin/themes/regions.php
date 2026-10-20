<?php
/**
 * Region manager, assign blocks to regions with ordering.
 *
 * @var string $pageTitle
 * @var array  $regions      region_id => {id, label, description, source}
 * @var array  $placements   region_id => BlockPlacement[]
 * @var array  $blocks       block_key => {label, description, options, ...}
 * @var array  $orphaned     BlockPlacement[], placements with no matching region
 * @var array  $savedValues  placement_id => options array
 * @var array  $fontChoices  Label => CSS font stack for font fields
 */
$wysiwygSelectors = [];
?>

<style>
.drag-over-above { box-shadow: 0 -2px 0 0 var(--tblr-primary); }
.drag-over-below { box-shadow: 0 2px 0 0 var(--tblr-primary); }
</style>

<?php if (!empty($orphaned)): ?>
<div class="alert alert-warning mb-4">
    <div class="d-flex align-items-center mb-2">
        <i class="ti ti-alert-triangle me-2"></i>
        <strong>Orphaned Placements</strong>
    </div>
    <p class="small mb-2">These blocks are assigned to regions that the current theme doesn't support. Reassign them to an available region or remove them.</p>
    <div class="table-responsive">
        <table class="table table-sm table-vcenter mb-0">
            <thead>
                <tr>
                    <th>Block</th>
                    <th>Former Region</th>
                    <th>Move To</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orphaned as $placement): ?>
                <?php $blockInfo = $blocks[$placement->block_key] ?? null; ?>
                <tr>
                    <td>
                        <?php if ($blockInfo): ?>
                            <?= htmlspecialchars($blockInfo['label'] ?? $placement->block_key) ?>
                        <?php else: ?>
                            <span class="text-secondary"><?= htmlspecialchars($placement->block_key) ?></span>
                            <small class="text-danger">(unregistered)</small>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($placement->region_id) ?></code></td>
                    <td>
                        <form method="POST" action="/admin/themes/regions/move" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="placement_id" value="<?= (int) $placement->id ?>">
                            <select name="region_id" class="form-select form-select-sm" style="max-width:200px">
                                <?php foreach ($regions as $rid => $rinfo): ?>
                                <option value="<?= htmlspecialchars($rid) ?>"><?= htmlspecialchars($rinfo['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Move</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="/admin/themes/regions/remove">
                            <?= csrf_field() ?>
                            <input type="hidden" name="placement_id" value="<?= (int) $placement->id ?>">
                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Remove">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($blocks)): ?>
<div class="alert alert-info mb-4">
    <i class="ti ti-info-circle me-2"></i>
    No blocks are registered. Plugins register blocks during startup, and the active theme can declare blocks in its pubvana.json.
</div>
<?php endif; ?>

<div class="row">
<?php foreach ($regions as $regionId => $regionInfo): ?>
    <?php $regionPlacements = $placements[$regionId] ?? []; ?>
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-0"><?= htmlspecialchars($regionInfo['label']) ?></h3>
                    <small class="text-secondary">
                        <?= htmlspecialchars($regionId) ?>
                        <span class="badge bg-<?= $regionInfo['source'] === 'platform' ? 'blue' : 'purple' ?>-lt ms-1">
                            <?= htmlspecialchars($regionInfo['source']) ?>
                        </span>
                    </small>
                </div>
            </div>

            <div class="card-body">
                <?php if (!empty($regionInfo['description'])): ?>
                    <p class="text-secondary small mb-3"><?= htmlspecialchars($regionInfo['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($regionPlacements)): ?>
                <div class="list-group list-group-flush mb-3" data-region="<?= htmlspecialchars($regionId) ?>">
                    <?php foreach ($regionPlacements as $placement): ?>
                        <?php
                        $blockInfo = $blocks[$placement->block_key] ?? null;
                        $hasOptions = !empty($blockInfo['options']);
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center"
                             data-placement-id="<?= (int) $placement->id ?>">
                            <div>
                                <i class="ti ti-grip-vertical text-secondary me-1 drag-handle" style="cursor:grab"></i>
                                <?php if ($blockInfo): ?>
                                    <?= htmlspecialchars($blockInfo['label'] ?? $placement->block_key) ?>
                                <?php else: ?>
                                    <span class="text-secondary"><?= htmlspecialchars($placement->block_key) ?></span>
                                    <small class="text-danger">(unregistered)</small>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-1">
                                <?php if ($hasOptions): ?>
                                <button type="button" class="btn btn-sm btn-ghost-primary btn-icon"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-block-<?= (int) $placement->id ?>">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <form method="POST" action="/admin/themes/regions/remove">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="placement_id" value="<?= (int) $placement->id ?>">
                                    <button type="submit" class="btn btn-sm btn-ghost-danger" title="Remove">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-secondary small mb-3">No blocks placed in this region.</p>
                <?php endif; ?>

                <?php if (!empty($blocks)): ?>
                <form method="POST" action="/admin/themes/regions/place">
                    <?= csrf_field() ?>
                    <input type="hidden" name="region_id" value="<?= htmlspecialchars($regionId) ?>">
                    <div class="input-group">
                        <select name="block_key" class="form-select form-select-sm"
                                aria-label="Block to add to the <?= htmlspecialchars($regionInfo['label']) ?> region">
                            <option value="">Add block...</option>
                            <?php
                            foreach ($blocks as $bKey => $bInfo):
                            ?>
                            <option value="<?= htmlspecialchars($bKey) ?>"><?= htmlspecialchars($bInfo['label'] ?? $bKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary" title="Add block">
                            <i class="ti ti-device-floppy"></i>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (empty($regions)): ?>
<div class="card">
    <div class="card-body text-center text-secondary py-4">
        No regions available. Activate a theme to see its regions.
    </div>
</div>
<?php endif; ?>

<!-- Block edit modals -->
<?php foreach ($placements as $regionPlacements): ?>
    <?php foreach ($regionPlacements as $placement): ?>
        <?php
        $blockInfo = $blocks[$placement->block_key] ?? null;
        $optionDefs = $blockInfo['options'] ?? [];
        if (empty($optionDefs)) {
            continue;
        }
        $vals = $savedValues[(int) $placement->id] ?? [];
        $modalId = 'modal-block-' . (int) $placement->id;
        ?>
        <div class="modal modal-blur fade" id="<?= $modalId ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="/admin/themes/regions/values">
                        <?= csrf_field() ?>
                        <input type="hidden" name="placement_id" value="<?= (int) $placement->id ?>">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= htmlspecialchars($blockInfo['label'] ?? $placement->block_key) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <?php foreach ($optionDefs as $fieldKey => $fieldDef): ?>
                                <?php $fieldType = $fieldDef['type'] ?? 'input'; ?>
                                <?php $blockFieldId = 'block-option-' . (int) $placement->id . '-' . preg_replace('/[^a-z0-9_-]/i', '-', $fieldKey); ?>

                                <?php if ($fieldType === 'repeater'): ?>
                                    <?php
                                    $repeaterFields = is_array($fieldDef['fields'] ?? null) ? $fieldDef['fields'] : [];
                                    $repeaterRows = is_array($vals[$fieldKey] ?? null) ? $vals[$fieldKey] : [];
                                    $repeaterRows = array_filter(
                                        $repeaterRows,
                                        static fn($rowKey): bool => (string) $rowKey !== '__INDEX__',
                                        ARRAY_FILTER_USE_KEY
                                    );
                                    $inputPrefix = 'values[' . $fieldKey . ']';
                                    $idPrefix = $blockFieldId;
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <div class="repeater-group"
                                             data-input-prefix="<?= htmlspecialchars($inputPrefix) ?>"
                                             data-id-prefix="<?= htmlspecialchars($idPrefix) ?>">
                                            <?php foreach ($repeaterRows as $rowIndex => $row): ?>
                                                <?php
                                                $row = is_array($row) ? $row : [];
                                                $rowIndex = (string) $rowIndex;
                                                $isTemplate = false;
                                                include __DIR__ . '/_repeater_row.php';
                                                ?>
                                            <?php endforeach; ?>
                                            <?php
                                            $row = [];
                                            $rowIndex = '__INDEX__';
                                            $isTemplate = true;
                                            include __DIR__ . '/_repeater_row.php';
                                            ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add>
                                            <i class="ti ti-plus me-1"></i>Add
                                        </button>
                                    </div>

                                <?php elseif ($fieldType === 'textarea'): ?>
                                    <?php $textareaId = 'block-option-' . (int) $placement->id . '-' . preg_replace('/[^a-z0-9_-]/i', '-', $fieldKey); ?>
                                    <?php if (($fieldDef['wysiwyg'] ?? true) !== false): ?>
                                    <?php $wysiwygSelectors[] = '#' . $textareaId; ?>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <textarea id="<?= htmlspecialchars($textareaId) ?>" class="form-control" rows="5"
                                                  name="values[<?= htmlspecialchars($fieldKey) ?>]"><?= htmlspecialchars($vals[$fieldKey] ?? $fieldDef['default'] ?? '') ?></textarea>
                                    </div>

                                <?php elseif ($fieldType === 'toggle'): ?>
                                    <?php $toggleChecked = !empty($vals[$fieldKey] ?? $fieldDef['default'] ?? false); ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <input type="hidden" name="values[<?= htmlspecialchars($fieldKey) ?>]" value="0">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                   name="values[<?= htmlspecialchars($fieldKey) ?>]" value="1"
                                                   <?= $toggleChecked ? 'checked' : '' ?>>
                                        </label>
                                    </div>

                                <?php elseif ($fieldType === 'select'): ?>
                                    <?php $choices = is_array($fieldDef['choices'] ?? null) ? $fieldDef['choices'] : [];
                                    $current = (string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? ''); ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <select class="form-select" id="<?= htmlspecialchars($blockFieldId) ?>"
                                                name="values[<?= htmlspecialchars($fieldKey) ?>]">
                                            <?php foreach ($choices as $choiceValue => $choiceLabel): ?>
                                                <option value="<?= htmlspecialchars((string) $choiceValue) ?>"
                                                        <?= $current === (string) $choiceValue ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $choiceLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                <?php elseif ($fieldType === 'color'): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <input type="color" class="form-control form-control-color"
                                               id="<?= htmlspecialchars($blockFieldId) ?>"
                                               name="values[<?= htmlspecialchars($fieldKey) ?>]"
                                               value="<?= htmlspecialchars((string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? '')) ?>">
                                    </div>

                                <?php elseif ($fieldType === 'number'): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <input type="number" class="form-control"
                                               id="<?= htmlspecialchars($blockFieldId) ?>"
                                               name="values[<?= htmlspecialchars($fieldKey) ?>]"
                                               value="<?= htmlspecialchars((string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? '')) ?>"
                                               <?php if (isset($fieldDef['min'])): ?>min="<?= htmlspecialchars((string) $fieldDef['min']) ?>"<?php endif; ?>
                                               <?php if (isset($fieldDef['max'])): ?>max="<?= htmlspecialchars((string) $fieldDef['max']) ?>"<?php endif; ?>
                                               <?php if (isset($fieldDef['step'])): ?>step="<?= htmlspecialchars((string) $fieldDef['step']) ?>"<?php endif; ?>>
                                    </div>

                                <?php elseif ($fieldType === 'range'): ?>
                                    <?php $rangeOutputId = $blockFieldId . '-output'; ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="range" class="form-range"
                                                   id="<?= htmlspecialchars($blockFieldId) ?>"
                                                   name="values[<?= htmlspecialchars($fieldKey) ?>]"
                                                   value="<?= htmlspecialchars((string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? '')) ?>"
                                                   min="<?= htmlspecialchars((string) ($fieldDef['min'] ?? 0)) ?>"
                                                   max="<?= htmlspecialchars((string) ($fieldDef['max'] ?? 100)) ?>"
                                                   step="<?= htmlspecialchars((string) ($fieldDef['step'] ?? 1)) ?>"
                                                   data-range-output="<?= htmlspecialchars($rangeOutputId) ?>">
                                            <output id="<?= htmlspecialchars($rangeOutputId) ?>" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars((string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? '')) ?></output>
                                        </div>
                                    </div>

                                <?php elseif ($fieldType === 'font'): ?>
                                    <?php
                                    $fontStack = (string) ($vals[$fieldKey] ?? $fieldDef['default'] ?? '');
                                    $fontChoices = $fontChoices ?? [];
                                    $choices = is_array($fieldDef['choices'] ?? null) ? $fieldDef['choices'] : $fontChoices;
                                    if ($choices === []) {
                                        $choices = $fontChoices;
                                    }
                                    if ($fontStack !== '' && !in_array($fontStack, $choices, true)) {
                                        $choices = ['Custom (' . $fontStack . ')' => $fontStack] + $choices;
                                    }
                                    $fontPreviewId = $blockFieldId . '-preview';
                                    $fontPreviewText = is_string($fieldDef['preview'] ?? null)
                                        ? $fieldDef['preview']
                                        : 'The quick brown fox jumps over the lazy dog.';
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="<?= htmlspecialchars($blockFieldId) ?>"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <select class="form-select" id="<?= htmlspecialchars($blockFieldId) ?>"
                                                name="values[<?= htmlspecialchars($fieldKey) ?>]"
                                                data-font-preview="<?= htmlspecialchars($fontPreviewId) ?>">
                                            <?php foreach ($choices as $fontLabel => $stack): ?>
                                                <option value="<?= htmlspecialchars((string) $stack) ?>"
                                                        style="font-family: <?= htmlspecialchars((string) $stack) ?>"
                                                        <?= $fontStack === (string) $stack ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $fontLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="border rounded p-2 mt-2 mb-0" id="<?= htmlspecialchars($fontPreviewId) ?>"
                                           style="font-family: <?= htmlspecialchars($fontStack) ?>">
                                            <?= htmlspecialchars($fontPreviewText) ?>
                                        </p>
                                    </div>

                                <?php else: ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= htmlspecialchars($fieldDef['label'] ?? $fieldKey) ?></label>
                                        <input type="text" class="form-control"
                                               name="values[<?= htmlspecialchars($fieldKey) ?>]"
                                               value="<?= htmlspecialchars($vals[$fieldKey] ?? $fieldDef['default'] ?? '') ?>">
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($fieldDef['help'])): ?>
                                    <span class="form-hint d-block mb-3"><?= htmlspecialchars((string) $fieldDef['help']) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
<?php endforeach; ?>
<?php endforeach; ?>

<?php foreach (array_unique($wysiwygSelectors) as $selector): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.querySelector('<?= $selector ?>');
    if (el && typeof Jodit !== 'undefined') {
        new Jodit(el, { height: 300, disablePlugins: 'beautify,ace' });
    }
});
</script>
<?php endforeach; ?>

<?php include __DIR__ . '/_repeater_script.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Sortable reordering via drag-and-drop
    document.querySelectorAll('[data-region]').forEach(function(list) {
        var regionId = list.dataset.region;
        var dragEl = null;
        var currentTarget = null;
        var insertBefore = false;

        function clearIndicator() {
            if (currentTarget) {
                currentTarget.classList.remove('drag-over-above', 'drag-over-below');
            }
        }

        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            var target = e.target.closest('[data-placement-id]');
            if (!target || target === dragEl) return;

            clearIndicator();

            var rect = target.getBoundingClientRect();
            var midY = rect.top + rect.height / 2;
            insertBefore = (e.clientY < midY);

            currentTarget = target;
            target.classList.add(insertBefore ? 'drag-over-above' : 'drag-over-below');
        });

        list.addEventListener('drop', function(e) {
            e.preventDefault();
            clearIndicator();
            if (dragEl && currentTarget && currentTarget !== dragEl) {
                if (insertBefore) {
                    list.insertBefore(dragEl, currentTarget);
                } else {
                    list.insertBefore(dragEl, currentTarget.nextSibling);
                }
                submitReorder(list, regionId);
            }
            currentTarget = null;
        });

        list.addEventListener('dragend', function() {
            clearIndicator();
            currentTarget = null;
            if (dragEl) {
                dragEl.classList.remove('opacity-50');
                dragEl = null;
            }
        });

        list.querySelectorAll('[data-placement-id]').forEach(function(item) {
            item.draggable = true;

            item.addEventListener('dragstart', function(e) {
                dragEl = this;
                this.classList.add('opacity-50');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.placementId);
            });
        });
    });

    function submitReorder(list, regionId) {
        var ids = Array.from(list.querySelectorAll('[data-placement-id]'))
            .map(function(el) { return el.dataset.placementId; });

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/themes/regions/reorder';
        form.style.display = 'none';

        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            csrfInput.value = csrf.content;
            form.appendChild(csrfInput);
        }

        var regionInput = document.createElement('input');
        regionInput.type = 'hidden';
        regionInput.name = 'region_id';
        regionInput.value = regionId;
        form.appendChild(regionInput);

        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'placement_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
});
</script>
