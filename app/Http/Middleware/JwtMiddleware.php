<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\Response as ApiResponse;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth('api');

        if(!$guard->check()) {
            return app(ApiResponse::class)->unauthorizedError(reason: 'Token Invalid');
        }

        return $next($request);
    }
}
