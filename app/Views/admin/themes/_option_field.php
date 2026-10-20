<?php
/**
 * Single option field partial, included from options.php.
 *
 * @var string $inputName HTML input name attribute
 * @var string $dbKey     Key used in $saved lookup
 * @var array  $fieldDef  Field definition from pubvana.json
 * @var array  $saved     All saved values
 * @var array  $fontChoices Label => CSS font stack for the font field
 */

$savedVal  = $saved[$dbKey] ?? ($fieldDef['default'] ?? '');
$label     = htmlspecialchars($fieldDef['label'] ?? $dbKey);
$fieldType = $fieldDef['type'] ?? 'input';
$fontChoices = is_array($fontChoices ?? null) ? $fontChoices : [];

// One id per field so the visible label points at the control. The media type
// renders no labelable element of its own (the picker brings its own button),
// and a repeater has no single control, so neither label carries a `for`.
$fieldId = htmlspecialchars('theme-option-' . str_replace(['.', '_'], '-', $dbKey));
$forAttr = in_array($fieldType, ['media', 'repeater'], true) ? '' : ' for="' . $fieldId . '"';
?>
<div class="mb-3 row">
    <label class="col-sm-3 col-form-label"<?= $forAttr ?>><?= $label ?></label>
    <div class="col-sm-9">
        <?php if ($fieldType === 'toggle'): ?>
            <input type="hidden" name="<?= $inputName ?>" value="0">
            <label class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       id="<?= $fieldId ?>"
                       name="<?= $inputName ?>" value="1"
                       <?= $savedVal ? 'checked' : '' ?>>
            </label>

        <?php elseif ($fieldType === 'select'): ?>
            <select class="form-select" id="<?= $fieldId ?>" name="<?= $inputName ?>">
                <?php foreach (($fieldDef['choices'] ?? []) as $val => $lbl): ?>
                    <option value="<?= htmlspecialchars((string) $val) ?>" <?= $savedVal == $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($fieldType === 'color'): ?>
            <input type="color" class="form-control form-control-color"
                   id="<?= $fieldId ?>" name="<?= $inputName ?>" value="<?= htmlspecialchars($savedVal) ?>">

        <?php elseif ($fieldType === 'media'): ?>
            <?= $mediaPickers[$dbKey] ?? '' ?>

        <?php elseif ($fieldType === 'textarea'): ?>
            <textarea class="form-control" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                      rows="<?= (int) ($fieldDef['rows'] ?? 5) ?>"><?= htmlspecialchars((string) $savedVal) ?></textarea>

        <?php elseif ($fieldType === 'number'): ?>
            <input type="number" class="form-control" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                   value="<?= htmlspecialchars((string) $savedVal) ?>"
                   <?php if (isset($fieldDef['min'])): ?>min="<?= htmlspecialchars((string) $fieldDef['min']) ?>"<?php endif; ?>
                   <?php if (isset($fieldDef['max'])): ?>max="<?= htmlspecialchars((string) $fieldDef['max']) ?>"<?php endif; ?>
                   <?php if (isset($fieldDef['step'])): ?>step="<?= htmlspecialchars((string) $fieldDef['step']) ?>"<?php endif; ?>>

        <?php elseif ($fieldType === 'range'): ?>
            <?php $rangeOutputId = $fieldId . '-output'; ?>
            <div class="d-flex align-items-center gap-3">
                <input type="range" class="form-range" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                       value="<?= htmlspecialchars((string) $savedVal) ?>"
                       min="<?= htmlspecialchars((string) ($fieldDef['min'] ?? 0)) ?>"
                       max="<?= htmlspecialchars((string) ($fieldDef['max'] ?? 100)) ?>"
                       step="<?= htmlspecialchars((string) ($fieldDef['step'] ?? 1)) ?>"
                       data-range-output="<?= $rangeOutputId ?>">
                <output id="<?= $rangeOutputId ?>" for="<?= $fieldId ?>"><?= htmlspecialchars((string) $savedVal) ?></output>
            </div>

        <?php elseif ($fieldType === 'font'): ?>
            <?php
            $fontStack = is_string($savedVal) ? $savedVal : '';
            $choices = $fieldDef['choices'] ?? $fontChoices;
            /** @var array<string, string> $choices */
            $choices = is_array($choices) && $choices !== [] ? $choices : $fontChoices;
            if ($fontStack !== '' && !in_array($fontStack, $choices, true)) {
                $choices = ['Custom (' . $fontStack . ')' => $fontStack] + $choices;
            }
            $fontPreviewId = $fieldId . '-preview';
            $fontPreviewText = is_string($fieldDef['preview'] ?? null)
                ? $fieldDef['preview']
                : 'The quick brown fox jumps over the lazy dog.';
            ?>
            <select class="form-select" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                    data-font-preview="<?= $fontPreviewId ?>">
                <?php foreach ($choices as $fontLabel => $stack): ?>
                    <option value="<?= htmlspecialchars((string) $stack) ?>"
                            style="font-family: <?= htmlspecialchars((string) $stack) ?>"
                            <?= $fontStack === (string) $stack ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $fontLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="border rounded p-2 mt-2 mb-0" id="<?= $fontPreviewId ?>"
               style="font-family: <?= htmlspecialchars($fontStack) ?>">
                <?= htmlspecialchars($fontPreviewText) ?>
            </p>

        <?php elseif ($fieldType === 'repeater'): ?>
            <?php
            $repeaterFields = is_array($fieldDef['fields'] ?? null) ? $fieldDef['fields'] : [];
            $repeaterRows = is_array($savedVal) ? $savedVal : [];
            $repeaterRows = array_filter(
                $repeaterRows,
                static fn($rowKey): bool => (string) $rowKey !== '__INDEX__',
                ARRAY_FILTER_USE_KEY
            );
            $inputPrefix = $inputName;
            $idPrefix = $fieldId;
            ?>
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

        <?php else: ?>
            <input type="text" class="form-control" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                   value="<?= htmlspecialchars((string) $savedVal) ?>">
        <?php endif; ?>

        <?php if (!empty($fieldDef['help'])): ?>
            <span class="form-hint"><?= htmlspecialchars($fieldDef['help']) ?></span>
        <?php endif; ?>
    </div>
</div>
