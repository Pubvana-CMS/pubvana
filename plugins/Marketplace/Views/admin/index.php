<?php
/**
 * Marketplace overview - admin page.
 *
 * @var string $pageTitle
 * @var bool $connected
 * @var string $accountEmail
 * @var string $prefillEmail
 * @var array<string, mixed> $installed
 * @var string $adminBase
 * @var array<string, mixed>|null $catalog
 * @var array<int, array<string, mixed>> $tabs
 * @var string $openTab
 * @var string $searchQuery
 */
?>

<div class="d-flex gap-2 mb-3">
    <a href="<?= $adminBase ?>" class="btn btn-sm btn-outline-secondary">Marketplace home</a>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#license-info">
        License Info
    </button>
</div>

<div class="modal modal-blur fade" id="license-info" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">License Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary">Each license covers one site. The tier purchased determines updates.</p>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="text-secondary small text-center">Tier</th>
                            <th class="text-secondary small text-center">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Patch updates</td>
                            <td class="text-secondary">Fixes. You get every release in the same major/minor version (1.2.x).</td>
                        </tr>
                        <tr>
                            <td>Minor updates</td>
                            <td class="text-secondary">Fixes and new features. You get every release in the same major version (1.x.x).</td>
                        </tr>
                        <tr>
                            <td>Major updates</td>
                            <td class="text-secondary">Everything. Includes all future versions (x.x.x).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!$connected): ?>
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-1">Marketplace</h1>
            </div>
            <div class="card">
                <div class="card-body">
                    <p>
                        Marketplace is your connection to the Pubvana Digital Store. 
                        Download, purchase, and install free and paid themes and plugins from
                        right here. Please login or register, doing so will create a user account
                        on <a target="_blank" href="https://pubvanacms.com">Pubvana's Website</a>
                    </p>
                    <div id="pv-connect-login">
                        <h2 class="card-title mb-1">Sign in</h2>
                        <p class="text-secondary">Use your Pubvana account.</p>
                        <form method="POST" action="<?= $adminBase ?>/connect">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label class="form-label" for="pv-login-email">Email</label>
                                <input type="email" id="pv-login-email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($prefillEmail) ?>" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pv-login-password">Password</label>
                                <input type="password" id="pv-login-password" name="password" class="form-control"
                                       autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Sign in</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" data-pv-toggle="register">Need an account? Create one</a>
                        </div>
                    </div>
                    <div id="pv-connect-register" class="d-none">
                        <h2 class="card-title mb-1">Create account</h2>
                        <p class="text-secondary"><small>New accounts must verify their email address</small></p>
                        <form method="POST" action="<?= $adminBase ?>/connect">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="register">
                            <div class="mb-3">
                                <label class="form-label" for="pv-register-email">Email</label>
                                <input type="email" id="pv-register-email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($prefillEmail) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pv-register-password">Password</label>
                                <input type="password" id="pv-register-password" name="password" class="form-control"
                                       autocomplete="new-password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pv-register-password-conf">Confirm password</label>
                                <input type="password" id="pv-register-password-conf" name="password_conf" class="form-control"
                                       autocomplete="new-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create account</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" data-pv-toggle="login">Already have an account? Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var login = document.getElementById('pv-connect-login');
            var register = document.getElementById('pv-connect-register');
            if (!login || !register) { return; }
            document.querySelectorAll('[data-pv-toggle]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    var showLogin = link.getAttribute('data-pv-toggle') === 'login';
                    login.classList.toggle('d-none', !showLogin);
                    register.classList.toggle('d-none', showLogin);
                    var target = showLogin
                        ? document.getElementById('pv-login-email')
                        : document.getElementById('pv-register-email');
                    if (target) { target.focus(); }
                });
            });
        })();
    </script>
<?php else: ?>
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>Connected as <code><?= htmlspecialchars($accountEmail) ?></code></div>
            <div>
                <a href="<?= $adminBase ?>/purchases" class="btn btn-outline-primary">Purchases</a>
                <?php $cartItems = is_array($cart['items'] ?? null) ? $cart['items'] : []; ?>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cart-modal" aria-label="Cart">
                    <i class="ti ti-shopping-cart"></i>
                    <span class="badge bg-primary-lt ms-2" id="cart-count"><?= count($cartItems) ?></span>
                </button>
                <form method="POST" action="<?= $adminBase ?>/disconnect" class="d-inline">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button class="btn btn-ghost-secondary">Disconnect</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="cart-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="cart-modal-body">
                    <?php if (!empty($cart['items'])): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-secondary small">Item</th>
                                <th class="text-secondary small text-center">Price</th>
                                <th class="text-secondary small"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $line): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars((string) ($line['name'] ?? '')) ?>
                                    <?php if (!empty($line['tier_label'])): ?>
                                    <div class="text-secondary small"><?= htmlspecialchars((string) $line['tier_label']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">$<?= number_format((float) ($line['payable'] ?? 0), 2) ?></td>
                                <td class="text-end">
                                    <form method="POST" action="<?= $adminBase ?>/cart-remove">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int) ($line['cart_item_id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-secondary">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-secondary mb-0">Your cart is empty.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="text-secondary">Total $<span id="cart-modal-total"><?= number_format((float) ($cart['total'] ?? 0), 2) ?></span></span>
                    <a href="<?= $adminBase ?>/cart-open" target="_blank" rel="noopener" class="btn btn-primary">Checkout <i class="ti ti-external-link"></i></a>
                </div>
            </div>
        </div>
    </div>

    <?php
    // The store owns the catalog; this screen renders one page of it. A
    // store that cannot be reached says so and shows nothing else. Each tab
    // and each search says when it has no matches.
    $catalogOk = is_array($catalog) && !empty($catalog['ok']);
    $items = $catalogOk ? $catalog['items'] : [];
    $tabKey = (string) ($openTab ?? '');
    $searching = $searchQuery !== '';
    // Search or not, the tab is part of every link: a search lives inside
    // its tab, so paging and tab switches both keep the query and the tab.
    $baseQuery = ['tab' => $tabKey];
    if ($searching) {
        $baseQuery['q'] = $searchQuery;
    }
    $link = static function (array $overrides) use ($adminBase, $baseQuery): string {
        $query = array_filter(array_merge($baseQuery, $overrides), static fn ($v): bool => $v !== '' && $v !== null);
        return $adminBase . '?' . http_build_query($query);
    };
    ?>

    <?php if (!$catalogOk): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <p class="text-secondary mb-0"><?= htmlspecialchars((string) ($catalog['reason'] ?? 'The store could not be reached.')) ?></p>
            </div>
        </div>
    <?php else: ?>
        <form method="GET" action="<?= $adminBase ?>" class="row g-2 align-items-end mb-3">
            <?php if ($tabKey !== ''): ?>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabKey) ?>">
            <?php endif; ?>
            <div class="col-md-5">
                <label class="form-label" for="pv-q">Search</label>
                <input type="text" id="pv-q" name="q" class="form-control" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Name or description">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Search</button>
            </div>
        </form>

        <ul class="nav nav-tabs mb-3">
            <?php foreach ($tabs as $tab): ?>
                <?php $key = (string) ($tab['key'] ?? ''); ?>
                <li class="nav-item">
                    <a class="nav-link<?= $key === $tabKey ? ' active' : '' ?>" href="<?= htmlspecialchars($link(['tab' => $key])) ?>"><?= htmlspecialchars((string) ($tab['label'] ?? $key)) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($items === []): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <?php
                    $tabLabels = [];
                    foreach ($tabs as $tabRow) {
                        $tabLabels[(string) ($tabRow['key'] ?? '')] = (string) ($tabRow['label'] ?? '');
                    }
                    $tabLabel = $tabLabels[$tabKey] ?? '';
                    ?>
                    <p class="text-secondary mb-0">
                        <?php if ($searching && $tabLabel !== ''): ?>
                            No items match <i><?= htmlspecialchars($searchQuery) ?></i> in <?= htmlspecialchars($tabLabel) ?>.
                        <?php elseif ($searching): ?>
                            No items match <i><?= htmlspecialchars($searchQuery) ?></i>.
                        <?php else: ?>
                            No items here yet.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item): ?>
                <?php
                $id = (int) ($item['id'] ?? 0);
                $package = (string) ($item['package'] ?? '');
                // Store records written before the addon toggle was on carry no
                // package identity, and an empty value can only be rejected.
                // The slug is accepted everywhere the package id is (the
                // free endpoint takes it, and installFreePackage matches on
                // either), so fall back to it, the same way the update paths
                // in MarketplaceService already do.
                $packageId = $package !== '' ? $package : (string) ($item['slug'] ?? '');
                // Free to install anywhere: fully free, or any tier priced 0.
                // Mirrors the check the install-free path makes.
                $isFree = !empty($item['is_free']) || !empty($item['free_tier']);
                // Addons physically on this site, from the local manifest
                // scan: covers Marketplace installs, core-shipped, and
                // manual uploads alike.
                $installedInfo = $installed[$packageId] ?? null;
                $installedHere = $installedInfo !== null;
                // Tiers for sale, from the store card, in Patch, Minor,
                // Major order. Free tiers are not purchases and carry no
                // button.
                $paidTiers = [];
                foreach ((array) ($item['tiers'] ?? []) as $tierRow) {
                    if (is_array($tierRow) && (float) ($tierRow['price'] ?? 0) > 0) {
                        $paidTiers[] = $tierRow;
                    }
                }
                ?>
                <?php
                // Whether the card has a details modal to open at all.
                $hasInfo = !empty($item['description']) || !empty($item['versions']) || !empty($item['package']) || !empty($item['image']);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <?php if (!empty($item['on_sale'])): ?>
                                    <span class="badge bg-danger-lt">On sale</span>
                                <?php endif; ?>
                                <?php if ($isFree): ?>
                                    <span class="badge bg-success-lt">Free</span>
                                <?php endif; ?>
                                <?php if ($installedHere): ?>
                                    <span class="badge bg-info-lt">Installed v<?= htmlspecialchars((string) $installedInfo['version']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h3 class="card-title mb-0"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></h3>
                                <?php if ($hasInfo): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#item-info-<?= (int) $id ?>">
                                    More Info
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['versions'])): ?>
                                <table class="table table-sm mb-2">
                                    <thead>
                                        <tr>
                                            <th class="text-secondary small text-center">Version</th>
                                            <th class="text-secondary small text-center">Pubvana Req'd</th>
                                            <th class="text-secondary small text-center">Your Pubvana</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($item['versions'] as $row): ?>
                                        <tr>
                                            <td class="small text-center">v<?= htmlspecialchars((string) $row['version']) ?></td>
                                            <td class="small text-center"><?= htmlspecialchars((string) $row['pubvana']) ?></td>
                                            <td class="small text-center"><?= !empty($row['fits']) ? '&#10003;' : '&#10007;' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif (!empty($item['version'])): ?>
                                <p class="text-secondary small mb-2">v<?= htmlspecialchars((string) $item['version']) ?></p>
                            <?php endif; ?>
                            <?php
                            // Add to Cart shows whether or not the addon is
                            // installed here; the Installed badge already says
                            // so. The free download stays limited to items
                            // that are not installed yet.
                            ?>
                            <?php if ($paidTiers !== [] || !$installedHere): ?>
                            <div class="mt-3">
                                <?php if ($paidTiers !== []): ?>
                                <p class="text-secondary small fw-semibold mb-2">Add to Cart</p>
                                <?php endif; ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($item['free_tier']) && !$installedHere): ?>
                                        <form method="POST" action="<?= $adminBase ?>/install-free" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="package" value="<?= htmlspecialchars($packageId) ?>">
                                            <button class="btn <?= $paidTiers === [] ? 'btn-primary' : 'btn-success' ?>"><?= $paidTiers === [] ? 'Download &amp; install' : 'Free download' ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($paidTiers !== []): ?>
                                        <?php foreach ($paidTiers as $tierRow): ?>
                                        <button type="button" class="btn btn-primary"
                                                data-cart-product="<?= $id ?>"
                                                data-cart-tier="<?= htmlspecialchars((string) ($tierRow['tier'] ?? '')) ?>">
                                            <?= htmlspecialchars(ucfirst((string) ($tierRow['tier'] ?? ''))) ?> $<?= number_format((float) $tierRow['price'], 2) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($hasInfo): ?>
                    <div class="modal modal-blur fade" id="item-info-<?= (int) $id ?>" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($item['image'])): ?>
                                    <img src="<?= htmlspecialchars((string) $item['image']) ?>"
                                         alt="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>"
                                         class="img-fluid rounded mb-3" loading="lazy">
                                    <?php endif; ?>
                                    <?php if (!empty($item['description'])): ?>
                                    <div class="text-secondary"><?= (string) $item['description'] ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['versions'])): ?>
                                    <table class="table table-sm mt-3 mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-secondary small text-center">Version</th>
                                                <th class="text-secondary small text-center">Pubvana Req'd</th>
                                                <th class="text-secondary small text-center">Your Pubvana</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($item['versions'] as $row): ?>
                                            <tr>
                                                <td class="small text-center">v<?= htmlspecialchars((string) $row['version']) ?></td>
                                                <td class="small text-center"><?= htmlspecialchars((string) $row['pubvana']) ?></td>
                                                <td class="small text-center"><?= !empty($row['fits']) ? '&#10003;' : '&#10007;' ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                    <?php if (!empty($item['package'])): ?>
                                    <p class="text-secondary small mt-3 mb-0">Package: <code><?= htmlspecialchars((string) $item['package']) ?></code></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        $pageNo = (int) ($catalog['page'] ?? 1);
        $pageCount = (int) ($catalog['pages'] ?? 1);
        ?>
        <?php if ($pageCount > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item<?= $pageNo <= 1 ? ' disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($link(['page' => max(1, $pageNo - 1)])) ?>">Previous</a>
                    </li>
                    <li class="page-item disabled"><span class="page-link">Page <?= $pageNo ?> of <?= $pageCount ?></span></li>
                    <li class="page-item<?= $pageNo >= $pageCount ? ' disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($link(['page' => min($pageCount, $pageNo + 1)])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        function cartEscape(value) {
            var div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        }
        function refreshCartModal() {
            fetch('<?= $adminBase ?>/cart-data', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var items = (data && data.items) ? data.items : [];
                    var body = document.getElementById('cart-modal-body');
                    if (body) {
                        if (!items.length) {
                            body.innerHTML = '<p class="text-secondary mb-0">Your cart is empty.</p>';
                        } else {
                            var html = '<table class="table table-sm"><thead><tr>'
                                + '<th class="text-secondary small">Item</th>'
                                + '<th class="text-secondary small text-center">Price</th>'
                                + '<th class="text-secondary small"></th>'
                                + '</tr></thead><tbody>';
                            items.forEach(function (line) {
                                html += '<tr><td>' + cartEscape(line.name)
                                    + (line.tier_label ? '<div class="text-secondary small">' + cartEscape(line.tier_label) + '</div>' : '')
                                    + '</td><td class="text-center">$' + Number(line.payable || 0).toFixed(2) + '</td>'
                                    + '<td class="text-end"><form method="POST" action="<?= $adminBase ?>/cart-remove">'
                                    + '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">'
                                    + '<input type="hidden" name="cart_item_id" value="' + parseInt(line.cart_item_id || 0, 10) + '">'
                                    + '<button class="btn btn-sm btn-outline-secondary">Remove</button>'
                                    + '</form></td></tr>';
                            });
                            html += '</tbody></table>';
                            body.innerHTML = html;
                        }
                    }
                    var total = document.getElementById('cart-modal-total');
                    if (total) { total.textContent = Number((data && data.total) || 0).toFixed(2); }
                    var count = document.getElementById('cart-count');
                    if (count) { count.textContent = items.length; }
                })
                .catch(function () {});
        }
        (function () {
            var cartModal = document.getElementById('cart-modal');
            if (!cartModal) { return; }
            cartModal.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form || String(form.action).indexOf('/cart-remove') === -1) { return; }
                event.preventDefault();
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' }
                }).then(function () {
                    refreshCartModal();
                }).catch(function () {});
            });
        })();
        document.querySelectorAll('[data-cart-product]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-cart-product');
                var tier = btn.getAttribute('data-cart-tier') || '';
                btn.disabled = true;
                btn.textContent = 'Adding…';
                var fd = new FormData();
                fd.append('product_id', id);
                fd.append('tier', tier);
                fd.append('currency', 'USD');
                fd.append('_csrf_token', '<?= csrf_token() ?>');
                fetch('<?= $adminBase ?>/cart-add', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (res && res.ok) {
                        btn.textContent = 'Added to cart';
                        btn.classList.remove('btn-primary', 'btn-outline-primary');
                        btn.classList.add('btn-success');
                        refreshCartModal();
                    } else {
                        btn.textContent = 'Try again';
                        btn.disabled = false;
                    }
                }).catch(function () {
                    btn.textContent = 'Try again';
                    btn.disabled = false;
                });
            });
        });
    </script>
<?php endif; ?>