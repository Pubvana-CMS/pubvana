<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SocialLinks;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\SocialLinks\Models\SocialLink;
use Pubvana\Plugins\SocialLinks\Services\SocialLinksService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SocialLinksService + SocialLink model.
 */
#[CoversClass(SocialLinksService::class)]
#[CoversClass(SocialLink::class)]
final class SocialLinksServiceTest extends TestCase
{
    private PDO $pdo;
    private SocialLinksService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        SocialLinksSchema::create($this->pdo);
        $this->service = new SocialLinksService($this->pdo, []);
    }

    public function testPlatformCatalog(): void
    {
        $platforms = SocialLinksService::platforms();
        self::assertGreaterThan(30, count($platforms));
        self::assertSame('Facebook', $platforms['facebook']['label']);
        self::assertSame('fa-brands fa-facebook', $platforms['facebook']['icon']);
        // Signal ships as a brand mark (fa-signal-messenger), not fa-solid fa-signal.
        self::assertSame('fa-brands fa-signal-messenger', $platforms['signal']['icon']);

        $options = $this->service->platformOptions();
        self::assertSame('Facebook', $options['facebook']);
        self::assertSame('Custom', $options['custom']);
        self::assertSame('custom', array_key_last($options));
    }

    public function testPlatformLabelAndIconFallbacks(): void
    {
        self::assertSame('Facebook', $this->service->platformLabel('facebook'));
        self::assertSame('fa-brands fa-facebook', $this->service->platformIcon('facebook'));

        // Unknown keys fall back to config, then hardcoded defaults.
        self::assertSame('Website', $this->service->platformLabel('nope'));
        self::assertSame('fa-solid fa-link', $this->service->platformIcon('nope'));

        $custom = new SocialLinksService($this->pdo, ['fallback_label' => 'Site', 'fallback_icon' => 'fa-x']);
        self::assertSame('Site', $custom->platformLabel('nope'));
        self::assertSame('fa-x', $custom->platformIcon('nope'));
    }

    public function testCreateKnownPlatform(): void
    {
        $link = $this->service->create(['platform' => 'GitHub', 'url' => 'https://github.com/acme']);

        self::assertNotNull($link);
        self::assertSame('github', $link->platform);
        self::assertSame('GitHub', $link->label);
        self::assertSame('fa-brands fa-github', $link->icon);
        self::assertSame('https://github.com/acme', $link->url);
        self::assertSame(0, (int) $link->sort_order);
        self::assertSame(1, (int) $link->is_active);
    }

    public function testCreateCustomPlatform(): void
    {
        $link = $this->service->create([
            'platform' => 'myspace',
            'url' => 'https://example.com/profile',
            'label' => 'My Page',
            'icon' => 'fa-brands fa-x-twitter',
        ]);

        self::assertNotNull($link);
        self::assertSame('custom', $link->platform);
        self::assertSame('My Page', $link->label);
        self::assertSame('fa-brands fa-x-twitter', $link->icon);
        self::assertSame('https://example.com/profile', $link->url);
        self::assertSame(0, (int) $link->sort_order);
    }

    public function testCreateRejectsBareHostWithoutAScheme(): void
    {
        // The law: a full URL or nothing. No scheme is assumed for the admin.
        self::assertNull($this->service->create([
            'platform' => 'custom',
            'url' => 'example.com/profile',
        ]));
    }

    public function testCreateCustomFallsBackOnBlankLabelAndBadIcon(): void
    {
        $link = $this->service->create(['platform' => 'custom', 'url' => 'https://example.com', 'label' => '', 'icon' => 'not an icon!!!']);

        self::assertNotNull($link);
        self::assertSame('Website', $link->label);
        self::assertSame('fa-solid fa-link', $link->icon);
    }

    public function testCreateCustomFallsBackOnUnknownIconClass(): void
    {
        // A syntactically valid class the staged stylesheets do not define
        // renders nothing, so it falls back instead of storing a blank icon.
        $link = $this->service->create([
            'platform' => 'custom',
            'url' => 'https://example.com',
            'label' => 'Elsewhere',
            'icon' => 'fa-brands fa-twiter',
        ]);

        self::assertNotNull($link);
        self::assertSame('Elsewhere', $link->label);
        self::assertSame('fa-solid fa-link', $link->icon);
    }

    public function testCreateRejectsBadUrls(): void
    {
        self::assertNull($this->service->create(['platform' => 'github', 'url' => '']));
        self::assertNull($this->service->create(['platform' => 'github', 'url' => '   ']));
        self::assertNull($this->service->create(['platform' => 'github', 'url' => 'https://']));
        self::assertSame(0, count($this->service->all()));
    }

    public function testCreateTruncatesLongUrl(): void
    {
        $long = 'https://example.com/' . str_repeat('a', 600);
        $link = $this->service->create(['platform' => 'github', 'url' => $long]);

        self::assertNotNull($link);
        self::assertSame(500, mb_strlen((string) $link->url));
    }

    public function testCreateSortOrderAppends(): void
    {
        $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        $second = $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);

        self::assertNotNull($second);
        self::assertSame(1, (int) $second->sort_order);
    }

    public function testCreateSortOrderLandsPastAGapLeftByADeletion(): void
    {
        $first = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        $second = $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);
        $third = $this->service->create(['platform' => 'linkedin', 'url' => 'https://c.test']);

        self::assertNotNull($first);
        self::assertTrue($this->service->delete((int) $first->id));

        // Two rows left holding 1 and 2. A row count would hand the new link
        // 2, tying with a row already there; it has to be 3.
        $fourth = $this->service->create(['platform' => 'facebook', 'url' => 'https://d.test']);

        self::assertNotNull($fourth);
        self::assertSame(3, (int) $fourth->sort_order);

        $orders = array_map(static fn ($link): int => (int) $link->sort_order, $this->service->all());
        self::assertSame([1, 2, 3], $orders, 'every sort_order is distinct');
        self::assertSame((int) $fourth->id, (int) $this->service->all()[2]->id, 'the new link sorts last');
    }

    public function testCreateSortOrderStartsAtZeroOnAnEmptyTable(): void
    {
        $link = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);

        self::assertNotNull($link);
        self::assertSame(0, (int) $link->sort_order);
    }

    public function testAllActiveLinksCountFind(): void
    {
        self::assertSame([], $this->service->all());
        self::assertSame([], $this->service->activeLinks());
        self::assertSame(0, $this->service->countActive());
        self::assertNull($this->service->find(99999));

        $a = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        $b = $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);
        self::assertNotNull($a);
        self::assertNotNull($b);

        self::assertCount(2, $this->service->all());
        self::assertCount(2, $this->service->activeLinks());
        self::assertSame(2, $this->service->countActive());
        self::assertNotNull($this->service->find((int) $a->id));

        $this->service->toggle((int) $a->id);
        self::assertCount(1, $this->service->activeLinks());
        self::assertSame(1, $this->service->countActive());
    }

    public function testToggle(): void
    {
        $link = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $id = (int) $link->id;

        self::assertTrue($this->service->toggle($id));
        self::assertSame(0, (int) $this->service->find($id)?->is_active);
        self::assertTrue($this->service->toggle($id));
        self::assertSame(1, (int) $this->service->find($id)?->is_active);

        self::assertFalse($this->service->toggle(99999));
    }

    public function testDelete(): void
    {
        $link = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $id = (int) $link->id;

        self::assertTrue($this->service->delete($id));
        self::assertNull($this->service->find($id));
        self::assertFalse($this->service->delete($id));
        self::assertFalse($this->service->delete(99999));
    }

    public function testUpdateKnownPlatformResetsLabelAndIcon(): void
    {
        $link = $this->service->create([
            'platform' => 'custom',
            'url' => 'https://a.test',
            'label' => 'Old',
            'icon' => 'fa-brands fa-x-twitter',
        ]);
        self::assertNotNull($link);
        $id = (int) $link->id;

        $updated = $this->service->update($id, ['platform' => 'github', 'url' => 'https://github.com/acme']);

        self::assertNotNull($updated);
        self::assertSame('github', (string) $this->service->find($id)?->platform);
        self::assertSame('GitHub', (string) $this->service->find($id)?->label);
        self::assertSame('fa-brands fa-github', (string) $this->service->find($id)?->icon);
        self::assertSame('https://github.com/acme', (string) $this->service->find($id)?->url);
    }

    public function testUpdateCustomKeepsPostedValues(): void
    {
        $link = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $id = (int) $link->id;

        $updated = $this->service->update($id, [
            'platform' => 'custom',
            'url'      => 'https://example.com/me',
            'label'    => 'My Page',
            'icon'     => 'fa-brands fa-mastodon',
        ]);

        self::assertNotNull($updated);
        self::assertSame('custom', (string) $this->service->find($id)?->platform);
        self::assertSame('My Page', (string) $this->service->find($id)?->label);
        self::assertSame('fa-brands fa-mastodon', (string) $this->service->find($id)?->icon);
    }

    public function testUpdateRejectsBadUrlAndMissingId(): void
    {
        $link = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $id = (int) $link->id;

        self::assertNull($this->service->update($id, ['platform' => 'github', 'url' => 'example.com']));
        self::assertSame('https://a.test', (string) $this->service->find($id)?->url, 'a rejected update leaves the row alone');

        self::assertNull($this->service->update(99999, ['platform' => 'github', 'url' => 'https://b.test']));
    }

    public function testMove(): void
    {
        $a = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        $b = $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);
        $c = $this->service->create(['platform' => 'reddit', 'url' => 'https://c.test']);
        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertNotNull($c);

        // Move middle down: a, c, b.
        self::assertTrue($this->service->move((int) $b->id, 'down'));
        self::assertSame([(int) $a->id, (int) $c->id, (int) $b->id], $this->ids($this->service->all()));

        // Move middle up: a, b, c again.
        self::assertTrue($this->service->move((int) $b->id, 'up'));
        self::assertSame([(int) $a->id, (int) $b->id, (int) $c->id], $this->ids($this->service->all()));

        // Edges refuse to move past the ends.
        self::assertFalse($this->service->move((int) $a->id, 'up'));
        self::assertFalse($this->service->move((int) $c->id, 'down'));
        // Unknown id refuses.
        self::assertFalse($this->service->move(99999, 'up'));
        // Unknown direction refuses and leaves the order alone.
        self::assertFalse($this->service->move((int) $a->id, 'sideways'));
        self::assertSame([(int) $a->id, (int) $b->id, (int) $c->id], $this->ids($this->service->all()));
    }

    public function testSocialLinksBlock(): void
    {
        $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        $hidden = $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);
        self::assertNotNull($hidden);
        $this->service->toggle((int) $hidden->id);

        $block = $this->service->socialLinksBlock([]);
        self::assertSame('Follow Us', $block['title']);
        self::assertCount(1, $block['links']);
        self::assertSame('GitHub', $block['links'][0]['label']);
        self::assertSame('_blank', $block['target']);
        self::assertSame('noopener noreferrer', $block['rel']);

        $titled = $this->service->socialLinksBlock(['title' => 'Find Me']);
        self::assertSame('Find Me', $titled['title']);

        $custom = new SocialLinksService($this->pdo, [
            'block_title' => 'Elsewhere',
            'default_target' => '_self',
            'link_rel' => 'me',
        ]);
        $block = $custom->socialLinksBlock([]);
        self::assertSame('Elsewhere', $block['title']);
        self::assertSame('_self', $block['target']);
        self::assertSame('me', $block['rel']);
    }

    public function testModelOrderingAndFinders(): void
    {
        $this->service->create(['platform' => 'x', 'url' => 'https://b.test']);
        $first = $this->service->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($first);
        $this->service->move((int) $first->id, 'up');

        $all = (new SocialLink($this->pdo))->allOrdered();
        self::assertSame('github', $all[0]->platform);

        $active = (new SocialLink($this->pdo))->activeOrdered();
        self::assertCount(2, $active);

        self::assertNotNull((new SocialLink($this->pdo))->findById((int) $first->id));
        self::assertNull((new SocialLink($this->pdo))->findById(99999));
    }

    public function testFindByIdRunsOnFreshInstance(): void
    {
        $a = $this->service->create(['platform' => 'x', 'url' => 'https://a.test']);
        $b = $this->service->create(['platform' => 'github', 'url' => 'https://b.test']);
        self::assertNotNull($a);
        self::assertNotNull($b);

        $model = new SocialLink($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame('x', (string) $first->platform);
        self::assertNull($model->findById(99999));
    }

    /** @param list<object> $links */
    private function ids(array $links): array
    {
        return array_map(static fn($l): int => (int) $l->id, $links);
    }
}
