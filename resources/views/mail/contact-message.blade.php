<p><strong>From:</strong> {{ $contactMessage->name }} ({{ $contactMessage->email }})</p>
<p><strong>Subject:</strong> {{ $contactMessage->subject }}</p>
<hr>
<p>{!! nl2br(e($contactMessage->message)) !!}</p>
<hr>
<p>Reply to this email to answer directly, or <a href="{{ route('admin.messages.show', $contactMessage) }}">open it in the admin panel</a>.</p>
