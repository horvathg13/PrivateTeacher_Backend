<?php

namespace App\Http\Middleware;

use App\Models\Roles;
use App\Models\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ParentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getParent=Roles::where("name", "Parent")->pluck('id')->first();

        foreach($getUserRoles as $role){
            if(($role['role_id'] === $getParent)){
                return $next($request);
            }
        }
        return throw new \Exception(__("messages.denied.permission"),401);
    }
}
