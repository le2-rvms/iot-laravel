<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'roles' => $user->roles->pluck('name')->sort()->values()->all(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Users/Index', [
            'filters' => [
                'search' => $search,
            ],
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'availableRoles' => $this->availableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::create(Arr::except($validated, ['roles']));

        $user->syncRoles($validated['roles'] ?? []);

        $user->sendEmailVerificationNotification();

        return to_route('users.index')->with('success', '用户已创建，并已发送验证邮件。');
    }

    public function edit(User $user): Response
    {
        $user->loadMissing('roles:id,name');

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            ],
            'availableRoles' => $this->availableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $emailChanged = array_key_exists('email', $validated) && $validated['email'] !== $user->email;

        if (blank($validated['password'] ?? null)) {
            $validated = Arr::except($validated, ['password']);
        }

        $roles = $validated['roles'] ?? [];
        $validated = Arr::except($validated, ['roles']);

        if ($emailChanged) {
            $user->forceFill([
                'email_verified_at' => null,
            ]);
        }

        $user->update($validated);
        $user->syncRoles($roles);

        if ($emailChanged) {
            $user->save();
            $user->sendEmailVerificationNotification();
        }

        return to_route('users.edit', $user)->with('success', '用户信息已更新。');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return to_route('users.index')->with('success', '用户已删除。');
    }

    /**
     * @return array<int, array{name: string}>
     */
    protected function availableRoles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Role $role) => [
                'name' => $role->name,
            ])
            ->all();
    }
}
