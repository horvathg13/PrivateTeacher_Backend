<?php

namespace App\Helper;

use App\Models\Children;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\CourseLocations;
use App\Models\Roles;
use App\Models\StudentCourse;
use App\Models\TeacherCourseRequests;
use App\Models\TeacherLocation;
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
    public static function checkPermissionForParentOrTeacher($permission, $childId=null){

        $user = JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getParent= Roles::where("name", "Parent")->pluck('id')->first();
        $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();

        if($permission==="READ"){
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getParent || $role['role_id'] === $getTeacher)){
                   return true;
                }
            }
            return false;
        }
        if($permission === "WRITE" && !is_null($childId)){
            foreach($getUserRoles as $role){
                if($role['role_id'] === $getParent){
                    return Permission::checkPermissionForParents("WRITE", $childId);
                }
                if($role['role_id'] === $getTeacher){
                    $getCourses=CourseInfos::where("teacher_id", $user->id)->pluck("id");

                    $getActiveStudentCourses=StudentCourse::whereIn("teacher_course_id", $getCourses)
                       ->where(function ($query) use($childId){
                           $query->where("child_id","=", $childId);
                           $query-> where("end_date", ">=", now());
                       })
                    ->exists();

                    if($getActiveStudentCourses){
                        return true;
                    }
                }

                return false;
            }
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
    public static function checkPermissionForTeachers($permission, $courseId=null, $studentCourseId=null, $locationId=null){
        $user=JWTAuth::parseToken()->authenticate();
        $getUserRoles= UserRoles::where("user_id",$user->id)->get();

        $getTeacher=Roles::where("name", "Teacher")->pluck('id')->first();
        if($permission==="READ"){
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getTeacher)){
                    if(isset($courseId) && !isset($studentCourseId)){
                        $validateTeacherCourse=CourseInfos::where(["id"=>$courseId, "teacher_id" => $user->id])->exists();
                        if($validateTeacherCourse){
                            return true;
                        }
                    }
                    if(!isset($courseId) && isset($studentCourseId)){
                        $getTeacherCourses=CourseInfos::where('teacher_id', "=", $user->id)->pluck('id');
                        $validateStudentCourse=StudentCourse::whereIn("teacher_course_id", $getTeacherCourses)
                            ->where('id', "=", $studentCourseId)
                        ->exists();
                        if($validateStudentCourse){
                            return true;
                        }
                    }
                    if(isset($courseId) && isset($studentCourseId)){
                        $validateStudentCourse=StudentCourse:: where('id', "=", $studentCourseId)
                            ->where("teacher_course_id", "=", $courseId)
                        ->exists();
                        if($validateStudentCourse){
                            return true;
                        }
                    }

                    if(isset($locationId) && (!isset($courseId) && !isset($studentCourseId))){
                        $getTeacherCourses=CourseInfos::where('teacher_id', "=", $user->id)->pluck('id');
                        $validateCourseLocation=CourseLocations::whereIn("course_id",$getTeacherCourses)
                            ->where('location_id', "=", $locationId)
                        ->exists();
                        if($validateCourseLocation){
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        if($permission==='WRITE'){
            foreach($getUserRoles as $role){
                if(($role['role_id'] === $getTeacher)){
                    if(isset($courseId) && !isset($studentCourseId)){
                        $validateTeacherCourse=CourseInfos::where(["id"=>$courseId, "teacher_id" => $user->id])
                            ->where("course_status", "!=", "DELETED")
                        ->exists();
                        if($validateTeacherCourse){
                            return true;
                        }
                    }
                    if(!isset($courseId) && isset($studentCourseId)){
                        $getTeacherCourses=CourseInfos::where(['teacher_id'=> $user->id,"course_status" => "ACTIVE"])->pluck('id');
                        $validateStudentCourse=StudentCourse::whereIn("teacher_course_id", $getTeacherCourses)
                            ->where('id', "=", $studentCourseId)
                            ->where("end_date", ">=", now())
                        ->exists();
                        if($validateStudentCourse){
                            return true;
                        }
                    }
                    if(isset($courseId) && isset($studentCourseId)){
                        $validateStudentCourse=StudentCourse::where('id', "=", $studentCourseId)
                            ->where("teacher_course_id", "=", $courseId)
                            ->where("end_date", ">=", now())
                        ->exists();
                        if($validateStudentCourse){
                            return true;
                        }
                    }
                    if(isset($locationId) && (!isset($courseId) && !isset($studentCourseId))){
                        $validateTeacherLocation=TeacherLocation::where("teacher_id", "=", $user->id)
                            ->where('location_id', "=", $locationId)
                        ->exists();
                        if($validateTeacherLocation){
                            return true;
                        }
                    }
                    if(isset($locationId) && isset($courseId)){
                        $getTeacherCourses=CourseInfos::where(['teacher_id'=> $user->id, "id"=>$courseId, "course_status" => "ACTIVE"])->first();
                        $validateCourseLocation=CourseLocations::where("course_id", "=", $getTeacherCourses->id)
                            ->where('location_id', "=", $locationId)
                        ->exists();
                        if($validateCourseLocation){
                            return true;
                        }
                    }
                }
            }
            return false;
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
    public static function menuButtonsAccess($user, array $getRoles)
    {
        $hasChild=ChildrenConnections::where(['parent_id' => $user->id])->exists();
        $success=[];
        if(in_array("Teacher", $getRoles) && !in_array("Parent", $getRoles)){
            $getTeacherCourses=CourseInfos::where("teacher_id", "=", $user->id)->pluck("id");
            $getTeacherCourseRequests=TeacherCourseRequests::whereIn("teacher_course_id", $getTeacherCourses)->exists();
            $getStudentCourses=StudentCourse::whereIn("teacher_course_id", $getTeacherCourses)->exists();
            $success[]=[
                "hasAccessRequests"=>$getTeacherCourseRequests,
                "hasAccessMessages"=>$getStudentCourses
            ];
        }

        if(in_array("Parent", $getRoles) && !in_array("Teacher", $getRoles)){
            if($hasChild){
                $getChildren=ChildrenConnections::where("parent_id", "=", $user->id)->pluck("child_id");
                $haveTeacherCourseRequests=TeacherCourseRequests::whereIn("child_id", $getChildren)->exists();
                $haveStudentCourse=false;

                if($haveTeacherCourseRequests){
                    $getTeacherCourseRequests=TeacherCourseRequests::whereIn("child_id", $getChildren)->pluck("id");
                    $haveStudentCourse=StudentCourse::whereIn("teacher_course_request_id", $getTeacherCourseRequests)->exists();
                }
                $success[]=[
                    "hasAccessRequests"=>$haveTeacherCourseRequests,
                    "hasAccessMessages"=> $haveStudentCourse
                ];
            }else{
                $success[]=[
                    "hasAccessRequests"=>false,
                    "hasAccessMessages"=>false
                ];
            }

        }

        if(in_array("Teacher", $getRoles) && in_array("Parent", $getRoles)){
            $getTeacherCourses=CourseInfos::where("teacher_id", "=", $user->id)->pluck("id");
            $getTeacherCourseRequests=TeacherCourseRequests::whereIn("teacher_course_id", $getTeacherCourses)->exists();
            $getStudentCourses=StudentCourse::whereIn("teacher_course_id", $getTeacherCourses)->exists();
            $success[]=[
                "hasAccessRequests"=>$getTeacherCourseRequests || $hasChild,
                "hasAccessMessages"=>$getStudentCourses || $hasChild
            ];
        }

        if(in_array("Admin", $getRoles) && !in_array("Teacher", $getRoles) && !in_array("Parent", $getRoles)){
            $success[]=[
                "hasAccessRequests"=>false,
                "hasAccessMessages"=>false
            ];
        }

        return $success;
    }
}
