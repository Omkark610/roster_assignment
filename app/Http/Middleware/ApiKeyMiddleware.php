<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware {
    public function handle(Request $request, Closure $next) {
        if ($request->header('X-API-KEY') !== env('API_KEY')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}