<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\Messages;
use App\Models\Notifications;
use App\Models\TeacherCourseRequests;
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

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->pluck('teacher_course_id');
        $getRequestChildId=TeacherCourseRequests::where('id',$request->requestId)->pluck('child_id');

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId[0], null)){
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

        if(Permission::checkPermissionForParents('WRITE',  $getRequestChildId[0])){
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
                DB::transaction(function() use($request, $findRequest, $user){
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
