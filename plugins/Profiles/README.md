# Profiles

Public and admin profiles for Pubvana. Each user gets a browsable profile page (display name, bio, avatar, website, social links, job title, employer) plus an owner-only edit page.

## Features

- One profile per user, created lazily on first visit
- Tabbed profile pages: Profile and Security, plus tabs other plugins register
- Self-service edit page in the admin for your own profile
- Admin can edit any user's profile when granted `profile.edit.any`
- Security tab: change the account email, change the password, and manage signed-in devices
- Avatar picked from the Media library, or uploaded from the profile editor
- Public profile page and edit form rendered through the active theme
- Author Card block for the current post or page author
- Cascades with the user: deleting an account removes its profile

## Adding a tab from another plugin

Register a label and a callable in the plugin's `Plugin.php`. The callable gets the profile owner and returns the tab body.

```php
$app->adext()->register('profile.tab', 'default', 'pubvana/addresses', [
    'label'    => 'Addresses',
    'callable' => fn($user) => '<input name="street">',
]);
```

The tab id in `?tab=` is the last part of the key, so `pubvana/addresses` opens with `?tab=addresses`. The tab appears on `/admin/profile`, `/admin/profile/{id}` and `/profile/{id}/edit`. The plugin owns the fields and the storage.

## Usage

- Your own profile: **Admin → Profile** (`/admin/profile`)
- Anyone's public profile: `/profile/{id}`
- Edit your public profile: open your profile page and use the edit link, or use the admin profile page
- Security tab: change your email, change your password, or sign out other devices
- Editing another user's profile requires the `profile.edit.any` permission

A profile stores nine optional fields. Empty fields are saved as empty, so a field only shows when you give it a value.

## License

MIT

Note: extensive details can be found in AGENTS.md