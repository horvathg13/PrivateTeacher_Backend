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
use function PHPUnit\Framework\isEmpty;

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
            return response()->json(['message'=>__('auth.token')]);
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
                throw new \Exception(__("messages.denied.user.active"));
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
        if($users->isEmpty()){
            throw new \Exception(__("messages.notFound.user"));
        }
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

        $success=[
            "data"=>$tableData,
            "header"=>$tableHeader,
            "pagination"=>$paginator
        ];
        return response()->json($success,200);


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
                    "label"=>__("enums.$r->name"),
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
                throw new \Exception(__("messages.notFound.user"));
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
            return response()->json(["message"=>__("messages.success")]);

        }


        /*  }else{
              throw new Exception('Access Denied');
          }*/


    }

    public function getSelectedUserData($userId){
        Validator::validate(['userId' => $userId], [
            'userId' => 'required|exists:users,id',
        ]);

        $user=User::where('id', $userId)->first();

        $success=[
            "id"=>$user->id,
            "firstname"=>$user->first_name,
            "lastname"=>$user->last_name,
            "email"=>$user->email,
            "status"=>$user->user_status,
        ];

        return response()->json($success);
    }
    public function getUserRoles($userId){

        $userRoles = UserRoles::where("user_id", $userId)->get();

        $data=[];

        if($userRoles){
            foreach($userRoles as $role){
                $roleName=Roles::where("id", $role['role_id'])->pluck('name')->first();
                $data[]=[
                    "role"=>__("enums.$roleName"),
                    "roleId"=>$role["role_id"],
                ];

            }
            $headerData=["role"];

            $success=[
                "header"=>$headerData,
                "userRoles"=>$data
            ];

            return response()->json($success);
        }else{
            return response()->json(__("messages.notFound.role"),500);
        }
    }

    public function removeUserRole($userId,$roleId){
        $validatorData=[
            "userId"=>$userId,
            "roleId"=>$roleId
        ];
        $validator=Validator::make($validatorData,[
            "userId"=>'required|exists:users,id',
            "roleId"=>'required|exists:roles,id'
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(Permission::checkPermissionForSchoolService("WRITE")){
            if($userId !== null || $roleId !== null) {
                try {
                    DB::transaction(function () use ($userId, $roleId) {

                        UserRoles::where(["user_id" => $userId, "role_id" => $roleId])->delete();

                    });
                    return response()->json([__("messages.success")], 200);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }else{
            throw new \Exception (__("messages.denied.role"));
        }
    }
    public function createUserRole(Request $request){

        $validator = Validator::make($request->all(), [
            "roleId"=>"required|exists:roles,id",
            "userId"=>"required|exists:users,id",
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $checkAlreadyAttached=UserRoles::where(["role_id"=>$request->roleId, "user_id" => $request->userId])->exists();
        if(!$checkAlreadyAttached) {
            try {
                DB::transaction(function () use ($request) {
                    UserRoles::insert([
                        "user_id" => $request->userId,
                        "role_id" => $request->roleId,
                    ]);

                });
            } catch (\Exception $e) {
                throw $e;
            }
        }else{
            throw new  \Exception(__("messages.attached.role"));
        }
        return response(__("messages.success"));
    }
}
