<?php

namespace App\Listeners;

use App\Events\ErrorEvent;
use App\Models\ErrorLogs;
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
    }
}
