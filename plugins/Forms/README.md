# Forms

Form builder for Pubvana. Create forms in the admin, embed them on your site, and collect and review submissions.

## Features

- Build forms with text, email, phone, textarea, select, radio, and checkbox fields
- Embed a form in content with a shortcode, or place it in a region as a block
- Review submissions in the admin
- Spam controls: honeypot, rate limit, and optional captcha
- Email each submission to the addresses you set
- Managing forms requires the forms.manage permission

## Usage

Build and manage forms under **Content → Forms**. Submissions are under **Content → Forms → Submissions**.

To put a form on a page, use the shortcode `{% forms slug 'contact' %}` (or by id), or add the Form block to a region.

## License

MIT

Note: extensive details can be found in AGENTS.md
