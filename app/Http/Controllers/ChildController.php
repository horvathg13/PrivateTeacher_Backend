<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Helper\Student;
use App\Models\Children;
use App\Models\ChildrenConnections;
use App\Models\CommonRequests;
use App\Models\CourseInfos;
use App\Models\StudentCourse;
use App\Models\TeacherCourseRequests;
use Carbon\Carbon;
use Exception;
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

    /**
     * @throws Exception
     */
    public function createChild(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForChildren("GENERATE")){
            $validator = Validator::make($request->all(), [
                "fname"=>"required|max:255",
                "lname"=>"required|max:255",
                "username"=>"required|unique:children,username",
                "birthday"=>"required|date|before:today",
                "psw"=>"required",
            ],[
                "fname.required"=>__('validation.custom.fname.required'),
                "fname.max"=>__('validation.custom.fname.max'),
                'lname.required' => __('validation.custom.lname.required'),
                'lname.max' => __('validation.custom.lname.max'),
                "username.required"=>__('validation.custom.username.required'),
                "username.unique"=>__('validation.custom.username.unique'),
                "username.max"=>__('validation.custom.username.max'),
                "birthday.required"=>__('validation.custom.birthday.required'),
                "birthday.date"=>__('validation.custom.birthday.date'),
                "birthday.before"=>__('validation.custom.birthday.before'),
                'psw.required' => __('validation.custom.password.required'),
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            DB::transaction(function() use($request, $user){
                try{
                    Children::create([
                        "first_name"=>$request->fname,
                        "last_name"=>$request->lname,
                        "username"=>$request->username,
                        "password"=>bcrypt($request->psw),
                        "birthday"=>$request->birthday
                    ]);
                }catch(Exception $e){
                    event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                    throw new ControllerException(__("messages.error"));
                }
            });

            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.denied.role"));
        }
    }

    /**
     * @throws Exception
     */
    public function connectToChild(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForChildren("GENERATE")){
            $validator = Validator::make($request->all(), [
                "username"=>"required",
                "psw"=>"required",
            ],[
                "username.required"=>__('validation.custom.username.required'),
                'psw.required' => __('validation.custom.password.required'),
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            $checkChild = Children::where("username",$request->username)->first();
            if(!$checkChild){
                throw new ControllerException(__("auth.failed"));
            }

            $checkAlreadyConnected=ChildrenConnections::where(['parent_id'=>$user->id, "child_id" => $checkChild->id])->exists();

            if($checkAlreadyConnected){
                throw new ControllerException(__("messages.attached.exists"),409);
            }
            if($checkChild && Hash::check($request->psw, $checkChild->password)){
                DB::transaction(function() use($checkChild, $user){
                    ChildrenConnections::insert([
                        "parent_id"=>$user->id,
                        "child_id"=>$checkChild['id']
                    ]);
                });
            }else{
                event(new ErrorEvent($user,'Auth failed', '401', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("auth.failed"));
            }
            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.denied.role"));
        }
    }

    /**
     * @throws Exception
     */
    public function getConnectedChildren(){
        $user = JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForParents("READ",null)){
            $getChildren= ChildrenConnections::where("parent_id",$user->id,)->get();

            if($getChildren){
                $data=[];
                $select=[];
                foreach($getChildren as $c){
                    $getChildData = Children::where("id", $c["child_id"])->first();

                    if($getChildData){
                        $data[]=  [
                            "id"=>$getChildData->id,
                            "firstname"=>$getChildData->first_name,
                            "lastname"=>$getChildData->last_name,
                            "birthday"=>$getChildData->birthday
                        ];
                        $select[]=[
                            "value"=>$getChildData->id,
                            "label"=>$getChildData->first_name . " " . $getChildData->last_name . " (". $getChildData->birthday .") "
                        ];

                    }else{
                        throw new ControllerException(__("messages.error"));
                    }
                }
                $header=[
                    "firstname","lastname","birthdate"
                ];
                $success=[
                    "header"=>$header,
                    "data"=>$data,
                    "select"=>$select
                ];
                return response()->json($success,200);
            }else{
                throw new ControllerException(__("messages.notFound.child"));
            }
        }
        if(Permission::checkPermissionForTeachers("READ", null, null)){
            $getTeacherCourses=CourseInfos::where(['teacher_id'=>$user->id, "course_status"=>"ACTIVE"])->pluck('id');
            $getChildren=TeacherCourseRequests::whereIn('teacher_course_id',$getTeacherCourses)
                ->where('status', "ACCEPTED")
                ->with('childInfo')
            ->get();

            $select=[];
            if($getChildren->isNotEmpty()){
                foreach ($getChildren as $getChild) {
                    $select[]=[
                        "value"=>$getChild->childInfo->id,
                        "label"=>$getChild->childInfo->first_name . $getChild->childInfo->last_name . " (". $getChild->childInfo->birthday .") "
                    ];
                }
            }
            $success=[
                "select"=>$select
            ];
            return response()->json($success);

        }
    }
    public function getChildInfo($childId){
        $validation=Validator::make(["childId"=>$childId],[
            "childId"=>"required|numeric|exists:children,id"
        ],[
            "childId.required"=>__("validation.custom.childId.required"),
            "childId.numeric"=>__("validation.custom.childId.numeric"),
            "childId.exists"=>__("validation.custom.childId.exists")
        ]);
        if($validation->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validation->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
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
            throw new ControllerException(__("messages.notFound.child"));
        }
    }

    public function updateChildInfo(Request $request){
        $validator = Validator::make($request->all(), [
            "childId"=>"required|exists:children,id",
            "userInfo"=>"required",
            "userInfo.first_name"=>"required|max:255",
            "userInfo.last_name"=>"required|max:255",
            "userInfo.birthday"=>"required|date|before:today",
            "userInfo.username"=>"required|max:255",
            "password"=>"nullable",
            "confirmPassword"=>"nullable|same:password",
        ],[
            "userInfo.first_name.required"=>__('validation.custom.fname.required'),
            "userInfo.first_name.max"=>__('validation.custom.fname.max'),
            'userInfo.last_name.required' => __('validation.custom.lname.required'),
            'userInfo.last_name.max' => __('validation.custom.lname.max'),
            "userInfo.username.required"=>__('validation.custom.username.required'),
            "userInfo.username.max"=>__('validation.custom.username.max'),
            "userInfo.birthday.required"=>__('validation.custom.birthday.required'),
            "userInfo.birthday.date"=>__('validation.custom.birthday.date'),
            "userInfo.birthday.before"=>__('validation.custom.birthday.before'),
            'confirmPassword.same' => __('validation.custom.confirmPassword.same'),
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
            throw new ControllerException(__("messages.notFound.child"));
        }
        $getChildData=Children::where('id',$request->childId)->first();

        if($request->userInfo["username"] !== $getChildData->username){
            $isUniqueUsername=Children::where('username', $request->userInfo['username'])->exists();
            if($isUniqueUsername){
                throw new ControllerException(__('validation.custom.username.unique'));
            }
        }
        if($getChildData){
            DB::transaction(function() use($getChildData, $request, $user){
                try {
                    $getChildData->update([
                        "first_name"=>$request->userInfo['first_name'],
                        "last_name"=>$request->userInfo['last_name'],
                        "username" => $request->userInfo['username'],
                        "birthday"=>$request->userInfo['birthday'],
                        "password" => bcrypt($request->password),
                    ]);

                }catch(Exception $e){
                    event(new ErrorEvent($user,'Update', '500', __("messages.error"), json_encode(debug_backtrace())));
                }
            });
            return response(__("messages.success"));
        }else{
            event(new ErrorEvent($user,'Not Found', '404', __("messages.error"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.notFound.child"));
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
            "numberOfLesson"=>"required|integer|min:1",
            "start"=>"required|date|after:today"
        ], [
            "childId.required" => __("validation.custom.childId.required"),
            "childId.exists" => __("validation.custom.childId.exists"),
            "courseId.required" => __("validation.custom.courseId.required"),
            "courseId.exists" => __("validation.custom.courseId.exists"),
            "notice.nullable" => __("validation.custom.notice.nullable"),
            "numberOfLesson.required" => __("validation.custom.numberOfLesson.required"),
            "numberOfLesson.integer" => __("validation.custom.numberOfLesson.integer"),
            "numberOfLesson.min" => __("validation.custom.numberOfLesson.min"),
            "start.required"=>__("validation.custom.courseRequest.start.required"),
            "start.date"=>__("validation.custom.courseRequest.start.required"),
            "start.after"=>__("validation.custom.courseRequest.start.after.today"),
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
            throw new ControllerException(__("messages.notFound.child"));
        }
        $checkAlreadyApply=TeacherCourseRequests::where([
            "child_id" => $request->childId,
            "teacher_course_id" => $request->courseId
        ])->exists();

        if($checkAlreadyApply){
            $validateStudentCourse=StudentCourse::where([
                "child_id" => $request->childId,
                "teacher_course_id" => $request->courseId
            ])->where("end_date", ">", now())->exists();
            if($validateStudentCourse){
                throw new ControllerException(__("messages.attached.exists"),409);
            }
        }
        $getCourseEndDate=CourseInfos::where("id", $request->courseId)->pluck('end_date')->first();
        if($request->start && $getCourseEndDate){
            $validateDates=$request->start <= $getCourseEndDate;
            if(!$validateDates){
                $validatorResponse=[
                    "validatorResponse"=>[__("messages.error")]
                ];
                return response()->json($validatorResponse,422);
            }
        }
        $checkStudentLimit=Student::checkLimit($request->courseId,$request->start,$getCourseEndDate);
        if($checkStudentLimit['message'] === "error"){
            $checkStudentLimit['goodDate']?
                throw new ControllerException(__("messages.studentLimit.goodDay", ["goodDay"=>$checkStudentLimit['goodDate']]))
                : throw new ControllerException(__("messages.studentLimit.null"));
        }
        $insertDataToTeacherCourseRequests=[
            "child_id"=>$request->childId,
            "teacher_course_id"=>$request->courseId,
            "number_of_lessons"=>$request->numberOfLesson,
            "start_date"=>$request->start,
        ];
        DB::transaction(function() use($insertDataToTeacherCourseRequests, $user, $request){
            try{
                $getCourseId =TeacherCourseRequests::create($insertDataToTeacherCourseRequests);
                if($getCourseId){
                    $getCourseId->request()->create([
                        "status"=>"UNDER_REVIEW",
                        "message"=>$request->notice,
                    ]);
                }
            }catch (Exception $e){
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.error"));
            }
        });
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
                    "status"=>$course->status,
                    "teacher_course_id"=>$course->teacher_course_id,
                ];
            }
            $header=[
                "id","name", "teacher_name", "status"
            ];
            $success=[
                "header"=>$header,
                "data"=>$finalData
            ];
            return response()->json($success);

        }
    }
    public function detachChild($childId){
        $user=JWTAuth::parseToken()->authenticate();

        if(Permission::checkPermissionForParents("WRITE", $childId)){
            $getConnection=ChildrenConnections::where(['parent_id'=>$user->id, 'child_id' => $childId])->first();

            if($getConnection){
                DB::transaction(function () use($getConnection, $user, $childId){
                    ChildrenConnections::where(['parent_id'=>$user->id, 'child_id' => $childId])->delete();
                });
                return response()->json([
                    "message"=>__("messages.success")
                ]);
            }else{
                throw new ControllerException(__("messages.error"));
            }
        }
        throw new ControllerException(__("messages.denied.permission"),403);
    }

}
