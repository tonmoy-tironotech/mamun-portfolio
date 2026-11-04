<p>{!! nl2br(e($reply->body)) !!}</p>
<hr>
<p style="color: #64748b;">On {{ $reply->contactMessage->created_at->format('d M Y') }}, you wrote:</p>
<blockquote style="margin: 0; padding-left: 12px; border-left: 3px solid #d8dee8; color: #64748b;">{!! nl2br(e($reply->contactMessage->message)) !!}</blockquote>
