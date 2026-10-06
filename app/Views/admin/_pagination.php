<?php
/**
 * Windowed pagination - shared admin partial.
 *
 * Expects $pagination as returned by PaginationService::build(). It is
 * null when there is one page or fewer, in which case this renders
 * nothing. The page URLs are already built by the caller, so this
 * partial carries no URL logic and no knowledge of the page's route.
 *
 * @var array{current: int, total: int, prev_url: string|null, next_url: string|null, pages: list<array{number: int|string, url: string, active: bool, gap: bool}>}|null $pagination
 */
?>
<?php if (!empty($pagination)): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php if ($pagination['prev_url'] !== null): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($pagination['prev_url'], ENT_QUOTES, 'UTF-8') ?>">Previous</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
            <?php endif; ?>

            <?php foreach ($pagination['pages'] as $paginationItem): ?>
                <?php if ($paginationItem['gap']): ?>
                    <li class="page-item disabled"><span class="page-link"><?= htmlspecialchars((string) $paginationItem['number'], ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php else: ?>
                    <li class="page-item<?= $paginationItem['active'] ? ' active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($paginationItem['url'], ENT_QUOTES, 'UTF-8') ?>"><?= (int) $paginationItem['number'] ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($pagination['next_url'] !== null): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($pagination['next_url'], ENT_QUOTES, 'UTF-8') ?>">Next</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Next</span></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
