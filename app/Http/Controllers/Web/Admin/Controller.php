<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

abstract class Controller extends BaseController
{
    protected function renderPage(array $props = []): Response
    {
        return Inertia::render(
            $this->inferInertiaPage(request()->route()?->getActionName() ?? ''),
            $props,
        );
    }

    private function inferInertiaPage(string $actionName): string
    {
        [$controllerClass, $action] = Str::parseCallback($actionName, '__invoke');

        if (! is_string($controllerClass) || $controllerClass === '' || ! is_string($action) || $action === '') {
            throw new LogicException('Unable to resolve the current controller action for Inertia page inference.');
        }

        $controllerName = class_basename($controllerClass);

        $segments[] = $resourceName = Str::of($controllerName)->beforeLast('Controller')->value();

        if ($action !== '__invoke') {
            $segments[] = Str::studly($action);
        }

        return implode('/', $segments);
    }
}
