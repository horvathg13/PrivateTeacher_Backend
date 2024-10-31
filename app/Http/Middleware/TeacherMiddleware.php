<?php

namespace App\Http\Middleware;

use App\Models\Roles;
use App\Models\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();

        foreach($getUserRoles as $role){
            if(($role['role_id'] === $getTeacher)){
                return $next($request);
            }
        }
        return throw new \Exception(__("messages.denied.permission"),401);
    }
}
