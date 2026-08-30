# Comments

Site comments for content. Content plugins register themselves as comment hosts. The Comments plugin renders a thread and reply form inside any host's public view.

Comment hosting is opt-in. A new host is off until an admin enables it on `/admin/comments/settings` (under Content). A host that is not enabled renders no thread and is dropped from the host list.

## Registering a host

In your `Plugin.php`, register with adext type `comments.host`:

```php
$adext->register('comments.host', 'content', 'pubvana.my-plugin', [
    'label'    => 'My Items',
    'callable' => fn() => $app->myPlugin()->commentHostItems($prefix),
]);
```

`label` and `callable` are required. `priority` is optional.

The `callable` takes no arguments and returns a list of host items.

## Host item shape

```php
[
    'type'           => 'item',      // stored as commentable_type, e.g. 'post', 'page'
    'id'             => 5,           // stored as commentable_id
    'title'          => 'My Item',
    'url'            => $prefix . '/my-item',
    'allow_comments' => (bool) $item->allow_comments,
]
```

- `type` and `id` together identify the content the comments belong to.
- `allow_comments` is the per-item opt-in. The default is `false`. The admin must switch it on for an item before a thread renders.
- `title` (falls back to `label`) and `url` link stored comments back to their content in the admin.

## Rendering the thread and form

In your public controller, call `render()` and pass the HTML to your view:

```php
$data['comments_html'] = $app->comments()->render('item', (int) $itemId, (bool) $item->allow_comments);
```

Then dump it in your template:

```twig
{! comments_html !}
```

`render()` returns the full thread and, when appropriate, the reply form. It returns an empty string when:

- the Comments plugin is disabled,
- comments are closed for that item (`allow_comments` is false),
- the item's host type has not been enabled by an admin,
- the visitor is not logged in and guest posting is off. Existing comments still show; only the form is hidden.

## Host management and guest posting

- Hosts are opt-in, not on by default. New hosts accept nothing until an admin adds them to `Comments.enabledHosts` on `/admin/comments/settings`.
- `Comments.allow_guest_comments` (default `0`) controls whether visitors who are not logged in can post. Guests can always view comments.
- The post endpoint is `POST /comments/{type}/{id}`. Submissions for a host that is not enabled are rejected with a silent redirect to the referring page.

## Moderation

Moderating comments (approve, reject, delete) requires the `comments.moderate` permission.