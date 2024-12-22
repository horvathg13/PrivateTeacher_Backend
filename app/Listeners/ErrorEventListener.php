<?php

namespace App\Listeners;

use App\Events\ErrorEvent;
use App\Models\ErrorLogs;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ErrorEventListener
{
    public function __construct()
    {
    }

    public function handle(ErrorEvent $event): void
    {
        DB::transaction(function () use ($event) {
            ErrorLogs::create([
                "user_id" => $event->user->id,
                "procedure_name" => $event->procedureName,
                "status_code" => $event->status,
                "message" => $event->message,
                "debug_backtrace" =>$event->debugBacktrace
            ]);
        });
        if($event->procedureName === 'Hack Attempt'){
           $findUser= User::where('id', $event->user->id)->first();
           $findUser->update(["user_status"=>"SUSPENDED"]);

        }
    }
}
