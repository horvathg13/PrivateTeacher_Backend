<?php

namespace App\Http\Controllers;

use App\Models\Statuses;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            "fname"=>"required",
            "lname"=>"required",
            "email"=>"required|email|unique:users,email",
            "psw"=>"required"
        ],[
            "fname.required"=>__('validation.custom.fname.required'),
            'lname.required' => __('validation.custom.lname.required'),
            'email.required' => __('validation.custom.email.required'),
            'email.unique' =>  __('validation.custom.email.unique'),
            'psw.required' => __('validation.custom.psw.required'),

        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
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

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            "email"=>"required|email",
            "psw"=>"required"
        ],[
            'email.required' => __('validation.custom.email.required'),
            'psw.required' => __('validation.custom.psw.required'),
        ]);        
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $finduser= User::where("email", $request->email)->first();
        $findStatus=Statuses::where('status','Active')->pluck('id')->first();
            if($finduser->status === $findStatus){
                $credentials = ["email"=>$request->email,"password"=>$request->psw];

                if (!auth()->attempt($credentials)) {
                    $response = [
                        "success" => false,
                        "message" => "Invalid credentials"
                    ];
                    return response()->json($response, 401);
                }
                $user = Auth::user();
                
                $success = [
                    "first_name"=>$user->first_name,
                    "last_name"=>$user->last_name,
                    "id"=>$user->id,
                    "email"=>$user->email,
                    "token"=>auth()->login($user)
                ];

                Auth::login($user, true);
        
                $response = [
                    "success"=>true,
                    "data"=>$success,
                    "message"=>"User Login Successfull",              
                ];
                return response()->json($response);
            }else{
                $response = [
                    "success" => false,
                    "message" => "Access Denied!"
                ];
                return response()->json($response, 401);
            }
    }

    public function logout(){
        try{
            $user= JWTAuth::parsetoken()->invalidate();
            return response()->json(['message' => 'Logout successful']);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Logout failed'], 500);
            
        }
        
    }
}
