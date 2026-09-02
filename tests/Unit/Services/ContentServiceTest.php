<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ContentService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * ContentService chains registered content.render transformers in priority
 * order. No database is involved; a real ExtensionRegistry behind a mapped
 * adext service drives the whole surface.
 */
#[CoversClass(ContentService::class)]
final class ContentServiceTest extends TestCase
{
    private function makeContentService(ExtensionRegistry $registry): ContentService
    {
        $app = $this->app([
            'adext' => fn (): ExtensionRegistry => $registry,
        ]);

        return new ContentService($app);
    }

    public function testRenderReturnsContentUnchangedWhenNoTransformers(): void
    {
        $content = new ContentService($this->app(['adext' => fn () => new ExtensionRegistry()]));

        self::assertSame('hello', $content->render('hello'));
    }

    public function testRenderChainsTransformersInPriorityOrder(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('content.render', 'default', 'alpha', [
            'priority' => 10,
            'callable' => fn (array $ctx): string => strtoupper($ctx['content']),
        ]);
        $registry->register('content.render', 'default', 'beta', [
            'priority' => 20,
            'callable' => fn (array $ctx): string => $ctx['content'] . '!',
        ]);

        $content = $this->makeContentService($registry);

        // alpha uppercases first, then beta appends '!'
        self::assertSame('HELLO!', $content->render('hello'));
    }

    public function testRenderSkipsNonStringCallableResults(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('content.render', 'default', 'nullish', [
            'callable' => fn (array $ctx): null => null,
        ]);
        $registry->register('content.render', 'default', 'works', [
            'priority' => 100,
            'callable' => fn (array $ctx): string => trim($ctx['content']),
        ]);

        $content = $this->makeContentService($registry);

        self::assertSame('spaced', $content->render('  spaced  '));
    }

    public function testRenderAppliesZeroCallablesWhenCallableMissing(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('content.render', 'default', 'nocallable', [
            'label' => 'No Callable',
        ]);

        $content = $this->makeContentService($registry);

        self::assertSame('raw', $content->render('raw'));
    }
}
