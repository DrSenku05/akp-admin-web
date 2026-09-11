<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token && DB::table('users')->where('api_token', $token)->where('active', true)->exists(), 401, 'Authentication required.');
        return $next($request);
    }
}
