<?php

namespace App\Http\Controllers;

use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\Messages;
use App\Models\ReadMessages;
use App\Models\TeacherCourseRequests;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\isEmpty;

class MessagesController extends Controller
{
    public function get()
    {
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents('READ',null)){
            $getChildren=ChildrenConnections::where('parent_id',$user->id)->with('childInfo')->get();
            $getChildCourses=[];
            $getMessages=[];
            $data=[];
            if($getChildren->isNotEmpty()) {
                foreach ($getChildren as $child) {
                    $getChildCourses[] = TeacherCourseRequests::where(['child_id' => $child->childInfo->id, "status" !== "UNDER_REVIEW"])->get();
                }
                if(!isEmpty($getChildCourses)){
                    foreach ($getChildCourses as $course){
                        $getMessages[]=Messages::where(["teacher_course_request_id"=>$course->id, "receiver_id" => $user->id])
                            ->with('courseInfo')
                            ->with('senderInfo')
                            ->with('childInfo')
                            ->orderBy('updated_at','desc')
                        ->get();
                    }
                    foreach ($getMessages as $message){
                        if(ReadMessages::where('message_id', $message->id)->doesntExist()){
                            $data[]=$message;
                        }
                    }
                    $success=[
                        "header"=>['id', "child_name", "course_name", "teacher_name", "created_at"],
                        "data"=>$data
                    ];
                    return response()->json($success);
                }
            }
        }
    }
}
