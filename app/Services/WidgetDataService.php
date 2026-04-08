<?php

namespace App\Services;

class WidgetDataService
{
    /**
     * Whitelisted Model.method providers.
     * Only these can be called from widget_info.json.
     */
    private const PROVIDERS = [
        'CategoryModel.getWithPostCount',
        'PostModel.getRecent',
        'TagModel.getWithPostCount',
        'SocialModel.getActive',
        'PostModel.getArchiveList',
        'CommentModel.getRecentApproved',
        'PostModel.getRelated',
        'AuthorProfileModel.getForPost',
        'LanguageModel.getSwitcherData',
    ];

    /**
     * Resolve all data providers for a widget.
     *
     * @param array $providers  The output.providers section from widget_info.json
     * @param array $options    Merged widget options (defaults + saved)
     * @return array            Template variable name => data
     */
    public function resolve(array $providers, array $options): array
    {
        $results = [];

        foreach ($providers as $varName => $config) {
            $providerStr = $config['provider'] ?? '';
            $params = $this->resolveParams($config['params'] ?? [], $options);

            if (! in_array($providerStr, self::PROVIDERS, true)) {
                $results[$varName] = null;
                continue;
            }

            [$modelName, $method] = explode('.', $providerStr, 2);
            $fqn = 'App\\Models\\' . $modelName;

            if (! class_exists($fqn) || ! method_exists($fqn, $method)) {
                $results[$varName] = null;
                continue;
            }

            $model = new $fqn();
            $results[$varName] = $model->$method(...array_values($params));
        }

        return $results;
    }

    /**
     * Substitute param definitions with actual option values or context values.
     * Values starting with @ are resolved from the request context.
     * All other values are option key lookups.
     */
    private function resolveParams(array $paramDefs, array $options): array
    {
        $resolved = [];
        foreach ($paramDefs as $paramName => $optionKey) {
            if (is_string($optionKey) && str_starts_with($optionKey, '@')) {
                $resolved[$paramName] = $this->resolveContext($optionKey);
            } else {
                $resolved[$paramName] = $options[$optionKey] ?? null;
            }
        }
        return $resolved;
    }

    /**
     * Resolve a @context_name value from the current request.
     */
    private function resolveContext(string $key): mixed
    {
        return match ($key) {
            '@current_post_id' => $this->getCurrentPostId(),
            default            => null,
        };
    }

    private function getCurrentPostId(): ?int
    {
        $segments = service('uri')->getSegments();
        $seg1 = $segments[0] ?? '';
        $slug = $segments[1] ?? '';

        if ($seg1 !== 'blog' || empty($slug)) {
            return null;
        }

        $row = model(\App\Models\PostModel::class)->published()->where('slug', $slug)->first();

        return $row ? (int) $row->id : null;
    }
}
