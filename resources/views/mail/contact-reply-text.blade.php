{{ $reply->body }}

--
On {{ $reply->contactMessage->created_at->format('d M Y') }}, you wrote:

{{ $reply->contactMessage->message }}
