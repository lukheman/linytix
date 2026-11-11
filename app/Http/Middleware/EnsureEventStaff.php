<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEventStaff
{
    /**
     * Pastikan pengguna berperan staff dan ditugaskan pada event terkait.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $event = $request->route('event');

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $eventId = is_object($event) ? $event->getKey() : $event;

        $assigned = $user->isStaff()
            && $eventId
            && $user->eventAssignments()->whereKey($eventId)->exists();

        if (! $assigned) {
            abort(403, 'Anda tidak ditugaskan pada event ini.');
        }

        return $next($request);
    }
}
