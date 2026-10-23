# AI Assistant

The AI Assistant lets an AI assistant you trust create and manage content on your site on your behalf.

## Features

- Write post and page drafts, then publish or schedule them
- Browse and search posts and pages (drafts included) so it can find what to work on
- Moderate comments
- Manage redirects
- Triage the 404s the site has collected, turning them into redirects
- Keep navigation menus tidy
- Fact check posts and pages, on your terms
- Let other plugins add their own permissions to the assistant

## Installation

AI Assistant is included with Pubvana but disabled by default. Enable it under **Plugins → Manage**.
## Usage

1. Under **Tools → AI Assistant**, create a key and give it a name.
2. Copy the one-time "AI startup text" shown after generating the key. The key and the API guide link are both in it.
3. Paste the whole message into your AI tool.
4. On the Manage page, tick only the permissions that assistant should have. Unticked permissions are denied.
5. Stop the assistant at any time by pausing or deleting its key.

For fact checking, also visit **Tools → AI Assistant → Fact Checking**: read the terms, accept them, and switch the service on (it needs at least one enabled key).

## Fact checking

Your AI assistant (CLI, IDE, or desktop) can check the claims in your posts and pages and file a structured report: findings, per-claim verdicts, and cited sources. The checks run under a versioned integrity prompt that your assistant fetches from this site before every check. You accept the terms once per prompt version under **Tools → AI Assistant → Fact Checking**, and the service refuses to run until you do, and again whenever the prompt is updated.

Accepted reports appear in the report history, in a read-only panel in the post and page editors, and (once you place the "Fact Check Summary" block in a region) on the public page, complete with a "checked under Pubvana fact-check prompt vX" line. If an article tries to steer its own fact check, the report flags the attempt and may void the agreement to use fact checking.

## Records

The plugin keeps its own records for keys, permissions, and activity. Keys are never stored in full, and every use is recorded in the audit log on the Manage page.

## Default author

AI-created posts and pages are attributed to a user you choose on the Manage page.

## Plugin permissions

A plugin adds its own permissions through the `ai.grant` extension type. The contributor key is the grant token, so a plugin registers all its tokens in one batch call from `Plugin::register()`:

```php
$app->adext()->register('ai.grant', 'default', [
    'myplugin.items.read' => [
        'group'   => 'myplugin',
        'label'   => 'View items',
        'summary' => 'List the items this plugin stores.',
        'method'  => 'GET',
        'path'    => '/api/ai/myplugin/items',
    ],
]);
```

| Key | Required | Meaning |
| --- | --- | --- |
| token (the array key) | yes | Grant name in namespaced dot notation |
| `group` | yes | Resource heading the Help page and the grant form file it under |
| `label` | yes | Short name in the grant form |
| `summary` | yes | One line shown in the Help page |
| `method` | yes | HTTP method the endpoint answers |
| `path` | yes | Endpoint path, measured from the API base |
| `endpoints` | no | Extra URLs one grant covers, for a label that spans routes |
| `priority` | no | Display order |

`AiService::helpCatalog()` merges the registrations into its built-in catalog. One registration puts the grant in `/api/ai/help`, the admin Help page, the per-key grant form, and the check that runs before a token is stored. A plugin cannot replace a built-in token. A collision is logged and ignored, so `posts.read` always means the same thing.

The plugin owns the endpoint. Register the route under the API base, and extend `AiApiController` for `requireKey()`, `requireGrant()`, `log()`, and the `ok()` and `fail()` envelope, so the endpoint gets the same auth, audit, and response rules as the built-in ones.

Grants stay deny-all. A registered token opens nothing until an admin ticks it on a key. Saving a key keeps every grant the form did not render, so a grant from a disabled plugin survives the save.

## License

MIT

Note: extensive details can be found in AGENTS.md