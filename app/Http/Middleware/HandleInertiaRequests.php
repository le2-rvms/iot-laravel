<?php

namespace App\Http\Middleware;

use App\Support\NavigationRegistry;
use App\Support\PermissionRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Str;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
            ],
            'navigation' => [
                'sections' => fn () => NavigationRegistry::sidebarFor($request->user()),
            ],
            'auth' => [
                'user' => fn () => $request->user()?->only('id', 'name', 'email', 'email_verified_at'),
                'access' => fn () => PermissionRegistry::accessMap($request->user()),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success') ?? $request->session()->get('status'),
                'success_key' => fn () => ($request->session()->has('success') || $request->session()->has('status'))
                    ? Str::uuid()->toString()
                    : null,
                'error' => fn () => $request->session()->get('error'),
                'error_key' => fn () => $request->session()->has('error')
                    ? Str::uuid()->toString()
                    : null,
            ],
        ];
    }
}
