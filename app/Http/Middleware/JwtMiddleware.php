<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Não autenticado.',
            ], 401);
        }

        Auth::guard('api')->setUser($user);

        return $next($request);
    }
}
