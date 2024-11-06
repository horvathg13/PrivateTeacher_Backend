<?php

namespace App\Http\Controllers;

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
    public function get(){
        $user = JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForTeachers('READ',null,null)) {
            $getCourses = CourseInfos::where('teacher_id', $user->id)->with('courseNamesAndLangs')->get();

            $courseRequests = [];
            foreach ($getCourses as $course) {
                $findRequests = TeacherCourseRequests::where(['teacher_course_id' => $course->id, "status" => "UNDER_REVIEW"])->exists();
                if ($findRequests) {
                    $courseRequests[] = TeacherCourseRequests::where(['teacher_course_id' => $course->id, "status" => "UNDER_REVIEW"])
                        ->with('childInfo')
                        ->with('parentInfo')
                        ->with('courseNamesAndLangs')
                        ->orderBy('created_at', 'desc')
                        ->get();

                }
            }
            $finalData=[];
            foreach ($courseRequests as $courseRequest) {
                foreach ($courseRequest as $item) {
                    $finalData[] = [
                        "id" => $item->id,
                        "number_of_lessons" => $item->number_of_lessons,
                        "notice" => $item->notice,
                        "created_at" => $item->created_at,
                        "updated_at" => $item->updated_at,
                        "status" => __("enums.$item->status"),
                        "child_info" => $item->childInfo,
                        "course_names_and_langs" => $item->courseNamesAndLangs
                    ];
                }
            }
            $tableHeader = [
                "id", "child_name", "course_name", "request_date",'status'
            ];

            $success = [
                "data" => $finalData,
                "header" => $tableHeader,
            ];

            return response()->json($success);
        }
        if(Permission::checkPermissionForParents('READ',null)){
            $getChildren=ChildrenConnections::where(['parent_id'=>$user->id])->pluck('child_id');
            $data=[];

            $getRequests=TeacherCourseRequests::whereIn('child_id',$getChildren)
                ->with('childInfo')
                ->with('courseNamesAndLangs')
                ->orderBy('updated_at', 'desc')
                ->get();
            foreach ($getRequests as $request) {
                $data[]=[
                    "id"=>$request->id,
                    "number_of_lessons"=>$request->number_of_lessons,
                    "notice"=>$request->notice,
                    "created_at"=>$request->created_at,
                    "updated_at"=>$request->updated_at,
                    "status"=>__("enums.$request->status"),
                    "child_info"=>$request->childInfo,
                    "course_names_and_langs"=>$request->courseNamesAndLangs
                ];
            }
            $header=["id", "child", "course_name", "request_date", 'status'];
            $success=[
                "header"=>$header,
                "data"=>$data
            ];

            return response()->json($success);
        }
        return response()->json(__("messages.error"));
    }

    public function getRequestDetails(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:teacher_course_requests,id",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->first()->pluck('teacher_course_id');
        $getRequestChildId=TeacherCourseRequests::where('id',$request->requestId)->first()->pluck('child_id');

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
                "status"=>__("enums.$getRequestInfo->status"),
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
                "status"=>__("enums.$getRequestInfo->status"),
                "child_info"=>$getRequestInfo->childInfo,
                "course_info"=>$getRequestInfo->courseInfo,
                "course_names_and_langs"=>$getRequestInfo->courseNamesAndLangs
            ];

            return response()->json($success);

        }

        return response()->json(__('messages.denied.permission'));
    }

    public function accept(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:teacher_course_requests,id",
            "message"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->first()->pluck('teacher_course_id');

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId[0],null)){
            $findRequest=TeacherCourseRequests::where('id',$request->requestId)->with('parentInfo')->first();
            if($findRequest){
                DB::transaction(function() use($request, $findRequest, $user){
                    $findRequest->update([
                        "status"=>"ACCEPTED"
                    ]);
                    foreach ($findRequest->parentInfo as $parent) {
                        Messages::create([
                            "teacher_course_request_id"=>$findRequest->id,
                            "sender_id"=>$user->id,
                            "receiver_id"=>$parent->id,
                            "message" => $request->message
                        ]);
                        Notifications::create([
                            "receiver_id"=>$parent->id,
                            "message"=>__("messages.notification.accepted"),
                            "url"=>"/requests/".$findRequest->id,
                        ]);
                    }

                });

                return response()->json(__('messages.success'));
            }
        }
        return response()->json(__('messages.denied.role'));
    }
    public function reject(Request $request){
        $validator = Validator::make($request->all(), [
            "requestId"=>"required|exists:teacher_course_requests,id",
            "message"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();

        $getRequestCourseId=TeacherCourseRequests::where('id',$request->requestId)->first()->pluck('teacher_course_id');

        if(Permission::checkPermissionForTeachers('WRITE',$getRequestCourseId[0],null)){
            $findRequest=TeacherCourseRequests::where('id',$request->requestId)->with('parentInfo')->first();

            if($findRequest){
                DB::transaction(function() use($request, $findRequest, $user){
                    $findRequest->update([
                        "status"=>"REJECTED"
                    ]);
                    foreach ($findRequest->parentInfo as $parent) {
                        Messages::create([
                            "teacher_course_request_id"=>$findRequest->id,
                            "sender_id"=>$user->id,
                            "receiver_id"=>$parent->id,
                            "message" => $request->message
                        ]);
                        Notifications::create([
                            "receiver_id"=>$parent->id,
                            "message"=>__("messages.notification.rejected"),
                            "url"=>"/requests/".$findRequest->id,
                        ]);
                    }

                });

                return response()->json(__('messages.success'));
            }
        }
        return response()->json(__('messages.denied.role'));
    }

}
