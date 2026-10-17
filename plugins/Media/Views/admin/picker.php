<?php
/**
 * Reusable media image picker, rendered by MediaService::picker().
 *
 * @var string $inputName    Form input name
 * @var string $currentValue Current image path
 * @var string $pickerId     Unique ID for this picker instance
 * @var string $adminBase    Admin URL base for this plugin's admin routes
 */
$hasImage = !empty($currentValue);
$previewSrc = $hasImage ? '/' . ltrim($currentValue, '/') : '';
?>
<div class="media-picker" id="<?= $pickerId ?>">
    <button type="button" class="media-picker-preview border rounded p-2 d-inline-block bg-transparent"
            style="cursor:pointer; min-width:80px; min-height:80px;"
            aria-label="<?= $hasImage ? 'Change the selected image' : 'Choose an image' ?>"
            data-bs-toggle="offcanvas" data-bs-target="#<?= $pickerId ?>-offcanvas">
        <?php if ($hasImage): ?>
            <img src="<?= htmlspecialchars($previewSrc) ?>" alt="Selected image"
                 class="rounded" style="max-width:120px; max-height:120px; object-fit:cover;">
        <?php else: ?>
            <span class="d-flex align-items-center justify-content-center text-secondary"
                  style="width:80px; height:80px;">
                <i class="ti ti-photo-plus" style="font-size:2rem;"></i>
            </span>
        <?php endif; ?>
    </button>
    <input type="hidden" name="<?= htmlspecialchars($inputName) ?>" value="<?= htmlspecialchars($currentValue) ?>">
    <?php if ($hasImage): ?>
        <button type="button" class="btn btn-ghost-danger btn-sm mt-1 media-picker-clear">
            <i class="ti ti-x"></i> Remove
        </button>
    <?php endif; ?>
</div>

<div id="<?= $pickerId ?>-offcanvas" class="offcanvas offcanvas-end" tabindex="-1" style="width:450px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Select Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <button type="button" id="<?= $pickerId ?>-upload-zone"
                class="border border-dashed rounded p-3 text-center mb-3 w-100 bg-transparent"
                style="cursor:pointer;"
                aria-label="Upload an image">
            <i class="ti ti-cloud-upload" style="font-size:1.5rem;"></i>
            <span class="d-block mt-1 small">Drop image or click to upload</span>
        </button>
        <input type="file" id="<?= $pickerId ?>-upload-input" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp">
        <div class="row row-cols-3 g-2" id="<?= $pickerId ?>-grid">
            <div class="text-center text-secondary py-4 w-100">Loading...</div>
        </div>
        <div class="text-center mt-3" id="<?= $pickerId ?>-load-more" style="display:none;">
            <button type="button" class="btn btn-outline-secondary btn-sm">Load more</button>
        </div>
    </div>
</div>

<script>
(function() {
    const picker = document.getElementById('<?= $pickerId ?>');
    const grid = document.getElementById('<?= $pickerId ?>-grid');
    const loadMoreWrap = document.getElementById('<?= $pickerId ?>-load-more');
    const offcanvasEl = document.getElementById('<?= $pickerId ?>-offcanvas');
    const hiddenInput = picker.querySelector('input[type="hidden"]');
    const preview = picker.querySelector('.media-picker-preview');
    const clearBtn = picker.querySelector('.media-picker-clear');
    let page = 1;
    let total = 0;
    let loaded = false;

    // Behave like a form control: consumers that listen for change
    // (e.g. the SEO panel's OG image field) need to see the update.
    function setHiddenValue(value) {
        hiddenInput.value = value;
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const uploadZone = document.getElementById('<?= $pickerId ?>-upload-zone');
    const uploadInput = document.getElementById('<?= $pickerId ?>-upload-input');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const adminBase = '<?= $adminBase ?>';

    function esc(value) {
        return String(value).replace(/[&<>"']/g, function(ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    uploadZone.addEventListener('click', (e) => {
        if (e.target.closest('input')) return;
        uploadInput.click();
    });
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('border-primary');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('border-primary'));
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('border-primary');
        if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
    });
    uploadInput.addEventListener('change', () => {
        if (uploadInput.files.length) uploadFile(uploadInput.files[0]);
        uploadInput.value = '';
    });

    function uploadFile(file) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_csrf_token', csrfToken);

        fetch(adminBase + '/upload/image', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.error) { alert(data.error); return; }
                page = 1;
                loaded = true;
                loadImages(1);
            })
            .catch(() => alert('Upload failed.'));
    }

    function loadImages(pg) {
        fetch(adminBase + '/json?type=image&page=' + pg)
            .then(r => r.json())
            .then(data => {
                if (pg === 1) grid.innerHTML = '';
                total = data.total;

                if (data.items.length === 0 && pg === 1) {
                    grid.innerHTML = '<div class="text-center text-secondary py-4 w-100">No images found.</div>';
                    loadMoreWrap.style.display = 'none';
                    return;
                }

                data.items.forEach(item => {
                    if (item.type !== 'image' || !item.thumb_url) return;
                    const col = document.createElement('div');
                    col.className = 'col';
                    col.innerHTML = `<div class="card card-sm media-picker-item"
                                          data-path="${esc(item.path)}"
                                          data-medium-path="${esc(item.medium_path || item.path)}"
                                          data-thumb-path="${esc(item.thumb_path || item.path)}">
                        <img src="${esc(item.thumb_url)}" class="card-img-top" loading="lazy"
                             style="max-height:90px; width:100%; object-fit:contain;"
                             alt="${esc(item.filename || '')}">
                        <div class="card-body p-2">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary media-size-choice" data-size="small">S</button>
                                <button type="button" class="btn btn-outline-secondary media-size-choice" data-size="medium">M</button>
                                <button type="button" class="btn btn-outline-secondary media-size-choice" data-size="large">L</button>
                            </div>
                        </div>
                    </div>`;
                    grid.appendChild(col);
                });

                const totalLoaded = pg * data.per_page;
                loadMoreWrap.style.display = totalLoaded < total ? '' : 'none';
            });
    }

    offcanvasEl.addEventListener('show.bs.offcanvas', function() {
        if (!loaded) {
            loadImages(1);
            loaded = true;
        }
    });

    loadMoreWrap.querySelector('button').addEventListener('click', function() {
        page++;
        loadImages(page);
    });

    grid.addEventListener('click', function(e) {
        const choice = e.target.closest('.media-size-choice');
        if (!choice) return;

        const item = choice.closest('.media-picker-item');
        if (!item) return;

        const size = choice.dataset.size;
        const path = size === 'small'
            ? (item.dataset.thumbPath || item.dataset.path)
            : (size === 'large' ? item.dataset.path : (item.dataset.mediumPath || item.dataset.path));
        setHiddenValue(path);

        preview.innerHTML = `<img src="/${esc(path)}" alt="Selected image"
            class="rounded" style="max-width:120px; max-height:120px; object-fit:cover;">`;

        if (!picker.querySelector('.media-picker-clear')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-ghost-danger btn-sm mt-1 media-picker-clear';
            btn.innerHTML = '<i class="ti ti-x"></i> Remove';
            btn.addEventListener('click', clearImage);
            preview.insertAdjacentElement('afterend', btn);
        }

        tabler.Offcanvas.getInstance(offcanvasEl)?.hide();
    });

    function clearImage() {
        setHiddenValue('');
        preview.innerHTML = `<div class="d-flex align-items-center justify-content-center text-secondary"
            style="width:80px; height:80px;">
            <i class="ti ti-photo-plus" style="font-size:2rem;"></i>
        </div>`;
        const btn = picker.querySelector('.media-picker-clear');
        if (btn) btn.remove();
    }

    if (clearBtn) clearBtn.addEventListener('click', clearImage);
})();
</script>
