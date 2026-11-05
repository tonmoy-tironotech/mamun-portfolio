<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentIsApproved
{
    /**
     * Send students still under review back to the Student Corner page, which explains it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isApproved()) {
            return to_route('student');
        }

        return $next($request);
    }
}
