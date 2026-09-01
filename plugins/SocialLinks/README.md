# Social Links

Central management of your site's social profile links, rendered anywhere on the public site as a block with Font Awesome 7 Free icons. This is the v3 port of the v2 `SocialLinks` widget.

## Features

- One place to hold every social profile link: Facebook, X, Instagram, YouTube, LinkedIn, TikTok, and ~35 more brands.
- Optional custom links with your own label and Font Awesome class.
- Enable/disable each link, and drag-free reordering with up/down controls.
- A public "Social Links" block with an optional title, placed in any region.
- Icons are self-hosted Font Awesome 7 Free (no external CDN), licensed files included in `assets/fontawesome/LICENSE.txt`.

## Requirements

- Pubvana v3.
- This is an in-tree application plugin; enable it from the admin Plugins screen ("Social Links").

## Installation

1. Confirm the `plugins/SocialLinks` folder is present.
2. Enable the plugin in Admin, Plugins. The plugin state table is managed there.
3. On enable, the migration creates the `social_links` table and the seed registers the `social.manage` permission.
4. Open Settings, Social Links and add your links.

## Usage

### Admin

Settings, Social Links shows an add form and the link list:

- Pick a platform from the dropdown; the label and icon come from the platform catalog automatically.
- Choose "Custom" to enter your own label and a Font Awesome class (for example `fa-brands fa-x-twitter`).
- URLs accept a bare domain (`x.com/user`) and are stored normalized with `https://`. Invalid or non-http(s) values are rejected.
- Buttons per row toggle activeness, move the link up or down, and delete it. New links are active by default.

Public display order equals the admin list order.

### Front page

Place the "Social Links" block in any region (Themes, Regions). The block exposes one option, a `Title`, default "Follow Us". Inactive links are skipped. Anchors open in a new tab with `rel="noopener noreferrer"`.

## Platform catalog

The icons live in `Services/SocialLinksService.php`. Known brands covered include Facebook, X, Threads, Bluesky, Instagram, YouTube, LinkedIn, Pinterest, TikTok, Snapchat, Reddit, Discord, Twitch, GitHub, GitLab, WhatsApp, Telegram, Mastodon, Tumblr, Vimeo, Flickr, Dribbble, Behance, Medium, Spotify, SoundCloud, Slack, Skype, Steam, Patreon, PayPal, WordPress, Kickstarter, Etsy, Bandcamp, Signal, Weibo, Blogger, App Store, itch.io, Android, Apple.

Notes:

- Icons are Font Awesome 7 Free brand marks.
- Some brands have no Free mark (Stack Overflow, Nextdoor, Buffer), so they are not in the catalog; use a custom link for those.

## Configuration

Defaults in `Config/Config.php`:

| Key | Default | Purpose |
|-----|---------|---------|
| `default_target` | `_blank` | Window for public links |
| `link_rel` | `noopener noreferrer` | Link relationship on public anchors |
| `fallback_label` | `Website` | Label used for custom links without one |
| `fallback_icon` | `fa-solid fa-link` | Icon used for custom links without one |
| `block_title` | `Follow Us` | Title when the block title option is empty |

## Database

Table `social_links`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | primary key | |
| `platform` | string(50) | catalog key or `custom` |
| `label` | string(100) | display label |
| `url` | string(500) | normalized http(s) URL |
| `icon` | string(100) | Font Awesome class |
| `sort_order` | integer | sequential display order |
| `is_active` | boolean | shown on the public block |
| `created_at` / `updated_at` | datetime | |

Indexes: `is_active`.

## Service reference

- `$app->socialLinks()->all()`: all links in admin order.
- `$app->socialLinks()->activeLinks()`: active links for the block.
- `$app->socialLinks()->create($data)`, `toggle($id)`, `delete($id)`, `move($id, 'up'|'down')`.
- `$app->socialLinks()->platforms()`, `platformOptions()`, `platformLabel($key)`, `platformIcon($key)`.
- `$app->socialLinks()->socialLinksBlock($options)`: block provider returning `title`, `links`, `target`, `rel`.

## License

MIT for the plugin code. Font Awesome 7 Free is MIT code, SIL OFL 1.1 fonts, and CC BY 4.0 icons; see `assets/fontawesome/LICENSE.txt`.