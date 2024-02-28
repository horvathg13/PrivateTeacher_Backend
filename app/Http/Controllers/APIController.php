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
use App\Models\UserRoles;
use App\Models\SpecialWorkDays;
use App\Models\CourseInfos;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        $user = JWTAuth::parseToken()->authenticate();
        //$getRoles= $user->roles()->pluck('name')->toArray();
        
       // if(in_array("Admin", $getRoles)){//
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
            

      /*  }else{
            throw new Exception('Access Denied');
        }*/
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
        $user = JWTAuth::parsetoken()->authenticate();
        //Role based AccessControl...
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
    }

    public function removeSchoolYear(Request $request){
        $user= JWTAuth::parsetoken()->authenticate();
        // role base check...
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






}


