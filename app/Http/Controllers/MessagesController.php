<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\CommonRequests;
use App\Models\CourseInfos;
use App\Models\CourseLangsNames;
use App\Models\Messages;
use App\Models\ReadMessages;
use App\Models\StudentCourse;
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
        if($user->isParent()){
            $getChildren=ChildrenConnections::where('parent_id',$user->id)->with('childInfo')->get();
            if($getChildren->isNotEmpty()) {
                foreach ($getChildren as $child) {
                    $getChildCourses[] = StudentCourse::where(['child_id' => $child->childInfo->id])->get();
                }
                if($getChildCourses){
                    foreach ($getChildCourses as $course){
                        foreach ($course as $c) {
                            $query= Messages::where(["student_course_id" => $c->id])
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
                            $getCourseName = CourseLangsNames::where('course_id',$m->courseInfo['id'])->get();
                            $getTeacherName = User::where('id', $m->courseInfo['teacher_id'])->first();

                            if(ReadMessages::where(['message_id'=> $m['id'], 'read_by' => $user->id])->doesntExist()){
                                $data[]=[
                                    "id"=>$m['student_course_id'],
                                    "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                                    "course_name"=>$getCourseName,
                                    "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                                    "status"=>"UNREAD",
                                ];
                            }else{
                                $data[]=[
                                    "id"=>$m['student_course_id'],
                                    "child_name"=>$m->childInfo->first_name . ' '. $m->childInfo->last_name,
                                    "course_name"=>$getCourseName,
                                    "teacher_name"=>$getTeacherName->first_name . ' '. $getTeacherName->last_name,
                                    "status"=>"READ",
                                ];
                            }
                        }
                    }
                }
            }
        }
        if($user->isTeacher()) {
            $getTeacherCourses = CourseInfos::where(['teacher_id' => $user->id, 'course_status' => "ACTIVE"])->pluck('id');

            $getRequestedCourses = StudentCourse::whereIn('teacher_course_id', $getTeacherCourses)->pluck('id');

            if ($getRequestedCourses) {
                foreach ($getRequestedCourses as $requestedCourse) {
                    $query = Messages::where(["student_course_id" => $requestedCourse])
                        ->with('courseInfo')
                        ->with('senderInfo')
                        ->with('childInfo')
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    if ($query->isNotEmpty()) {
                        $getMessages[] = $query;
                    }
                }
            }
            foreach ($getMessages as $message) {
                foreach ($message as $m) {
                    $getCourseName = CourseLangsNames::where('course_id', $m->courseInfo['id'])->get();
                    $getTeacherName = User::where('id', $m->courseInfo['teacher_id'])->first();

                    if (ReadMessages::where(['message_id' => $m['id'], 'read_by' => $user->id])->doesntExist()) {
                        $data[] = [
                            "id" => $m['student_course_id'],
                            "child_name" => $m->childInfo->first_name . ' ' . $m->childInfo->last_name,
                            "course_name" => $getCourseName,
                            "teacher_name" => $getTeacherName->first_name . ' ' . $getTeacherName->last_name,
                            "status" => "UNREAD",
                        ];
                    } else {
                        $data[] = [
                            "id" => $m['student_course_id'],
                            "child_name" => $m->childInfo->first_name . ' ' . $m->childInfo->last_name,
                            "course_name" => $getCourseName,
                            "teacher_name" => $getTeacherName->first_name . ' ' . $getTeacherName->last_name,
                            "status" => "READ",
                        ];
                    }
                }
            }
        }
        $filtering=array_values(array_unique($data, SORT_REGULAR));
        $success=[
            "header"=>[
                "id"=>false,
                "name"=>false,
                "course_name"=>false,
                "teacher_name"=>false,
                "status"=>false,
            ],
            "data"=>$filtering
        ];

        return response()->json($success);

    }

    public function getMessageInfo($Id,$childId=null){
        $validation=Validator::make(["messageId"=>$Id, "childId"=>$childId],[
            "messageId"=>"required|numeric|exists:messages,student_course_id",
            "childId"=>"nullable"
        ],[
            "messageId.required"=>__("validation.custom.messageId.required"),
            "messageId.numeric"=>__("validation.custom.messageId.numeric"),
            "messageId.exists"=>__("validation.custom.messageId.exists"),
            "childId.numeric"=>__("validation.custom.childId.numeric"),
            "childId.exists"=>__("validation.custom.childId.exists"),
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
        if(
            (Permission::checkPermissionForTeachers("WRITE", null, $Id)) ||
            (isset($childId) && Permission::checkPermissionForParents("WRITE", $childId))
        ){
            $getChildCourse= StudentCourse::where(['id' => $Id])->first();
        }else{
            throw new ControllerException(__("messages.denied.role"));
        }

        if ($getChildCourse) {
            $getCourseName=CourseLangsNames::where('course_id', $getChildCourse->teacher_course_id)->
            where("lang", "=", $getChildCourse->language)->pluck("name")->first();
            $query = Messages::where(["student_course_id" => $Id])
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
                    "courseName"=>$getCourseName,
                    "data"=>$query
                ];
            }

            return response()->json($getMessages);
        }
    }

    public function sendMessage(Request $request){
        $validation=Validator::make($request->all(),[
            "message"=>"required|max:255",
            'Id'=>"required|exists:student_course,id",
            "childId"=>"required|numeric|exists:children,id"
        ],[
            "message.required"=>__("validation.custom.message.required"),
            "message.max"=>__("validation.custom.message.max"),
            "Id.required"=>__("validation.custom.student_course.id.required"),
            "childId.required"=>__("validation.custom.childId.required"),
            "childId.numeric"=>__("validation.custom.childId.numeric"),
            "childId.exists"=>__("validation.custom.childId.exists"),
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('WRITE',$request->childId)) {
            $getChildCourse= StudentCourse::where(['id' => $request->Id, 'child_id' => $request->childId])->exists();
            if ($getChildCourse) {
                    DB::transaction(function () use ($request, $user) {
                        try {
                            Messages::create([
                                'student_course_id' => $request->Id,
                                'sender_id' => $user->id,
                                'receiver_id' => $request->teacherId,
                                'message' => $request->message,
                            ]);
                        }catch (\Exception $e){
                            event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                            throw new ControllerException(__("messages.error"));
                        }
                    });

                return response()->json(__('messages.success'));
            }else{
                return response()->json(__('messages.error'));
            }
        }

        if(Permission::checkPermissionForTeachers('WRITE',null, $request->Id)){

                DB::transaction( function () use ($request, $user) {
                    try {
                        Messages::create([
                            'student_course_id' => $request->Id,
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
        event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
        return response()->json(__('messages.denied.permission'),403);

    }

    public function getMessageControl($childId, $studentCourseId){
        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForParents('WRITE',$childId)){
            $validateStudentCourseId=StudentCourse::where(['id'=>$studentCourseId, 'child_id' => $childId])->exists();

            if(!$validateStudentCourseId){
                event(new ErrorEvent($user,'GET', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__('messages.error'));
            }
            if(Messages::where(['student_course_id' => $studentCourseId, 'sender_id' => $user->id])->exists()){
                return $this->getMessageInfo($studentCourseId, $childId);
            }else {
                $courseInfo = StudentCourse::where('id', $studentCourseId)
                    ->with('courseInfos')
                    ->with('courseNamesAndLangs')
                ->first();

                $success=[
                    "teacher_id"=> $courseInfo->courseInfos->teacher_id,
                    "courseName"=>$courseInfo->courseNamesAndLangs->where("lang", "=", $courseInfo->language)->pluck("name")->first()
                ];

                return response()->json($success);
            }
        }
        if(Permission::checkPermissionForTeachers("WRITE", null, $studentCourseId)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, "course_status"=>"ACTIVE"])->pluck('id');
            $getStudentCourse=StudentCourse::where('id', "=", $studentCourseId)->first();
            if($getStudentCourse->child_id == $childId){
                if(Messages::where(['student_course_id' => $studentCourseId, 'sender_id' => $user->id])->exists()){
                    return $this->getMessageInfo($studentCourseId, $childId);
                }else {
                    $success=[
                        "teacher_id"=> $getStudentCourse->courseInfos->teacher_id,
                        "courseName"=>$getStudentCourse->courseNamesAndLangs
                    ];

                    return response()->json($success);
                }
            }else{
                event(new ErrorEvent($user,'GET', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__('messages.error'));
            }
        }
    }
    public function accessToMessages(Request $request){
        $user=JWTAuth::parseToken()->authenticate();

        $validation=Validator::make($request->all(),[
            'Id'=>"required|exists:student_course,id",
        ],[
            "Id.required"=>__("validation.custom.student_course.id.required"),
            "Id.exists"=>__("validation.custom.student_course.id.exists")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        if(Permission::checkPermissionForTeachers("WRITE", NULL, $request->Id)){
            return $this->getMessageInfo($request->Id['id']);
        }
        $getChildId=StudentCourse::where(['id'=>$request->Id])->pluck('child_id')->first();
        if(Permission::checkPermissionForParents("WRITE", $getChildId)){
            return $this->getMessageInfo($request->Id['id'], $getChildId);
        }
        throw new ControllerException("messages.denied.permission",403);

    }
}
