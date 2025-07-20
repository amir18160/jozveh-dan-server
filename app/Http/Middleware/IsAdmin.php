<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()->role;
        if ($role === 'admin') {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Admins only.', 'status' => "fail"], 403);
    }
}
