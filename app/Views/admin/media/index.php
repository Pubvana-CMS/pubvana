<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.mediaLibrary') ?></h1>
</div>

<!-- Upload + Detail side by side -->
<div class="d-flex flex-nowrap mb-4" style="gap: 1rem;">
    <!-- Upload area — col-5 -->
    <div style="flex: 0 0 41.666%; max-width: 41.666%; max-height: 400px; overflow-y: auto; overflow-x: hidden;">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.mediaUpload') ?></h6>
            </div>
            <div class="card-body">
                <div id="upload-zone" class="border rounded p-4 text-center bg-light" style="border-style: dashed !important">
                    <i class="fas fa-cloud-arrow-up fa-3x text-muted mb-3"></i>
                    <p class="mb-2"><?= lang('Admin.mediaDragDrop') ?></p>
                    <input type="file" id="file-input" accept="image/*" multiple class="d-none">
                    <button class="btn btn-primary" onclick="document.getElementById('file-input').click()"><?= lang('Admin.mediaChooseFiles') ?></button>
                </div>
                <div id="upload-progress" class="mt-2 d-none">
                    <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div>
                    <p class="text-muted small mt-1"><?= lang('Admin.mediaUploading') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Details — col-7, hidden until image clicked -->
    <div id="detail-panel-wrap" style="flex: 0 0 58.333%; max-width: 58.333%; max-height: 400px; overflow-y: auto; overflow-x: hidden;">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.mediaImageDetails') ?></h6>
                <button type="button" id="detail-close" class="close"><span>&times;</span></button>
            </div>
            <div class="card-body">
                <div id="detail-content" class="d-flex align-items-start" style="visibility:hidden;">
                    <div class="mr-3 text-center" style="flex: 0 0 200px;">
                        <img id="detail-preview" src="" alt="" class="img-fluid rounded border" style="max-height:200px;object-fit:contain;">
                    </div>
                    <div class="flex-grow-1">
                        <p class="font-weight-bold text-truncate mb-2" id="detail-filename" title=""></p>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold mb-1" for="detail-alt"><?= lang('Admin.mediaAltText') ?></label>
                            <input type="text" id="detail-alt" class="form-control form-control-sm" placeholder="<?= lang('Admin.mediaAltPlaceholder') ?>">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold mb-1" for="detail-title"><?= lang('Admin.mediaTitle') ?></label>
                            <input type="text" id="detail-title" class="form-control form-control-sm" placeholder="<?= lang('Admin.mediaTitlePlaceholder') ?>">
                        </div>
                        <div class="d-flex align-items-center mt-2">
                            <button type="button" class="btn btn-sm btn-primary mr-2" id="detail-save-btn"><?= lang('Admin.save') ?></button>
                            <span id="detail-feedback" class="small text-success d-none"><?= lang('Admin.mediaSaved') ?></span>
                            <span id="detail-error" class="small text-danger d-none"></span>
                            <form method="POST" id="detail-delete-form" action="" onsubmit="return confirm('<?= lang('Admin.confirmDeleteMedia') ?>')" class="mb-0 ml-auto">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash fa-sm"></i> <?= lang('Admin.delete') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Media grid -->
<div class="row" id="media-grid">
    <?php foreach ($media as $item): ?>
    <div class="col-6 col-md-3 col-lg-2 mb-3 media-item" id="media-<?= $item->id ?>">
        <div class="card h-100 border shadow-sm media-card" data-id="<?= $item->id ?>" style="cursor:pointer;">
            <img src="<?= esc(base_url($item->path)) ?>"
                 class="card-img-top card-thumb-sm obj-cover"
                 alt="<?= esc($item->alt_text ?? $item->filename) ?>">
            <div class="card-body p-1 text-center">
                <p class="text-truncate small mb-1" title="<?= esc($item->filename) ?>"><?= esc($item->filename) ?></p>
                <p class="text-muted small"><?= round($item->size / 1024, 1) ?> KB</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($media)): ?>
    <div class="col-12"><p class="text-muted text-center py-4"><?= lang('Admin.noMediaYet') ?></p></div>
    <?php endif; ?>
</div>
<?php if (isset($pager)): ?>
<div class="mt-2"><?= $pager->links('default', 'bootstrap_full') ?></div>
<?php endif; ?>


<?php
$mediaJson = [];
foreach ($media as $item) {
    $mediaJson[$item->id] = [
        'id'       => $item->id,
        'filename' => $item->filename,
        'url'      => base_url($item->path),
        'alt_text' => $item->alt_text ?? '',
        'title'    => $item->title ?? '',
        'delete_url' => base_url('admin/media/' . $item->id . '/delete'),
    ];
}

$content = ob_get_clean(); ?>
<?php ob_start(); ?>
<script>
(function ($) {
    var mediaItems = <?= json_encode($mediaJson) ?>;
    var currentDetailId = null;

    // ---------------------------------------------------------------
    // Upload
    // ---------------------------------------------------------------
    document.getElementById('file-input').addEventListener('change', function (e) {
        var files = e.target.files;
        if (!files.length) return;

        var progress = document.getElementById('upload-progress');
        progress.classList.remove('d-none');
        var fileIndex = 0;

        function uploadNext() {
            if (fileIndex >= files.length) {
                progress.classList.add('d-none');
                return;
            }
            var file = files[fileIndex];
            fileIndex++;

            var formData = new FormData();
            formData.append('file', file);

            fetch('<?= base_url('admin/media/upload') ?>', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    mediaItems[data.id] = {
                        id:         data.id,
                        filename:   data.filename,
                        url:        data.url,
                        alt_text:   data.alt_text || '',
                        title:      data.title    || '',
                        delete_url: '<?= base_url('admin/media/') ?>' + data.id + '/delete'
                    };
                    prependToGrid(data);
                    openDetailPanel(data.id);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
                uploadNext();
            })
            .catch(function (err) {
                progress.classList.add('d-none');
                alert('Upload error: ' + err);
            });
        }

        uploadNext();
    });

    // ---------------------------------------------------------------
    // Prepend newly uploaded item to grid
    // ---------------------------------------------------------------
    function prependToGrid(data) {
        var grid = document.getElementById('media-grid');
        var empty = grid.querySelector('.text-muted.text-center');
        if (empty) empty.closest('.col-12').remove();

        var col = document.createElement('div');
        col.className = 'col-6 col-md-3 col-lg-2 mb-3 media-item';
        col.id = 'media-' + data.id;
        col.innerHTML =
            '<div class="card h-100 border shadow-sm media-card" data-id="' + data.id + '" style="cursor:pointer;">' +
            '<img src="' + escHtml(data.url || data.path) + '" class="card-img-top card-thumb-sm obj-cover" alt="' + escHtml(data.filename || '') + '">' +
            '<div class="card-body p-1 text-center">' +
            '<p class="text-truncate small mb-1" title="' + escHtml(data.filename || '') + '">' + escHtml(data.filename || '') + '</p>' +
            '</div></div>';
        grid.insertBefore(col, grid.firstChild);
        bindCardClick(col.querySelector('.media-card'));
    }

    // ---------------------------------------------------------------
    // Detail panel — open on grid item click
    // ---------------------------------------------------------------
    function openDetailPanel(id) {
        var item = mediaItems[id];
        if (!item) return;

        currentDetailId = id;
        $('#detail-preview').attr('src', item.url).attr('alt', item.alt_text || item.filename);
        $('#detail-filename').text(item.filename).attr('title', item.filename);
        $('#detail-alt').val(item.alt_text);
        $('#detail-title').val(item.title);
        $('#detail-delete-form').attr('action', item.delete_url);
        $('#detail-feedback').addClass('d-none');
        $('#detail-error').addClass('d-none');
        $('#detail-content').css('visibility', 'visible');
        // Highlight selected card
        $('.media-card').removeClass('border-primary');
        $('.media-card[data-id="' + id + '"]').addClass('border-primary');
    }

    $('#detail-close').on('click', function () {
        $('#detail-content').css('visibility', 'hidden');
        $('#detail-preview').attr('src', '').attr('alt', '');
        $('#detail-filename').text('').attr('title', '');
        $('#detail-alt').val('');
        $('#detail-title').val('');
        $('#detail-feedback').addClass('d-none');
        $('#detail-error').addClass('d-none');
        $('.media-card').removeClass('border-primary');
        currentDetailId = null;
    });

    // Save button
    $('#detail-save-btn').on('click', function () {
        if (!currentDetailId) return;
        var altText = $('#detail-alt').val();
        var title   = $('#detail-title').val();
        saveMetadata(currentDetailId, altText, title, function (ok, errMsg) {
            if (ok) {
                if (mediaItems[currentDetailId]) {
                    mediaItems[currentDetailId].alt_text = altText;
                    mediaItems[currentDetailId].title    = title;
                }
                $('#detail-feedback').removeClass('d-none');
                setTimeout(function () { $('#detail-feedback').addClass('d-none'); }, 2500);
            } else {
                $('#detail-error').text(errMsg || 'Save failed.').removeClass('d-none');
            }
        });
    });

    // ---------------------------------------------------------------
    // Shared AJAX save
    // ---------------------------------------------------------------
    function saveMetadata(id, altText, title, callback) {
        $.ajax({
            url: '<?= base_url('admin/media/update/') ?>' + id,
            method: 'POST',
            data: { alt_text: altText, title: title },
            dataType: 'json',
            success: function (data) {
                callback(data.success !== false);
            },
            error: function (xhr) {
                var msg = 'Request failed';
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
                callback(false, msg);
            }
        });
    }

    // ---------------------------------------------------------------
    // Bind click handlers
    // ---------------------------------------------------------------
    function bindCardClick(card) {
        card.addEventListener('click', function () {
            var id = parseInt(this.getAttribute('data-id'), 10);
            openDetailPanel(id);
        });
    }

    document.querySelectorAll('.media-card').forEach(bindCardClick);

    // ---------------------------------------------------------------
    // HTML escape utility
    // ---------------------------------------------------------------
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}(jQuery));
</script>
<?php $extra_scripts = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
