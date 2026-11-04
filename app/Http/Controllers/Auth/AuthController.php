<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('auth/Login');
    }

    public function login(Request $request): SymfonyResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if (! $request->user()->is_admin) {
            $target = redirect()->intended(route('student'))->getTargetUrl();

            // A file can't be the landing page after a login form
            return redirect(str_ends_with($target, '/download') ? route('student') : $target);
        }

        $dashboard = route('admin.dashboard');
        $target = redirect()->intended($dashboard)->getTargetUrl();

        // Admins always land in the admin panel, even if they first tried a student page.
        // It has its own stylesheet, so it needs a full page load.
        return Inertia::location(str_starts_with($target, $dashboard) ? $target : $dashboard);
    }

    public function showRegister(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $user = User::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]));

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('student');
    }

    public function logout(Request $request): SymfonyResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location(route('login'));
    }
}
