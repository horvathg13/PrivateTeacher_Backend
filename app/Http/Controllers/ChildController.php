<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Helper\Permission;
use App\Models\Children;
use App\Models\ChildrenConnections;
use App\Models\CourseInfos;
use App\Models\TeacherCourseRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChildController extends Controller
{
    public function index()
    {

    }
    public function createChild(Request $request){
        if(Permission::checkPermissionForChildren("GENERATE")){
            $validator = Validator::make($request->all(), [
                "fname"=>"required",
                "lname"=>"required",
                "username"=>"required|unique:children,username",
                "birthday"=>"required|date",
                "psw"=>"required",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            try{
                DB::transaction(function() use($request){
                    Children::create([
                        "first_name"=>$request->fname,
                        "last_name"=>$request->lname,
                        "username"=>$request->username,
                        "password"=>bcrypt($request->psw),
                        "birthday"=>$request->birthday
                    ]);
                });
            }catch(\Exception $e){
                throw $e;
            }
            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.error"), json_encode(debug_backtrace())));
            throw new \Exception(__("messages.denied.role"));
        }

    }

    public function connectToChild(Request $request){
        if(Permission::checkPermissionForChildren("GENERATE")){
            $user = JWTAuth::parseToken()->authenticate();
            $validator = Validator::make($request->all(), [
                "username"=>"required",
                "psw"=>"required",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            $checkChild = Children::where("username",$request->username)->first();
            if($checkChild && Hash::check($request->psw, $checkChild->password)){
                DB::transaction(function() use($checkChild, $user){
                    ChildrenConnections::insert([
                        "parent_id"=>$user->id,
                        "child_id"=>$checkChild['id']
                    ]);
                });
            }else{
                event(new ErrorEvent($user,'Auth failed', '401', __("messages.error"), json_encode(debug_backtrace())));
                throw new \Exception(__("auth.failed"));
            }
            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.error"), json_encode(debug_backtrace())));
            throw new \Exception(__("messages.denied.role"));
        }
    }

    public function getConnectedChildren(){
        $user = JWTAuth::parseToken()->authenticate();

        $getChildren= ChildrenConnections::where("parent_id",$user->id,)->get();

        if($getChildren){
            $data=[];
            foreach($getChildren as $c){
                $getChildData = Children::where("id", $c["child_id"])->first();

                if($getChildData){
                    $data[]=  [
                        "id"=>$getChildData->id,
                        "firstname"=>$getChildData->first_name,
                        "lastname"=>$getChildData->last_name,
                        "birthday"=>$getChildData->birthday
                    ];


                }else{
                    throw new \Exception(__("messages.error"));
                }
            }

            $header=["Firstname", "Lastname", "Birthday"];

            $success=[
                "header"=>$header,
                "data"=>$data,
            ];
            return response()->json($success,200);
        }else{
            throw new \Exception(__("messages.notFound.child"));
        }
    }

    public function getChildInfo($childId){
        $user=JWTAuth::parseToken()->authenticate();
        $validateConnection= ChildrenConnections::where(["parent_id" => $user->id, "child_id"=>$childId])->exists();

        if($validateConnection){
            $getChildData=Children::where('id',$childId)->first();

            $data=[
                "firstname"=>$getChildData->first_name,
                "lastname"=>$getChildData->last_name,
                "birthday"=>$getChildData->birthday,
                "username"=>$getChildData->username,
            ];

            return response()->json($data,200);
        }else{
            throw new \Exception(__("messages.notFound.child"));
        }
    }

    public function updateChildInfo(Request $request){
        $validator = Validator::make($request->all(), [
            "childId"=>"required|exists:children,id",
            "userInfo"=>"required",
            "userInfo.first_name"=>"required",
            "userInfo.last_name"=>"required",
            "userInfo.birthday"=>"required",
            "userInfo.username"=>"required",
            "password"=>"nullable",
            "confirmPassword"=>"nullable|same:password",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        $checkParent=ChildrenConnections::where(['parent_id'=>$user->id, "child_id" => $request->childId])->exists();
        if(!$checkParent){
            event(new ErrorEvent($user,'Not Found', '404', __("messages.error"), json_encode(debug_backtrace())));
            throw new \Exception(__("messages.notFound.child"));
        }
        $getChildData=Children::where('id',$request->childId)->first();

        if($getChildData){
            DB::transaction(function() use($getChildData, $request){
                $getChildData->update([
                    "first_name"=>$request->userInfo['first_name'],
                    "last_name"=>$request->userInfo['last_name'],
                    "birthday"=>$request->userInfo['birthday'],
                    "username"=>$request->userInfo['username'],
                    "password" => bcrypt($request->password),
                ]);
            });
            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Not Found', '404', __("messages.error"), json_encode(debug_backtrace())));
            throw new \Exception(__("messages.notFound.child"));
        }
    }
    public function getChildSelect(Request $request ){
        $user=JWTAuth::parseToken()->authenticate();
        $getChildren=ChildrenConnections::where(['parent_id'=>$user->id])->pluck('child_id');

        if(!empty($getChildren)) {
            $getChildData = Children::whereIn('id', $getChildren)->get();

            $select = [];
            foreach ($getChildData as $c) {
                $select[] = [
                    "value" => $c['id'],
                    "label" => $c['first_name'] . ' ' . $c['last_name']
                ];
            }
            return response()->json($select);
        }else{
            return response()->json(__('messages.notFound.child'),404);
        }
    }
    public function sendCourseRequest(Request $request){
        $validator = Validator::make($request->all(), [
            "childId"=>"required|exists:children,id",
            "courseId"=>"required|exists:course_infos,id",
            "notice"=>"nullable",
            "numberOfLesson"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $user=JWTAuth::parseToken()->authenticate();
        $checkParent=ChildrenConnections::where(['parent_id'=>$user->id, "child_id" => $request->childId])->exists();
        if(!$checkParent){
            event(new ErrorEvent($user,'Not Found', '404', __("messages.error"), json_encode(debug_backtrace())));
            throw new \Exception(__("messages.notFound.child"));
        }

        $insertData=[
            "child_id"=>$request->childId,
            "teacher_course_id"=>$request->courseId,
            "number_of_lessons"=>$request->numberOfLesson,
            "status"=>"UNDER_REVIEW",
            "notice"=>$request->notice
        ];
        try{
            DB::transaction(function() use($insertData){
                TeacherCourseRequests::create($insertData);
            });
        }catch (\Exception $e){
            event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
            throw $e;
        }

        return response()->json(__("messages.success"));

    }
    public function getChildCourses($childId){

        if(Permission::checkPermissionForParents('WRITE',$childId)){
            $getCourses=TeacherCourseRequests::where(['child_id'=>$childId, 'status'=>'ACCEPTED'])
                ->with('courseInfo')
                ->with('courseNamesAndLangs')
                ->orderBy('updated_at', 'desc')
            ->get();
            $finalData=[];
            foreach ($getCourses as $course) {
                $getTeacher=CourseInfos::where('id', $course->teacher_course_id)->with('teacher')->first();
                $finalData[]=[
                    "id"=>$course->id,
                    "name"=>$course->courseNamesAndLangs[0]->name,
                    "teacher"=>$getTeacher->teacher->first_name . ' '. $getTeacher->teacher->last_name,
                    "status"=>__("enums.$course->status"),
                    "teacher_course_id"=>$course->teacher_course_id,
                ];
            }
            $header=['id','name','teacher','status'];

            $success=[
                "header"=>$header,
                "data"=>$finalData
            ];
            return response()->json($success);

        }
    }

}
