<?php
/**
 * Single option field partial, included from options.php.
 *
 * @var string $inputName HTML input name attribute
 * @var string $dbKey     Key used in $saved lookup
 * @var array  $fieldDef  Field definition from pubvana.json
 * @var array  $saved     All saved values
 */

$savedVal  = $saved[$dbKey] ?? ($fieldDef['default'] ?? '');
$label     = htmlspecialchars($fieldDef['label'] ?? $dbKey);
$fieldType = $fieldDef['type'] ?? 'input';

// One id per field so the visible label points at the control. The media type
// renders no labelable element of its own (the picker brings its own button),
// so its label carries no `for`.
$fieldId = htmlspecialchars('theme-option-' . str_replace(['.', '_'], '-', $dbKey));
$forAttr = $fieldType === 'media' ? '' : ' for="' . $fieldId . '"';
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

        <?php else: ?>
            <input type="text" class="form-control" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                   value="<?= htmlspecialchars($savedVal) ?>">
        <?php endif; ?>

        <?php if (!empty($fieldDef['help'])): ?>
            <span class="form-hint"><?= htmlspecialchars($fieldDef['help']) ?></span>
        <?php endif; ?>
    </div>
</div>
