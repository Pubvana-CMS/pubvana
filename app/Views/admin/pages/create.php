<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.newPageTitle') ?></h1>
    <a href="<?= base_url('admin/pages') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.back') ?></a>
</div>

<form method="POST" action="<?= base_url('admin/pages/create') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="markdown">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold"><?= lang('Admin.postTitleField') ?></label>
                    <input type="text" name="title" class="form-control form-control-lg" required value="<?= esc(old('title')) ?>">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold"><?= lang('Admin.slug') ?> *</label>
                    <input type="text" name="slug" class="form-control" value="<?= esc(old('slug')) ?>" placeholder="auto-generated from title if left blank">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold d-block"><?= lang('Admin.postEditor') ?></label>
                    <div id="editor-toggle">
                        <button type="button" class="btn btn-sm mr-1 btn-outline-secondary" data-editor="html"><i class="fas fa-code"></i> <?= lang('Admin.postHtmlEditor') ?></button>
                        <button type="button" class="btn btn-sm btn-primary" data-editor="markdown"><i class="fab fa-markdown"></i> <?= lang('Admin.postMarkdown') ?></button>
                    </div>
                </div>
                <div id="editor-html" style="display:none">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc(old('content')) ?></textarea>
                </div>
                <div id="editor-md">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"></textarea>
                </div>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postSeoSection') ?></h6></div>
            <div class="card-body">
                <div class="form-group"><label><?= lang('Admin.postMetaTitle') ?></label><input type="text" name="meta_title" class="form-control" value="<?= esc(old('meta_title')) ?>"></div>
                <div class="form-group mb-0"><label><?= lang('Admin.postMetaDescription') ?></label><textarea name="meta_description" class="form-control" rows="2"><?= esc(old('meta_description')) ?></textarea></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.postPublishSection') ?></h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label><?= lang('Admin.postStatus') ?></label>
                    <select name="status" class="form-control">
                        <option value="draft" <?= old('status','draft')==='draft' ? 'selected' : '' ?>><?= lang('Admin.postStatusDraft') ?></option>
                        <option value="published" <?= old('status','draft')==='published' ? 'selected' : '' ?>><?= lang('Admin.postStatusPublished') ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= lang('Admin.save') ?></button>
            </div>
        </div>
    </div>
</div>
</form>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.css">
<script>
var mdeInit = false; var sme = null;
function switchEditor(t) {
    document.getElementById('content_type').value = t;
    document.querySelectorAll('#editor-toggle button').forEach(function(btn) {
        if (btn.dataset.editor === t) {
            btn.className = 'btn btn-sm mr-1 btn-primary';
        } else {
            btn.className = 'btn btn-sm mr-1 btn-outline-secondary';
        }
    });
    document.getElementById('editor-html').style.display = t==='html'?'block':'none';
    document.getElementById('editor-md').style.display  = t==='markdown'?'block':'none';
    if(t==='html' && !mdeInit){ var _c=document.getElementById('content-html').value; $('#content-html').summernote({height:400,toolbar:[['style',['bold','italic','underline','clear']],['para',['ul','ol','paragraph']],['insert',['link','picture','hr']],['view',['codeview','fullscreen']]]}); if(_c) $('#content-html').summernote('code',_c); mdeInit=true; }
    if(t==='markdown' && !sme){ sme=new EasyMDE({element:document.getElementById('content-md')}); }
}
document.addEventListener('DOMContentLoaded',function(){
    switchEditor('markdown');
    document.querySelectorAll('#editor-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() { switchEditor(this.dataset.editor); });
    });
    var slugEdited = false;
    var titleEl = document.querySelector('[name="title"]');
    var slugEl  = document.querySelector('[name="slug"]');
    if(slugEl.value !== '') slugEdited = true;
    slugEl.addEventListener('input', function(){ if(this.value !== '') slugEdited = true; else slugEdited = false; });
    titleEl.addEventListener('input', function(){
        if(slugEdited) return;
        var s = this.value.toLowerCase().trim()
            .replace(/&/g,'and')
            .replace(/[^a-z0-9\s-]/g,'')
            .replace(/[\s]+/g,'-')
            .replace(/-+/g,'-')
            .replace(/^-|-$/g,'');
        slugEl.value = s;
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
