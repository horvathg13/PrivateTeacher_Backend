<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\CourseLangsNames;
use App\Models\Messages;
use App\Models\ReadMessages;
use App\Models\TeacherCourseRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\isEmpty;

class MessagesController extends Controller
{
    public function get(Request $request)
    {
        $user=JWTAuth::parseToken()->authenticate();

        $getChildCourses=[];
        $getMessages=[];
        $data=[];
        if(Permission::checkPermissionForParents('READ',null)){
            $getChildren=ChildrenConnections::where('parent_id',$user->id)->with('childInfo')->get();
            if($getChildren->isNotEmpty()) {
                foreach ($getChildren as $child) {
                    $getChildCourses[] = TeacherCourseRequests::where(['child_id' => $child->childInfo->id])->get();
                }
                if($getChildCourses){
                    foreach ($getChildCourses as $course){
                        foreach ($course as $c) {
                            $query= Messages::where(["teacher_course_request_id" => $c->id])
                                ->with('courseInfo')
                                ->with('senderInfo')
                                ->with('childInfo')
                                ->orderBy('updated_at', 'desc')
                            ->get();

                            if($query->isNotEmpty()){
                                $getMessages[]=$query;
                            }
                        }
                    }
                    foreach ($getMessages as $message){
                        foreach ($message as $m){
                            $getCourseName = CourseLangsNames::where('course_id',$m->courseInfo['id'])->first();
                            $getTeacherName = User::where('id', $m->courseInfo['teacher_id'])->first();

                            if(ReadMessages::where(['message_id'=> $m['id'], 'read_by' => $user->id])->doesntExist()){
                                $data[]=[
                                    "id"=>$m['teacher_course_request_id'],
                                    "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                                    "course_name"=>$getCourseName->name,
                                    "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                                    "status"=>__("messages.status.unread"),
                                ];
                            }else{
                                $data[]=[
                                    "id"=>$m['teacher_course_request_id'],
                                    "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                                    "course_name"=>$getCourseName->name,
                                    "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                                    "status"=>__("messages.status.read"),
                                ];
                            }
                        }
                    }
                    $filtering=array_values(array_unique($data, SORT_REGULAR));
                    $success=[
                        "header"=>[
                            __("tableHeaders.id")=>false,
                            __("tableHeaders.name")=>false,
                            __("tableHeaders.course_name")=>false,
                            __("tableHeaders.teacher_name")=>false,
                            __("tableHeaders.status")=>false
                        ],
                        "data"=>$filtering
                    ];
                    return response()->json($success);
                }
            }
        }
        if(Permission::checkPermissionForTeachers('READ',null, null)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, 'course_status'=>"ACTIVE"])->pluck('id');

            $getRequestedCourses=TeacherCourseRequests::whereIn('teacher_course_id', $getTeacherCourses)->pluck('id');

            if($getRequestedCourses){
                foreach ($getRequestedCourses as $requestedCourse){
                    $query= Messages::where(["teacher_course_request_id" => $requestedCourse])
                        ->with('courseInfo')
                        ->with('senderInfo')
                        ->with('childInfo')
                        ->orderBy('updated_at', 'desc')
                    ->get();

                    if($query->isNotEmpty()){
                        $getMessages[]=$query;
                    }
                }
            }
            foreach ($getMessages as $message){
                foreach ($message as $m){
                    $getCourseName = CourseLangsNames::where('course_id',$m->courseInfo['id'])->first();
                    $getTeacherName = User::where('id', $m->courseInfo['teacher_id'])->first();

                    if(ReadMessages::where(['message_id'=> $m['id'], 'read_by' => $user->id])->doesntExist()){
                        $data[]=[
                            "id"=>$m['teacher_course_request_id'],
                            "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                            "course_name"=>$getCourseName->name,
                            "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                            "status"=>__("messages.status.unread"),
                        ];
                    }else{
                        $data[]=[
                            "id"=>$m['teacher_course_request_id'],
                            "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                            "course_name"=>$getCourseName->name,
                            "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                            "status"=>__("messages.status.read"),
                        ];
                    }
                }
            }
            $filtering=array_values(array_unique($data, SORT_REGULAR));
            $success=[
                "header"=>[__("tableHeaders.request_id")=>false,
                    __("tableHeaders.name")=>false,
                    __("tableHeaders.course_name")=>false,
                    __("tableHeaders.teacher_name")=>false,
                    __("tableHeaders.status")=>false],
                "data"=>$filtering
            ];
            return response()->json($success);
        }
    }

    public function getMessageInfo($Id, $childId){
        $validation=Validator::make(["messageId"=>$Id],[
            "messageId"=>"required|numeric|exists:messages,teacher_course_request_id"
        ],[
            "messageId.required"=>__("validation.custom.messageId.required"),
            "messageId.numeric"=>__("validation.custom.messageId.numeric"),
            "messageId.exists"=>__("validation.custom.messageId.exists")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        $getMessages = [];
        $getChildCourse = [];
        if(Permission::checkPermissionForParents('READ',null)) {
            if(is_null($childId)){
                $getChildCourse= TeacherCourseRequests::where(['id' => $Id, 'child_id' => $childId])->first();
            }else{
                $getChildCourse = TeacherCourseRequests::where(['id' => $Id])->first();
            }
        }
        if(Permission::checkPermissionForTeachers('READ',null, null)) {
            $getChildCourse= TeacherCourseRequests::where(['id' => $Id])->first();
        }
        if ($getChildCourse) {
            $getCourseName=CourseLangsNames::where('course_id', $getChildCourse->teacher_course_id)->first();
            $query = Messages::where(["teacher_course_request_id" => $Id])
                ->with('courseInfo')
                ->with('senderInfo')
                ->with('childInfo')
            ->get();

            foreach ($query as $message){
                if(ReadMessages::where(['message_id'=> $message->id, "read_by" => $user->id])->doesntExist()){
                    DB::transaction(function () use ($user, $message) {
                        ReadMessages::create([
                            "read_by" => $user->id,
                            "message_id"=>$message->id,
                        ]);
                    });
                }
            }

            if ($query->isNotEmpty()) {
                $getMessages=[
                    "userId"=>$user->id,
                    "courseName"=>$getCourseName['name'],
                    "data"=>$query
                ];
            }

            return response()->json($getMessages);
        }
    }

    public function sendMessage(Request $request){
        $validation=Validator::make($request->all(),[
            "message"=>"required|max:255",
            'Id'=>"required",
            "childId"=>"required"
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
            "Id.required"=>__("validation.custom.teacher_course_request_id.required")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('WRITE',$request->childId)) {
            $getChildCourse= TeacherCourseRequests::where(['id' => $request->Id, 'child_id' => $request->childId])->exists();
            if ($getChildCourse) {
                    DB::transaction(function () use ($request, $user) {
                        try {
                            Messages::create([
                                'teacher_course_request_id' => $request->Id,
                                'sender_id' => $user->id,
                                'receiver_id' => $request->teacherId,
                                'message' => $request->message,
                            ]);
                        }catch (\Exception $e){
                            event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                            throw $e;
                        }
                    });

                return response()->json(__('messages.success'));
            }else{
                return response()->json(__('messages.error'));
            }
        }

        $getCourseId=TeacherCourseRequests::where(['id' => $request->Id])->value('teacher_course_id');
        if(Permission::checkPermissionForTeachers('WRITE',$getCourseId, null)){

                DB::transaction( function () use ($request, $user) {
                    try {
                        Messages::create([
                            'teacher_course_request_id' => $request->Id,
                            'sender_id' => $user->id,
                            'receiver_id' => $request->teacherId,
                            'message' => $request->message,
                        ]);
                    }catch (\Exception $exception){
                        event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                    }
                });
            return response()->json(__('messages.success'));
        }
        return response()->json(__('messages.denied.permission'),500);

    }

    public function getMessageControl($childId, $requestId){
        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForParents('WRITE',$childId)){
            $validateRequest=TeacherCourseRequests::where(['id'=>$requestId, 'child_id' => $childId])->exists();

            if(!$validateRequest){
                event(new ErrorEvent($user,'GET', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new \Exception(__('messages.error'));
            }
            if(Messages::where(['teacher_course_request_id' => $requestId, 'sender_id' => $user->id])->exists()){
                return $this->getMessageInfo($requestId, $childId);
            }else {
                $courseInfo = TeacherCourseRequests::where('id', $requestId)->with('courseInfo')->with('courseNamesAndLangs')->first();

                $success=[
                    "teacher_id"=> $courseInfo->courseInfo->teacher_id,
                    "courseName"=>$courseInfo->courseNamesAndLangs[0]->name
                ];

                return response()->json($success);
            }
        }
        if(Permission::checkPermissionForTeachers("READ", null, null)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, "course_status"=>"ACTIVE"])->pluck('id');
            $validateRequest=TeacherCourseRequests::whereIn('teacher_course_id',$getTeacherCourses)
                ->where('status', "ACCEPTED")
                ->where('id', $requestId)
                ->where('child_id', $childId)
                ->with('childInfo')
                ->with('courseNamesAndLangs')
            ->first();

            if(!$validateRequest){
                event(new ErrorEvent($user,'GET', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new \Exception(__('messages.error'));
            }
            if(Messages::where(['teacher_course_request_id' => $requestId, 'sender_id' => $user->id])->exists()){
                return $this->getMessageInfo($requestId, $childId);
            }else {
                $success=[
                    "teacher_id"=> $validateRequest->courseInfo->teacher_id,
                    "courseName"=>$validateRequest->courseNamesAndLangs[0]->name
                ];

                return response()->json($success);
            }
        }
    }
}
