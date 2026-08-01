<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChildPinLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChildPinAuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/child-login', [
            'demoLearners' => app()->environment(['local', 'testing']) ? [
                ['label' => 'Amara', 'learner_id' => 'CB-LEARN-1001', 'pin' => '1234'],
                ['label' => 'Noah', 'learner_id' => 'CB-LEARN-1002', 'pin' => '2468'],
            ] : [],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(ChildPinLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
