# Contributing to Pubvana

Thanks for your interest in contributing. This guide covers how to report bugs, suggest features, and submit code.

---

## Reporting Bugs

Open an issue on [GitHub](https://github.com/Pubvana-CMS/pubvana/issues) with:

- What you expected to happen
- What actually happened
- Steps to reproduce
- PHP version, database, and web server

If you're a developer and would like to fix the bug or add functionality we gratefully accept PRs. See below.

---

## Security Issues

Found a security issue? Report it through [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new) instead of a public issue.

---

## Suggesting Features

Open an issue with the **feature request** label. Describe the problem you're trying to solve, not just the solution you want.

---

## Development Setup

See [README.md](README.md) for installation instructions.

---

## Submitting a PR

[CODING-STANDARDS.md](CODING-STANDARDS.md).

### What to include in your PR

- What the change does and why
- Screenshots if it changes the UI
- Migration notes if it changes the database

---

## Code Standards

Standards are in [CODING-STANDARDS.md](CODING-STANDARDS.md).

---

## Plugin and Theme Development

<!-- TODO: publish developer documentation -->

---

## Project Layout

```
app/                  - Core code (controllers, services, models, views)
plugins/              - One folder per plugin (each has pubvana.json)
themes/               - Theme folders with Vision templates
```

Plugin and theme assets are served by AssetService at `/assets/{type}/{name}/{path}`. Don't copy assets to `public/`.
