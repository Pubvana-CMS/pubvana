<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Forms;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Forms\Models\Form;
use Pubvana\Plugins\Forms\Models\FormField;
use Pubvana\Plugins\Forms\Models\FormSubmission;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Forms models CRUD and queries against in-memory SQLite.
 */
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormSubmission::class)]
final class FormsModelTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function makeForm(array $extra = []): Form
    {
        return (new Form($this->pdo))->createRecord(array_merge([
            'name' => 'Contact',
            'slug' => 'contact-' . uniqid(),
            'status' => 'draft',
            'submit_label' => 'Send',
        ], $extra));
    }

    public function testFormFindById(): void
    {
        self::assertNull((new Form($this->pdo))->findById(99999));

        $form = $this->makeForm();
        $found = (new Form($this->pdo))->findById((int) $form->id);
        self::assertNotNull($found);
        self::assertSame('Contact', (string) $found->name);
    }

    public function testFindPublishedBySlugSkipsDraftsAndDeleted(): void
    {
        $draft = $this->makeForm(['slug' => 'draft-form', 'status' => 'draft']);
        $live = $this->makeForm(['slug' => 'live-form', 'status' => 'published']);

        self::assertNull((new Form($this->pdo))->findPublishedBySlug('draft-form'));
        self::assertNull((new Form($this->pdo))->findPublishedBySlug('missing'));
        self::assertNotNull((new Form($this->pdo))->findPublishedBySlug('live-form'));

        $live->softDelete();
        self::assertNull((new Form($this->pdo))->findById((int) $live->id));
        self::assertNull((new Form($this->pdo))->findPublishedBySlug('live-form'));
        self::assertNull((new Form($this->pdo))->findById((int) $draft->id) === null ? null : $this->softDeletedFind($draft));
    }

    private function softDeletedFind(Form $form): ?Form
    {
        $form->softDelete();

        return (new Form($this->pdo))->findById((int) $form->id);
    }

    public function testSlugExistsWithExclude(): void
    {
        $form = $this->makeForm(['slug' => 'taken']);

        self::assertTrue((new Form($this->pdo))->slugExists('taken'));
        self::assertFalse((new Form($this->pdo))->slugExists('free'));
        self::assertFalse((new Form($this->pdo))->slugExists('taken', (int) $form->id));
    }

    public function testPaginateListAllCountAllSkipDeleted(): void
    {
        $a = $this->makeForm(['name' => 'Bravo', 'slug' => 'bravo']);
        $b = $this->makeForm(['name' => 'Alpha', 'slug' => 'alpha']);
        $c = $this->makeForm(['name' => 'Charlie', 'slug' => 'charlie']);
        $c->softDelete();

        $model = new Form($this->pdo);
        self::assertSame(2, $model->countAll());

        $all = $model->listAll();
        self::assertSame(['Alpha', 'Bravo'], array_map(static fn (Form $f): string => (string) $f->name, $all));

        $page = $model->paginate(1, 1);
        self::assertCount(1, $page);
        self::assertSame((int) $b->id, (int) $page[0]->id);
        self::assertSame((int) $a->id, (int) $model->paginate(2, 1)[0]->id);
    }

    public function testUpdateRecordWhitelistsFields(): void
    {
        $form = $this->makeForm(['slug' => 'keep-me', 'name' => 'Old']);
        $form->updateRecord([
            'name' => 'New',
            'slug' => 'changed',
            'status' => 'published',
            'submit_label' => 'Go',
            'success_message' => 'Thanks',
            'notification_emails' => 'a@test.example',
            'description' => 'Desc',
        ]);

        $found = (new Form($this->pdo))->findById((int) $form->id);
        self::assertNotNull($found);
        self::assertSame('New', (string) $found->name);
        self::assertSame('keep-me', (string) $found->slug);
        self::assertSame('published', (string) $found->status);
        self::assertSame('Go', (string) $found->submit_label);
        self::assertSame('Thanks', (string) $found->success_message);
        self::assertSame('a@test.example', (string) $found->notification_emails);
        self::assertSame('Desc', (string) $found->description);
    }

    public function testFormFieldForFormOrderedAndDelete(): void
    {
        $form = $this->makeForm();
        $id = (int) $form->id;
        $fields = new FormField($this->pdo);

        $fields->createRecord(['form_id' => $id, 'type' => 'text', 'name' => 'b', 'label' => 'B', 'sort_order' => 2]);
        $fields->createRecord(['form_id' => $id, 'type' => 'text', 'name' => 'a', 'label' => 'A', 'sort_order' => 1]);

        $rows = $fields->forForm($id);
        self::assertSame(['a', 'b'], array_map(static fn (FormField $f): string => (string) $f->name, $rows));

        $fields->deleteForForm($id);
        self::assertSame([], $fields->forForm($id));
    }

    public function testSubmissionFindPaginateCount(): void
    {
        self::assertNull((new FormSubmission($this->pdo))->findById(99999));

        $form = $this->makeForm();
        $id = (int) $form->id;
        $model = new FormSubmission($this->pdo);

        $one = $model->createRecord(['form_id' => $id, 'status' => 'received', 'payload_json' => '{"a":1}']);
        $model->createRecord(['form_id' => $id, 'status' => 'received', 'payload_json' => '{"b":2}']);
        $model->createRecord(['form_id' => 999, 'status' => 'received', 'payload_json' => '{}']);

        self::assertNotNull($model->findById((int) $one->id));
        self::assertSame(3, $model->countAll());
        self::assertSame(2, $model->countAll($id));

        $page = $model->paginate(1, 2);
        self::assertCount(2, $page);
        self::assertGreaterThan((int) $page[1]->id, (int) $page[0]->id);

        $filtered = $model->paginate(1, 10, $id);
        self::assertCount(2, $filtered);
    }

    public function testFormFindersRunOnFreshInstances(): void
    {
        $a = $this->makeForm(['slug' => 'one', 'status' => 'published']);
        $b = $this->makeForm(['slug' => 'two', 'status' => 'published']);

        $model = new Form($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame((int) $a->id, (int) $first->id);

        $byOne = $model->findPublishedBySlug('one');
        $byTwo = $model->findPublishedBySlug('two');
        self::assertNotNull($byOne);
        self::assertNotNull($byTwo);
        self::assertNotSame($byOne, $byTwo);
        self::assertNull($model->findById(99999));
    }

    public function testSubmissionFindByIdRunsOnFreshInstance(): void
    {
        $form = $this->makeForm();
        $id = (int) $form->id;
        $model = new FormSubmission($this->pdo);
        $a = $model->createRecord(['form_id' => $id, 'status' => 'received', 'payload_json' => '{"a":1}']);
        $b = $model->createRecord(['form_id' => $id, 'status' => 'received', 'payload_json' => '{"b":2}']);

        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame((int) $a->id, (int) $first->id);
        self::assertNull($model->findById(99999));
    }
}
