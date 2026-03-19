<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_settings_read_permission_can_view_the_settings_page(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 4));
    }

    public function test_users_without_settings_permission_cannot_view_the_settings_page(): void
    {
        $user = $this->createUserWithPermissions(['users.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertForbidden();
    }
}
