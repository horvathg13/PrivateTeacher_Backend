<?php

namespace App\Helper;

use App\Models\Roles;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class Permission
{
    /*static $student_object_types = [
        'WRITE-STUDENT',
        "READ",
       
    ];*/
    public static function checkPermissionForSchoolService($permission, int $schoolId){
        $user = JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        if($permission==="WRITE"){
            $getAdmin= Roles::where("name", "Admin")->pluck('id')->first();
            $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();
           
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getAdmin || $role['role_id'] === $getTeacher) && ($role['reference_id'] ? $role['reference_id'] === $schoolId : true)){
                   return true;
                }
            } 
            return false;
        }

        /*if($object_type==='WRITE_STUDENT'){
            $school_id = //todo kiszámoljuk student_id-ból;
        }
        if(in_array($permission, $getRoles)){
            
            return true;
        }else{
            return false;
        }*/
    }

   

    /*public static function x(){
        if(!PermissionController::checkPermissionStudent("WRITE-STUDENT", 520, 52, "2022-05-01")){
            throw new \Exception("Nincs jogosultság");
        }

        if(PermissionController::checkPermission("admin;teacher=52;szulo="));
    }*/
}
