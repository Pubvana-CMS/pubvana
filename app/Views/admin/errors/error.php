<?php
/**
 * Admin error page body.
 *
 * Rendered by AdminController::renderErrorPage() inside the admin layout.
 * The layout prints the page title as the page's only <h1>, so this file
 * carries no heading of its own.
 *
 * @var int    $status  HTTP status code
 * @var string $message Headline, e.g. 'Page not found'
 */
?>
<div class="card">
    <div class="card-body text-center py-5">
        <div class="display-4 fw-bold text-secondary mb-2"><?= (int) $status ?></div>
        <p class="text-secondary mb-4"><?= htmlspecialchars($message) ?></p>
        <a href="/admin" class="btn btn-primary">Back to the dashboard</a>
    </div>
</div>
