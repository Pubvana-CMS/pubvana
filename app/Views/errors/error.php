<?php
/**
 * Error page
 *
 * @var int $status
 * @var string $message
 */
?>
<div class="page page-center">
    <div class="container-xl text-center py-5">
        <h1 class="error-title"><?= $status ?></h1>
        <p class="error-subtitle"><?= htmlspecialchars($message ?? 'Something went wrong.') ?></p>
        <div class="mt-4">
            <a href="/admin" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>
