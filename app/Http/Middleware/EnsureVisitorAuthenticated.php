<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVisitorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->get('visitor_id')) {
            return redirect()->route('visitor.login');
        }

        return $next($request);
    }
}
