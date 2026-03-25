<?php

namespace Tests\Feature\Audits;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Audit;
use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Settings\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_audit_read_permission_can_view_the_audits_index(): void
    {
        $admin = $this->createUserWithPermissions(['audit.read']);
        $actor = AdminUser::factory()->create([
            'name' => '审计操作者',
            'email' => 'audit-actor@example.com',
        ]);

        Audit::query()->delete();

        $audit = Audit::query()->create([
            'auditable_type' => AdminUser::class,
            'auditable_id' => 99,
            'actor_id' => $actor->id,
            'event' => 'updated',
            'old_values' => ['name' => '旧名称'],
            'new_values' => ['name' => '新名称', 'email' => 'new@example.com'],
            'meta' => [
                'route' => 'admin.admin-users.update',
                'method' => 'PUT',
                'ip' => '127.0.0.1',
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/audits')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Index')
                ->where('filters.search__func', '')
                ->where('filters.event__eq', '')
                ->where('filters.auditable_type__eq', '')
                ->has('eventOptions', fn (Assert $options) => $options
                    ->where('0.value', 'updated')
                    ->where('0.label', '更新')
                    ->etc())
                ->has('resourceTypeOptions', fn (Assert $options) => $options
                    ->where('0.value', AdminUser::class)
                    ->where('0.label', '管理员用户')
                    ->etc())
                ->has('audits.data', 1)
                ->where('audits.data.0.id', $audit->id)
                ->where('audits.data.0.event', 'updated')
                ->where('audits.data.0.event_label', '更新')
                ->where('audits.data.0.resource_type_label', '管理员用户')
                ->where('audits.data.0.actor.name', '审计操作者')
                ->where('audits.data.0.route', 'admin.admin-users.update')
                ->where('audits.data.0.changed_fields', ['name', 'email'])
                ->where('audits.data.0.changes_count', 2)
                ->where('audits.data.0.change_summary', '{"名称":"旧名称 → 新名称","邮箱":"空 → new@example.com"}'));
    }

    public function test_users_without_audit_read_permission_cannot_view_the_audits_index(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get('/admin/audits')
            ->assertForbidden();
    }

    public function test_audits_can_be_filtered_by_search_callback(): void
    {
        $admin = $this->createUserWithPermissions(['audit.read']);
        $actor = AdminUser::factory()->create([
            'name' => 'Route Owner',
            'email' => 'route-owner@example.com',
        ]);

        Audit::query()->delete();

        Audit::query()->create([
            'auditable_type' => AdminUser::class,
            'auditable_id' => 41,
            'actor_id' => $actor->id,
            'event' => 'updated',
            'old_values' => ['name' => '旧'],
            'new_values' => ['name' => '新'],
            'meta' => ['route' => 'admin.admin-users.update', 'method' => 'PUT'],
        ]);

        Audit::query()->create([
            'auditable_type' => Config::class,
            'auditable_id' => 88,
            'actor_id' => null,
            'event' => 'approved',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
            'meta' => ['route' => 'admin.settings.application-configs.update', 'method' => 'PUT'],
        ]);

        $this->actingAs($admin)
            ->get('/admin/audits?search__func=Route%20Owner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search__func', 'Route Owner')
                ->has('audits.data', 1)
                ->where('audits.data.0.actor.name', 'Route Owner'));

        $this->actingAs($admin)
            ->get('/admin/audits?search__func=88')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search__func', '88')
                ->has('audits.data', 1)
                ->where('audits.data.0.auditable_id', 88));

        $this->actingAs($admin)
            ->get('/admin/audits?search__func=application-configs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search__func', 'application-configs')
                ->has('audits.data', 1)
                ->where('audits.data.0.event', 'approved'));
    }

    public function test_audits_can_be_filtered_by_event_and_resource_type(): void
    {
        $admin = $this->createUserWithPermissions(['audit.read']);

        Audit::query()->delete();

        Audit::query()->create([
            'auditable_type' => AdminUser::class,
            'auditable_id' => 1,
            'actor_id' => null,
            'event' => 'created',
            'old_values' => null,
            'new_values' => ['name' => 'One'],
            'meta' => ['route' => 'admin.admin-users.store', 'method' => 'POST'],
        ]);

        Audit::query()->create([
            'auditable_type' => Config::class,
            'auditable_id' => 2,
            'actor_id' => null,
            'event' => 'approved',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
            'meta' => ['route' => 'admin.settings.application-configs.update', 'method' => 'PUT'],
        ]);

        $this->actingAs($admin)
            ->get('/admin/audits?event__eq=approved')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.event__eq', 'approved')
                ->has('audits.data', 1)
                ->where('audits.data.0.event', 'approved')
                ->has('eventOptions', fn (Assert $options) => $options
                    ->where('0.value', 'approved')
                    ->etc()));

        $this->actingAs($admin)
            ->get('/admin/audits?auditable_type__eq='.urlencode(Config::class))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.auditable_type__eq', Config::class)
                ->has('audits.data', 1)
                ->where('audits.data.0.resource_type_label', '配置')
                ->has('resourceTypeOptions', fn (Assert $options) => $options
                    ->where('0.value', AdminUser::class)
                    ->where('1.value', Config::class)
                    ->where('1.label', '配置')
                    ->etc()));
    }

    public function test_empty_filter_values_are_ignored_when_building_the_query(): void
    {
        $admin = $this->createUserWithPermissions(['audit.read']);

        Audit::query()->delete();

        Audit::query()->create([
            'auditable_type' => AdminUser::class,
            'auditable_id' => 1,
            'actor_id' => null,
            'event' => 'created',
            'old_values' => null,
            'new_values' => ['name' => 'One'],
            'meta' => ['route' => 'admin.admin-users.store', 'method' => 'POST'],
        ]);

        Audit::query()->create([
            'auditable_type' => Config::class,
            'auditable_id' => 2,
            'actor_id' => null,
            'event' => 'approved',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
            'meta' => ['route' => 'admin.settings.application-configs.update', 'method' => 'PUT'],
        ]);

        $this->actingAs($admin)
            ->get('/admin/audits?event__eq=&auditable_type__eq=')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search__func', '')
                ->where('filters.event__eq', '')
                ->where('filters.auditable_type__eq', '')
                ->has('audits.data', 2));
    }

    public function test_audits_index_supports_partial_reload_props(): void
    {
        $admin = $this->createUserWithPermissions(['audit.read']);

        Audit::query()->delete();

        Audit::query()->create([
            'auditable_type' => AdminRole::class,
            'auditable_id' => 5,
            'actor_id' => null,
            'event' => 'deleted',
            'old_values' => ['name' => 'Old Role'],
            'new_values' => null,
            'meta' => ['route' => 'admin.admin-roles.destroy', 'method' => 'DELETE'],
        ]);

        $version = app(HandleInertiaRequests::class)->version(request()) ?? '';

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'Audits/Index',
                'X-Inertia-Partial-Data' => 'audits,filters',
            ])
            ->get('/admin/audits?event__eq=deleted')
            ->assertOk()
            ->assertJsonPath('component', 'Audits/Index')
            ->assertJsonPath('props.filters.event__eq', 'deleted')
            ->assertJsonPath('props.audits.data.0.event', 'deleted');
    }
}
