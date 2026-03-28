<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.socialTitle') ?></h1>
</div>

<?php if (! empty($iconNotice)): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle fa-fw"></i> <?= esc($iconNotice) ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Add new link -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialTitle') ?></h6>
            </div>
            <div class="card-body">
                <?php if (! empty($themeInfo['icon_pack'])): ?>
                <div class="alert alert-info small mb-3">
                    <i class="fas fa-palette fa-fw"></i>
                    The current theme <strong><?= esc($themeInfo['name'] ?? 'Unknown') ?></strong> uses
                    <strong><?= esc($themeInfo['icon_pack']) ?></strong>
                    (v<?= esc($themeInfo['icon_pack_ver'] ?? '?') ?>) for icons.
                    Below you can choose the icons available that will display for the Social Links feature of this site.
                </div>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('admin/social/store') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label><?= lang('Admin.socialPlatform') ?></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="icon-preview"><i class="fas fa-icons fa-fw"></i></span>
                            </div>
                            <input type="text" id="icon-search" class="form-control" placeholder="Search platforms..." autocomplete="off">
                        </div>
                        <input type="hidden" name="platform" id="platform-value">
                        <input type="hidden" name="icon" id="icon-value">
                        <div id="icon-dropdown" class="border rounded mt-1 bg-white" style="display:none; max-height:200px; overflow-y:auto">
                        </div>
                        <small class="form-text text-muted">These icons are just a representation of the icon that will be used. The actual icon may differ depending on the active theme's icon pack.</small>
                    </div>
                    <div class="form-group">
                        <label><?= lang('Admin.socialUrl') ?></label>
                        <input type="url" name="url" class="form-control" required placeholder="https://twitter.com/yourhandle">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= lang('Admin.add') ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current links -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialTitle') ?></h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th><?= lang('Admin.socialPlatform') ?></th>
                            <th><?= lang('Admin.socialUrl') ?></th>
                            <th class="text-right text-nowrap"><?= lang('Admin.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <i class="<?= esc($adminIcons[$link->id] ?? $link->icon) ?> fa-fw fa-lg text-primary"></i>
                            </td>
                            <td>
                                <a href="#" class="copy-url" data-url="<?= esc($link->url) ?>" data-toggle="tooltip" title="<?= esc($link->url) ?>">
                                    <i class="far fa-hand-pointer fa-fw"></i> <?= lang('Admin.clickToCopy') ?>
                                </a>
                            </td>
                            <td class="text-right text-nowrap">
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $link->is_active ? 'btn-outline-secondary' : 'btn-success' ?>">
                                        <?= $link->is_active ? lang('Admin.deactivate') : lang('Admin.activate') ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= base_url('admin/social/' . $link->id . '/delete') ?>" class="d-inline ml-1" onsubmit="return confirm('<?= lang('Admin.confirmDelete') ?>')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger"><?= lang('Admin.delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4"><?= lang('Admin.noResultsFound') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php $extra_scripts = <<<'SCRIPT'
<script>
$(function(){
    // Tooltip + copy-to-clipboard
    $('[data-toggle="tooltip"]').tooltip();
    $('.copy-url').on('click', function(e){
        e.preventDefault();
        var $el = $(this);
        var url = $el.data('url');
        var tmp = $('<textarea>').val(url).appendTo('body').select();
        document.execCommand('copy');
        tmp.remove();
        var orig = $el.html();
        $el.html('<i class="fas fa-check fa-fw"></i> Copied!');
        setTimeout(function(){ $el.html(orig); }, 1500);
    });

    // Icon picker
    var icons = [
        {label:'Facebook',    cls:'fab fa-facebook'},
        {label:'Messenger',   cls:'fab fa-facebook-messenger'},
        {label:'X',           cls:'fab fa-x-twitter'},
        {label:'Instagram',   cls:'fab fa-instagram'},
        {label:'YouTube',     cls:'fab fa-youtube'},
        {label:'LinkedIn',    cls:'fab fa-linkedin'},
        {label:'Pinterest',   cls:'fab fa-pinterest'},
        {label:'TikTok',      cls:'fab fa-tiktok'},
        {label:'Snapchat',    cls:'fab fa-snapchat'},
        {label:'Reddit',      cls:'fab fa-reddit'},
        {label:'Discord',     cls:'fab fa-discord'},
        {label:'Twitch',      cls:'fab fa-twitch'},
        {label:'GitHub',      cls:'fab fa-github'},
        {label:'WhatsApp',    cls:'fab fa-whatsapp'},
        {label:'Telegram',    cls:'fab fa-telegram'},
        {label:'Mastodon',    cls:'fab fa-mastodon'},
        {label:'Tumblr',      cls:'fab fa-tumblr'},
        {label:'Vimeo',       cls:'fab fa-vimeo-v'},
        {label:'Flickr',      cls:'fab fa-flickr'},
        {label:'Dribbble',    cls:'fab fa-dribbble'},
        {label:'Behance',     cls:'fab fa-behance'},
        {label:'Medium',      cls:'fab fa-medium'},
        {label:'Spotify',     cls:'fab fa-spotify'},
        {label:'SoundCloud',  cls:'fab fa-soundcloud'},
        {label:'Slack',       cls:'fab fa-slack'},
        {label:'Skype',       cls:'fab fa-skype'},
        {label:'Steam',       cls:'fab fa-steam'},
        {label:'Patreon',     cls:'fab fa-patreon'},
        {label:'PayPal',      cls:'fab fa-paypal'}
    ];

    var $search   = $('#icon-search');
    var $dropdown = $('#icon-dropdown');
    var $hidden   = $('#icon-value');
    var $platform = $('#platform-value');

    function renderList(filter) {
        var html = '';
        var q = (filter || '').toLowerCase();
        icons.forEach(function(ic){
            if (q && ic.label.toLowerCase().indexOf(q) === -1 && ic.cls.toLowerCase().indexOf(q) === -1) return;
            html += '<a href="#" class="d-flex align-items-center px-3 py-2 text-dark icon-option" data-cls="' + ic.cls + '">'
                  + '<i class="' + ic.cls + ' fa-fw fa-lg mr-2"></i> '
                  + ic.label
                  + '</a>';
        });
        $dropdown.html(html || '<div class="px-3 py-2 text-muted">No matches</div>');
    }

    $search.on('focus', function(){
        renderList($search.val());
        $dropdown.show();
    });

    $search.on('input', function(){
        renderList($search.val());
        $dropdown.show();
    });

    $(document).on('click', '.icon-option', function(e){
        e.preventDefault();
        var cls = $(this).data('cls');
        var label = $(this).text().trim();
        $search.val(label);
        $hidden.val(cls);
        $platform.val(label);
        $('#icon-preview').html('<i class="' + cls + ' fa-fw"></i>');
        $dropdown.hide();
    });

    $(document).on('click', function(e){
        if (!$(e.target).closest('#icon-search, #icon-dropdown').length) {
            $dropdown.hide();
        }
    });

    // Show all on initial focus
    renderList('');
});
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
