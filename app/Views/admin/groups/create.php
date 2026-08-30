<?php
/**
 * Create group - admin form.
 *
 * @var string $pageTitle
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/groups" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="/admin/groups/store">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="alias">Alias</label>
                        <input type="text" name="alias" id="alias" class="form-control"
                               placeholder="e.g. editors" required>
                        <div class="form-hint">Lowercase, no spaces. Used internally as a unique identifier.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control"
                               placeholder="e.g. Editors" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"
                                  placeholder="What can members of this group do?"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Create Group</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary">
                        After creating the group, you can assign permissions on the edit page.
                    </p>
                    <button type="submit" class="btn btn-primary w-100">Create Group</button>
                </div>
            </div>
        </div>
    </div>
</form>
