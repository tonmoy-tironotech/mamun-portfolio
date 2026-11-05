<?php

namespace App\Http\Middleware;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Admin pages use their own root template, without the public site's styles.
     */
    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : $this->rootView;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // A different version forces a full reload, so moving between the
        // public site and the admin panel swaps their stylesheets too
        return parent::version($request).'-'.$this->rootView($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => ['user' => $request->user()?->only('id', 'name', 'email', 'is_admin')],
            'flash' => fn () => ['status' => $request->session()->get('status')],
            'unreadMessages' => fn () => $request->is('admin', 'admin/*') && $request->user()?->is_admin
                ? ContactMessage::whereNull('read_at')->count()
                : null,
            'pendingUsers' => fn () => $request->is('admin', 'admin/*') && $request->user()?->is_admin
                ? User::pending()->count()
                : null,
        ];
    }
}
