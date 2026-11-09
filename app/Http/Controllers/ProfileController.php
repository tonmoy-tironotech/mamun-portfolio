<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/Profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]));

        $emailChanged = $user->isDirty('email');

        if ($emailChanged && ! $user->is_admin) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationCode();

            return to_route('verification.notice');
        }

        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('status', 'Password changed.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $request->user()->replaceProfilePhoto($request->file('photo'));

        return back()->with('status', 'Profile photo updated.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $request->user()->removeProfilePhoto();

        return back()->with('status', 'Profile photo removed.');
    }
}
