<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Helper\Student;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\Messages;
use App\Models\Notifications;
use App\Models\StudentCourse;
use App\Models\StudentCourseTeachingDays;
use App\Models\TeacherCourseRequests;
use App\Models\TerminationCourseRequests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Symfony\Component\String\b;

class RequestsController extends Controller
{
    public function get(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParentOrTeacher("READ")) {
            $status = $request->status ?: 'UNDER_REVIEW';
            $finalData = [];
            $getCourses = CourseInfos::where('teacher_id', $user->id)->with('courseNamesAndLangs')->get();
            if ($getCourses) {
                $courseRequests = [];
                foreach ($getCourses as $course) {
                    $findRequests = TeacherCourseRequests::where(['teacher_course_id' => $course->id, "status" => $status])->exists();
                    if ($findRequests) {
                        $courseRequests[] = TeacherCourseRequests::where(['teacher_course_id' => $course->id, "status" => $status])
                            ->with('childInfo')
                            ->with('parentInfo')
                            ->with('courseNamesAndLangs')
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                }

                foreach ($courseRequests as $courseRequest) {
                    foreach ($courseRequest as $item) {
                        $finalData[] = [
                            "id" => $item->id,
                            "number_of_lessons" => $item->number_of_lessons,
                            "notice" => $item->notice,
                            "created_at" => $item->created_at,
                            "updated_at" => $item->updated_at,
                            "status" => $item->status,
                            "child_info" => $item->childInfo,
                            "course_names_and_langs" => $item->courseNamesAndLangs
                        ];
                    }
                }
            }
            $getChildren = ChildrenConnections::where(['parent_id' => $user->id])->pluck('child_id');
            if ($getChildren) {
                $getRequests = TeacherCourseRequests::whereIn('child_id', $getChildren)
                    ->with('childInfo')
                    ->with('courseNamesAndLangs')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                foreach ($getRequests as $request) {
                    $finalData[] = [
                        "id" => $request->id,
                        "number_of_lessons" => $request->number_of_lessons,
                        "notice" => $request->notice,
                        "created_at" => $request->created_at,
                        "updated_at" => $request->updated_at,
                        "status" => $request->status,
                        "child_info" => $request->childInfo,
                        "course_names_and_langs" => $request->courseNamesAndLangs
                    ];
                }
            }
            $tableHeader = [
                "id", "name", "course_name", "request_date", "status"
            ];
            $arrayUnique=array_unique($finalData, SORT_REGULAR);
            $success = [
                "data" => $arrayUnique,
                "header" => $tableHeader,
            ];

            return response()->json($success);
        }else{
            throw new ControllerException(__("messages.denied.permission"),403);
        }
    }

    public function getRequestDetails(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|numeric|exists:teacher_course_requests,id",
        ],[
            "requestId.required"=>__("validation.custom.requestId.required"),
            "requestId.numeric"=>__("validation.custom.requestId.numeric"),
            "requestId.exists"=>__("validation.custom.requestId.exists"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->pluck('teacher_course_id')->first();
        $getRequestChildId=TeacherCourseRequests::where('id',$request->requestId)->pluck('child_id')->first();

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId, null)){
            $getRequestInfo=TeacherCourseRequests::where('id',$request->requestId)
                ->with("childInfo")
                ->with('parentInfo')
                ->with('courseInfo')
                ->with('courseNamesAndLangs')
            ->first();

            $success=[
                "id"=>$getRequestInfo->id,
                "number_of_lessons"=>$getRequestInfo->number_of_lessons,
                "notice"=>$getRequestInfo->notice,
                "created_at"=>$getRequestInfo->created_at,
                "updated_at"=>$getRequestInfo->updated_at,
                "status"=>$getRequestInfo->status,
                "teacher_justification"=>$getRequestInfo->teacher_justification,
                "child_info"=>$getRequestInfo->childInfo,
                "parent_info"=>$getRequestInfo->parentInfo,
                "course_info"=>$getRequestInfo->courseInfo,
                "course_names_and_langs"=>$getRequestInfo->courseNamesAndLangs
            ];

            return response()->json($success);
        }

        if(Permission::checkPermissionForParents('WRITE',  $getRequestChildId)){
            $getRequestInfo=TeacherCourseRequests::where('id',$request->requestId)
                ->with('courseInfo')
                ->with('childInfo')
                ->with('courseNamesAndLangs')
            ->first();

            $success=[
                "id"=>$getRequestInfo->id,
                "number_of_lessons"=>$getRequestInfo->number_of_lessons,
                "notice"=>$getRequestInfo->notice,
                "created_at"=>$getRequestInfo->created_at,
                "updated_at"=>$getRequestInfo->updated_at,
                "status"=>$getRequestInfo->status,
                "teacher_justification"=>$getRequestInfo->teacher_justification,
                "child_info"=>$getRequestInfo->childInfo,
                "course_info"=>$getRequestInfo->courseInfo,
                "course_names_and_langs"=>$getRequestInfo->courseNamesAndLangs
            ];

            return response()->json($success);

        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.permission'),403);
    }

    public function accept(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|numeric|exists:teacher_course_requests,id",
            "message"=>"required|max:255",
            "start"=>"required|date",
            "teaching_day_details"=>"required|array",
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
            "start.required"=>__("validation.custom.courseRequest.start.required"),
            "start.date"=>__("validation.custom.courseRequest.start.date"),
            "teaching_day_details.required"=>__("validation.custom.teaching_day_details.required"),
            "teaching_day_details.required_array_keys"=>__("validation.custom.teaching_day_details.required_array_keys"),
            "teaching_day_details.required_array_keys.from"=>__("validation.custom.from.required"),
            "teaching_day_details.required_array_keys.to"=>__("validation.custom.to.required"),
            "teaching_day_details.required_array_keys.teaching_day"=>__("validation.custom.teaching_day.required"),
            "requestId.required"=>__("validation.custom.requestId.required"),
            "requestId.numeric"=>__("validation.custom.requestId.numeric"),
            "requestId.exists"=>__("validation.custom.requestId.exists"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        foreach ($request->teaching_day_details as $e){
            if($e['to'] <= $e['from']){
                throw new ControllerException(__("validation.custom.to.after"));
            }
        }

        $isUnique=count($request->teaching_day_details) === count(array_unique($request->teaching_day_details, SORT_REGULAR));
        if(!$isUnique){
            throw new ControllerException(__('validation.custom.teaching_day_details.teaching_day.unique'));
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->pluck('teacher_course_id')->first();

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId,null)){
            $findRequest=TeacherCourseRequests::where('id',$request->requestId)->with('parentInfo')->first();
            $validateStudentCourse=StudentCourse::where([
                "child_id" => $findRequest->child_id,
                "teacher_course_id" => $findRequest->teacher_course_id
            ])->where("end_date", ">", now())->exists();
            if($validateStudentCourse){
                throw new ControllerException(__("messages.attached.exists"),409);
            }
            if($findRequest){
                $getCourseEndDate=CourseInfos::where("id", $findRequest->teacher_course_id)->pluck('end_date')->first();
                if($request->start && $getCourseEndDate){
                    $validateDates=$request->start <= $getCourseEndDate;
                    if(!$validateDates){
                        $validatorResponse=[
                            "validatorResponse"=>[__("messages.error")]
                        ];
                        return response()->json($validatorResponse,422);
                    }
                }
                $studentLimit=Student::checkLimit($findRequest->teacher_course_id, $request->start, $getCourseEndDate);
                if($studentLimit['message'] === "error"){
                    $studentLimit['goodDate']?
                    throw new ControllerException(__("messages.studentLimit.goodDay", ["goodDay"=>$studentLimit['goodDate']]))
                    : throw new ControllerException(__("messages.studentLimit.null"));
                }
                DB::transaction(function() use($request, $findRequest, $user, $getCourseEndDate){
                    try {
                        $findRequest->update([
                            "status"=>"ACCEPTED",
                            "teacher_justification"=>$request->message
                        ]);
                        foreach ($findRequest->parentInfo as $parent) {
                            Notifications::create([
                                "receiver_id"=>$parent->id,
                                "message"=>__("messages.notification.accepted"),
                                "url"=>"/requests/".$findRequest->id,
                            ]);
                        }
                        $createStudentCourse=StudentCourse::insertGetId([
                            "teacher_course_request_id" => $findRequest->id,
                            "child_id" => $findRequest->child_id,
                            "teacher_course_id" => $findRequest->teacher_course_id,
                            "start_date" => $request->start,
                            "end_date" => $getCourseEndDate,
                            "created_at" => now(),
                            "updated_at" => now()
                        ]);
                        foreach ($request->teaching_day_details as $e){
                            StudentCourseTeachingDays::create([
                                "student_course_id"=>$createStudentCourse,
                                "teaching_day_id"=>$e->teaching_day,
                                "from"=>$e->from,
                                "to"=>$e->to
                            ]);
                        }

                    }catch (\Exception $e){
                        event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                    }
                });

                return response()->json(__('messages.success'));
            }
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.role'),403);
    }
    public function reject(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:teacher_course_requests,id",
            "message"=>"required|max:255"
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->pluck('teacher_course_id')->first();

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId,null)){
            $findRequest=TeacherCourseRequests::where('id',$request->requestId)->with('parentInfo')->first();

            if($findRequest){
                try {
                    DB::transaction(function() use($request, $findRequest, $user){
                        $findRequest->update([
                            "status"=>"REJECTED",
                            "teacher_justification"=>$request->message
                        ]);
                        foreach ($findRequest->parentInfo as $parent) {
                            Notifications::create([
                                "receiver_id"=>$parent->id,
                                "message"=>__("messages.notification.rejected"),
                                "url"=>"/requests/".$findRequest->id,
                            ]);
                        }
                    });
                }catch (\Exception $e){
                    event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                }

                return response()->json(__('messages.success'));
            }
        }
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.role'),403);
    }
    public function getChildRequests($childId){
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('WRITE', $childId)){
            $getRequests=TeacherCourseRequests::where('child_id',$childId)->with('courseNamesAndLangs')->get();
            $success=[];
            foreach ($getRequests as $request) {
                $success[]=[
                    "value"=>$request->id,
                    "label"=>$request->courseNamesAndLangs[0]->name
                ];
            }

            return response()->json($success);
        }
        if(Permission::checkPermissionForTeachers("READ", null,null)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, "course_status"=>"ACTIVE"])->pluck('id');
            $getRequests=TeacherCourseRequests::whereIn('teacher_course_id',$getTeacherCourses)
                ->where('status', "ACCEPTED")
                ->where('child_id',$childId)
                ->with('childInfo')
                ->with('courseNamesAndLangs')
            ->get();
            $success=[];
            foreach ($getRequests as $request) {
                $success[]=[
                    "value"=>$request->id,
                    "label"=>$request->courseNamesAndLangs[0]->name
                ];
            }
            return response()->json($success);
        }
    }
}
