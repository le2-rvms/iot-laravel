<?php

namespace Tests\Unit\Support;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Middleware\AuthorizeControllerPermission;
use App\Support\PermissionStructureBuilder;
use Illuminate\Support\Facades\Route;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PermissionStructureBuilderTest extends TestCase
{
    public function test_it_builds_a_locale_agnostic_permission_structure(): void
    {
        Route::middleware([AuthorizeControllerPermission::class])
            ->get('/__permission-structure-builder-probe', [StructureProbeController::class, 'index']);

        $groups = collect((new PermissionStructureBuilder())->build()['definitions'])->keyBy('module');

        $this->assertSame([
            'module' => 'structure-probe',
            'permissions' => [
                [
                    'name' => 'structure-probe.read',
                    'action' => 'read',
                ],
                [
                    'name' => 'structure-probe.write',
                    'action' => 'write',
                ],
            ],
        ], $groups['structure-probe']);
    }

    public function test_it_fails_during_structure_build_when_a_protected_action_is_missing_permission_action(): void
    {
        Route::middleware([AuthorizeControllerPermission::class])
            ->get('/__permission-structure-builder-missing-action', [MissingPermissionActionProbeController::class, 'index']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Missing #[PermissionAction] on controller action ['.MissingPermissionActionProbeController::class.'@index].',
        );

        (new PermissionStructureBuilder())->build();
    }

    public function test_it_includes_protected_route_controllers_outside_app_http_controllers(): void
    {
        Route::middleware([AuthorizeControllerPermission::class])
            ->get('/__permission-structure-builder-runtime-probe', RuntimeRouteProbeController::class);

        $groups = collect((new PermissionStructureBuilder())->build()['definitions'])->keyBy('module');

        $this->assertArrayHasKey('runtime-route-probe', $groups->all());
        $this->assertSame('runtime-route-probe.read', $groups['runtime-route-probe']['permissions'][0]['name']);
    }
}

#[PermissionGroup]
class MissingPermissionActionProbeController
{
    public function index(): Response
    {
        return response('missing');
    }
}

#[PermissionGroup]
class StructureProbeController
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        return response('index');
    }

    #[PermissionAction('write')]
    public function store(): Response
    {
        return response('store');
    }
}

#[PermissionGroup]
class RuntimeRouteProbeController
{
    #[PermissionAction('read')]
    public function __invoke(): Response
    {
        return response('runtime');
    }
}
