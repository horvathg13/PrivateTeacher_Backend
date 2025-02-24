<?php

namespace App\Http\Middleware;

use App\Exceptions\ControllerException;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenRefressMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
           $user=JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $request->headers->set('Authorization', 'Bearer ' . $newToken);
            $success=["token"=>$newToken];

            return response()->json($success);
        }
        return $next($request);
    }
}
