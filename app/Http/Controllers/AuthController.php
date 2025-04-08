<?php

namespace App\Http\Controllers;

use App\Events\ErrorEvent;
use App\Exceptions\ControllerException;
use App\Helper\Permission;
use App\Models\ChildrenConnections;
use App\Models\PasswordResets;
use App\Models\Roles;
use App\Models\Statuses;
use App\Models\User;
use App\Models\UserRoles;
use Carbon\Carbon;
use Couchbase\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Mockery\Generator\StringManipulation\Pass\Pass;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{
    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            "fname"=>"required|max:255",
            "lname"=>"required|max:255",
            "email"=>"required|email|unique:users,email|max:255",
            "psw"=>"required|max:255"
        ],[
            "fname.required"=>__('validation.custom.fname.required'),
            "fname.max"=>__('validation.custom.fname.max'),
            'lname.max' => __('validation.custom.lname.max'),
            'lname.required' => __('validation.custom.lname.required'),
            'email.required' => __('validation.custom.email.required'),
            'email.unique' =>  __('validation.custom.email.unique'),
            'psw.required' => __('validation.custom.password.required'),

        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        DB::transaction(function () use ($request){

            $user = User::insertGetId([
                "first_name"=> $request->fname,
                "last_name"=>$request->lname,
                "email"=>$request->email,
                "password"=> bcrypt($request->psw),
                "user_status" => "ACTIVE",
                "created_at" => \Carbon\Carbon::now(),
                "updated_at" => \Carbon\Carbon::now(),
            ]);

            $getParentId=Roles::where('name', "Parent")->value('id');

            UserRoles::insert([
                "user_id" => $user,
                "role_id" => $getParentId
            ]);

            return response()->json([__('messages.success'),200]);

        });
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            "email"=>"required|email",
            "psw"=>"required"
        ],[
            'email.required' => __('validation.custom.email.required'),
            'email.exists' => __('validation.custom.email.exists'),
            'psw.required' => __('validation.custom.password.required'),
        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $findUser= User::where("email", $request->email)->where("user_status", "ACTIVE")->first();
        if(!empty($findUser)){
                $credentials = ["email" => $request->email, "password" => $request->psw];

                if (!auth()->attempt($credentials)) {
                        $response = [
                            "success" => false,
                            "message" => __("auth.invalid.credentials")
                        ];
                    return response()->json($response, 401);
                }
                $user = Auth::user();
                $getRoles=$user->roles->pluck('name')->toArray();
                $success = [
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "id" => $user->id,
                    "email" => $user->email,
                    "roles" => $getRoles
                ];

                Auth::login($user, true);

                $menuButtonsPermission = Permission::menuButtonsAccess($user, $getRoles);

                $hasChild=ChildrenConnections::where("parent_id", "=", $user->id)->exists();
                $response = [
                    "success" => true,
                    "data" => $success,
                    "token" => auth()->login($user),
                    "message" => __('messages.success'),
                    "menuButtonsPermission"=>$menuButtonsPermission,
                    "hasChild"=>$hasChild
                ];

                return response()->json($response);

        }else{
            throw new ControllerException(__('passwords.user'));
        }
    }

    public function logout(){
        try{
            $user= JWTAuth::parsetoken()->invalidate();
            return response()->json(['message' => __('auth.logout.success')]);
        }catch (\Exception $e) {
            throw new ControllerException(__('auth.logout.fail'));
        }

    }

    public function createUser(Request $request){
        $validator = Validator::make($request->all(), [
            "fname"=>"required|max:255",
            "lname"=>"required|max:255",
            "email"=>"required|email|unique:users,email|max:255",
            "psw"=>"required|max:255"
        ],[
            "fname.required"=>__('validation.custom.fname.required'),
            "fname.max"=>__('validation.custom.fname.max'),
            'lname.required' => __('validation.custom.lname.required'),
            'lname.max' => __('validation.custom.lname.max'),
            'email.required' => __('validation.custom.email.required'),
            'email.max' => __('validation.custom.email.max'),
            'email.unique' =>  __('validation.custom.email.unique'),
            'psw.required' => __('validation.custom.password.required'),
            'psw.max' => __('validation.custom.password.max'),

        ]);
        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }

        $token= Str::random(60);
        DB::transaction(function () use ($request, $token){

            PasswordResets::create([
                "email"=>$request->email,
                "token"=>$token
            ]);

            $user = User::insertGetId([
                "first_name"=> $request->fname,
                "last_name"=>$request->lname,
                "email"=>$request->email,
                "password"=> bcrypt($request->psw),
                "user_status" => "ACTIVE",
                "created_at" => \Carbon\Carbon::now(),
                "updated_at" => \Carbon\Carbon::now(),
            ]);

            $getParentId=Roles::where('name', "Parent")->value('id');

            UserRoles::insert([
                "user_id" => $user,
                "role_id" => $getParentId
            ]);
        });
        $success=[
            "message"=>__('messages.success'),
            "link"=>"localhost:3000/generated-user/$token"
        ];

        return response()->json($success);
    }

    /**
     * @throws Exception
     */
    public function passwordReset($token){
        if($token){
            $findToken= PasswordResets::where("token",$token)->first();

            if($findToken){
                $validateToken = Carbon::parse($findToken->created_at)->addDay(5) >= now();

                if($validateToken){
                    $findUser=User::where("email", $findToken['email'])->first();

                    if($findUser){
                        $success=[
                            "id"=>$findUser["id"],
                            "firstname"=>$findUser["first_name"],
                            "lastname"=>$findUser["last_name"],
                            "email"=>$findUser["email"]
                        ];

                        return response()->json($success);
                    }else{
                        throw new ControllerException(__('passwords.user'));
                    }
                }else{
                    throw new ControllerException(__('passwords.invalidToken'));
                }

            }
        }else{
            throw new ControllerException(__('passwords.token'));
        }

    }

    /**
     * @throws Exception
     */
    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            "psw"=>"required",
        ],[
            "psw.required"=>__('validation.custom.password.required'),
        ]);

        if($validator->fails()){
            $validatorResponse=[
                "validatorResponse"=>$validator->errors()->all()
            ];
            return response()->json($validatorResponse,422);
        }
        $validateUserToken = PasswordResets::where('token', $request->token)->first();
        $validateToken=Carbon::parse($validateUserToken->created_at)->addDay(5) >= now();
        if(!$validateToken){
            throw new ControllerException(__('passwords.invalidToken'));
        }
        $validateUser=User::where("email", $validateUserToken->email)->value('id');

        if(!$validateUserToken || !$validateUser){
            throw new ControllerException(__("messages.error"),500);
        }
        return DB::transaction(function () use ($request, $validateUser){

            $findUser=User::where("id", $validateUser)->first();
            $findUser->update([
                "password"=> bcrypt($request->psw),
                "user_status" => "ACTIVE"
            ]);
            $findinResetPassword=PasswordResets::where("email", $findUser['email'])->exists();
            if($findinResetPassword){
                PasswordResets::where("email", $findUser['email'])->first()->delete();
            }

            return response()->json([__('passwords.reset')],200);
        });

    }
}
