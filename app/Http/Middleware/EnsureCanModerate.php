<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the /admin dashboard to admins and moderators. Registered as the
 * `can.moderate` route-middleware alias.
 */
class EnsureCanModerate
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canModerate(), 403);

        return $next($request);
    }
}
