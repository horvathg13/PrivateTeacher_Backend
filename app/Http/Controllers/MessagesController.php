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
                            $query= Messages::where(["teacher_course_request_id" => $c->id, "receiver_id" => $user->id])
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
                    $success=[
                        "header"=>['request_id'=>false, "child_name"=>false, "course_name"=>false, "teacher_name"=>false, "status"=>false],
                        "data"=>$data
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
            $success=[
                "header"=>['request_id'=>false, "child_name"=>false, "course_name"=>false, "teacher_name"=>false, "status"=>false],
                "data"=>$data
            ];
            return response()->json($success);
        }
    }

    public function getMessageInfo($Id){
        $user=JWTAuth::parseToken()->authenticate();
        $getMessages = [];
        $getChildCourse = [];
        if(Permission::checkPermissionForParents('READ',null)) {
            $getChildren = ChildrenConnections::where('parent_id', $user->id)->with('childInfo')->get();
            if ($getChildren->isNotEmpty()) {
                foreach ($getChildren as $child) {
                    $getChildCourse= TeacherCourseRequests::where(['id' => $Id, 'child_id' => $child->childInfo->id])->first();
                }
            }
        }
        if(Permission::checkPermissionForTeachers('READ',null, null)) {
            $getChildCourse= TeacherCourseRequests::where(['id' => $Id])->first();
        }

        if ($getChildCourse) {
            $getCourseName=CourseLangsNames::where('course_id', $getChildCourse->teacher_course_id)->first();
            $query = Messages::where(["teacher_course_request_id" => $Id])
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('receiver_id', $user->id);
                })
                ->with('courseInfo')
                ->with('senderInfo')
                ->with('childInfo')
            ->get();

            foreach ($query as $message){
                if(ReadMessages::where('message_id', $message->id)->doesntExist()){
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
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('WRITE',$request->childId)) {
            $getChildCourse= TeacherCourseRequests::where(['id' => $request->Id, 'child_id' => $request->childId])->exists();

            if ($getChildCourse) {
                DB::transaction(function () use ($request, $user) {
                    Messages::create([
                        'teacher_course_request_id' => $request->Id,
                        'sender_id' => $user->id,
                        'receiver_id' => $request->teacherId,
                        'message' => $request->message,
                    ]);

                    return response()->json(__('messages.success'));
                });

            }else{
                return response()->json(__('messages.error'));
            }
        }

        $getCourseId=TeacherCourseRequests::where(['id' => $request->Id])->value('teacher_course_id');

        if(Permission::checkPermissionForTeachers('WRITE',$getCourseId, null)){
            try {
                DB::transaction( function () use ($request, $user) {
                    Messages::create([
                        'teacher_course_request_id' => $request->Id,
                        'sender_id' => $user->id,
                        'receiver_id' => $request->teacherId,
                        'message' => $request->message,
                    ]);
                    return response()->json(__('messages.success'));
                });
            }catch (\Exception $exception){
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
            }

        }
        return response()->json(__('messages.denied.permission'),500);

    }
}
