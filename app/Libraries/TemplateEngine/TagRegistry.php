<?php

namespace App\Libraries\TemplateEngine;

class TagRegistry
{
    /**
     * Call a whitelisted tag function by name.
     *
     * @param string $name       Function name (e.g. 'lang', 'base_url')
     * @param array  $args       Resolved argument values
     * @return string            Output string (already HTML-safe where applicable)
     */
    public function call(string $name, array $args): string
    {
        return match ($name) {
            'lang'           => $this->tagLang($args),
            'theme_url'      => $this->tagThemeUrl($args),
            'base_url'       => $this->tagBaseUrl($args),
            'site_url'       => $this->tagSiteUrl($args),
            'widget_area'    => $this->tagWidgetArea($args),
            'widget'         => $this->tagWidget($args),
            'post_url'       => $this->tagPostUrl($args),
            'category_url'   => $this->tagCategoryUrl($args),
            'tag_url'        => $this->tagTagUrl($args),
            'render_content' => $this->tagRenderContent($args),
            default          => '', // Unknown tag function — silent
        };
    }

    private function tagLang(array $args): string
    {
        $key = (string) ($args[0] ?? '');
        $params = array_slice($args, 1);
        return lang($key, $params);
    }

    private function tagThemeUrl(array $args): string
    {
        return theme_url((string) ($args[0] ?? ''));
    }

    private function tagBaseUrl(array $args): string
    {
        $path = (string) ($args[0] ?? '');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return base_url($path);
    }

    private function tagSiteUrl(array $args): string
    {
        return site_url((string) ($args[0] ?? ''));
    }

    private function tagWidgetArea(array $args): string
    {
        return widget_area((string) ($args[0] ?? ''));
    }

    private function tagWidget(array $args): string
    {
        $folder = (string) ($args[0] ?? '');
        if ($folder === '') {
            return '';
        }
        return (new \App\Services\WidgetService())->renderWidget($folder, null);
    }

    private function tagPostUrl(array $args): string
    {
        return post_url((string) ($args[0] ?? ''));
    }

    private function tagCategoryUrl(array $args): string
    {
        return category_url((string) ($args[0] ?? ''));
    }

    private function tagTagUrl(array $args): string
    {
        return tag_url((string) ($args[0] ?? ''));
    }

    private function tagRenderContent(array $args): string
    {
        $entity = $args[0] ?? null;
        if (is_object($entity)) {
            return render_content($entity);
        }
        return '';
    }
}
