<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_permission_middleware_authorizes_using_runtime_permission_discovery(): void
    {
        Permission::findOrCreate('user.read', 'web');

        $authorizedUser = $this->createUserWithPermissions(['user.read']);
        $forbiddenUser = $this->createUserWithPermissions(['role.read']);

        $this->actingAs($authorizedUser)
            ->get('/users')
            ->assertOk();

        $this->actingAs($forbiddenUser)
            ->get('/users')
            ->assertForbidden();
    }
}
