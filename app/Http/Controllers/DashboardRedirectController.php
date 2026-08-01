<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = request()->user();

        abort_unless($user, 403);

        $role = $user->primaryRole();

        abort_unless($role instanceof RoleName, 403);

        return redirect()->route($role->dashboardRoute());
    }
}
