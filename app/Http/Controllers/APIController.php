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

        DB::transaction(function () use ($request){

            SchoolYears::create([
                "year"=>$request->year,
                "school_id"=>$request->schoolId,
                "name"=>$request->name,
                "start" => $request->startDate,
                "end" => $request->endDate
            ]);
        });

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
    public function getSchoolBreaks(Request $request){
        $years = SchoolBreaks::where("school_id", $request->schoolId)->pagiate($request->perPage ?: 5)->get();
        
        $paginator=[
            "currentPageNumber"=>$years->currentPage(),
            "hasMorePages"=>$years->hasMorePages(),
            "lastPageNumber"=>$years->lastPage(),
            "total"=>$years->total(),
        ];
        $tableData=[];
        foreach($years as $year){
            $tableData[]=[
                "id"=>$year->id,
                "year"=>$year->year,
                "name"=>$year->name,
                "start"=>$year->start,
                "end"=>$year->end,
            ];
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
            "pagination"=>$paginator
        ];
        return response()->json($success);
    }
    public function getSpeacialWorkDays(Request $request){
    
        $getSchoolYear = SchoolYears::where("id", )->get();

        $query = SpecialWorkDays::where("school_id", $request->schoolId)->pagiate($request->perPage ?: 5)->get();
        $paginator=[
            "currentPageNumber"=>$query->currentPage(),
            "hasMorePages"=>$query->hasMorePages(),
            "lastPageNumber"=>$query->lastPage(),
            "total"=>$query->total(),
        ];
        $tableData=[];
        foreach($query as $q){
            $tableData[]=[
                "id"=>$q->id,
                "year"=>$q->year,
                "name"=>$q->name,
                "start"=>$q->start,
                "end"=>$q->end,
            ];
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
            "pagination"=>$paginator
        ];
        return response()->json($success);
    }
}
