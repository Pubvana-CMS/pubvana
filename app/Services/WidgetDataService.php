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
        'AuthorProfileModel.getForCurrentPost',
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
     * Substitute param definitions with actual option values.
     * Each param value is an option key — look up its saved value.
     */
    private function resolveParams(array $paramDefs, array $options): array
    {
        $resolved = [];
        foreach ($paramDefs as $paramName => $optionKey) {
            $resolved[$paramName] = $options[$optionKey] ?? null;
        }
        return $resolved;
    }
}
