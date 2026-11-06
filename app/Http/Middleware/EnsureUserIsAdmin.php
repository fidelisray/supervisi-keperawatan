<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('admin.roles.*') && $user->hasPermission('manage_roles')) {
            return $next($request);
        }

        if ($request->routeIs('admin.incident-categories.*') && $user->hasPermission('manage_incident_categories')) {
            return $next($request);
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul ini.');
    }
}
