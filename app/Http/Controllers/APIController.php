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
        $user = JWTAuth::parseToken()->authenticate();
        if(!$user){
            return response()->json('Invalid Token');
        }
        $getRoles= $user->roles()->pluck('name');
        
        $success=[
            "user"=>$user,
            "roles"=>$getRoles
        ];
        return response()->json($success);
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
}
