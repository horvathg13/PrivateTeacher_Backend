<?php

namespace App\Http\Controllers;

use App\Helper\Permission;
use App\Models\Children;
use App\Models\ChildrenConnections;
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
                throw new \Exception(__("auth.failed"));
            }
            return response(__("messages.success"));
        }else{
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
            throw new \Exception(__("messages.notFound.child"));
        }
    }

}
