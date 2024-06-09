<?php

namespace App\Http\Controllers;

use App\Helper\Permission;
use App\Models\Roles;
use App\Models\Schools;
use App\Models\Statuses;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function index()
    {

    }
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

            if($findUser->user_status==="ACTIVE"){
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
        $users= User::where('user_status', "ACTIVE")->paginate($request->perPage ?: 5);

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
                "status"=>$user->user_status
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
    public function getGlobalRoles()
    {
        $user=JWTAuth::parseToken()->authenticate();
        if($user){
            $roles=Roles::all();
            $success=[];
            foreach ($roles as $r){
                $success[]=[
                    "value"=>$r->id,
                    "label"=>$r->name,
                ];
            }
            return response()->json($success);
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
                throw new \Exception('User is not found');
            }
        }else{
            return response()->json($roles);
        }

    }

    public function getUserStatuses(){
        $statuses=[
            [
                "value"=>"ACTIVE",
                "label"=>"Active"
            ],
            [
                "value"=>"SUSPENDED",
                "label"=>"Suspended"
            ],
            [
                "value"=>"BANNED",
                "label"=>"Banned"
            ]
        ];

        return response()->json($statuses);
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
                    "user_status"=>$userInfo["status"]
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

            $success=[
                "id"=>$user->id,
                "firstname"=>$user->first_name,
                "lastname"=>$user->last_name,
                "email"=>$user->email,
                "status"=>$user->user_status,
            ];

            return response()->json($success);
        }else{
            throw new \Exception('Request fail');
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
    public function createUserRole(Request $request){

        $validator = Validator::make($request->all(), [
            "roleId"=>"required|exists:roles,id",
            "userId"=>"required|exists:users,id",
            "refId"=>"required|exists:schools,id",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $checkAlreadyAttached=UserRoles::where(["role_id"=>$request->roleId, "user_id" => $request->userId, "reference_id" => $request->refId])->exists();
        if(!$checkAlreadyAttached) {
            try {
                DB::transaction(function () use ($request) {
                    UserRoles::insert([
                        "user_id" => $request->userId,
                        "role_id" => $request->roleId,
                        "reference_id" => $request->refId
                    ]);

                });
            } catch (\Exception $e) {
                throw $e;
            }
        }else{
            throw new  \Exception("This role already attached to this user");
        }
        return response("Success");
    }
}
