<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    private const SENT = 'Thank you! Your message has been sent.';

    public function store(Request $request): RedirectResponse
    {
        // Hidden from people, so only bots fill it in. They get the normal answer.
        if ($request->filled('website')) {
            return back()->with('status', self::SENT);
        }

        $contactMessage = ContactMessage::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]));

        $adminEmail = User::where('is_admin', true)->value('email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new ContactMessageReceived($contactMessage));
        }

        return back()->with('status', self::SENT);
    }
}
