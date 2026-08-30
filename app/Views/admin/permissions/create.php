<?php
/**
 * Create permission - admin form.
 *
 * @var string $pageTitle
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/permissions" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="/admin/permissions/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="alias">Alias</label>
                        <input type="text" name="alias" id="alias" class="form-control"
                               placeholder="e.g. blog.create" required>
                        <div class="form-hint">Use dot notation for organization (e.g. blog.create, users.edit).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"
                                  placeholder="What does this permission grant access to?"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Create Permission</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary">
                        After creating the permission, assign it to groups on the group edit page.
                    </p>
                    <button type="submit" class="btn btn-primary w-100">Create Permission</button>
                </div>
            </div>
        </div>
    </div>
</form>
