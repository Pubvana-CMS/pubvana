<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.newPostTitle') ?></h1>
    <a href="<?= base_url('admin/posts') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.backToPosts') ?></a>
</div>

<form method="POST" action="<?= base_url('admin/posts/create') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="markdown">

<div class="row">
    <div class="col-lg-8">

        <!-- Title -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold"><?= lang('Admin.postTitleField') ?></label>
                    <input type="text" name="title" class="form-control form-control-lg" value="<?= esc(old('title')) ?>" required>
                </div>

                <!-- Editor Toggle -->
                <div class="form-group">
                    <label class="font-weight-bold d-block"><?= lang('Admin.postEditor') ?></label>
                    <div id="editor-toggle">
                        <button type="button" class="btn btn-sm mr-1 btn-outline-secondary" data-editor="html"><i class="fas fa-code"></i> <?= lang('Admin.postHtmlEditor') ?></button>
                        <button type="button" class="btn btn-sm btn-primary" data-editor="markdown"><i class="fab fa-markdown"></i> <?= lang('Admin.postMarkdown') ?></button>
                    </div>
                </div>

                <!-- HTML Editor -->
                <div id="editor-html" style="display:none">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
                <!-- Markdown Editor -->
                <div id="editor-md">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Excerpt -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postExcerpt') ?></h6></div>
            <div class="card-body">
                <textarea name="excerpt" class="form-control" rows="3" placeholder="<?= lang('Admin.postExcerptPlaceholder') ?>"><?= esc(old('excerpt')) ?></textarea>
            </div>
        </div>

        <!-- SEO -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postSeoSection') ?></h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label><?= lang('Admin.postMetaTitle') ?></label>
                    <input type="text" name="meta_title" class="form-control" value="<?= esc(old('meta_title')) ?>">
                </div>
                <div class="form-group">
                    <label><?= lang('Admin.postMetaDescription') ?></label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= esc(old('meta_description')) ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <!-- Publish Settings -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postPublishSection') ?></h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label><?= lang('Admin.postStatus') ?></label>
                    <select name="status" class="form-control">
                        <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>><?= lang('Admin.postStatusDraft') ?></option>
                        <option value="published" <?= old('status') === 'published' ? 'selected' : '' ?>><?= lang('Admin.postStatusPublished') ?></option>
                        <option value="scheduled" <?= old('status') === 'scheduled' ? 'selected' : '' ?>><?= lang('Admin.postStatusScheduled') ?></option>
                    </select>
                </div>
                <div class="form-group" id="published-at-group" style="display:none">
                    <label><?= lang('Admin.postScheduledAt') ?></label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= esc(old('published_at')) ?>">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" value="1">
                    <label class="form-check-label" for="is_featured"><?= lang('Admin.postFeatured') ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_premium" id="is_premium" class="form-check-input" value="1">
                    <label class="form-check-label" for="is_premium"><i class="fas fa-lock fa-xs text-warning mr-1"></i><?= lang('Admin.postMembersOnly') ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="share_on_publish" id="share_on_publish" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="share_on_publish"><?= lang('Admin.postShareOnPublish') ?></label>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-3"><?= lang('Admin.postSaveBtn') ?></button>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postFeaturedImage') ?></h6></div>
            <div class="card-body">
                <input type="hidden" name="media_id" id="featured_media_id" value="">
                <div id="featured-image-preview" class="mb-2" style="<?= old('featured_image') ? '' : 'display:none' ?>">
                    <img id="featured-image-preview-img" src="<?= esc(old('featured_image') ? base_url(old('featured_image')) : '') ?>" class="img-fluid rounded" alt="" style="max-height:180px">
                </div>
                <div id="featured-image-empty" class="text-center text-muted border rounded p-3 mb-2" style="<?= old('featured_image') ? 'display:none' : '' ?>">
                    <i class="fas fa-image fa-2x mb-1"></i><br><small>No image selected</small>
                </div>
                <div class="d-flex mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="browse-featured-image">
                        <i class="fas fa-folder-open mr-1"></i>Browse Media
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger ml-1" id="remove-featured-image" style="<?= old('featured_image') ? '' : 'display:none' ?>">
                        <i class="fas fa-times mr-1"></i>Remove
                    </button>
                </div>
                <input type="text" name="featured_image" id="featured_image_url" class="form-control" placeholder="<?= lang('Admin.postFeaturedImagePlaceholder') ?>" value="<?= esc(old('featured_image')) ?>">
            </div>
        </div>

        <!-- Categories -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postCategories') ?></h6></div>
            <div class="card-body" style="max-height:200px;overflow-y:auto">
                <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input type="checkbox" name="categories[]" id="cat<?= $cat->id ?>" value="<?= $cat->id ?>" class="form-check-input">
                    <label class="form-check-label" for="cat<?= $cat->id ?>"><?= esc($cat->name) ?></label>
                </div>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <p class="text-muted small mb-0"><?= lang('Admin.noCategoriesYet') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postTags') ?></h6></div>
            <div class="card-body">
                <input type="text" name="tags_raw" class="form-control" placeholder="<?= lang('Admin.postTagsPlaceholder') ?>" value="<?= esc(old('tags_raw')) ?>">
                <small class="text-muted"><?= lang('Admin.commasSeparated') ?></small>
            </div>
        </div>

    </div>
</div>
</form>

<script>
(function() {
    var sel = document.querySelector('select[name="status"]');
    var grp = document.getElementById('published-at-group');
    function toggle() { grp.style.display = sel.value === 'scheduled' ? 'block' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.css">
<script>
var summernoteInitialized = false;
var easymdeEditor = null;

function switchEditor(type) {
    document.getElementById('content_type').value = type;
    document.querySelectorAll('#editor-toggle button').forEach(function(btn) {
        if (btn.dataset.editor === type) {
            btn.className = 'btn btn-sm mr-1 btn-primary';
        } else {
            btn.className = 'btn btn-sm mr-1 btn-outline-secondary';
        }
    });
    if (type === 'html') {
        document.getElementById('editor-html').style.display = 'block';
        document.getElementById('editor-md').style.display = 'none';
        if (!summernoteInitialized) {
            var mediaPickerBtn = function(context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="fas fa-image"/>',
                    tooltip: 'Insert Image',
                    click: function() {
                        openMediaPicker(function(media) {
                            var imgHtml = '<img src="' + media.url + '" alt="' + (media.alt_text || '') + '" title="' + (media.title || '') + '" class="img-fluid">';
                            $('#content-html').summernote('pasteHTML', imgHtml);
                        });
                    }
                });
                return button.render();
            };
            $('#content-html').summernote({
                height: 400,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'mediapicker', 'hr']],
                    ['view', ['codeview', 'fullscreen']]
                ],
                buttons: {
                    mediapicker: mediaPickerBtn
                },
                callbacks: {
                    onImageUpload: function(files) {
                        var file = files[0];
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                        $.ajax({
                            url: '<?= base_url('admin/media/upload') ?>',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(resp) {
                                if (resp.success) {
                                    var imgHtml = '<img src="' + resp.url + '" alt="' + (resp.alt_text || '') + '" title="' + (resp.title || '') + '" class="img-fluid">';
                                    $('#content-html').summernote('pasteHTML', imgHtml);
                                }
                            }
                        });
                    }
                }
            });
            summernoteInitialized = true;
        }
    } else {
        if (summernoteInitialized) {
            document.getElementById('content-html').value = $('#content-html').summernote('code');
        }
        document.getElementById('editor-html').style.display = 'none';
        document.getElementById('editor-md').style.display = 'block';
        if (!easymdeEditor) {
            easymdeEditor = new EasyMDE({
                element: document.getElementById('content-md'),
                toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', {
                    name: 'image',
                    action: function(editor) {
                        openMediaPicker(function(media) {
                            var md = '![' + (media.alt_text || media.filename) + '](' + media.url + ')';
                            editor.codemirror.replaceSelection(md);
                        });
                    },
                    className: 'fa fa-image',
                    title: 'Insert Image'
                }, '|', 'preview', 'side-by-side', 'fullscreen']
            });
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    switchEditor('markdown');
    document.querySelectorAll('#editor-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() { switchEditor(this.dataset.editor); });
    });

    // Sync active editor content into the 'content' field before form submission
    document.querySelector('form').addEventListener('submit', function() {
        var type = document.getElementById('content_type').value;
        if (type === 'markdown' && easymdeEditor) {
            document.getElementById('content-html').value = easymdeEditor.value();
        } else if (summernoteInitialized) {
            $('#content-html').val($('#content-html').summernote('code'));
        }
    });

    // Featured image picker
    document.getElementById('browse-featured-image').addEventListener('click', function() {
        openMediaPicker(function(media) {
            document.getElementById('featured_media_id').value = media.id;
            document.getElementById('featured_image_url').value = media.path;
            document.getElementById('featured-image-preview-img').src = media.url;
            document.getElementById('featured-image-preview').style.display = '';
            document.getElementById('featured-image-empty').style.display = 'none';
            document.getElementById('remove-featured-image').style.display = '';
        });
    });

    document.getElementById('remove-featured-image').addEventListener('click', function() {
        document.getElementById('featured_media_id').value = '';
        document.getElementById('featured_image_url').value = '';
        document.getElementById('featured-image-preview-img').src = '';
        document.getElementById('featured-image-preview').style.display = 'none';
        document.getElementById('featured-image-empty').style.display = '';
        this.style.display = 'none';
    });

    document.getElementById('featured_image_url').addEventListener('input', function() {
        document.getElementById('featured_media_id').value = '';
        if (this.value) {
            document.getElementById('featured-image-preview-img').src = this.value;
            document.getElementById('featured-image-preview').style.display = '';
            document.getElementById('featured-image-empty').style.display = 'none';
            document.getElementById('remove-featured-image').style.display = '';
        } else {
            document.getElementById('featured-image-preview').style.display = 'none';
            document.getElementById('featured-image-empty').style.display = '';
            document.getElementById('remove-featured-image').style.display = 'none';
        }
    });
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
