From: {{ $contactMessage->name }} ({{ $contactMessage->email }})
Subject: {{ $contactMessage->subject }}

{{ $contactMessage->message }}

Admin panel: {{ route('admin.messages.show', $contactMessage) }}
