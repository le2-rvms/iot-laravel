<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevQuickLoginController extends Controller
{
    public function __invoke(Request $request, AdminUser $adminUser): RedirectResponse
    {
        abort_unless(app()->environment('dev'), 404);

        Auth::login($adminUser);
        $request->session()->regenerate();

        return redirect()->to(config('fortify.home'));
    }
}
