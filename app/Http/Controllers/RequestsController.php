<?php

namespace App\Http\Controllers;

use App\Models\CourseInfos;
use App\Models\TeacherCourseRequests;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RequestsController extends Controller
{
    public function get(){
        $user=JWTAuth::parseToken()->authenticate();
        $getCourses=CourseInfos::where('teacher_id',$user->id)->with('courseNamesAndLangs')->get();

        $courseRequests=[];
        foreach ($getCourses as $course){
            $findRequests=TeacherCourseRequests::where(['teacher_course_id'=>$course->id, "status" => "UNDER_REVIEW"])->exists();
            if($findRequests){
                $courseRequests[]= TeacherCourseRequests::where(['teacher_course_id'=>$course->id, "status" => "UNDER_REVIEW"])
                    ->with('childInfo')
                    ->with('parentInfo')
                    ->with('courseNamesAndLangs')
                    ->get();

            }
        }

        $tableHeader=[
            "id","child_name", "course_name","request_date"
        ];

        $success=[
            "data"=>$courseRequests,
            "header"=>$tableHeader,
        ];

        return response()->json($success);
    }
}
