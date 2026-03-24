<?php

namespace Tests\Feature\Database;

use App\Models\Audit;
use App\Models\Auth\AdminUser;
use App\Models\Concerns\Auditable;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('audit_test_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->text('content')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('audit_test_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function test_created_writes_audit_record_with_actor_and_meta(): void
    {
        $actor = AdminUser::factory()->create();

        $this->actingAs($actor);

        $post = AuditTestPost::query()->create([
            'title' => 'First Post',
            'status' => 'draft',
            'content' => 'hello',
        ]);

        $audit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('created', $audit->event);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertNull($audit->old_values);
        $this->assertSame('First Post', $audit->new_values['title']);
        $this->assertSame(AuditTestPost::class, $audit->auditable_type);
        $this->assertSame('audit_test_posts', $audit->meta['table']);
        $this->assertTrue($audit->auditable->is($post));
        $this->assertTrue($audit->actor->is($actor));
    }

    public function test_updated_only_records_changed_fields(): void
    {
        $post = AuditTestPost::query()->create([
            'title' => 'Original',
            'status' => 'draft',
        ]);

        Audit::query()->delete();

        $post->update([
            'title' => 'Updated',
            'status' => 'draft',
        ]);

        $audit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('updated', $audit->event);
        $this->assertSame(['title' => 'Original'], $audit->old_values);
        $this->assertSame(['title' => 'Updated'], $audit->new_values);
    }

    public function test_deleted_restored_and_force_deleted_capture_snapshots(): void
    {
        $post = AuditTestPost::query()->create([
            'title' => 'Disposable',
            'status' => 'draft',
        ]);

        Audit::query()->delete();

        $post->delete();

        $deletedAudit = Audit::query()->latest('id')->firstOrFail();
        $this->assertSame('deleted', $deletedAudit->event);
        $this->assertSame('Disposable', $deletedAudit->old_values['title']);
        $this->assertNull($deletedAudit->new_values);

        $post->restore();

        $restoredAudit = Audit::query()->latest('id')->firstOrFail();
        $this->assertSame('restored', $restoredAudit->event);
        $this->assertArrayHasKey('deleted_at', $restoredAudit->old_values);
        $this->assertSame(['deleted_at' => null], $restoredAudit->new_values);

        Audit::query()->delete();

        $forceDeletedPost = AuditTestPost::query()->create([
            'title' => 'Force Delete',
            'status' => 'draft',
        ]);

        Audit::query()->delete();

        $forceDeletedPost->forceDelete();

        $forceDeletedAudit = Audit::query()->latest('id')->firstOrFail();
        $this->assertSame('force_deleted', $forceDeletedAudit->event);
        $this->assertSame('Force Delete', $forceDeletedAudit->old_values['title']);
        $this->assertNull($forceDeletedAudit->new_values);
    }

    public function test_custom_event_can_be_written_manually(): void
    {
        $post = AuditTestPost::query()->create([
            'title' => 'Custom Event',
            'status' => 'pending',
        ]);

        Audit::query()->delete();

        AuditLogger::custom(
            $post,
            'approved',
            old: ['status' => 'pending'],
            new: ['status' => 'approved'],
            meta: ['reason' => 'manual_review_passed'],
        );

        $audit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('approved', $audit->event);
        $this->assertSame(['status' => 'pending'], $audit->old_values);
        $this->assertSame(['status' => 'approved'], $audit->new_values);
        $this->assertSame('manual_review_passed', $audit->meta['reason']);
    }

    public function test_audit_except_is_respected(): void
    {
        $user = AuditTestUser::query()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret',
            'remember_token' => 'token',
            'status' => 'pending',
        ]);

        $createAudit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('created', $createAudit->event);
        $this->assertSame([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => '[已隐藏]',
            'remember_token' => '[已隐藏]',
        ], $createAudit->new_values);

        Audit::query()->delete();

        $user->delete();

        $deleteAudit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('deleted', $deleteAudit->event);
        $this->assertSame([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => '[已隐藏]',
            'remember_token' => '[已隐藏]',
        ], $deleteAudit->old_values);
        $this->assertNull($deleteAudit->new_values);
    }

    public function test_bulk_operations_and_quiet_methods_do_not_trigger_model_events(): void
    {
        $post = AuditTestPost::query()->create([
            'title' => 'Bulk Case',
            'status' => 'draft',
        ]);

        Audit::query()->delete();

        AuditTestPost::query()
            ->whereKey($post->id)
            ->update(['title' => 'Bulk Updated']);

        $post->forceFill(['title' => 'Quiet Updated'])->saveQuietly();

        AuditTestPost::withoutEvents(function () use ($post): void {
            $post->forceFill(['title' => 'No Events'])->save();
        });

        $this->assertSame(0, Audit::query()->count());
    }
}

class AuditTestPost extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'audit_test_posts';

    protected $guarded = [];
}

class AuditTestUser extends Model
{
    use Auditable;

    protected $table = 'audit_test_users';

    protected $guarded = [];

    /**
     * @return array<int, string>
     */
    public function auditExcept(): array
    {
        $fields = [
            $this->getKeyName(),
            'status',
        ];

        $createdAtColumn = $this->getCreatedAtColumn();

        if (is_string($createdAtColumn) && $createdAtColumn !== '') {
            $fields[] = $createdAtColumn;
        }

        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (is_string($updatedAtColumn) && $updatedAtColumn !== '') {
            $fields[] = $updatedAtColumn;
        }

        return array_values(array_unique($fields));
    }
}
