# Profiles

Public and admin profiles for Pubvana. Each user gets a browsable profile page (display name, bio, avatar, website, social links, job title, employer) plus an owner-only edit page.

## Features

- One profile per user, created lazily on first visit.
- Self-service edit page in the admin for your own profile.
- Admin can edit any user's profile when granted `profile.edit.any`.
- Avatar picked from the Media library.
- Public profile page and edit form rendered through the active theme (`profile` and `profile_edit` templates).
- Cascades with the user: deleting an account removes its profile.
- Public styles prefixed `pv-profile-`, loaded from `/assets/plugin/Profiles/css/profiles.css`.

## Installation

Profiles ships with Pubvana and loads with the rest of the app. There is no `composer.json` and no separate install step.

`<!-- TODO: add [exact install/enable steps for an in-tree plugin] -->`

Prerequisites:

- A Pubvana v3 install with the Media plugin active for the avatar picker.

## Usage

- Your own profile: **Admin → Profile** (`/admin/profile`).
- Anyone's public profile: `/profile/{username}`.
- Edit your public profile: open your profile page and use the edit link (`/profile/{username}/edit`), or use the admin profile page.
- Editing another user's profile requires the `profile.edit.any` permission.

A profile stores nine optional fields. Empty fields are saved as empty (null), so a field only shows when you give it a value.

## Configuration

No environment variables and no config file beyond the plugin route prefix (`routePrepend`). There are no settings to change.

## Contributing

Run the parser and the app's static analysis before opening a PR:

```
find plugins/Profiles -name '*.php' -exec php -l {} \;
vendor/bin/phpstan analyse
```

There is no test suite to run.

`<!-- TODO: add [test/coverage setup] -->`