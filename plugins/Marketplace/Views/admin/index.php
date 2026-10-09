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

<a href="<?= $adminBase ?>" class="btn btn-sm btn-outline-secondary mb-3">Marketplace home</a>

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
                <a href="<?= $adminBase ?>/cart-open" target="_blank" rel="noopener" class="btn btn-outline-primary">Checkout <i class="ti ti-external-link"></i></a>
                <form method="POST" action="<?= $adminBase ?>/disconnect" class="d-inline">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button class="btn btn-ghost-secondary">Disconnect</button>
                </form>
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
    $baseQuery = $searching ? [] : ['tab' => $tabKey];
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
            <?php if (!$searching): ?>
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

        <?php if (!$searching): ?>
        <ul class="nav nav-tabs mb-3">
            <?php foreach ($tabs as $tab): ?>
                <?php $key = (string) ($tab['key'] ?? ''); ?>
                <li class="nav-item">
                    <a class="nav-link<?= $key === $tabKey ? ' active' : '' ?>" href="<?= htmlspecialchars($link(['tab' => $key])) ?>"><?= htmlspecialchars((string) ($tab['label'] ?? $key)) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <p class="text-secondary mb-0">No items<?= $searching ? ' match that search' : ' here yet' ?>.</p>
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
                // Free to install anywhere: fully free, any free tier, or a
                // package with no license scope. Mirrors the check the
                // install-free path makes.
                $isFree = !empty($item['is_free'])
                    || !empty($item['free_tier'])
                    || (($item['license_scope'] ?? '') === 'none');
                // Addons physically on this site, from the local manifest
                // scan: covers Marketplace installs, core-shipped, and
                // manual uploads alike.
                $installedInfo = $installed[$packageId] ?? null;
                $installedHere = $installedInfo !== null;
                $price = (float) ($item['price'] ?? 0);
                $priceMulti = isset($item['price_multi']) && $item['price_multi'] !== null ? (float) $item['price_multi'] : null;
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
                            <h3 class="card-title mb-1"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></h3>
                            <?php if (!empty($item['version'])): ?>
                                <p class="text-secondary small mb-2">v<?= htmlspecialchars((string) $item['version']) ?></p>
                            <?php endif; ?>
                            <p class="card-text text-secondary"><?= htmlspecialchars((string) ($item['description'] ?? '')) ?></p>
                            <?php if (!empty($item['min_pubvana'])): ?>
                                <p class="text-secondary small mb-2">Requires Pubvana <?= htmlspecialchars((string) $item['min_pubvana']) ?>+</p>
                            <?php endif; ?>
                            <?php
                            // Update available: installed here and the store
                            // ships a newer version. installFromPackage()
                            // routes free items through the free endpoint and
                            // purchased items through license validation.
                            $updateAvailable = $installedHere
                                && (string) ($item['version'] ?? '') !== ''
                                && version_compare((string) $item['version'], (string) $installedInfo['version'], '>');
                            ?>
                            <?php if ($updateAvailable): ?>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <span class="badge bg-orange-lt">Update to v<?= htmlspecialchars((string) $item['version']) ?></span>
                                <form method="POST" action="<?= $adminBase ?>/install-free" class="d-inline">
                                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="package" value="<?= htmlspecialchars($packageId) ?>">
                                    <button class="btn btn-primary">Update</button>
                                </form>
                            </div>
                            <?php elseif (!$installedHere): ?>
                            <div class="d-flex align-items-center justify-content-between mt-3 gap-2 flex-wrap">
                                <?php if ($isFree): ?>
                                    <strong>Free</strong>
                                    <form method="POST" action="<?= $adminBase ?>/install-free" class="d-inline">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="package" value="<?= htmlspecialchars($packageId) ?>">
                                        <button class="btn btn-primary">Download &amp; install</button>
                                    </form>
                                <?php elseif ($price <= 0 && $priceMulti !== null && $priceMulti > 0): ?>
                                    <div class="btn-group">
                                        <form method="POST" action="<?= $adminBase ?>/install-free" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="package" value="<?= htmlspecialchars($packageId) ?>">
                                            <button class="btn btn-success">Free download</button>
                                        </form>
                                        <button type="button" class="btn btn-primary"
                                                data-cart-product="<?= $id ?>"
                                                data-cart-scope="multi_site">Multi $<?= number_format($priceMulti, 2) ?></button>
                                    </div>
                                <?php elseif ($price > 0 && $priceMulti !== null && $priceMulti > 0): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary"
                                                data-cart-product="<?= $id ?>"
                                                data-cart-scope="single_site">1-site $<?= number_format($price, 2) ?></button>
                                        <button type="button" class="btn btn-outline-primary"
                                                data-cart-product="<?= $id ?>"
                                                data-cart-scope="multi_site">Multi $<?= number_format($priceMulti, 2) ?></button>
                                    </div>
                                <?php elseif ($price > 0): ?>
                                    <strong>$<?= number_format($price, 2) ?></strong>
                                    <button type="button" class="btn btn-primary"
                                            data-cart-product="<?= $id ?>"
                                            data-cart-scope="single_site">Add to cart</button>
                                <?php elseif ($priceMulti !== null && $priceMulti > 0): ?>
                                    <strong>$<?= number_format($priceMulti, 2) ?></strong>
                                    <button type="button" class="btn btn-primary"
                                            data-cart-product="<?= $id ?>"
                                            data-cart-scope="multi_site">Add to cart</button>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isFree && ($price > 0 || ($priceMulti ?? 0) > 0)): ?>
                                <p class="text-secondary small mb-0 mt-2">
                                    <?= ($priceMulti !== null && $price > 0) ? '1-site licenses bind to one domain; multi-site licenses cover a set of registered domains.' : (($priceMulti !== null && $priceMulti > 0) ? 'Licensed to a set of registered domains.' : 'Licensed to one domain. Release the site in your Pubvana account to move it.') ?>
                                </p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
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
        document.querySelectorAll('[data-cart-product]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-cart-product');
                var scope = btn.getAttribute('data-cart-scope') || 'single_site';
                btn.disabled = true;
                btn.textContent = 'Adding…';
                var fd = new FormData();
                fd.append('product_id', id);
                fd.append('scope', scope);
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