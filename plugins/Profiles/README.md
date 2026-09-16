# Profiles

Public and admin profiles for Pubvana. Each user gets a browsable profile page (display name, bio, avatar, website, social links, job title, employer) plus an owner-only edit page.

## Features

- One profile per user, created lazily on first visit
- Self-service edit page in the admin for your own profile
- Admin can edit any user's profile when granted `profile.edit.any`
- Avatar picked from the Media library
- Public profile page and edit form rendered through the active theme
- Cascades with the user: deleting an account removes its profile

## Usage

- Your own profile: **Admin → Profile** (`/admin/profile`)
- Anyone's public profile: `/profile/{username}`
- Edit your public profile: open your profile page and use the edit link, or use the admin profile page
- Editing another user's profile requires the `profile.edit.any` permission

A profile stores nine optional fields. Empty fields are saved as empty, so a field only shows when you give it a value.

## License

MIT

Note: extensive details can be found in AGENTS.md