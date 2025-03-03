<?php

namespace App\Http\Middleware;

use App\Exceptions\ControllerException;
use App\Models\User;
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
           $findUser=User::where(['id'=> $user->id])->first();
           if($findUser->user_status != "ACTIVE"){
               throw new ControllerException(__("messages.denied.user.active"),403);
           }
        } catch (TokenExpiredException $e) {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $request->headers->set('Authorization', 'Bearer ' . $newToken);
            $success=["token"=>$newToken];

            return response()->json($success);
        }
        return $next($request);
    }
}
