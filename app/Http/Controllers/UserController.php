<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\ErrorLogs;
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
                throw new ControllerException(__("messages.denied.user.active"));
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
            throw new ControllerException(__("messages.notFound.user"));
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
            __("tableHeaders.id")=>false,
            __("tableHeaders.firstname")=>false,
            __("tableHeaders.lastname")=>false,
            __("tableHeaders.email")=>false,
            __("tableHeaders.status")=>false,
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
                "label"=>__('enums.ACTIVE')
            ],
            [
                "value"=>"SUSPENDED",
                "label"=>__("enums.SUSPENDED")
            ],
            [
                "value"=>"BANNED",
                "label"=>__("enums.BANNED")
            ]
        ];

        return response()->json($statuses);
    }

    public function UpdateUser(Request $request){
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            "id"=>"required|exists:users,id",
            "userInfo"=>"required",
            "userInfo.first_name"=>"required|max:255",
            "userInfo.last_name"=>"required|max:255",
            "userInfo.email"=>"required|email|max:255",
            "userInfo.status"=>"nullable",
            "newPassword"=>"nullable",
            "confirmPassword"=>"same:newPassword|nullable"
        ],[
            "userInfo.required"=>__("validation.custom.userInfo.required"),
            "userInfo.first_name"=>__("validation.custom.fname.required"),
            "userInfo.first_name.max"=>__("validation.custom.fname.max"),
            "userInfo.last_name"=>__("validation.custom.lname.required"),
            "userInfo.last_name.max"=>__("validation.custom.lname.max"),
            "userInfo.email"=>__("validation.custom.email.required"),
            "userInfo.email.unique"=>__("validation.custom.email.unique"),
            "userInfo.email.email"=>__("validation.custom.email.email"),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        if(Permission::checkPermissionForAdmin() || $user->id === $request->id){
            $findUser=User::find($request->id);
            if($findUser){
                DB::transaction(function () use ($request, $findUser){
                    $userInfo=$request->userInfo;

                    $findUser->update([
                        "first_name"=>$userInfo['first_name'],
                        "last_name"=>$userInfo['last_name'],
                    ]);
                    if($userInfo['email']){
                        if($findUser->email !== $userInfo['email']){
                            $checkUniqueEmail=User::where('email', $userInfo['email'])->exists();
                            if(!$checkUniqueEmail){
                                $findUser->update([
                                    "email"=>$userInfo['email'],
                                ]);
                            }else{
                                throw new ControllerException(__("validation.custom.email.unique"));
                            }
                        }
                    }
                    if($userInfo["status"]){
                        $findUser->update([
                            "user_status"=>$userInfo["status"]
                        ]);
                    }
                    if($request->newPassword){
                        $findUser->update([
                            "password"=>bcrypt($request->newPassword)
                        ]);
                    }
                });
                return response()->json(["message"=>__("messages.success")]);

            }else{
                event(new ErrorEvent($user,'Update', '404', __("messages.notFound.user"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.notFound.user"));
            }
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.hack_attempt"), json_encode(debug_backtrace())));
            throw new ControllerException(__("messages.hack_attempt"));
        }

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
            $headerData=[__("tableHeaders.role")];

            $success=[
                "header"=>$headerData,
                "userRoles"=>$data
            ];

            return response()->json($success);
        }else{
            return response()->json(__("messages.notFound.role"),404);
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
        $user=JWTAuth::parseToken()->authenticate();
        if(Permission::checkPermissionForSchoolService("WRITE")){
            if($userId !== null || $roleId !== null) {
                try {
                    DB::transaction(function () use ($userId, $roleId) {

                        UserRoles::where(["user_id" => $userId, "role_id" => $roleId])->delete();

                    });
                    return response()->json([__("messages.success")], 200);
                } catch (\Exception $e) {
                    event(new ErrorEvent($user,'Remove', '500', __("messages.error"), json_encode(debug_backtrace())));
                    throw $e;
                }
            }
        }else{
            event(new ErrorEvent($user,'Forbidden Control', '403', __("messages.denied.permission"), json_encode(debug_backtrace())));
            throw new ControllerException (__("messages.denied.role"));
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
        $user=JWTAuth::parseToken()->authenticate();

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
                event(new ErrorEvent($user,'Create', '500', __("messages.error"), json_encode(debug_backtrace())));
                throw new ControllerException(__("messages.error"));
            }
        }else{
            event(new ErrorEvent($user,'Create', '500', __("messages.attached.role"), json_encode(debug_backtrace())));
            throw new  \Exception(__("messages.attached.role"));
        }
        return response(__("messages.success"));
    }

    public function getUserLogs(Request $request){
        $user = JWTAuth::parseToken()->authenticate();

        $query=ErrorLogs::query();

        if($request->userId !== null){
            $query->where('user_id', $request->userId)->with('user');
        }

        $errorLogs=$query->get();
        $success=[];
        foreach ($errorLogs as $e){
            $success[]=[
                "user"=>$e->user->first_name . ' '. $e->user->last_name . ' ('.$e->user->email . ') ',
                "procedure_name"=>$e->procedure_name,
                "status_code"=>$e->status_code,
                "message"=>$e->message,
                "create_at"=>$e->created_at
            ];
        }
        return response()->json($success);
    }

    public function getProcedureNames(){
        $get=ErrorLogs::all()->pluck("procedure_name");
        return response()->json($get);
    }
     public function getStatusCodes()
     {
         $get = ErrorLogs::all()->pluck("status_code");
         return response()->json($get);
     }



}
