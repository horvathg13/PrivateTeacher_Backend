<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\CourseInfos;
use App\Models\StudentCourse;
use App\Models\StudentCourseTeachingDays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentCourseController extends Controller
{
    public function get($id)
    {

        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForTeachers("READ", null,null)){
            $validator = Validator::make(["studentCourseId"=>$id], [
                "studentCourseId"=>"required|numeric|exists:student_course,id",
            ],[
                "studentCourseId.required"=>__("validation.custom.courseId.required"),
                "studentCourseId.numeric"=>__("validation.custom.courseId.numeric"),
                "studentCourseId.exists"=>__("validation.custom.courseId.exists")
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            $getTeacherCourseId=StudentCourse::where('id',$id)->pluck('teacher_course_id')->first();
            if($getTeacherCourseId && Permission::checkPermissionForTeachers("WRITE", $getTeacherCourseId,null)){
                $getStudentCourseInfo=StudentCourse::where('id',$id)
                    ->with('courseInfo')
                    ->with('childInfo')
                    ->with('parentInfo')
                    ->with('teachingDays')
                ->first();

                if($getStudentCourseInfo){
                    return response()->json($getStudentCourseInfo);
                }
            }else{
                event(new ErrorEvent($user,'GET', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
            }

            throw new ControllerException(__("messages.error"),500);
        }
        event(new ErrorEvent($user,'GET', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        throw new ControllerException(__("messages.denied.permission"),500);
    }
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            "studentCourseId"=>"required|numeric|exists:student_course,id",
            "start_date"=>"required|date",
            "end_date"=>"required|date|after:start_date",
            "teaching_day"=>"required|string",
            "from"=>"required",
            "to"=>"required|after:from"
        ],[
            "studentCourseId.required"=>__("validation.custom.courseId.required"),
            "studentCourseId.numeric"=>__("validation.custom.courseId.numeric"),
            "studentCourseId.exists"=>__("validation.custom.courseId.exists"),
            "start_date.required"=>__("validation.custom.courseRequest.start.required"),
            "start_date.date"=>__("validation.custom.courseRequest.start.date"),
            "end_date.required"=>__("validation.custom.courseRequest.end.required"),
            "end_date.date"=>__("validation.custom.courseRequest.end.date"),
            "end_date.after"=>__("validation.custom.courseRequest.end.after"),
            "from.required"=>__("validation.custom.from.required"),
            "to.required"=>__("validation.custom.to.required"),
            "to.after"=>__("validation.custom.to.after"),
            "teaching_day.required"=>__("validation.custom.teaching_day.required"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getTeacherCourseId=StudentCourse::where('id',$request->studentCourseId)->pluck('teacher_course_id')->first();
        if($getTeacherCourseId && Permission::checkPermissionForTeachers("WRITE", $getTeacherCourseId,null)){
            try {
                DB::transaction(function () use($request,$user,$getTeacherCourseId){
                    $getTeacherCourseId->update([
                        "start_date"=>$request->start_date,
                        "end_date"=>$request->end_date
                    ]);
                    $getTeachingDays=StudentCourseTeachingDays::where('student_course_id',$request->studentCourseId)->first();
                    $getTeachingDays?->update([
                        "teaching_day" => $request->teaching_day,
                        "from" => $request->from,
                        "to" => $request->to
                    ]);
                });
                return response()->json([__("messages.success")]);
            }catch(\Exception $e){
                event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.error"),500);
            }
        }else{
            event(new ErrorEvent($user,'Update', '403', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.error"),500);
        }
    }

}
