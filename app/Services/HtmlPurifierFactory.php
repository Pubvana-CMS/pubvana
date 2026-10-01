<?php

declare(strict_types=1);

namespace Pubvana\Services;

/**
 * Builds the HTMLPurifier configuration the application sanitizes with.
 *
 * Callers: blog posts, pages, comments, markdown imports, and theme block
 * options. Stock HTMLPurifier drops attributes and elements the editor
 * writes, so the defaults here add:
 *
 *   - `target` on links. HTMLPurifier validates it as an enum against
 *     Attr.AllowedFrameTargets, which defaults to an empty array, so every
 *     value fails validation and the attribute is dropped.
 *   - `rel` values. Attr.AllowedRel defaults empty, which drops the
 *     `nofollow` the Jodit link dialog writes and the `noopener noreferrer`
 *     Jodit adds to `target="_blank"` links. HTML.TargetNoopener and
 *     HTML.TargetNoreferrer stay on, so kept links keep both.
 *   - HTML5 markup: figure, figcaption, video, source. HTMLPurifier 4.x
 *     ships no module for these, so the definition is extended directly.
 *   - iframe embeds, limited to the providers the Media library stores,
 *     through URI.SafeIframeRegexp. HTML.SafeIframe refuses to load a
 *     whitelist-free config.
 *
 * Element additions run only while a definition is built from scratch, so
 * bump DEFINITION_REV with any change to the element list below. Otherwise
 * the serialized definition from the previous revision loads as-is.
 *
 * @package Pubvana\Services
 */
final class HtmlPurifierFactory
{
    /** Identifies the app's definition in HTMLPurifier's definition cache. */
    private const DEFINITION_ID = 'pubvana.html';

    /** Definition cache revision. Bump when the element list changes. */
    private const DEFINITION_REV = 1;

    /** Providers the Media library recognizes as embeds. */
    private const IFRAME_SOURCES = '%^https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/|player\.vimeo\.com/video/)%';

    /**
     * Build a purifier config with the application defaults.
     *
     * @param array<string, mixed> $overrides Directives that replace the defaults.
     */
    public static function create(array $overrides = []): \HTMLPurifier_Config
    {
        $config = \HTMLPurifier_Config::create($overrides + self::directives());

        $config->set('HTML.DefinitionID', self::DEFINITION_ID);
        $config->set('HTML.DefinitionRev', self::DEFINITION_REV);

        self::addHtml5Elements($config);

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private static function directives(): array
    {
        return [
            'Attr.AllowedFrameTargets' => ['_blank', '_self', '_parent', '_top'],
            'Attr.AllowedRel'          => ['nofollow', 'noopener', 'noreferrer'],
            'HTML.SafeIframe'          => true,
            'URI.SafeIframeRegexp'     => self::IFRAME_SOURCES,
        ];
    }

    /**
     * Extend a freshly built definition with the HTML5 elements the editor
     * produces. A definition loaded from cache returns early: its revision
     * already carries these elements.
     */
    private static function addHtml5Elements(\HTMLPurifier_Config $config): void
    {
        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition === null) {
            return;
        }

        $definition->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
        $definition->addElement('figcaption', 'Block', 'Flow', 'Common');

        $definition->addElement(
            'video',
            'Block',
            'Optional: (source, Flow) | (Flow, source) | Flow',
            'Common',
            [
                'src'         => 'URI',
                'poster'      => 'URI',
                'controls'    => 'Bool',
                'autoplay'    => 'Bool',
                'loop'        => 'Bool',
                'muted'       => 'Bool',
                'playsinline' => 'Bool',
                'preload'     => 'Enum#none,metadata,auto',
                'width'       => 'Length',
                'height'      => 'Length',
            ]
        );

        $definition->addElement('source', 'Block', 'Flow', 'Common', [
            'src'  => 'URI',
            'type' => 'Text',
        ]);
    }
}
