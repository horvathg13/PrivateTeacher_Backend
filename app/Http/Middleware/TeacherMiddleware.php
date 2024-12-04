<?php

namespace App\Http\Middleware;

use App\Events\ErrorEvent;
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
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return throw new \Exception(__("messages.denied.permission"),401);
    }
}
