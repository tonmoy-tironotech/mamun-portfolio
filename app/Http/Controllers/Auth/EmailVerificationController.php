<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return to_route('student');
        }

        return Inertia::render('auth/VerifyEmail', [
            'email' => $user->email,
            'resendIn' => $user->verificationCodeCooldown(),
            'expiresInMinutes' => User::VERIFICATION_CODE_MINUTES,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return to_route('student');
        }

        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $user->confirmEmailWithCode($validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'That code is not valid or has expired. Check it, or send a new one.',
            ]);
        }

        return to_route('student')->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return to_route('student');
        }

        if ($user->verificationCodeCooldown() > 0) {
            return back();
        }

        $user->sendEmailVerificationCode();

        return back()->with('status', "We sent a new code to {$user->email}.");
    }
}
