<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\SchoolBreaks;
use App\Models\Schools;
use App\Models\SchoolYears;
use App\Models\Statuses;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\UserRoles;
use App\Models\SpecialWorkDays;
use App\Models\CourseInfos;
use App\Models\Children;
use App\Models\ChildrenConnections;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\PermissionController;
use App\Helper\Permission;

class APIController extends Controller
{
    public function getUserData(){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $getRoles= $user->roles()->pluck('name');
            
            $success=[
                "user"=>$user,
                "roles"=>$getRoles
            ];
            return response()->json($success);
        }catch(\Exception $e){
            return response()->json(['message'=>'Invalid Token']);
        }
       
    }

    public function createRoles(Request $request){
        $validator = Validator::make($request->all(), [
            "userId"=>"required",
            "roleId"=>"required",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        DB::transaction(function () use ($request){
            $findUser= User::find($request->userId);
            $findRoleId= Roles::find($request->roleId)->exists();

            $findActiveStatus=Statuses::where("status","Active")->pluck('id')->first();

            if($findUser->status=== $findActiveStatus){
                if($findRoleId===true){
                    UserRoles::create([
                        "user_id"=>$findUser->id,
                        "role_id"=>$request->roleId,
                    ]);
                }
                
            }else{
                throw new Exception('Operation denied: User is not active');
            }
        });
    }

    public function getUsers(Request $request){
        $findActiveStatus=Statuses::where("status","Active")->first();
        $users= User::where('status', $findActiveStatus['id'])->paginate($request->perPage ?: 5);

        $paginator=[
          "currentPageNumber"=>$users->currentPage(),
          "hasMorePages"=>$users->hasMorePages(),
          "lastPageNumber"=>$users->lastPage(),
          "total"=>$users->total(),
        ];
        $tableData=[];
        foreach($users as $user){
            $tableData[]=[
                "id"=>$user->id,
                "firstname"=>$user->first_name,
                "lastname"=>$user->last_name,
                "email"=>$user->email,
                "status"=>$findActiveStatus['status']
            ];
        }

        $tableHeader=[
            "id"=>true,
            "firstname"=>true,
            "lastname"=>true,
            "email"=>false,
            "status"=>false,
        ];

        if($users){
           $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
            "pagination"=>$paginator
           ];
            return response()->json($success,200);
        }else{
            throw new Exception('Database Error Occured!');
        }

    }

    public function getRoles(Request $request){
        $roles=Roles::all();

        if($request){

            $validator = Validator::make($request->all(), [
                "userId"=>"required",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            $findUser=User::where("id", $request->userId)->first();
            $success=[];

            if($findUser){
                $findUserRoles = $findUser->roles()->get();
                $userRoles=[];
                if($findUserRoles){
                    foreach($findUserRoles as $findUserRole){
                        $userRoles[]=$findUserRole->name;
                    }
                }
               
                foreach($roles as $role){
                     
                    $success[]=[
                        "id"=>$role->id,
                        "name"=>$role->name,
                        "userRoles"=>$userRoles ? in_array($role->name,$userRoles) : false
                    ];
                    
                }
              
                return response()->json($success);
            }else{
                throw new Exception('User is not found');
            }
        }else{
            return response()->json($roles);
        }
        
    }

    public function getUserStatuses(){
        $statuses=Statuses::whereIn("status", ["Active","Suspended","Ban"])->get();

        if($statuses){
            $success=[];
            foreach($statuses as $status){
                $success[]=[
                    'id'=>$status->id,
                    'label'=>$status->status,
                ];
            }

            return response()->json($success);
        }else{
            throw new Exception('Database Error Occured');
        }

        
    }

    public function UpdateUser(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        //$getRoles= $user->roles()->pluck('name')->toArray();
        
       // if(in_array("Admin", $getRoles)){//
            $validator = Validator::make($request->all(), [
                "id"=>"required|exists:users,id",
                "userInfo"=>"required",
                "newPassword"=>"nullable",
                "confirmPassword"=>"same:newPassword|nullable"
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            $findUser=User::find($request->id);
            if($findUser){
                DB::transaction(function () use ($request, $findUser){
                    $userInfo=$request->userInfo;
                    
                    $findUser->update([
                        "first_name"=>$userInfo['first_name'],
                        "last_name"=>$userInfo['last_name'],
                        "email"=>$userInfo['email'],
                        "status"=>$userInfo["status"]
                    ]);
                    

                    if($request->newPassword){
                    
                        $findUser->update([
                            "password"=>bcrypt($request->newPassword)
                        ]);
                    }
                });
                return response()->json(["message"=>"Update Successful"]);
                
            }
            

      /*  }else{
            throw new Exception('Access Denied');
        }*/
        

    }

    public function getSelectedUserData($userId){
        if($userId){
            $user=User::where('id', $userId)->first();
            $getUserStatus= Statuses::where("id", $user['status'])->first();

            $success=[
                "id"=>$user->id,
                "firstname"=>$user->first_name,
                "lastname"=>$user->last_name,
                "email"=>$user->email,
                "status"=>$getUserStatus->status,
                "statusId"=>$getUserStatus->id
            ];

            return response()->json($success);
        }else{
            throw new Exception('Request fail');
        }
    }

    public function SchoolCreate(Request $request){
        $user = JWTAuth::parsetoken()->authenticate();
        //Role based AccessControl...
        $validator = Validator::make($request->all(), [
            "name"=>"required",
            "country"=>"required",
            "zip"=>"required",
            "city"=>"required",
            "street"=>"required",
            "number"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        try{
            DB::transaction(function () use ($request){
                $createSchool=Schools::create([
                    "name"=>$request->name,
                    "country"=>$request->country,
                    "zip"=>$request->zip,
                    "city"=>$request->city,
                    "street"=>$request->street,
                    "number"=>$request->number
                ]);

            });
        }catch (Exception $e){
            throw $e;
        }


        return response()->json(["message"=>"School Creation Success"],200);
    }


    public function SchoolList(Request $request){
        $list = Schools::paginate($request->perPage ?: 5);
        

        if(!$list){
            throw new Exception('Schools not found');
        }

        $paginator=[
            "currentPageNumber"=>$list->currentPage(),
            "hasMorePages"=>$list->hasMorePages(),
            "lastPageNumber"=>$list->lastPage(),
            "total"=>$list->total(),
        ];
        $tableData=[];
        foreach($list as $l){
            $tableData[]=[
                "id"=>$l->id,
                "name"=>$l->name,
                "country"=>$l->country,
                "city"=>$l->city,
                "zip"=>$l->zip,
                "street"=>$l->street,
                "number"=>$l->number,
            ];
        }
        $tableHeader=[
            "id"=>true,
            "name"=>true,
            "country"=>true,
            "city"=>true,
            "zip"=>true,
            "street"=>false,
            "number"=>false,
        ];

        $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
            "pagination"=>$paginator
        ];
        return response()->json($success);
    }

    public function getSchoolInfo($schoolId){
        if($schoolId){
            $school=Schools::where('id', $schoolId)->first();

            return response()->json($school);
        }else{
            throw new Exception('Request fail');
        }
    }

    public function SchoolUpdate(Request $request){
        
        $validator = Validator::make($request->all(), [
            "id"=>"required|exists:schools,id",
            "name"=>"required",
            "city"=>"required",
            "zip"=>"required",
            "street"=>"required",
            "number"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(Permission::checkPermissionForSchoolService("WRITE",$request->id)){
            $findSchool=Schools::find($request->id)->first();
            if($findSchool){
                DB::transaction(function () use ($request, $findSchool){
                    
                    $findSchool->update([
                        "name"=>$request->name,
                        "city"=>$request->city,
                        "zip"=>$request->zip,
                        "street"=>$request->street,
                        "number"=>$request->number
                    ]);

                    
                });
                return response()->json(["message"=>"Update Successful"]);
                
            }
        }else{
            throw new Exception("Denied");
        }
            

      
    }

    public function getSchoolYears(Request $request){
        $years = SchoolYears::where("school_id", $request->schoolId)->get();
        
       
        $tableData=[];
        if($years){
            foreach($years as $year){
                $tableData[]=[
                    "id"=>$year->id,
                    "year"=>$year->year,
                    "name"=>$year->name,
                    "start"=>$year->start,
                    "end"=>$year->end,
                ];
            }
        }
        
        $tableHeader=[
            "id"=>false,
            'year'=>false,
            'name'=>false,
            'start'=>false,
            'end'=>false,
        ];

        
        $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
        ];
        return response()->json($success);
    }

    public function createSchoolYear(Request $request){
        if(Permission::checkPermissionForSchoolService("WRITE",$request->id)){
            $validator = Validator::make($request->all(), [
                "year"=>"required",
                "name"=>"required",
                "startDate"=>"required",
                "endDate"=>"required",
                "id"=>"nullable"
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }

            if($request->endDate < $request->startDate){
                throw new Exception("The end of the school year must be later then start date!");
            }
            if($request->id != null){
                $findSchoolYear= SchoolYears::where("id", $request->id)->first();
                DB::transaction(function () use ($request, $findSchoolYear){

                    $findSchoolYear->update([
                        "year"=>$request->year,
                        "name"=>$request->name,
                        "start" => $request->startDate,
                        "end" => $request->endDate
                    ]);
                });
            }else{
                DB::transaction(function () use ($request){

                    SchoolYears::create([
                        "year"=>$request->year,
                        "school_id"=>$request->schoolId,
                        "name"=>$request->name,
                        "start" => $request->startDate,
                        "end" => $request->endDate
                    ]);
                });
            }
            return response()->json(["Opration Successful"],200);
        }else{
            throw new Exception("Denied");
        }
    }

    public function removeSchoolYear(Request $request){
        if(Permission::checkPermissionForSchoolService("WRITE",$request->id)){ 
            $validator = Validator::make($request->all(), [
                "schoolId"=>"required|exists:schools,id",
                "yearId"=>"required|exists:school_years,id",
            ]);
            if($validator->fails()){
                $validatorResponse=[
                    "validatorResponse"=>$validator->errors()->all()
                ];
                return response()->json($validatorResponse,422);
            }
            try{
                DB::transaction(function () use ($request){

                    SchoolYears::where(["school_id"=>$request->schoolId, "id"=>$request->yearId])->delete();
                
                });
            }catch (Exception $e){
                throw $e;
            }
            
            
            return response()->json("Operation Successful");
        }else{
            throw new Exception("Denied");
        }

    }
    public function getSchoolYearInfos($schoolId, $schoolYearId){
        $validSchool = Schools::where("id", $schoolId)->exists();
        $validSchoolYear = SchoolYears::where("id", $schoolYearId)->exists();

        if($validSchool === false || $validSchoolYear === false){
            throw new Exception("Invalid server call");
        }

       
        $SchoolYearDetails = SchoolYears::where("id", $schoolYearId)->first();
        $schoolInfos = Schools::where("id", $schoolId)->first();

        $success=[
            "year"=>$SchoolYearDetails->year,
            "name"=>$SchoolYearDetails->name,
            "start"=>$SchoolYearDetails->start,
            "end"=>$SchoolYearDetails->end,
            "schoolName"=>$schoolInfos->name,
            "schoolId"=>$schoolInfos->id
        ];
        return response()->json($success, 200);
    }
    public function getSchoolYearDetails($schoolId, $schoolYearId){
        $breaks = SchoolBreaks::where(["school_id"=> $schoolId, "school_year_id"=> $schoolYearId])->get();
        
        
        /*$tableData=[];
        foreach($years as $year){
            $tableData[]=[
                "id"=>$year->id,
                "year"=>$year->year,
                "name"=>$year->name,
                "start"=>$year->start,
                "end"=>$year->end,
            ];
        }*/

        // getSpecialWorkDays

        $specWorkDays = SpecialWorkDays::where(["school_id"=> $schoolId, "school_year_id"=>$schoolYearId])->get();
        
        $tableHeader=[
            "id",
            "name",
            "start",
            "end",
        ];
        
        $success=[
            "header"=>$tableHeader,
            "breaks"=>$breaks,
            "specialWorkDays"=>$specWorkDays,
        ];
        return response()->json($success);
    }
    public function createSchoolBreak(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "name"=>"required",
            "start"=>"required",
            "end"=>"required",
            "id"=>"nullable"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(!$request->id){
            try{
                DB::transaction(function () use($request){
                    SchoolBreaks::create([
                        "name"=>$request->name,
                        "start"=>$request->start,
                        "end"=>$request->end,
                        "school_id"=>$request->schoolId,
                        "school_year_id"=>$request->yearId
                    ]);
                });
            }catch(Exception $e){
                throw $e;
            }
            return response("Create Successful");
        }else{
            $findId= SchoolBreaks::where("id", $request->id)->first();

            if($findId){
                try{
                    DB::transaction(function() use($request, $findId){
                        $findId->update([
                            "name"=>$request->name,
                            "start"=>$request->start,
                            "end"=>$request->end,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
                }catch(Exception $e){
                    throw $e;
                }

                return response("Update Successful!");
            }
        }
    }
    public function createSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "name"=>"required",
            "start"=>"required",
            "end"=>"required",
            "id"=>"nullable"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(!$request->id){
            try{
                DB::transaction(function () use($request){
                    SpecialWorkDays::create([
                        "name"=>$request->name,
                        "start"=>$request->start,
                        "end"=>$request->end,
                        "school_id"=>$request->schoolId,
                        "school_year_id"=>$request->yearId
                    ]);
                });
            }catch(Exception $e){
                throw $e;
            }
            return response("Create Successful");
        }else{
            $findId= SpecialWorkDays::where("id", $request->id)->first();

            if($findId){
                try{
                    DB::transaction(function() use($request, $findId){
                        $findId->update([
                            "name"=>$request->name,
                            "start"=>$request->start,
                            "end"=>$request->end,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
                }catch(Exception $e){
                    throw $e;
                }

                return response("Update Successful!");
            }
        }
        
    }

    public function removeSchoolBreak(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:school_breaks,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                SchoolBreaks::where("id", $request->id)->delete();
            });
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");
        
    }

    public function removeSpecialWorkDay(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:special_work_days,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                SpecialWorkDays::where("id", $request->id)->delete();
            });
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");
        
    }

    public function createSchoolCourse(Request $request){
        //Role base check here...
        $user= JWTAuth::parsetoken()->authenticate();

        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "courseId"=>"nullable|exists:course_infos,id",
            "name"=>"required",
            "subject"=>"required",
            "studentLimit"=>"required",
            "minutesLesson"=>"required",
            "minTeachingDay"=>"required",
            "doubleTime"=>"required",
            "couresPricePerLesson"=>"required",
            "status"=>"required"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        if($request->courseId === null){

            try{

                DB::transaction(function() use($request){
                    CourseInfos::create([
                        "name"=>$request->name,
                        "subject"=>$request->subject,
                        "student_limit"=>$request->studentLimit,
                        "minutes_lesson"=>$request->minutesLesson,
                        "min_teaching_day"=>$request->minTeachingDay,
                        "double_time"=>$request->doubleTime,
                        "course_price_per_lesson"=>$request->couresPricePerLesson,
                        "status_id"=>$request->status,
                        "school_id"=>$request->schoolId,
                        "school_year_id"=>$request->yearId
                    ]);
                });

            }catch(Exception $e){
                throw $e;
            }
            return response("Create Successful");
        }else{
            $findCourse=CourseInfos::where("id", $request->courseId)->first();
            if($findCourse){
                try{

                    DB::transaction(function() use($request, $findCourse){
                        $findCourse->update([
                            "name"=>$request->name,
                            "subject"=>$request->subject,
                            "student_limit"=>$request->studentLimit,
                            "minutes/lesson"=>$request->minutesLesson,
                            "min_teaching_day"=>$request->minTeachingDay,
                            "double_time"=>$request->doubleTime,
                            "course_price_per_lesson"=>$request->couresPricePerLesson,
                            "status_id"=>$request->status,
                            "school_id"=>$request->schoolId,
                            "school_year_id"=>$request->yearId
                        ]);
                    });
    
                }catch(Exception $e){
                    throw $e;
                }
                return response("Update Successful");
            }else{
                throw new Exception("Database error occured!");
            }
            
        }
    }

    public function getSchoolCourses($schoolId, $schoolYearId){

        $user= JWTAuth::parsetoken()->authenticate();

        $courses=CourseInfos::where(["school_id"=>$schoolId, "school_year_id"=>$schoolYearId])->get();

        $tableHeader=[
            "id",
            'name',
            /*'subject',
            'student_limit',
            'minutes/lesson',
            'min_teaching_day',
            'double_time',
            'course_price_per_lesson',*/
            'status', 
        ];

        if($courses){
            $final=[];
            foreach ($courses as $course){
                $status = $course->status()->first();
                $final[]=[
                    "id"=>$course->id,
                    'name'=>$course->name,
                    'subject'=>$course->subject,
                    'student_limit'=>$course->student_limit,
                    'minutes_lesson'=>$course->minutes_lesson,
                    'min_teaching_day'=>$course->min_teaching_day,
                    'double_time'=>$course->double_time,
                    'course_price_per_lesson'=>$course->course_price_per_lesson,
                    'status'=>$status->status, 
                ];
            }
            
            $success=[
                "header"=>$tableHeader,
                "courses"=>$final
            ];
            return response()->json($success,200);
        }else{
            throw new Exception("Database error occured");
        }

    }

    public function removeSchoolCourse(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
        $validator = Validator::make($request->all(), [
            "schoolId"=>"required|exists:schools,id",
            "yearId"=>"required|exists:school_years,id",
            "id"=>"required|exists:course_infos,id"
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                CourseInfos::where("id", $request->id)->delete();
            });
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");
    }

    public function getSchoolCourseStatuses(){
        
        $CourseStatuses=Statuses::whereIn("status", ["Active", "Suspended"])->get();
       
        if($CourseStatuses){
            $success=[];
            foreach($CourseStatuses as $status){
                $success[]=[
                    "id"=>$status->id,
                    "label"=>$status->status
                ];
            }
            return response()->json($success);
        }else{
            throw new Exception("Error Orrured!");
        }
    }

    public function getSchoolCourseInfo($schoolId, $schoolYearId, $courseId){
        $user= JWTAuth::parsetoken()->authenticate();

        if($schoolId === null || $schoolYearId === null || $courseId === null){
            throw new Exception("Request fail");
        }else{
            $course=CourseInfos::where(["school_id"=>$schoolId, "school_year_id"=>$schoolYearId, "id"=>$courseId])->first();
            $status = $course->status()->first();
            
            if($course){
                $success=[
                    "courses"=>[
                        "id"=>$course->id,
                        'name'=>$course->name,
                        'subject'=>$course->subject,
                        'student_limit'=>$course->student_limit,
                        'minutes_lesson'=>$course->minutes_lesson,
                        'min_teaching_day'=>$course->min_teaching_day,
                        'double_time'=>$course->double_time,
                        'course_price_per_lesson'=>$course->course_price_per_lesson,
                        'status'=>$status->status, 
                        'status_id'=>$course->status_id
                    ]
                ];
                return response()->json($success,200);
            }else{
                throw new Exception("Database error occured");
            }
        }
    }

    public function getUserRoles($userId){
        

        $userRoles = UserRoles::where("user_id", $userId)->get();

        $datas=[];

        if($userRoles){
            foreach($userRoles as $role){
                $roleName=Roles::where("id", $role['role_id'])->pluck('name')->first();
                $reference=Schools::where("id", $role['reference_id'])->first();
                $success[]=[
                    $datas[]=[
                        "role"=>$roleName,
                        "roleId"=>$role["role_id"],
                        "reference"=>$reference,
                    ]
                   
                ];
            }
            $headerData=["role", "reference"];

            $success=[
                "header"=>$headerData,
                "userRoles"=>$datas
            ];
            
            return response()->json($success);
        }else{
            return response()->json("No registered role to this user.",500);
        }

    }

    public function removeUserRole($userId,$roleId,$referenceId){
        if(Permission::checkPermissionForSchoolService("WRITE", $referenceId)){
            if($userId !== null || $roleId !== null){
                try{
                    DB::transaction(function() use($userId,$roleId,$referenceId){
                        
                        $findUserRole = UserRoles::where(["user_id"=>$userId, "role_id"=>$roleId, "reference_id"=>$referenceId])->first();
                        if($findUserRole){
                            $findUserRole= UserRoles::where(["user_id"=>$userId, "role_id"=>$roleId, "reference_id"=>$referenceId])->delete();
                        }
                    });
                    return response()->json(["Operation successful"],200);
                }catch(Exception $e){
                    throw $e;
                }
                
            }else{
                throw new Exception('Bad parameters to this function');
            }
        }else{
            throw new Exception ('Denied');
        }
    }

    public function getRolesandSchools($userId){
        //if(Permission::checkPermissionForSchoolService("WRITE", 0)){
            $getAttachedRoles = UserRoles::where("user_id",$userId)->pluck("role_id")->toArray();
            $getRoles =Roles::all()->pluck('id')->toArray();
            if($getAttachedRoles){
            $notAttached = array_diff_key($getRoles, $getAttachedRoles);
            
                if($notAttached){
                    $roleNames=[];
                    foreach($notAttached as $n){
                        $result=Roles::where("id", $n)->first();
                        $roleNames[]= [
                            "id"=>$result["id"],
                            "label"=>$result["name"]
                        ];
                    }
                }
            }else{
                $getRoles =Roles::all();
            }

            $getSchools=Schools::all();

            if($getSchools){

            
                $finalSchool=[];

                foreach($getSchools as $s){
                    $finalSchool[]=[
                        "id"=>$s['id'],
                        "label"=>$s['name']
                    ];
                }
            }
            $success=[
                "roles"=>$roleNames,
                "schools"=>$finalSchool
            ];

            return response()->json($success);
        /*}else{
            throw new Exception("Denied");
        }*/
    }

    public function createUserRole(Request $request){

        $validator = Validator::make($request->all(), [
            "roleId"=>"required",
            "userId"=>"required",
            "refId"=>"nullable",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        try{
            DB::transaction(function() use($request){
                UserRoles::insert([
                    "user_id"=>$request->userId, 
                    "role_id"=>$request->roleId,
                    "reference_id"=>$request->refId ? $request->refId : null
                ]);
                
            });
        }catch(Exception $e){
            throw $e;
        }
        return response("Success");
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
            }catch(Exception $e){
                throw $e;
            }
            return response("Success");
        }else{
            throw new Exception("Denied");
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
                throw new Exception("Invalid Credentials");
            }
            return response("Success");
        }else{
            throw new Exception("Denied");
        }
    }

    public function getConnectedChildren(){
        $user = JWTAuth::parseToken()->authenticate();

        $getChildren= ChildrenConnections::where("parent_id",$user->id,)->get();

        if($getChildren){
            $datas=[];
            foreach($getChildren as $c){
                $getChildData = Children::where("id", $c["child_id"])->first();

                if($getChildData){
                    $datas[]=  [
                        "firstname"=>$getChildData->first_name,
                        "lastname"=>$getChildData->last_name,
                        "birthday"=>$getChildData->birthday
                    ];
                       
                    
                }else{
                    throw new Exception("Something went wrong");
                }
            }   

            $header=["Firstname", "Lastname", "Birthday"];

            $success=[
                "header"=>$header,
                "data"=>$datas,
            ];
            return response()->json($success,200);
        }else{
            throw new Exception("No child connected to this user.");
        }

    }

    public function searchTeacher(Request $request){

        $email=$request->email;

        if($email === null){
            throw new Exception("Invalid search credentials");
        }

        $findUser = User::where('email', $email)->first();

        if($findUser){
            $teacher=Roles::where('name', "Teacher")->pluck('id')->first();
            $isTeacher= UserRoles::where(['user_id'=>$findUser['id'], 'role_id'=> $teacher])->exists();

            if($isTeacher){
                $getSchools=UserRoles::where(['user_id'=>$findUser['id'], 'role_id'=> $teacher])->pluck('reference_id');

                if($getSchools){
                    $findSchools=Schools::whereIn("id", $getSchools)->get();
                }
            }else{
                throw new Exception('Invalid email');
            }
        }
        
        $header=["id", "firstname","lastname","email"];
        $success=[
            "header"=>$header,
            "datas"=>$findSchools
        ];

        return response()->json($success,200);
    }

    public function searchSchool(Request $request){

        $name = $request->name?: null;
        $country = $request->country?: null;
        $zip=$request->zip?: null;
        $city=$request->city?: null;
        $street=$request->street?: null;
        $number=$request->number?: null;

        $getSchools= Schools::query();

        if($name!== null){
            $getSchools->where("name",$request->name);
        }
        if($country!== null){
            $getSchools->where("country",$request->country);
        }
        if($zip!== null){
            $getSchools->where("zip",$request->zip);
        }
        if($city!== null){
            $getSchools->where("city",$request->city);
        }
        if($street!== null){
            $getSchools->where("street",$request->street);
        }
        if($number!== null){
            $getSchools->where("number",$request->number);
        }
        
        if(!empty($request->sortData)){
            foreach($request->sortData as $sort){
                $getSchools->orderBy($sort['key'], $sort['abridgement']);
            }
        }

        $Results=$getSchools->paginate($request->perPage ?: 5);

        $paginator=[
          "currentPageNumber"=>$Results->currentPage(),
          "hasMorePages"=>$Results->hasMorePages(),
          "lastPageNumber"=>$Results->lastPage(),
          "total"=>$Results->total(),
        ];

        if($Results){
            foreach($Results as $school){
                $datas[]= [
                    "id"=>$school['id'],
                    "name"=>$school['name'],
                    "country"=>$school['country'],
                    "zip"=>$school['zip'],
                    "city"=>$school['city'],
                    "street"=>$school['street'],
                    "number"=>$school['number']
                ];
            }
        }

        $header=["id"=>false, "name"=>false,"country"=>false,"zip"=>false,"city"=>false,"street"=>false,"number"=>false];
        $success=[
            "header"=>$header,
            "datas"=>$datas,
            "pagination"=>$paginator
        ];

        return response()->json($success,200);
    }

    
}


