<?php

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_permission_middleware_authorizes_using_runtime_permission_discovery(): void
    {
        AdminPermission::findOrCreate('admin-user.read', 'web');

        $authorizedUser = $this->createUserWithPermissions(['admin-user.read']);
        $forbiddenUser = $this->createUserWithPermissions(['admin-role.read']);

        $this->actingAs($authorizedUser)
            ->get('/admin/admin-users')
            ->assertOk();

        $this->actingAs($forbiddenUser)
            ->get('/admin/admin-users')
            ->assertForbidden();
    }
}
