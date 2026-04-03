<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.editPageTitle') ?>: <?= esc($page->title) ?></h1>
    <a href="<?= base_url('admin/pages') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.back') ?></a>
</div>

<form method="POST" action="<?= base_url('admin/pages/' . $page->id . '/edit') ?>">
<?= csrf_field() ?>
<input type="hidden" name="content_type" id="content_type" value="<?= esc($page->content_type ?? 'html') ?>">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold"><?= lang('Admin.postTitleField') ?></label>
                    <input type="text" name="title" class="form-control form-control-lg" required value="<?= esc($page->title) ?>">
                </div>
                <div class="form-group">
                    <label>Slug <small class="text-muted">(cannot change)</small></label>
                    <input type="text" class="form-control" value="<?= esc($page->slug) ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold d-block"><?= lang('Admin.postEditor') ?></label>
                    <div id="editor-toggle">
                        <button type="button" class="btn btn-sm mr-1 <?= ($page->content_type ?? 'html') === 'html' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-editor="html"><i class="fas fa-code"></i> <?= lang('Admin.postHtmlEditor') ?></button>
                        <button type="button" class="btn btn-sm <?= ($page->content_type ?? 'html') === 'markdown' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-editor="markdown"><i class="fab fa-markdown"></i> <?= lang('Admin.postMarkdown') ?></button>
                    </div>
                </div>
                <div id="editor-html" style="<?= ($page->content_type ?? 'html') === 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content" id="content-html" class="form-control" rows="15"><?= esc($page->content) ?></textarea>
                </div>
                <div id="editor-md" style="<?= ($page->content_type ?? 'html') !== 'markdown' ? 'display:none' : '' ?>">
                    <textarea name="content_md" id="content-md" class="form-control" rows="15"><?= esc($page->content) ?></textarea>
                </div>
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
                        <option value="draft" <?= $page->status === 'draft' ? 'selected' : '' ?>><?= lang('Admin.postStatusDraft') ?></option>
                        <option value="published" <?= $page->status === 'published' ? 'selected' : '' ?>><?= lang('Admin.postStatusPublished') ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= lang('Admin.update') ?></button>
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
var mdeInit=false,sme=null;
function switchEditor(t){
    document.getElementById('content_type').value=t;
    document.querySelectorAll('#editor-toggle button').forEach(function(btn){
        if(btn.dataset.editor===t){btn.className='btn btn-sm mr-1 btn-primary';}
        else{btn.className='btn btn-sm mr-1 btn-outline-secondary';}
    });
    document.getElementById('editor-html').style.display=t==='html'?'block':'none';
    document.getElementById('editor-md').style.display=t==='markdown'?'block':'none';
    if(t==='html'&&!mdeInit){var _c=document.getElementById('content-html').value;$('#content-html').summernote({height:400,toolbar:[['style',['bold','italic','underline','clear']],['para',['ul','ol','paragraph']],['insert',['link','picture','hr']],['view',['codeview','fullscreen']]]});if(_c)$('#content-html').summernote('code',_c);mdeInit=true;}
    if(t==='markdown'&&!sme){sme=new EasyMDE({element:document.getElementById('content-md')});}
}
document.addEventListener('DOMContentLoaded',function(){
    var t=document.getElementById('content_type').value;
    switchEditor(t);
    document.querySelectorAll('#editor-toggle button').forEach(function(btn){
        btn.addEventListener('click',function(){switchEditor(this.dataset.editor);});
    });
});
</script>
HTML;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
