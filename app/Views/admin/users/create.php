<?php
/**
 * Create user - admin form.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\AuthGroup[] $groups
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/users" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="/admin/users/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Group</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="group">Assign to Group</label>
                        <select name="group" id="group" class="form-select">
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= htmlspecialchars($g->alias) ?>" <?= $g->alias === 'user' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g->title) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create User</button>
                </div>
            </div>
        </div>
    </div>
</form>
