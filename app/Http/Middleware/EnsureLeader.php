<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLeader
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isLeader() || ! $user->leader) {
            abort(403);
        }

        return $next($request);
    }
}
