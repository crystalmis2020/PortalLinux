<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        session(['user_id' => Auth::id()]);

        $request->user()?->forceFill([
            'is_login' => 'Yes',
            'last_login' => now(),
            'last_seen_at' => now(),
        ])->save();

        if (empty(Auth::user()?->profile_photo_path)) {
            session()->flash('show_profile_photo_modal', true);
        }

        return redirect()->intended(route('dashboard.index'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->forceFill([
            'is_login' => 'No',
            'last_seen_at' => now(),
        ])->save();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
