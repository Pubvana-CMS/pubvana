# Media

Media library with image and video uploads. Exposes a service facade, `$app->media()`, that other plugins use to embed Jodit editors, media pickers, and to query the library.

## Jodit editor

Embed a Jodit rich text editor in any admin view.

Controller:

```php
public function create(): void
{
    $joditHtml = $this->app->media()->joditInit('#content');

    $this->render('pubvana/my-plugin/admin/create', [
        'pageTitle' => 'Create Item',
        'joditHtml' => $joditHtml,
    ]);
}
```

View:

```php
<textarea name="content" id="content" class="form-control"><?= htmlspecialchars($content ?? '') ?></textarea>
<?= $joditHtml ?>
```

`joditInit($selector)` returns a `<script>` block that creates a Jodit instance on the selector. It handles dark mode, CSP-safe plugin disabling, and custom image controls automatically.

If you construct Jodit directly instead of using `joditInit()`, disable the `beautify` and `ace` plugins yourself. Jodit loads them by default and they violate the app's CSP:

```js
new Jodit('#content', { disablePlugins: 'beautify,ace' });
```

Same for dark mode: Jodit uses an iframe with its own document, so parent page CSS cannot reach inside. `joditInit()` installs a `MutationObserver` that watches `data-bs-theme` on `<body>` and repaints the iframe body when dark mode is toggled. A manual setup does not get that.

## Media picker

`picker()` returns a self-contained image picker widget (preview plus offcanvas media library) for a form field:

```php
// Controller
$pickerHtml = $this->app->media()->picker('featured-image', $article->featured_image ?? '');

// View
<?= $pickerHtml ?>
```

There is also `avatarPicker($inputName, $currentValue)` for avatar fields.

## Featured image pattern

The standard pattern for a featured image field uses hidden inputs plus the offcanvas picker. See `plugins/Blog/Views/admin/edit.php` for a full working example.

Hidden inputs plus preview:

```php
<input type="hidden" name="featured_image" id="featured-image-path" value="<?= htmlspecialchars($item->featured_image ?? '') ?>">
<input type="hidden" name="media_id" id="featured-image-media-id" value="<?= (int) ($item->media_id ?? 0) ?>">
<div id="featured-image-preview" class="mb-2">
    <?php if (!empty($item->featured_image)): ?>
        <img src="<?= htmlspecialchars($item->featured_image) ?>" class="img-fluid rounded" alt="">
    <?php else: ?>
        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:150px;">
            <i class="ti ti-photo text-secondary" style="font-size:2rem;"></i>
        </div>
    <?php endif; ?>
</div>
```

To build it from scratch: hidden inputs (`featured_image` path and `media_id` ID), an offcanvas panel listing images from `/admin/media/json?type=image`, a drag-and-drop upload zone that posts to `/admin/media/upload/image`, a select handler that sets the hidden inputs and preview, and a remove button that clears them.

## Querying the library

```php
// Paginated list. Per page default is 24. Filter by type ('image', 'video', 'embed').
$items = $this->app->media()->list($page, $perPage, $type);

// Total count, with an optional type filter.
$count = $this->app->media()->countAll($type);

// Most recent items, optional type filter.
$recent = $this->app->media()->recent($limit, $type);

// A single item by id.
$media = $this->app->media()->find($id);
```

## Media URLs

API responses include a leading slash (for example `/uploads/image.webp`). Do not prepend another slash when building `<img src>` attributes, or you get `//uploads/...` which the browser may not resolve.

```php
// Wrong
<img src="/<?= htmlspecialchars($media->url) ?>">

// Correct
<img src="<?= htmlspecialchars($media->url) ?>">
```

Same in JavaScript:

```js
// Wrong
preview.innerHTML = '<img src="/' + url + '">';

// Correct
preview.innerHTML = '<img src="' + url + '">';
```

## Admin endpoints

- `GET /admin/media/json` lists media. Use `?type=image` to filter.
- `POST /admin/media/upload/image` uploads an image.
- `POST /admin/media/upload/video` uploads a video.

## Inline JS rule

All JavaScript in plugin admin views must be inline. The `.htaccess` blocks access to the `plugins/` directory:

```
RewriteRule ^(app|vendor|writable|plugins)(/|$) - [F,L]
```

External `.js` files served from `plugins/` return 403. Do not register `admin.js` via adext. Put all JS directly in the view file.

## Creating thumbnails

When processing image keys, cast to string first. Image arrays may have numeric keys, and passing an integer to `str_starts_with()` throws a `TypeError`:

```php
// Wrong: $key may be an integer
if (str_starts_with($key, 'thumb_')) {

// Correct
if (str_starts_with((string) $key, 'thumb_')) {
```

See `plugins/Media/Services/GdProcessor.php` for the image processing pipeline.