<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_permission_users_receive_only_the_expected_access_map_and_quick_links(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.access', fn ($access) => ($access['dashboard.read'] ?? false) === true
                    && ($access['user.read'] ?? false) === false
                    && ($access['role.read'] ?? false) === false
                    && ($access['settings.read'] ?? false) === false)
                ->where('quickLinks', []));
    }
}
