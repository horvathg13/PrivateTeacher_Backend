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
}
