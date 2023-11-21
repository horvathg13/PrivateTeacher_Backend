<?php

namespace App\Http\Controllers;

use App\Models\Statuses;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            "fname"=>"required",
            "lname"=>"required",
            "email"=>"required|email",
            "psw"=>"required"
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all(),422);
        }
        DB::transaction(function () use ($request){
            $findActiveStatus=Statuses::where("status","Active")->pluck('id')->first();
            if(!$findActiveStatus){
                throw new Exception('Database error');
            }
            
            $user = User::create([
                "first_name"=> $request->fname,
                "last_name"=>$request->lname,
                "email"=>$request->email,
                "password"=> bcrypt($request->psw),
                "status"=>$findActiveStatus
            ]);

            if($user){
                $success="Register successful";
                return response()->json([$success,200]);
            }
        });

        
    }
}
