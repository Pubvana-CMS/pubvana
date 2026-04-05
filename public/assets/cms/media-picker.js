/**
 * Pubvana Media Picker
 * Global API: openMediaPicker(callback)
 * callback receives: { id, url, path, alt_text, title, filename }
 */
(function ($) {
    'use strict';

    // ── state ─────────────────────────────────────────────────────────────────
    var _callback       = null;   // current caller's callback
    var _currentPage    = 1;
    var _hasMore        = false;
    var _selectedMedia  = null;   // the item currently shown in the detail sidebar
    var _uploadedMedia  = null;   // the most recently uploaded item (pre-use state)
    var _modal          = null;   // cached jQuery modal object

    // ── helpers ───────────────────────────────────────────────────────────────

    function modal() {
        if (!_modal) { _modal = $('#mediaPickerModal'); }
        return _modal;
    }

    function baseUrl() {
        return modal().data('base-url') || '';
    }

    function csrfName() {
        return modal().data('csrf-name') || 'csrf_test_name';
    }

    function csrfHash() {
        return modal().data('csrf-hash') || '';
    }

    /** Rebuild FormData with a fresh CSRF hash from the modal's data attr.
     *  After each POST CI4 rotates the token, so we re-read it from the meta tag. */
    function freshCsrf() {
        // CI4 renders <meta name="csrf-token-name" content="csrf-hash"> in <head>
        var name = csrfName();
        var metaEl = document.querySelector('meta[name="' + name + '"]');
        return metaEl ? metaEl.getAttribute('content') : csrfHash();
    }

    function url(path) {
        var base = baseUrl().replace(/\/$/, '');
        return base + '/' + path.replace(/^\//, '');
    }

    // ── grid ──────────────────────────────────────────────────────────────────

    function loadGrid(page) {
        var $grid    = $('#mp-grid');
        var $loading = $('#mp-grid-loading');
        var $empty   = $('#mp-grid-empty');
        var $lmWrap  = $('#mp-load-more-wrap');

        if (page === 1) {
            $grid.empty();
            $loading.removeClass('d-none');
            $empty.addClass('d-none');
            $lmWrap.hide();
        }

        $.ajax({
            url:      url('admin/media/json'),
            method:   'GET',
            data:     { page: page },
            dataType: 'json',
            success: function (res) {
                $loading.addClass('d-none');

                var items = res.data || res;   // support {data:[...]} or plain array
                _hasMore  = !!(res.pages && res.page < res.pages);

                if (page === 1 && items.length === 0) {
                    $empty.removeClass('d-none');
                    return;
                }

                $.each(items, function (i, item) {
                    $grid.append(buildCard(item));
                });

                if (_hasMore) {
                    $lmWrap.show();
                } else {
                    $lmWrap.hide();
                }
            },
            error: function () {
                $loading.addClass('d-none');
                $empty.removeClass('d-none');
            }
        });
    }

    function buildCard(item) {
        var thumbSrc = item.thumb_path
            ? url(item.thumb_path)
            : url(item.path);

        var $col = $('<div class="col-4 col-md-3 col-lg-2 px-1 mb-2">');
        var $card = $('<div class="card border shadow-sm mp-media-card" style="cursor:pointer;overflow:hidden;">')
            .data('media', item)
            .attr('title', item.filename);

        var $img = $('<img class="card-img-top" alt="">')
            .attr('src', thumbSrc)
            .css({ height: '80px', objectFit: 'cover' });

        $card.append($img);
        $col.append($card);
        return $col;
    }

    // ── detail sidebar ────────────────────────────────────────────────────────

    function showDetail(item) {
        _selectedMedia = item;

        var $detail = $('#mp-detail');

        // Adjust left column width to make room for sidebar
        $('#mp-left-col').css('overflow', 'hidden');

        $('#mp-detail-img')
            .attr('src', url(item.path))
            .attr('alt', item.alt_text || '');
        $('#mp-detail-filename').text(item.filename);
        $('#mp-detail-dims').text('');
        $('#mp-detail-alt').val(item.alt_text || '');
        $('#mp-detail-title').val(item.title || '');

        $detail.removeClass('d-none').addClass('d-flex flex-column');

        // Highlight selected card
        $('.mp-media-card').removeClass('border-primary');
        $('.mp-media-card').filter(function () {
            return $(this).data('media') && $(this).data('media').id === item.id;
        }).addClass('border-primary');
    }

    function saveDetail(id, data) {
        var postData = {};
        postData[csrfName()] = freshCsrf();
        postData.alt_text    = data.alt_text;
        postData.title       = data.title;

        $.post(url('admin/media/update/' + id), postData);
    }

    // ── upload ────────────────────────────────────────────────────────────────

    function doUpload(file) {
        var $zone     = $('#mp-upload-zone');
        var $progress = $('#mp-upload-progress');
        var $meta     = $('#mp-upload-meta');

        $zone.addClass('d-none');
        $meta.addClass('d-none');
        $progress.removeClass('d-none');

        var fd = new FormData();
        fd.append('file', file);
        fd.append(csrfName(), freshCsrf());

        $.ajax({
            url:         url('admin/media/upload'),
            method:      'POST',
            data:        fd,
            processData: false,
            contentType: false,
            dataType:    'json',
            success: function (res) {
                $progress.addClass('d-none');
                $zone.removeClass('d-none');

                if (res.success) {
                    // Refresh CSRF from response header/meta if CI4 rotated it
                    _uploadedMedia = res;

                    // Show inline metadata row
                    var thumbSrc = res.thumb_path ? url(res.thumb_path) : url(res.path);
                    $('#mp-upload-thumb').attr('src', thumbSrc);
                    $('#mp-upload-alt').val(res.alt_text || '');
                    $('#mp-upload-title').val(res.title || '');
                    $meta.removeClass('d-none');

                    // Prepend the new item to the grid
                    $('#mp-grid').prepend(buildCard(res));

                    // Update CSRF meta tag if CI4 provides new hash in response
                    if (res.csrf_hash) {
                        var metaEl = document.querySelector('meta[name="' + csrfName() + '"]');
                        if (metaEl) { metaEl.setAttribute('content', res.csrf_hash); }
                    }
                } else {
                    alert('Upload failed: ' + (res.error || 'Unknown error'));
                }
            },
            error: function () {
                $progress.addClass('d-none');
                $zone.removeClass('d-none');
                alert('Upload failed. Please try again.');
            }
        });
    }

    // ── "use" actions ─────────────────────────────────────────────────────────

    function useMedia(item) {
        if (typeof _callback === 'function') {
            _callback({
                id:       item.id,
                url:      url(item.path),
                path:     item.path,
                alt_text: item.alt_text || '',
                title:    item.title    || '',
                filename: item.filename
            });
        }
        modal().modal('hide');
    }

    // ── reset state ───────────────────────────────────────────────────────────

    function resetState() {
        _currentPage   = 1;
        _selectedMedia = null;
        _uploadedMedia = null;

        $('#mp-upload-meta').addClass('d-none');
        $('#mp-upload-progress').addClass('d-none');
        $('#mp-upload-zone').removeClass('d-none');
        $('#mp-upload-alt').val('');
        $('#mp-upload-title').val('');

        $('#mp-detail').addClass('d-none').removeClass('d-flex flex-column');
        $('#mp-detail-img').attr('src', '');
        $('#mp-detail-alt').val('');
        $('#mp-detail-title').val('');

        $('#mp-file-input').val('');

        // Refresh CSRF hash in data attr from live meta tag
        var liveHash = freshCsrf();
        modal().data('csrf-hash', liveHash);
    }

    // ── event wiring (runs once on DOM ready) ─────────────────────────────────

    $(function () {

        // Open: reset + load grid
        $(document).on('show.bs.modal', '#mediaPickerModal', function () {
            resetState();
            loadGrid(1);
        });

        // Upload zone click → trigger file input
        $(document).on('click', '#mp-upload-zone', function (e) {
            if (e.target.id === 'mp-file-input') return;
            $('#mp-file-input').click();
        });

        // File input change
        $(document).on('change', '#mp-file-input', function () {
            var file = this.files[0];
            if (file) { doUpload(file); }
        });

        // Drag-and-drop on upload zone
        $(document).on('dragover dragenter', '#mp-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-primary bg-white');
        });

        $(document).on('dragleave', '#mp-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-primary bg-white');
        });

        $(document).on('drop', '#mp-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-primary bg-white');
            var file = e.originalEvent.dataTransfer.files[0];
            if (file) { doUpload(file); }
        });

        // Grid card click → show detail
        $(document).on('click', '#mp-grid .mp-media-card', function () {
            var item = $(this).data('media');
            if (item) { showDetail(item); }
        });

        // Detail sidebar: alt text / title auto-save on blur
        $(document).on('blur', '#mp-detail-alt, #mp-detail-title', function () {
            if (!_selectedMedia) { return; }
            var altVal   = $('#mp-detail-alt').val();
            var titleVal = $('#mp-detail-title').val();
            // Update local state
            _selectedMedia.alt_text = altVal;
            _selectedMedia.title    = titleVal;
            saveDetail(_selectedMedia.id, { alt_text: altVal, title: titleVal });
        });

        // Upload metadata: save on blur
        $(document).on('blur', '#mp-upload-alt, #mp-upload-title', function () {
            if (!_uploadedMedia) { return; }
            var altVal   = $('#mp-upload-alt').val();
            var titleVal = $('#mp-upload-title').val();
            _uploadedMedia.alt_text = altVal;
            _uploadedMedia.title    = titleVal;
            saveDetail(_uploadedMedia.id, { alt_text: altVal, title: titleVal });
        });

        // "Use This Image" button (post-upload inline)
        $(document).on('click', '#mp-use-uploaded', function () {
            if (!_uploadedMedia) { return; }
            // Capture current field values before using
            _uploadedMedia.alt_text = $('#mp-upload-alt').val();
            _uploadedMedia.title    = $('#mp-upload-title').val();
            saveDetail(_uploadedMedia.id, {
                alt_text: _uploadedMedia.alt_text,
                title:    _uploadedMedia.title
            });
            useMedia(_uploadedMedia);
        });

        // Dismiss uploaded metadata row
        $(document).on('click', '#mp-upload-dismiss', function () {
            _uploadedMedia = null;
            $('#mp-upload-meta').addClass('d-none');
        });

        // "Use Image" button in detail sidebar
        $(document).on('click', '#mp-use-btn', function () {
            if (!_selectedMedia) { return; }
            // Capture latest field values
            _selectedMedia.alt_text = $('#mp-detail-alt').val();
            _selectedMedia.title    = $('#mp-detail-title').val();
            saveDetail(_selectedMedia.id, {
                alt_text: _selectedMedia.alt_text,
                title:    _selectedMedia.title
            });
            useMedia(_selectedMedia);
        });

        // Load More button
        $(document).on('click', '#mp-load-more', function () {
            _currentPage++;
            loadGrid(_currentPage);
        });

    });

    // ── public API ────────────────────────────────────────────────────────────

    /**
     * Open the media picker modal.
     * @param {function} callback - Called with a media object on selection.
     */
    window.openMediaPicker = function (callback) {
        _callback = typeof callback === 'function' ? callback : null;
        modal().modal('show');
    };

}(jQuery));
