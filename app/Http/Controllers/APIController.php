<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\Statuses;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRoles;
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

    
}
