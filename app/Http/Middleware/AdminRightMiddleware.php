<?php

namespace App\Http\Middleware;

use App\Models\Roles;
use App\Models\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminRightMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getAdmin=Roles::where("name", "Admin")->pluck('id')->first();

        foreach($getUserRoles as $role){
            if(($role['role_id'] === $getAdmin)){
                return $next($request);
            }
        }
        return throw new \Exception(__("messages.denied.permission"),401);
    }
}
