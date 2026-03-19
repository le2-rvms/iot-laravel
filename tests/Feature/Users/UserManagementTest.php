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
        $admin = User::factory()->create();

        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 4)
                ->where('filters.search', ''));
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
        $admin = User::factory()->create();

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

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'New User',
                'email' => 'new-user@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/users');

        $user = User::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame('New User', $user->name);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_updated_and_email_changes_reset_verification(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put("/users/{$user->id}", [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'password' => '',
            ])
            ->assertRedirect("/users/{$user->id}/edit");

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_deleted(): void
    {
        $admin = User::factory()->create();
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
        $admin = User::factory()->create();

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
        $admin = User::factory()->create();
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
}
