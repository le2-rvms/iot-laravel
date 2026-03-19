<?php

namespace Tests\Feature\Users;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_users_can_view_the_users_index(): void
    {
        $admin = $this->createSuperAdmin();

        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 4)
                ->where('filters.search', '')
                ->where('auth.access', fn ($access) => ($access['users.read'] ?? false) === true)
                ->missing('auth.roles'));
    }

    public function test_unverified_users_cannot_access_the_users_index(): void
    {
        $admin = User::factory()->unverified()->create();

        $this->actingAs($admin)
            ->get('/users')
            ->assertRedirect('/email/verify');
    }

    public function test_verified_users_can_view_the_dashboard(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.usersCount', 1));
    }

    public function test_users_can_be_created_and_receive_a_verification_email(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdmin();
        $role = \Spatie\Permission\Models\Role::create([
            'name' => 'Editor',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['users.read']);

        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'New User',
                'email' => 'new-user@example.com',
                'password' => 'password',
                'roles' => ['Editor'],
            ])
            ->assertRedirect('/users');

        $user = User::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame('New User', $user->name);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Editor'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_updated_and_email_changes_reset_verification(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdmin();
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $role = \Spatie\Permission\Models\Role::create([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['users.read', 'users.write']);

        $this->actingAs($admin)
            ->put("/users/{$user->id}", [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'password' => '',
                'roles' => ['Manager'],
            ])
            ->assertRedirect("/users/{$user->id}/edit");

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Manager'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/users/{$user->id}")
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_users_can_be_filtered_by_search(): void
    {
        $admin = $this->createSuperAdmin();

        User::factory()->create([
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
        ]);

        User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/users?search=alice')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('filters.search', 'alice')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'alice@example.com'));
    }

    public function test_users_index_supports_partial_reload_props(): void
    {
        $admin = $this->createSuperAdmin();
        User::factory()->create([
            'name' => 'Partial Match',
            'email' => 'partial@example.com',
        ]);

        $version = app(HandleInertiaRequests::class)->version(request()) ?? '';

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'Users/Index',
                'X-Inertia-Partial-Data' => 'users,filters',
            ])
            ->get('/users?search=partial')
            ->assertOk()
            ->assertJsonPath('component', 'Users/Index')
            ->assertJsonPath('props.filters.search', 'partial')
            ->assertJsonPath('props.users.data.0.email', 'partial@example.com');
    }

    public function test_read_only_users_can_view_users_but_cannot_modify_them(): void
    {
        $user = $this->createUserWithPermissions(['users.read']);
        $target = User::factory()->create();

        $this->actingAs($user)
            ->get('/users')
            ->assertOk();

        $this->actingAs($user)
            ->get('/users/create')
            ->assertForbidden();

        $this->actingAs($user)
            ->delete("/users/{$target->id}")
            ->assertForbidden();
    }

    public function test_users_with_write_permission_can_execute_user_writes(): void
    {
        Notification::fake();

        $user = $this->createUserWithPermissions(['users.write']);

        $this->actingAs($user)
            ->post('/users', [
                'name' => 'Writer Created',
                'email' => 'writer-created@example.com',
                'password' => 'password',
                'roles' => [],
            ])
            ->assertRedirect('/users');

        $created = User::where('email', 'writer-created@example.com')->firstOrFail();

        $this->actingAs($user)
            ->put("/users/{$created->id}", [
                'name' => 'Writer Updated',
                'email' => 'writer-updated@example.com',
                'password' => '',
                'roles' => [],
            ])
            ->assertRedirect("/users/{$created->id}/edit");

        $this->actingAs($user)
            ->delete("/users/{$created->id}")
            ->assertRedirect('/users');
    }
}
