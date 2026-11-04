<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                        ->orWhere('email', 'like', "%{$search}%");
                }))
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user) => [
                    ...$user->only('id', 'name', 'email', 'is_admin'),
                    'joined' => $user->created_at?->format('d M Y'),
                ]),
            'search' => $search,
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Form', [
            'user' => $user->only('id', 'name', 'email'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->fill($request->only('name', 'email'));

        if ($validated['password'] ?? null) {
            $user->password = $validated['password'];
        }

        $user->save();

        return to_route('admin.users.index')->with('status', 'User updated.');
    }
}
