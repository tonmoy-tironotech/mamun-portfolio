<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplySent;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('admin/messages/Index', [
            'messages' => ContactMessage::query()
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                }))
                ->withCount('replies')
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (ContactMessage $message) => [
                    ...$message->only('id', 'name', 'email', 'subject'),
                    'preview' => Str::limit($message->message, 80),
                    'is_read' => $message->read_at !== null,
                    'is_replied' => $message->replies_count > 0,
                    'received' => $message->created_at->format('d M Y, g:i A'),
                ]),
            'search' => $search,
        ]);
    }

    public function show(ContactMessage $message): Response
    {
        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return Inertia::render('admin/messages/Show', [
            'contactMessage' => [
                ...$message->only('id', 'name', 'email', 'subject', 'message'),
                'received' => $message->created_at->format('d M Y, g:i A'),
            ],
            'replies' => $message->replies()->oldest('id')->get()->map(fn (ContactMessageReply $reply) => [
                'id' => $reply->id,
                'body' => $reply->body,
                'sent' => $reply->created_at->format('d M Y, g:i A'),
            ]),
        ]);
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $reply = $message->replies()->create($request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]));

        Mail::to($message->email, $message->name)->queue(new ContactReplySent($reply));

        return back()->with('status', "Reply is on its way to {$message->email}.");
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return to_route('admin.messages.index')->with('status', 'Message deleted.');
    }
}
