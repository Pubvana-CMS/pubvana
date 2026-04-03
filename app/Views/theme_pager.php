<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Theme-aware pagination template.
 *
 * Reads cls_pager_* classes from the active theme's css_class_mapping.
 * Defaults to framework-agnostic pv-* classes when no theme overrides exist.
 *
 * @var PagerRenderer $pager
 */

$cls = service('theme')->getPaginationClasses();
$pager->setSurroundCount(2);
?>

<ul class="<?= esc($cls['cls_pager_list']) ?>">
    <?php if ($pager->hasPrevious()): ?>
    <li class="<?= esc($cls['cls_pager_item']) ?>">
        <a class="<?= esc($cls['cls_pager_link']) ?>" href="<?= $pager->getFirst() ?>" aria-label="First">&laquo;</a>
    </li>
    <li class="<?= esc($cls['cls_pager_item']) ?>">
        <a class="<?= esc($cls['cls_pager_link']) ?>" href="<?= $pager->getPrevious() ?>" aria-label="Previous">&lsaquo;</a>
    </li>
    <?php else: ?>
    <li class="<?= esc($cls['cls_pager_item']) ?> <?= esc($cls['cls_pager_disabled']) ?>">
        <span class="<?= esc($cls['cls_pager_link']) ?>">&laquo;</span>
    </li>
    <li class="<?= esc($cls['cls_pager_item']) ?> <?= esc($cls['cls_pager_disabled']) ?>">
        <span class="<?= esc($cls['cls_pager_link']) ?>">&lsaquo;</span>
    </li>
    <?php endif; ?>

    <?php foreach ($pager->links() as $link): ?>
    <li class="<?= esc($cls['cls_pager_item']) ?> <?= $link['active'] ? esc($cls['cls_pager_active']) : '' ?>">
        <a class="<?= esc($cls['cls_pager_link']) ?>" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
    </li>
    <?php endforeach; ?>

    <?php if ($pager->hasNext()): ?>
    <li class="<?= esc($cls['cls_pager_item']) ?>">
        <a class="<?= esc($cls['cls_pager_link']) ?>" href="<?= $pager->getNext() ?>" aria-label="Next">&rsaquo;</a>
    </li>
    <li class="<?= esc($cls['cls_pager_item']) ?>">
        <a class="<?= esc($cls['cls_pager_link']) ?>" href="<?= $pager->getLast() ?>" aria-label="Last">&raquo;</a>
    </li>
    <?php else: ?>
    <li class="<?= esc($cls['cls_pager_item']) ?> <?= esc($cls['cls_pager_disabled']) ?>">
        <span class="<?= esc($cls['cls_pager_link']) ?>">&rsaquo;</span>
    </li>
    <li class="<?= esc($cls['cls_pager_item']) ?> <?= esc($cls['cls_pager_disabled']) ?>">
        <span class="<?= esc($cls['cls_pager_link']) ?>">&raquo;</span>
    </li>
    <?php endif; ?>
</ul>
