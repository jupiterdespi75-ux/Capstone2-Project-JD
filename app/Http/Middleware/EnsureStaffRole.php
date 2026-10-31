<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStaffRole
{
    /**
     * Usage in routes: ->middleware('staff:admin') or ->middleware('staff:admin,guard')
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $userId = $request->session()->get('user_id');
        $userRole = $request->session()->get('user_role');

        if (!$userId || !in_array($userRole, $roles, true)) {
            return redirect()->route('staff.login');
        }

        return $next($request);
    }
}
