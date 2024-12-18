<?php

namespace App\Helper;

use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\CourseLocations;
use App\Models\Roles;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class Permission
{
    public static function checkPermissionForSchoolService($permission){
        $user = JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        if($permission==="WRITE"){
            $getAdmin= Roles::where("name", "Admin")->pluck('id')->first();
            $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();

            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getAdmin || $role['role_id'] === $getTeacher)){
                   return true;
                }
            }
            return false;
        }
    }
    public static function checkPermissionForParentOrTeacher($permission){
        $user = JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        if($permission==="READ"){
            $getParent= Roles::where("name", "Parent")->pluck('id')->first();
            $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();

            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getParent || $role['role_id'] === $getTeacher)){
                   return true;
                }
            }
            return false;
        }
    }

    public static function checkPermissionForChildren($permission, int $schoolId = null, int $childId = null){
        $user = JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();
        $Parent= Roles::where("name", "Parent")->first();

        if($permission==="GENERATE"){

            foreach($getUserRoles as $role){

                if($role['role_id'] === $Parent['id']){
                   return true;
                }
            }
            return false;
        }
        if($permission==="WRITE"){

            foreach($getUserRoles as $role){
                if($role['role_id'] === $Parent['id'] && ($role['reference_id'] ? $role['reference_id'] === $schoolId : true)){
                    $getConnection = ChildrenConnections::where(["parent_id"=>$user->id, "child_id"=>$childId])->first();

                    if($getConnection){
                        return true;
                    }
                }
            }
            return false;
        }
    }
    public static function checkPermissionForAdmin(){
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getAdmin=Roles::where("name", "Admin")->pluck('id')->first();

        foreach($getUserRoles as $role){
            if(($role['role_id'] === $getAdmin)){
                return true;
            }
        }
        return false;

    }
    public static function checkPermissionForTeachers($permission, $courseId, $locationId){
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();
        if($permission==="READ"){
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getTeacher)){
                    return true;
                }
            }
            return false;
        }
        if($permission==='WRITE'){
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getTeacher)){
                    if(!$courseId && $locationId){
                        $getCourseIds=CourseInfos::where('teacher_id', $user->id)->pluck('id');
                        foreach ($getCourseIds as $getCourseId) {
                            if(CourseLocations::where(['course_id'=>$getCourseId, 'location_id'=>$locationId])->exists()){
                                return true;
                            };
                        }
                    }
                    if(!$locationId && $courseId){
                        $validateCourse=CourseInfos::where(['teacher_id'=> $user->id, 'id'=>$courseId])->exists();
                        if($validateCourse) {
                            return true;
                        }
                    }
                    if($courseId && $locationId){
                        $validateCourse=CourseInfos::where(['teacher_id'=> $user->id, 'id'=>$courseId])->first()->pluck('id');
                        $validateLocation= CourseLocations::where(['course_id'=>$validateCourse, 'location_id'=>$locationId])->exists();
                        if($validateLocation) {
                            return true;
                        }
                    }
                }
            }
        }
    }
    public static function checkPermissionForParents($permission, $childId){
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getParent=Roles::where("name", "Parent")->pluck('id')->first();

        if($permission==="READ"){
            foreach($getUserRoles as $role){
                if($role['role_id'] === $getParent){
                    return true;
                }
            }
            return false;
        }

        if($permission==="WRITE"){
            foreach($getUserRoles as $role){
                if($role['role_id'] === $getParent){
                    if(ChildrenConnections::where(["parent_id"=>$user->id, "child_id" => $childId])->exists()){
                        return true;
                    };
                }
            }
            return false;
        }

    }
}
