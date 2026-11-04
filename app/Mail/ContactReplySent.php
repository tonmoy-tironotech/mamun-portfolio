<?php

namespace App\Mail;

use App\Models\ContactMessageReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplySent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public ContactMessageReply $reply) {}

    public function envelope(): Envelope
    {
        $adminEmail = User::where('is_admin', true)->value('email');

        return new Envelope(
            replyTo: $adminEmail ? [$adminEmail] : [],
            subject: "Re: {$this->reply->contactMessage->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-reply',
            text: 'mail.contact-reply-text',
        );
    }
}
