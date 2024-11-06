<?php

namespace App\Http\Controllers;

use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    public function get()
    {
        $user=JWTAuth::parseToken()->authenticate();
        $getNotifications=Notifications::where(['receiver_id'=>$user->id])->get();

        return response()->json($getNotifications);
    }

    public function haveUnreadNotifications(){
        $user=JWTAuth::parseToken()->authenticate();

        $haveUnreadNotifications = Notifications::where(['receiver_id'=>$user->id, 'read'=>false])->exists();

        return response()->json($haveUnreadNotifications);
    }

    public function readNotification($id){
        $user=JWTAuth::parseToken()->authenticate();

        $find=Notifications::where(['id'=>$id, 'receiver_id'=>$user->id])->firstOrFail();

        DB::transaction(function () use($find){
            $find->update(['read'=>true]);
        });

        return response()->json(__("messages.success"));

    }
}
