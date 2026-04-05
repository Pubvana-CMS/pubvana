<!-- ===== Media Picker Modal ===== -->
<!-- Loaded once in the admin layout; opened via openMediaPicker(callback) -->
<div class="modal fade" id="mediaPickerModal" tabindex="-1" role="dialog"
     data-base-url="<?= base_url() ?>"
     data-csrf-name="<?= csrf_token() ?>"
     data-csrf-hash="<?= csrf_hash() ?>">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header py-3">
                <h5 class="modal-title"><i class="fas fa-images mr-2 text-primary"></i>Media Library</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3">
                <div class="row no-gutters">

                    <!-- Left: upload zone + grid -->
                    <div class="col" id="mp-left-col">

                        <!-- Upload Zone -->
                        <div id="mp-upload-zone"
                             class="border rounded p-3 mb-3 text-center bg-light"
                             style="border-style: dashed !important; cursor: pointer;">
                            <i class="fas fa-cloud-arrow-up fa-2x text-muted mb-2"></i>
                            <p class="mb-1 text-muted small">Drag &amp; drop image here or click to browse</p>
                            <input type="file" id="mp-file-input" accept="image/*" class="d-none">
                        </div>

                        <!-- Post-upload inline metadata row (hidden until an upload completes) -->
                        <div id="mp-upload-meta" class="d-none mb-3">
                            <div class="d-flex align-items-start">
                                <img id="mp-upload-thumb" src="" alt="" class="rounded mr-3"
                                     style="width:80px;height:80px;object-fit:cover;flex-shrink:0;">
                                <div class="flex-grow-1">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold mb-1">Alt Text</label>
                                        <input type="text" id="mp-upload-alt"
                                               class="form-control form-control-sm"
                                               placeholder="Describe the image for accessibility">
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold mb-1">Title</label>
                                        <input type="text" id="mp-upload-title"
                                               class="form-control form-control-sm"
                                               placeholder="Image title (optional)">
                                    </div>
                                    <button type="button" id="mp-use-uploaded" class="btn btn-sm btn-primary">
                                        Use This Image
                                    </button>
                                    <button type="button" id="mp-upload-dismiss" class="btn btn-sm btn-link text-muted">
                                        Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Upload progress indicator -->
                        <div id="mp-upload-progress" class="d-none mb-3">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     style="width:100%"></div>
                            </div>
                            <p class="text-muted small mt-1 mb-0">Uploading&hellip;</p>
                        </div>

                        <!-- Media Grid -->
                        <div class="row no-gutters mx-n1" id="mp-grid">
                            <!-- Populated by JS -->
                        </div>

                        <!-- Load More -->
                        <div class="text-center mt-3" id="mp-load-more-wrap" style="display:none !important;">
                            <button type="button" id="mp-load-more" class="btn btn-sm btn-outline-secondary">
                                Load More
                            </button>
                        </div>

                        <!-- Empty / loading states -->
                        <div id="mp-grid-loading" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p class="mb-0 small">Loading media&hellip;</p>
                        </div>
                        <div id="mp-grid-empty" class="text-center py-4 text-muted d-none">
                            <i class="fas fa-photo-film fa-2x mb-2"></i>
                            <p class="mb-0 small">No media uploaded yet.</p>
                        </div>

                    </div><!-- /mp-left-col -->

                    <!-- Right: detail sidebar (hidden until an image is clicked) -->
                    <div id="mp-detail" class="d-none border-left ml-3 pl-3" style="width:220px;flex-shrink:0;">
                        <img id="mp-detail-img" src="" alt=""
                             class="img-fluid rounded mb-2"
                             style="max-height:160px;width:100%;object-fit:contain;background:#f8f9fc;">
                        <p id="mp-detail-filename" class="small font-weight-bold text-truncate mb-1"></p>
                        <p id="mp-detail-dims" class="small text-muted mb-2"></p>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold mb-1">Alt Text</label>
                            <input type="text" id="mp-detail-alt"
                                   class="form-control form-control-sm"
                                   placeholder="Alt text">
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold mb-1">Title</label>
                            <input type="text" id="mp-detail-title"
                                   class="form-control form-control-sm"
                                   placeholder="Title">
                        </div>
                        <button type="button" id="mp-use-btn" class="btn btn-primary btn-block btn-sm">
                            <i class="fas fa-check mr-1"></i>Use Image
                        </button>
                    </div><!-- /mp-detail -->

                </div><!-- /row -->
            </div><!-- /modal-body -->

        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div>
<!-- ===== /Media Picker Modal ===== -->
