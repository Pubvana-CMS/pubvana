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
 *   - HTML5 markup: the sectioning landmarks (main, nav, section, article,
 *     aside, header, footer), figure, figcaption, details, summary, video,
 *     and source. HTMLPurifier 4.x ships no module for these, so the
 *     definition is extended directly.
 *   - iframe embeds, limited to the providers the Media library stores,
 *     through URI.SafeIframeRegexp. HTML.SafeIframe refuses to load a
 *     whitelist-free config.
 *   - `role`, `tabindex`, and the ARIA attributes. HTMLPurifier 4.x defines
 *     no ARIA attribute, so each one is registered for every element through
 *     the global attribute map. `id` is switched on with Attr.EnableID so
 *     `aria-labelledby` and `aria-describedby` have targets. Attribute
 *     wildcards do not exist here, so `data-*` attributes still drop.
 *
 * Element, attribute, and global attribute additions run only while a
 * definition is built from scratch, so bump DEFINITION_REV with any change to
 * the lists below. Otherwise the serialized definition from the previous
 * revision loads as-is.
 *
 * @package Pubvana\Services
 */
final class HtmlPurifierFactory
{
    /** Identifies the app's definition in HTMLPurifier's definition cache. */
    private const DEFINITION_ID = 'pubvana.html';

    /** Definition cache revision. Bump when the element or attribute lists change. */
    private const DEFINITION_REV = 2;

    /** Providers the Media library recognizes as embeds. */
    private const IFRAME_SOURCES = '%^https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/|player\.vimeo\.com/video/)%';

    /**
     * Attribute type for each accessibility attribute, resolved through
     * HTMLPurifier_AttrTypes. Types follow ARIA: boolean states take
     * true/false, relationship attributes hold space separated ID lists, and
     * free text attributes take anything.
     *
     * `tabindex` is absent on purpose. It accepts signed integers and no stock
     * type does, so addAccessibilityAttributes() registers it by hand.
     *
     * @var array<string, string>
     */
    private const ACCESSIBILITY_ATTRIBUTES = [
        // Widget roles and relationships.
        'role'                   => 'Text',
        'aria-label'             => 'Text',
        'aria-labelledby'        => 'NMTOKENS',
        'aria-describedby'       => 'NMTOKENS',
        'aria-description'       => 'Text',
        'aria-details'           => 'Text',
        'aria-errormessage'      => 'Text',
        'aria-controls'          => 'NMTOKENS',
        'aria-owns'              => 'NMTOKENS',
        'aria-activedescendant'  => 'Text',
        'aria-flowto'            => 'NMTOKENS',
        'aria-roledescription'   => 'Text',
        'aria-keyshortcuts'      => 'Text',
        // Widget states.
        'aria-current'           => 'Enum#page,step,location,date,time,true,false',
        'aria-hidden'            => 'Enum#true,false',
        'aria-expanded'          => 'Enum#true,false',
        'aria-selected'          => 'Enum#true,false',
        'aria-pressed'           => 'Enum#true,false',
        'aria-checked'           => 'Enum#true,false',
        'aria-disabled'          => 'Enum#true,false',
        'aria-required'          => 'Enum#true,false',
        'aria-readonly'          => 'Enum#true,false',
        'aria-multiline'         => 'Enum#true,false',
        'aria-multiselectable'   => 'Enum#true,false',
        'aria-modal'             => 'Enum#true,false',
        'aria-invalid'           => 'Enum#true,false,grammar,spelling',
        'aria-haspopup'          => 'Enum#true,false,menu,listbox,tree,grid,dialog',
        'aria-autocomplete'      => 'Enum#none,inline,list,both',
        'aria-orientation'       => 'Enum#horizontal,vertical',
        'aria-sort'              => 'Enum#none,ascending,descending,other',
        // Live regions, ranges, and position in a set.
        'aria-live'              => 'Enum#off,polite,assertive',
        'aria-atomic'            => 'Enum#true,false',
        'aria-busy'              => 'Enum#true,false',
        'aria-relevant'          => 'Text',
        'aria-level'             => 'Number',
        'aria-posinset'          => 'Number',
        'aria-setsize'           => 'Number',
        'aria-valuemin'          => 'Text',
        'aria-valuemax'          => 'Text',
        'aria-valuenow'          => 'Text',
        'aria-valuetext'         => 'Text',
        'aria-placeholder'       => 'Text',
    ];

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

        self::extendDefinition($config);

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
            'Attr.EnableID'            => true,
            'Attr.ID.HTML5'            => true,
            'HTML.SafeIframe'          => true,
            'URI.SafeIframeRegexp'     => self::IFRAME_SOURCES,
        ];
    }

    /**
     * Add the elements and attributes this application needs to a definition
     * that is still being built. A definition loaded from cache returns early:
     * its revision already carries everything below.
     */
    private static function extendDefinition(\HTMLPurifier_Config $config): void
    {
        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition === null) {
            return;
        }

        self::addHtml5Elements($definition);
        self::addAccessibilityAttributes($definition);
    }

    /**
     * Add the HTML5 elements the editor produces.
     */
    private static function addHtml5Elements(\HTMLPurifier_HTMLDefinition $definition): void
    {
        $definition->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
        $definition->addElement('figcaption', 'Block', 'Flow', 'Common');

        // Sectioning landmarks. Without these an editor's <main>, <nav>,
        // or <section aria-labelledby="..."> is unwrapped on save and the
        // landmark disappears.
        $definition->addElement('main', 'Block', 'Flow', 'Common');
        $definition->addElement('nav', 'Block', 'Flow', 'Common');
        $definition->addElement('section', 'Block', 'Flow', 'Common');
        $definition->addElement('article', 'Block', 'Flow', 'Common');
        $definition->addElement('aside', 'Block', 'Flow', 'Common');
        $definition->addElement('header', 'Block', 'Flow', 'Common');
        $definition->addElement('footer', 'Block', 'Flow', 'Common');

        $definition->addElement('details', 'Block', 'Optional: (summary, Flow) | Flow', 'Common', [
            'open' => 'Bool',
        ]);
        $definition->addElement('summary', 'Block', 'Inline', 'Common');

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

    /**
     * Register the accessibility attributes for every element. HTMLPurifier
     * ships no ARIA module and cannot match attribute names by wildcard, so
     * each attribute goes into info_global_attr as a definition object. The
     * attribute validator consults that map for any attribute whose element
     * has no definition of its own.
     *
     * @see \HTMLPurifier_AttrValidator
     */
    private static function addAccessibilityAttributes(\HTMLPurifier_HTMLDefinition $definition): void
    {
        $types = new \HTMLPurifier_AttrTypes();

        foreach (self::ACCESSIBILITY_ATTRIBUTES as $attribute => $type) {
            $definition->info_global_attr[$attribute] = $types->get($type);
        }

        // tabindex takes a signed integer: -1 removes an element from the tab
        // order, and the stock Number type rejects negatives and zero.
        $definition->info_global_attr['tabindex'] = new \HTMLPurifier_AttrDef_Integer(true, true, true);
    }
}
