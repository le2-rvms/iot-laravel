<?php

namespace Tests\Feature\Auth;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Middleware\AuthorizeControllerPermission;
use App\Models\Auth\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ControllerPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'verified', AuthorizeControllerPermission::class])
            ->get('/_testing/controller-permission', [ControllerPermissionProbeController::class, 'index']);

        Route::middleware(['web', 'auth', 'verified', AuthorizeControllerPermission::class])
            ->get('/_testing/missing-controller-permission', [MissingPermissionActionProbeController::class, 'index']);
    }

    public function test_controller_permission_middleware_authorizes_using_controller_attributes(): void
    {
        Permission::findOrCreate('controller-permission-probe.read', 'web');

        $authorizedUser = $this->createUserWithPermissions(['controller-permission-probe.read']);
        $forbiddenUser = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($authorizedUser)
            ->get('/_testing/controller-permission')
            ->assertOk()
            ->assertSeeText('ok');

        $this->actingAs($forbiddenUser)
            ->get('/_testing/controller-permission')
            ->assertForbidden();
    }

    public function test_controller_permission_middleware_returns_server_error_when_action_attribute_is_missing(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->get('/_testing/missing-controller-permission')
            ->assertStatus(500);
    }
}

#[PermissionGroup('控制器权限探针')]
class ControllerPermissionProbeController
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        return response('ok');
    }
}

#[PermissionGroup('缺少动作权限探针')]
class MissingPermissionActionProbeController
{
    public function index(): Response
    {
        return response('missing');
    }
}
