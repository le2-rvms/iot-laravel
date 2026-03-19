<?php

namespace App\Http\Middleware;

use App\Support\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeControllerPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route || ! method_exists($route, 'getControllerClass')) {
            throw new \LogicException('The controller.permission middleware can only be used on controller routes.');
        }

        [$controllerClass, $actionMethod] = Str::parseCallback($route->getActionName(), '__invoke');

        if (! $controllerClass || ! $actionMethod) {
            throw new \LogicException('Unable to resolve the controller action for permission authorization.');
        }

        $permission = PermissionRegistry::permissionForControllerAction($controllerClass, $actionMethod);

        Gate::forUser($request->user())->authorize($permission);

        return $next($request);
    }
}
