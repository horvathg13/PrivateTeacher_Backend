<?php

namespace App\Http\Controllers;

use App\Models\Statuses;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            "f_name"=>"required",
            "l_name"=>"required",
            "email"=>"required|email",
            "psw"=>"required"
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }
        DB::transaction(function ($request){
            $findActiveStatus=Statuses::where("status","Active")->pluck('id');
            if(!$findActiveStatus){
                throw new Exception('Database error');
            }
            $user = User::create([
                "first_name"=> $request->f_name,
                "last_name"=>$request->l_name,
                "email"=>$request->email,
                "password"=> bcrypt($request->password),
                "status"=>$findActiveStatus
            ]);

            if($user){
                $success="Register successful";
                return response()->json([$success,200]);
            }
        });

        
    }
}
