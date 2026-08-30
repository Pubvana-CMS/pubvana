<?php
/**
 * Search source manager - admin page.
 *
 * Lists every registered search source (Blog, Pages, ...) with an
 * enable/disable toggle, plus the global search settings.
 *
 * @var string $pageTitle
 * @var array  $sources  Search sources keyed by contributor key, each with 'label', 'description', 'enabled'
 * @var int    $resultsPerPage
 * @var int    $minQueryLength
 */
?>

<form method="POST" action="/admin/search">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Settings</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="results_per_page">Results per page</label>
                    <input type="number" class="form-control" id="results_per_page"
                           name="results_per_page" min="1" value="<?= (int) $resultsPerPage ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="min_query_length">Minimum query length</label>
                    <input type="number" class="form-control" id="min_query_length"
                           name="min_query_length" min="1" value="<?= (int) $minQueryLength ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Search Sources</h3>
                <div class="text-secondary small">
                    Content types that can appear in site search results.
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th class="w-1">Enabled</th>
                        <th>Source</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sources)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                No search sources are registered. Content plugins register themselves as search
                                sources (e.g. Blog, Pages).
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sources as $key => $source): ?>
                            <tr>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="source_<?= htmlspecialchars($key) ?>"
                                               name="source_<?= htmlspecialchars($key) ?>"
                                               value="1"
                                               <?= $source['enabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="source_<?= htmlspecialchars($key) ?>">
                                            <span class="visually-hidden">Enable</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars((string) ($source['label'] ?? $key)) ?></div>
                                    <div class="text-secondary small"><code><?= htmlspecialchars($key) ?></code></div>
                                </td>
                                <td class="text-secondary">
                                    <?= htmlspecialchars((string) ($source['description'] ?? '')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Save Search Settings
        </button>
    </div>
</form>
