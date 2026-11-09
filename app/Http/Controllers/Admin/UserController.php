<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                }))
                ->orderByRaw('case when is_admin = 0 and email_verified_at is not null and approved_at is null and rejected_at is null then 0 else 1 end')
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user) => [
                    ...$user->only('id', 'name', 'email', 'student_id', 'is_admin', 'member_status'),
                    'is_unverified' => ! $user->hasVerifiedEmail(),
                    'is_rejected' => $user->rejected_at !== null,
                    'is_pending' => $user->hasVerifiedEmail() && ! $user->isApproved() && $user->rejected_at === null,
                    'joined' => $user->created_at?->format('d M Y'),
                ]),
            'search' => $search,
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Form', [
            'user' => $user->only('id', 'name', 'email', 'is_admin', 'student_id', 'education_level', 'mobile', 'member_status', 'profile_photo'),
            'memberStatuses' => User::MEMBER_STATUSES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'student_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'student_id')->ignore($user)],
            'education_level' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'regex:'.User::MOBILE_PATTERN],
            'member_status' => ['sometimes', Rule::in(array_keys(User::MEMBER_STATUSES))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [], [
            'student_id' => 'student ID',
            'education_level' => 'education level',
        ]);

        $user->fill(Arr::only($validated, ['name', 'email', 'student_id', 'education_level', 'mobile']));

        if ($validated['password'] ?? null) {
            $user->password = $validated['password'];
        }

        // Not mass assignable, so sign up can never set it
        if (isset($validated['member_status'])) {
            $user->member_status = $validated['member_status'];
        }

        $user->save();

        return to_route('admin.users.index')->with('status', 'User updated.');
    }

    public function approve(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            return back()->with('status', "{$user->name} has not verified their email yet.");
        }

        $user->forceFill(['approved_at' => now(), 'rejected_at' => null])->save();

        return back()->with('status', "{$user->name} can now see the documents.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('status', 'The admin account cannot be rejected.');
        }

        $user->forceFill(['approved_at' => null, 'rejected_at' => now()])->save();

        return back()->with('status', "{$user->name} has been rejected.");
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->forceFill(['approved_at' => null])->save();

        return back()->with('status', "{$user->name} is back under review.");
    }

    public function destroyPhoto(User $user): RedirectResponse
    {
        $user->removeProfilePhoto();

        return back()->with('status', "{$user->name}'s photo was removed.");
    }
}
