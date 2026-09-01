# Core Blocks

Two blocks for Pubvana that don't belong to a specific content plugin. A Text block that escapes its content, and an HTML block that prints raw markup.

## Features

- Text block: optional title plus free-form content. Both render HTML-escaped.
- HTML block: raw, unescaped markup for trusted admin content.
- Blocks appear in the standard block picker and work in any region.
- No PHP code involved. Each block is defined in `pubvana.json` and rendered from a small Vision template.

## Installation

Core Blocks ships with Pubvana and loads with the rest of the app. There is no `composer.json` and no separate install step.

`<!-- TODO: add [exact install/enable steps for an in-tree plugin] -->`

Prerequisites:

- A Pubvana v3 install where plugins and the block (RegionManager) system are active.

## Usage

Open the block picker in any region and add one of the two blocks.

A Text block stores two options: `title` and `content`. The template only prints the title when it has a value, so an empty title does no damage:

```
{% if title %}<h6 class="block-title">{{ title }}</h6>{% endif %}
```

An HTML block stores one option: `content`. The template prints it as-is:

```
{! content !}
```

Saved options flow straight into the template. You do not configure the block with code.

## Configuration

None. The two blocks take their settings from the option fields in the block picker. There are no environment variables and no config file.

## Contributing

Run the parser and the app's static analysis before opening a PR:

```
find plugins/CoreBlocks -name '*.php' -exec php -l {} \;
vendor/bin/phpstan analyse
```

There is no test suite to run.

`<!-- TODO: add [test/coverage setup] -->`